<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ExtendUserSession
{
    protected $request;

    /**
     * Create the event listener.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        // Получаем время жизни сессии из конфига
        $lifetime = config('session.lifetime', 4320);
        
        // Устанавливаем дополнительную метку времени входа
        Session::put('auth.login_time', time());
        
        // Добавляем идентификатор пользователя для безопасности
        Session::put('auth.user_id', Auth::id());
        
        // Устанавливаем cookie для дополнительного подтверждения сессии
        $cookie = cookie('session_verified', Auth::id(), $lifetime);
        $this->request->session()->regenerate();
    }
}
