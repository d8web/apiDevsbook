<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

use App\Models\User;
use App\Models\Post;
use App\Models\UserRelation;

class UserController extends Controller
{
  public function update(Request $request)
  {
    $array = ['error' => ''];

    $name = $request->input('name');
    $email = $request->input('email');
    $birthdate = $request->input('birthdate');
    $city = $request->input('city');
    $work = $request->input('work');
    $password = $request->input('password');
    $password_confirm = $request->input('password_confirm');

    $user = Auth::user();
    $newUser = User::find($user['id']);

    if($name) {
      $newUser->name = $name;
    }

    if($email) {
      if($email != $newUser->email)
      {
        $emailExists = User::where('email', $email)->count();
        if($emailExists === 0) {
          $newUser->email = $email;
        } else {
          $array['error'] = 'Email já existe!';
          return $array;
        }
      }
    }
    if($birthdate) {
      if(strtotime($birthdate) === false) {
        $array['error'] = 'Data inválida';
        return $array;
      }

      $newUser->birthdate = $birthdate;
    }

    if($city) {
      $newUser->city = $city;
    }

    if($work) {
      $newUser->work = $work;
    }

    if($password && $password_confirm)
    {
      if($password === $password_confirm) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $newUser->password = $hash;
      } else {
        $array['error'] = 'As senhas não são iguais!';
        return $array;
      }
    }

    $newUser->save();

    return $array;
  }

  public function updateAvatar(Request $request)
  {
    $array = ['error' => ''];
    // Tipos permitidos de arquivo imagem.
    $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

    $image = $request->file('avatar');

    if($image) {
      if(in_array($image->getClientMimeType(), $allowedTypes)) {

        $fileName = md5(time().rand(0, 9999)).'.jpg';
        // Pasta para salvar
        $destPath = public_path('/media/avatars');

        // Image crop using library Intervation Image Laravel Integration.
        $img = Image::make($image->path())
          ->fit(200, 200)
          ->save($destPath.'/'.$fileName);

          $user = Auth::user();
          $newUser = User::find($user['id']);

          $newUser->avatar = $fileName;
          $newUser->save();

          // Retornando nome do arquivo(avatar user) salvo
          $array['url'] = url('/media/avatars/'.$fileName);

      } else {
        $array['error'] = 'Arquivo não suportado pelo sistema!';
      }

    } else {
      $array['error'] = 'Avatar não enviado!';
    }

    return $array;
  }

  public function updateCover(Request $request)
  {
    $array = ['error' => ''];
    // Tipos permitidos de arquivo imagem.
    $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

    $image = $request->file('cover');

    if($image) {
      if(in_array($image->getClientMimeType(), $allowedTypes)) {

        $fileName = md5(time().rand(0, 9999)).'.jpg';
        // Pasta para salvar
        $destPath = public_path('/media/covers');

        // Image crop using library Intervation Image Laravel Integration.
        $img = Image::make($image->path())
          ->fit(850, 310)
          ->save($destPath.'/'.$fileName);

          $user = Auth::user();
          $newUser = User::find($user['id']);

          $newUser->cover = $fileName;
          $newUser->save();

          // Retornando nome do arquivo(avatar user) salvo
          $array['url'] = url('/media/covers/'.$fileName);

      } else {
        $array['error'] = 'Arquivo não suportado pelo sistema!';
      }

    } else {
      $array['error'] = 'Cover não enviado!';
    }

    return $array;
  }

  public function read($id = false)
  {
    $array = ['error' => ''];

    if($id) {
      $info = User::find($id);

      if(!$info) {
        $array['error'] = 'Usuário não existe!';
        return $array;
      }
    } else {
      $info = Auth::user();
    }

    $loggedUserId = Auth::user();
    // Modify avatar with cover to url correct in array $info.
    $info['avatar'] = url('media/avatars/'.$info['avatar']);
    $info['cover'] = url('media/covers/'.$info['cover']);

    // Verify if user id = loggedUserId, return true or false
    $info['me'] = ($info['id'] == $loggedUserId['id']) ? true : false;

    // Get and mount age from user information
    $dateFrom = new \DateTime($info['birthdate']);
    $dateTo = new \DateTime('today');
    $info['age'] = $dateFrom->diff($dateTo)->y;

    // Get total numeric value followers and following and photos from user
    $info['followers'] = UserRelation::where('user_to', $info['id'])->count();
    $info['following'] = UserRelation::where('user_from', $info['id'])->count();
    $info['photos'] = Post::where('id_user', $info['id'])->where('type', 'photo')->count();

    // Get info if user logged 'following' user id
    $hasRelation = UserRelation::where('user_from', $loggedUserId['id'])
      ->where('user_to', $info['id'])
      ->count();
    $info['isFollowing'] = ($hasRelation > 0) ? true : false;

    $array['data'] = $info;
    return $array;
  }

  public function follow($id)
  {
    $array = ['error' => ''];

    $loggedUserId = Auth::user();
    if($loggedUserId['id'] == $id) {
      $array['error'] = 'Você não pode seguir você mesmo!';
      return $array;
    }

    $userExists = User::find($id);
    if($userExists) {

      $relation = UserRelation::where('user_from', $loggedUserId['id'])
        ->where('user_to', $id)
        ->first();

      // Unfollow
      if($relation)
      {
        $relation->delete();
      } else {
        // Follow
        $newRelation = new UserRelation();
        $newRelation->user_from = $loggedUserId['id'];
        $newRelation->user_to = $id;
        $newRelation->save();
      }

    } else {
      $array['error'] = 'Usuário não existe!';
      return $array;
    }

    return $array;
  }

  public function followers($id)
  {
    $array = ['error' => ''];

    $userExists = User::find($id);
    if($userExists) {

      $followers = UserRelation::where('user_to', $id)->get();
      $following = UserRelation::where('user_from', $id)->get();

      $array['followers'] = [];
      $array['following'] = [];

      foreach($followers as $item) {
        $user = User::find($item['user_from']);
        $array['followers'][] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'avatar' => url('media/avatars/'.$user['avatar'])
        ];
      }

      foreach($following as $item) {
        $user = User::find($item['user_from']);
        $array['following'][] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'avatar' => url('media/avatar/'.$user['avatar'])
        ];
      }

    } else {
      $array['error'] = 'Usuário não existe!';
      return $array;
    }

    return $array;
  }


}
