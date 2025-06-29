<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TourMiddleware
{
    /**
     * Обработать входящий запрос.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Проверка аутентификации
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Логирование для отладки
        Log::debug('TourMiddleware: обрабатываем запрос', [
            'user_id' => Auth::id(),
            'path' => $request->path(),
            'method' => $request->method()
        ]);

        return $next($request);
    }
}
