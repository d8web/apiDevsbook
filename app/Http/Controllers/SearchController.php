<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\User;

class SearchController extends Controller
{
  public function search(Request $request)
  {
    $array = ['error' => '', 'users' => []];

    $validator = Validator::make($request->all(), [
      'txt' => 'required'
    ]);

    if(!$validator->fails()) {

      // Search users using like operator
      $txt = $request->input('txt');
      $userList = User::where('name', 'like', '%'.$txt.'%')->get();
      foreach($userList as $userItem) {
        $array['users'][] = [
          'id' => $userItem['id'],
          'name' => $userItem['name'],
          'avatar' => url('media/avatars/'.$userItem['avatar'])
        ];
      }

    } else {
      $array['error'] = $validator->errors()->first();
      return $array;
    }

    return $array;
  }
}
