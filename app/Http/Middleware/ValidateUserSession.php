<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class ValidateUserSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем только авторизованных пользователей
        if (Auth::check()) {
            // Проверяем наличие маркеров безопасности сессии
            $hasUserId = Session::has('auth.user_id');
            $hasLoginTime = Session::has('auth.login_time');
            $hasLastActivity = Session::has('last_activity');
            
            // Если отсутствуют необходимые маркеры, выполняем повторную авторизацию
            if (!$hasUserId || !$hasLoginTime) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('login')
                    ->with('error', 'Ваша сессия устарела. Пожалуйста, войдите снова.');
            }
            
            // Проверяем корреляцию идентификатора пользователя
            if (Session::get('auth.user_id') !== Auth::id()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('login')
                    ->with('error', 'Обнаружена проблема с сессией. Пожалуйста, войдите снова.');
            }
            
            // Получаем время жизни сессии из конфига (в минутах)
            $sessionLifetime = config('session.lifetime', 4320) * 60;
            
            // Проверяем не истекла ли сессия по времени неактивности
            if ($hasLastActivity) {
                $lastActivity = Session::get('last_activity');
                $currentTime = time();
                
                if (($currentTime - $lastActivity) > $sessionLifetime) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    return redirect()->route('login')
                        ->with('error', 'Ваша сессия истекла из-за длительного отсутствия активности.');
                }
            }
        }

        return $next($request);
    }
}
