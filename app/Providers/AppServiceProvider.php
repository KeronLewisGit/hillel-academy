<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Paginator::defaultView('components.pagination');

        // Never serve plain HTTP in production.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Parents often fill forms on the school's own Wi-Fi at open mornings, so the
        // per-network cap is generous; the tight cap is per email address.
        RateLimiter::for('forms', function (Request $request) {
            $email = strtolower(input_string($request->input('email')));
            $limits = [Limit::perMinute(40)->by('ip|'.$request->ip())];
            if ($email !== '') {
                $limits[] = Limit::perMinute(5)->by('email|'.$email.'|'.$request->ip());
            }

            return $limits;
        });
        // Sign-in links: a few per address, so nobody can flood a family's inbox.
        RateLimiter::for('family-link', fn (Request $request) => [
            Limit::perMinute(20)->by('ip|'.$request->ip()),
            Limit::perHour(6)->by('link|'.strtolower(input_string($request->input('email'))).'|'.$request->user('family')?->getAuthIdentifier()),
        ]);
        RateLimiter::for('family-consume', fn (Request $request) => Limit::perMinute(20)->by('consume|'.$request->ip()));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower(input_string($request->input('email'))).'|'.$request->ip()));
    }
}
