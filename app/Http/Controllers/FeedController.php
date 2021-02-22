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

    $array['posts'] = $posts;
    $array['pageCount'] = $pageCount;
    $array['currentPage'] = $page;

    return $array;
  }

  public function userFeed(Request $request, $id = false)
  {
    $array = ['error' => ''];
    $userId = Auth::user();

    if($id == false) {
      $id = $userId['id'];
    }

    $page = intval($request->input('page'));
    $perPage = 2;

    // get posts user order by date
    $postList = Post::where('id_user', $id)
      ->orderBy('created_at', 'desc')
      ->offset($page * $perPage)
      ->limit($perPage)
      ->get();

    $total = Post::where('id_user', $id)->count();
    $pageCount = ceil($total / $perPage);

    // read more informations
    $posts = $this->_postListToObject($postList, $id);

    $array['posts'] = $posts;
    $array['pageCount'] = $pageCount;
    $array['currentPage'] = $page;

    return $array;
  }

  private function _postListToObject($postList, $loggedId)
  {
    foreach($postList as $Postkey => $postItem)
    {
      // Verify if post is user logged.
      if($postItem['id_user'] == $loggedId) {
        $postList[$Postkey]['mine'] = true;
      } else {
        $postList[$Postkey]['mine'] = false;
      }

      // add more informations on user post
      $userInfo = User::find($postItem['id_user']);

      // Rewrite avatar and cover to url complete
      $userInfo['avatar'] = url('media/avatars/'.$userInfo['avatar']);
      $userInfo['cover'] = url('media/covers/'.$userInfo['cover']);

      $postList[$Postkey]['user'] = $userInfo;

      // add information count from likes
      $likes = PostLike::where('id_post', $postItem['id'])->count();
      $postList[$Postkey]['likeCount'] = $likes;

      // Get information user logged liked post
      $isLiked = PostLike::where('id_post', $postItem['id'])
        ->where('id_user', $loggedId)
        ->count();
      $postList[$Postkey]['liked'] = ($isLiked > 0) ? true : false;

      // add information from comments
      $comments = PostComment::where('id_post', $postItem['id'])
        ->get();

      foreach($comments as $commentKey => $commentValue) {
        $user = User::find($commentValue['id_user']);

        // Rewrite avatar and cover to url complete
        $user['avatar'] = url('media/avatars/'.$user['avatar']);
        $user['cover'] = url('media/covers/'.$user['cover']);

        $comments[$commentKey]['users'] = $user;
      }

      $postList[$Postkey]['comments'] = $comments;

    }

    return $postList;
  }

  public function userPhotos(Request $request, $id = false)
  {
    $array = ['error' => ''];
    $userId = Auth::user();

    if($id == false) {
      $id = $userId['id'];
    }

    $page = intval($request->input('page'));
    $perPage = 2;

    // get photos user order by date
    $postList = Post::where('id_user', $id)
      ->where('type', 'photo')
      ->orderBy('created_at', 'desc')
      ->offset($page * $perPage)
      ->limit($perPage)
      ->get();

    $total = Post::where('id_user', $id)
      ->where('type', 'photo')
      ->count();
    $pageCount = ceil($total / $perPage);

    // read more informations
    $posts = $this->_postListToObject($postList, $id);

    foreach($posts as $postKey => $postValue) {
      $posts[$postKey]['body'] = url('media/uploads/'.$postValue['body']);
    }

    $array['posts'] = $posts;
    $array['pageCount'] = $pageCount;
    $array['currentPage'] = $page;

    return $array;
  }

}
