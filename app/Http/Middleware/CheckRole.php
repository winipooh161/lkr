<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Обработка входящего запроса.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if (!Auth::check()) {
            return redirect('login');
        }
        
        if ($role === 'admin' && Auth::user()->role !== 'admin') {
            abort(403, 'У вас нет доступа к этой странице.');
        }
        
        if ($role === 'partner' && Auth::user()->role !== 'partner') {
            abort(403, 'У вас нет доступа к этой странице.');
        }
        
        if ($role === 'client' && Auth::user()->role !== 'client') {
            abort(403, 'У вас нет доступа к этой странице.');
        }
        
        if ($role === 'estimator' && Auth::user()->role !== 'estimator') {
            abort(403, 'У вас нет доступа к этой странице.');
        }
        
        return $next($request);
    }
}
