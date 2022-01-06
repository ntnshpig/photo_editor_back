<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role != "admin" && $user->id != $id) {
            return response([
                'message' => 'You have no access'
            ], 403);
        } else {
            $user_find = User::find($id);
            $validated = $request->validate([
                'name' => 'string',
                'email' => 'string',
            ]);
            $user_find->update($validated);
            return $user_find;
        }
    }

    public function update_password(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password'  => 'required|confirmed|min:6|max:28',
        ]);
        if ($validator->fails()) {
            return response([
                'status' => 400,
                $validator->errors()->toArray(),
            ], 400);
        }
        $user_find = User::find($user->id);
        if (Hash::check($request->input("old_password"), $user_find->password)) {
            $user_find->update([
                'password' => Hash::make($request->input("password"))
            ]);

            return response([
                'message' => 'Password changed!'
            ], 200);
        } else {

            return response([
                'message' => 'Старый пароль не подходит!'
            ], 400);
        }
    }
}
