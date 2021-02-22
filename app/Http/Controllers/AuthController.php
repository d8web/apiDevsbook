<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\User;

class AuthController extends Controller
{
    public function unauthorized()
    {
      return response()->json(['error' => 'Você precisa estar logado'], 401);
    }

    public function create(Request $request)
    {
      $array = ['error' => ''];

      $validator = Validator::make($request->all(), [
        'name' => 'required|string|min:3|max:100',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:4|max:100',
        'birthdate' => 'required|date|date_format:Y-m-d'
      ]);

      if(!$validator->fails())
      {
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $birthdate = $request->input('birthdate');

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->birthdate = $birthdate;
        $user->save();

        $token = Auth::attempt(['email' => $email, 'password' => $password]);
        if(!$token) {
          $array['error'] = 'Ocorreu um erro!';
          return $array;
        }

        $array['token'] = $token;
        $array['user'] = Auth::user();

      } else {
        $array['error'] = $validator->errors()->first();
      }

      return $array;
    }

    public function login(Request $request)
    {
      $array = ['error' => ''];

      $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required'
      ]);

      if(!$validator->fails())
      {
        $email = $request->input('email');
        $password = $request->input('password');

        $token = Auth::attempt(['email' => $email, 'password' => $password]);

        if(!$token)
        {
          return $array['error'] = 'Email e/ou senha incorretos!';
        }
        $array['token'] = $token;

      } else {
        $array['error'] = $validator->errors()->first();
      }

      return $array;
    }

    public function logout()
    {
      $array = ['error' => ''];

      Auth::logout();

      return $array;
    }

    public function refresh()
    {
      $token = Auth::refresh();
      return [
        'error' => '',
        'token' => $token
      ];
    }

}
