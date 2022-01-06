<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
//use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\SupportTicket;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:60',
            'password'  => 'required|confirmed|min:6|max:28',
            'email'     => 'required|unique:App\Models\User,email'
        ]);
        if ($validator->fails()) {
            return response([
                'status' => 400,
                $validator->errors()->toArray(),
            ], 400);
        }

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'email_confirmed' => false
        ]);
      
        \DB::table('email_confirmations')->insert([
            'email' => $user->email,
            'token' => Str::random(20)
        ]);
        $tokenData = \DB::table('email_confirmations')->where('email', $user->email)->first();

        $link = 'http://localhost:3000/user/email_confirmation/' . $tokenData->token;

        $data = [
            'login' => $user->name,
            'link' => $link
         ];

        Mail::send('confirm_email', $data, function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Confirm email on itic');
        });

        return response([
            $user,
            "Confirmation link sent"
        ], 200);
    }
    public function confirm_email(Request $request, $id)
    {
        $tokenData = \DB::table('email_confirmations')->where('token', $request->token)->first();

        if (!$tokenData) return response([
            'message' => 'Wrong Token'
        ], 400);

        $user = User::where('email', $tokenData->email)->first();
        $user->email_confirmed = true;
        $user->save();

        \DB::table('email_confirmations')->where('email', $tokenData->email)->delete();

        return response([
            'message' => 'Email confirmed'
        ], 200);
    }
    public function login(Request $request)
    {
        $request->validate(
            [
                'email'    => 'required|string',
                'password' => 'required|string',
            ]
        );

        $user = User::where('email', $request->email)->first();
        if(!$user) {
            return response([
                'message' => 'Такого пользователя нет'
            ], 403);
        }
        if($user->email_confirmed != true) {
            return response([
                'message' => 'Вы ещё не подтвердили почту'
            ], 400);
        }
        
    if ($user = Auth::guard()->attempt($request->only('email', 'password'))) {
        $user = User::where('email', $request->email)->first();

            $request->session()->regenerate();

            return response([
                "user" => $user
            ], 200);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    public function logout(Request $request)
    {
        Auth::guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response([
            "message" => "Logout succeed"
        ], 200);
    }
    public function reset_password(Request $request)
    {
        $user = \DB::table('users')->where('email', '=', $request->input("email"))->first();

        \DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Str::random(20)
        ]);
        $tokenData = \DB::table('password_resets')->where('email', $user->email)->first();

        $link = 'http://localhost:3000/new_password/' . $tokenData->token;

        $data = [
            'login' => $user->name,
            'link' => $link
        ];

        Mail::send('reset_password', $data, function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Reset password at usof');
        });
        return "Password reset link sent";
    }
    public function confirm_token(Request $request, $id)
    {
        $tokenData = \DB::table('password_resets')->where('token', $request->token)->first();

        if (!$tokenData) return "Wrong token";

        $user = User::where('email', $tokenData->email)->first();

        if (!$user) return "Wrong email";

        $user->password = \Hash::make($request->input('password'));
        $user->update();

        \DB::table('password_resets')->where('email', $tokenData->email)->delete();

        return response([
            'message' => 'Password changed'
        ]);
    }
}
