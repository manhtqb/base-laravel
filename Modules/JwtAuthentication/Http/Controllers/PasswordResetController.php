<?php

namespace Modules\JwtAuthentication\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\JwtAuthentication\Entities\PasswordReset;
use Modules\JwtAuthentication\Notifications\PasswordResetRequest;
use Modules\JwtAuthentication\Notifications\PasswordResetSuccess;
use Modules\JwtAuthentication\Services\UserServiceInterface;

class PasswordResetController extends Controller
{
    private $service;

    public function __construct(UserServiceInterface $userService)
    {
        $this->service = $userService;
    }

    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);
        $user = $this->service->firstUserByCondition([
            'email' => $request->email
        ]);
        if (!$user)
            return response()->json([
                'message' => 'We can not find a user with that e - mail address . '
            ], 404);
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Str::random(60)
            ]
        );
        if ($user && $passwordReset)
            $user->notify(
                new PasswordResetRequest($passwordReset->token)
            );
        return response()->json([
            'message' => 'We have e - mailed your password reset link!'
        ]);
    }

    public function find($token)
    {
        $passwordReset = PasswordReset::where('token', $token)
            ->first();

        if (!$passwordReset)
            return response()->json([
                'message' => 'This password reset token is invalid . '
            ], 404);
        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'message' => 'This password reset token is invalid . '
            ], 404);
        }
        return response()->json($passwordReset);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required | string | email',
            'password' => 'required | string | confirmed',
            'token' => 'required | string'
        ]);
        $passwordReset = PasswordReset::where([
            ['token', $request->token],
            ['email', $request->email]
        ])->first();
        if (!$passwordReset)
            return response()->json([
                'message' => 'This password reset token is invalid . '
            ], 404);
        $user = $this->service->firstUserByCondition([
            'email' => $passwordReset->email
        ]);
        if (!$user)
            return response()->json([
                'message' => 'We can not find a user with that e-mail address.'
            ], 404);
        $user = $this->service->updateUser($user->id, [
            'password' => bcrypt($request->password),
        ]);

        $passwordReset->delete();
        $user->notify(new PasswordResetSuccess());

        return response()->json($user);
    }
}
