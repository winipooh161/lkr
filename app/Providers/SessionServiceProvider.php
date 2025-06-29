<?php

namespace App\Providers;

use App\Listeners\ExtendUserSession;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Регистрация событий для управления сессиями
        $this->app['events']->listen(
            Login::class,
            ExtendUserSession::class
        );
    }
}
