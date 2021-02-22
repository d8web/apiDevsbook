<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;

class PostController extends Controller
{
    public function like($id)
    {
      $array = ['error' => ''];

      // Verify if posts exists
      $postExists = Post::find($id);
      if($postExists) {
        $loggedUserId = Auth::user();

        // Verify if me liked this post
        $isLiked = PostLike::where('id_post', $postExists['id'])
          ->where('id_user', $loggedUserId['id'])
          ->count();

        // Case isLiked > 0, remove
        if($isLiked > 0) {
          $postLike = PostLike::where('id_post', $id)
            ->where('id_user', $loggedUserId['id'])
            ->first();
          $postLike->delete();

          $array['isLiked'] = false;
        } else {
          // Case isLiked < 0, insert
          $newPostLike = new PostLike();
          $newPostLike->id_post = $id;
          $newPostLike->id_user = $loggedUserId['id'];
          $newPostLike->created_at = date('Y-m-d H:i:s');
          $newPostLike->save();

          $array['isLiked'] = true;
        }

        $array['likeCount'] = PostLike::where('id_post', $postExists['id'])
          ->count();

      } else {
        $array['error'] = 'Post não existe';
        return $array;
      }

      return $array;
    }

    public function comment(Request $request, $id)
    {
      $array = ['error' => ''];

      $txt = $request->input('txt');
      $postExists = Post::find($id);

      if($postExists) {
        if($txt) {
          $loggedUserId = Auth::user();

          $newComment = new PostComment();
          $newComment->id_post = $id;
          $newComment->id_user = $loggedUserId['id'];
          $newComment->body = $txt;
          $newComment->created_at = date('Y-m-d H:i:s');
          $newComment->save();

        } else {
          $array['error'] = 'Não enviou nada!';
          return $array;
        }
      } else {
        $array['error'] = 'Post não existe!';
        return $array;
      }

      return $array;
    }
}
