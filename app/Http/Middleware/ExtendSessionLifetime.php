<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ExtendSessionLifetime
{
    /**
     * Обработка запроса для продления сессии.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Если пользователь авторизован
        if (Auth::check()) {
            // Получаем время жизни сессии из конфига (в минутах)
            $lifetime = config('session.lifetime', 4320); // 3 дня по умолчанию
            
            // Продлеваем время сессии
            $response = $next($request);
            
            // Обновляем время последней активности и продлеваем сессию
            Session::put('last_activity', time());
            
            // Регенерируем идентификатор сессии для безопасности, но сохраняем данные
            if (!$request->ajax() && !$request->wantsJson() && $request->isMethod('GET') && rand(1, 100) <= 10) {
                // Обновляем в 10% случаев чтобы не замедлять работу
                $request->session()->migrate(true);
            }
            
            // Устанавливаем cookie с увеличенным сроком жизни
            $cookie = cookie('remember_web_session', time(), $lifetime);
            $response->withCookie($cookie);
            
            return $response;
        }

        return $next($request);
    }
}
