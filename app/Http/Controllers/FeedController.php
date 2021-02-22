<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;
use App\Models\UserRelation;
use App\Models\User;

class FeedController extends Controller
{
  public function create(Request $request)
  {
    $array = ['error' => ''];

    $type = $request->input('type');
    $body = $request->input('body');
    $photo = $request->file('photo');

    if($type) {

      switch($type) {

        case "text":
          $validator = Validator::make($request->all(), [
            'body' => 'required'
          ]);

          if($validator->fails()) {
            $array['error'] = $validator->errors()->first();
          }
        break;

        case "photo":
          if($photo) {

            $validator = Validator::make($request->all(), [
              'photo' => 'image|mimes:jpeg,png,jpg|max:2048'
            ]);

            if(!$validator->fails()) {

              $fileName = md5(time().rand(0, 9999)).'.jpg';
              $destPath = public_path('/media/uploads');

              $img = Image::make($photo->path())
                ->resize(800, null, function($constraint) {
                  $constraint->aspectRatio();
                })->save($destPath.'/'.$fileName);

              $body = $fileName;

            } else {
              $array['error'] = $validator->errors()->first();
            }

          } else {
            $array['error'] = 'Arquivo não enviado!';
          }
        break;

        default:
          $array['error'] = 'Tipo de post não existe!';
        break;
      }

      $user = Auth::user();

      if($body) {
        $newPost = new Post();
        $newPost->id_user = $user['id'];
        $newPost->type = $type;
        $newPost->created_at = date('Y-m-d H:i:s');
        $newPost->body = $body;
        $newPost->save();
      }

    } else {
      $array['error'] = 'Dados não enviados!';
      return $array;
    }

    return $array;
  }

  public function read(Request $request)
  {
    $array = ['error' => ''];

    $page = intval($request->input('page'));
    $perPage = 2;

    // get list users from i follow, (includes me)
    $users = [];
    $user = Auth::user();

    $userList = UserRelation::where('user_from', $user['id'])->get();
    foreach($userList as $userItem) {
        $users[] = $userItem['user_to'];
    }
    $users[] = $user['id'];

    // get posts from all users order by date
    $postList = Post::whereIn('id_user', $users)
      ->orderBy('created_at', 'desc')
      ->offset($page * $perPage)
      ->limit($perPage)
      ->get();

    $total = Post::whereIn('id_user', $users)->count();
    $pageCount = ceil($total / $perPage);

    // add more information to posts, quantity in liked, list comments, i liked [true or false]
    $posts = $this->_postListToObject($postList, $user['id']);

    $array['posts'] = [];
    $array['pageCount'] = $pageCount;
    $array['currentPage'] = $page;

    return $array;
  }

  private function _postListToObject($postList, $idUser)
  {
    return $postList;
  }

}
