<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class SessionConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Настраиваем основные параметры куки сессии
        Config::set('session.lifetime', 4320); // 3 дня в минутах
        Config::set('session.expire_on_close', false);
        Config::set('session.encrypt', true);
        
        // Важно для правильного сохранения сессий
        Config::set('session.same_site', 'lax');
        
        // Установка secure cookie только для HTTPS
        if (request()->isSecure()) {
            Config::set('session.secure', true);
        }
    }
}
