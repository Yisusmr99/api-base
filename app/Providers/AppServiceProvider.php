<?php

namespace App\Providers;

use App\Listeners\Auth\LogFailedLogin;
use App\Listeners\Auth\LogSuccessfulLogin;
use App\Models\Transaccion;
use App\Observers\TransaccionObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Auditoría: listeners de eventos de autenticación.
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Failed::class, LogFailedLogin::class);

        // Auditoría: observer que crea snapshots de transacciones en MongoDB.
        Transaccion::observe(TransaccionObserver::class);
    }
}
