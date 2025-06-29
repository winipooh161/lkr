<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LocalEnvironmentOnly
{
    /**
     * Middleware, ограничивающий доступ только для локального окружения
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Проверяем, что окружение локальное
        if (app()->environment() !== 'local') {
            abort(404);
        }

        return $next($request);
    }
}
