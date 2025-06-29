<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Здесь можно указать маршруты, которые следует исключить из CSRF проверки
        // Например: 'api/*'
    ];

    /**
     * Переопределение для улучшенного логирования CSRF ошибок
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $match = parent::tokensMatch($request);
        
        if (!$match && !$this->inExceptArray($request)) {
            Log::warning('CSRF token mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->header('User-Agent'),
                'referer' => $request->header('Referer'),
            ]);
        }
        
        return $match;
    }
}
