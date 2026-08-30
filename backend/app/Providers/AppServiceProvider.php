<?php

namespace App\Providers;

use App\Support\ExtensionMimeTypeGuesser;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Mime\MimeTypes;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! extension_loaded('fileinfo') && class_exists(MimeTypes::class)) {
            MimeTypes::getDefault()->registerGuesser(new ExtensionMimeTypeGuesser);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compatibilité MySQL/WAMP (index utf8mb4)
        Schema::defaultStringLength(191);

        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $base = rtrim((string) config('app.frontend_url'), '/');

            return $base.'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });
    }
}
