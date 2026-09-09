<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $email = Str::lower($request->string('email')->toString());
        $throttleKey = Str::transliterate('login:'.$email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => [__('auth.throttle', ['seconds' => $seconds])],
            ]);
        }

        try {
            $user = User::query()
                ->with(['roles.permissions', 'departments'])
                ->where('email', $email)
                ->first();

            $passwordOk = $user && Hash::check($request->string('password')->toString(), $user->password);

            if (! $passwordOk || ! $user->isActive()) {
                RateLimiter::hit($throttleKey, 900);
                AuditLogger::record('login.failed', $user?->id, 'user', $user?->id, [
                    'email' => $email,
                ], $request);

                throw ValidationException::withMessages([
                    'email' => [__('auth.failed')],
                ]);
            }

            RateLimiter::clear($throttleKey);

            Auth::guard('web')->login($user, $request->boolean('remember'));
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            $user->forceFill(['last_login_at' => now()])->save();
            try {
                $user->tokens()->delete();
            } catch (Throwable $e) {
                report($e);
            }

            AuditLogger::record('login.success', $user->id, 'user', $user->id, [], $request);

            return response()->json([
                'user' => (new UserResource($user->fresh()->load(['roles.permissions', 'departments'])))->resolve(),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => __('auth.generic_error'),
            ], 500);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            Password::broker()->sendResetLink(
                $request->only('email')
            );
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => __('auth.reset_link_sent'),
            'status' => 'accepted',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();

                AuditLogger::record('password.reset', $user->id, 'user', $user->id, [], $request);

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return response()->json([
            'message' => __('auth.password_reset'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles.permissions', 'departments']);

        return response()->json([
            'data' => (new UserResource($user))->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        AuditLogger::record('logout', $user?->id, 'user', $user?->id, [], $request);

        $user?->currentAccessToken()?->delete();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}
