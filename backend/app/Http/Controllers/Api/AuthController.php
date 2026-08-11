<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = User::query()
                ->with('roles.permissions')
                ->where('email', $request->string('email')->toString())
                ->first();

            if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
                throw ValidationException::withMessages([
                    'email' => [__('auth.failed')],
                ]);
            }

            if (! $user->isActive()) {
                throw ValidationException::withMessages([
                    'email' => ['Compte désactivé.'],
                ]);
            }

            $user->forceFill(['last_login_at' => now()])->save();

            $token = $user->createToken(
                $request->string('device_name')->toString() ?: 'rdp-web'
            )->plainTextToken;

            return response()->json([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => (new UserResource($user->fresh()->load('roles.permissions')))->resolve(),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Server Error',
                'error' => $e->getMessage(),
                'exception' => class_basename($e),
            ], 500);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::broker()->sendResetLink(
                $request->only('email')
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to send reset email. Check mail configuration.',
                'error' => $e->getMessage(),
            ], 500);
        }

        // Always the same response (no email enumeration)
        return response()->json([
            'message' => __('passwords.sent'),
            'status' => $status === Password::RESET_LINK_SENT ? 'sent' : 'accepted',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles.permissions');

        return response()->json([
            'data' => (new UserResource($user))->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}
