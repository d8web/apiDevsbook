<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

use App\Models\User;

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

}
