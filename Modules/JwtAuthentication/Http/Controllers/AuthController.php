<?php

namespace Modules\JwtAuthentication\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\JwtAuthentication\Entities\User;
use Modules\JwtAuthentication\Notifications\SignupActivate;
use Modules\JwtAuthentication\Services\UserServiceInterface;

class AuthController extends Controller
{
    private $service;

    public function __construct(UserServiceInterface $userService)
    {
        $this->service = $userService;
    }

    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed'
        ]);
        $user = $this->service->createUser([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'activation_token' => Str::random(60)
        ]);

        $user->notify(new SignupActivate());

        return response()->json([
            'message' => 'Successfully created user!',
            'user' => $user
        ], $user ? 200 : 500);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);
        $credentials = request(['email', 'password']);
        $credentials['active'] = 1;
        $credentials['deleted_at'] = null;
        $user = User::where('email', $credentials['email'])->first();
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 401);
        }
        if(!Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Password not match'
            ], 401);
        }

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function signupActivate($token)
    {
        $user = $this->service->firstUserByCondition([
            'activation_token' => $token
        ]);
        if (!$user) {
            return response()->json([
                'message' => 'This activation token is invalid.'
            ], 404);
        }

        $user = $this->service->updateUser($user->id, [
            'active' => true,
            'activation_token' => true,
        ]);

        return response()->json($user);
    }
}
