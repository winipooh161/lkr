<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;

class RefreshCsrfToken
{
    /**
     * Проверяет и обновляет CSRF токен при необходимости
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Проверяем, авторизован ли пользователь
        if (auth()->check()) {
            // Если запрос не содержит валидный токен или токен устарел
            if ($request->hasSession() && !$request->isMethod('GET')) {
                $token = $request->session()->token();
                if (!$request->hasHeader('X-CSRF-TOKEN') && !$request->has('_token')) {
                    // Обновляем токен в сессии
                    $token = csrf_token();
                    Session::put('_token', $token);
                }
            }
        }

        $response = $next($request);
        
        // Обновляем метаданные сессии, чтобы она не истекала
        if ($request->hasSession()) {
            $request->session()->migrate(true);
        }
        
        return $response;
    }
}
