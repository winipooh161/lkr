<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use App\Services\TelegramNotifier;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e, Request $request = null) {
            // Не отправляем уведомления в локальном окружении
            if (env('APP_ENV') === 'local') {
                return;
            }
            // Отправляем уведомление в Telegram только если не локальное окружение
            if (env('APP_ENV') !== 'local') {
                try {
                    $notifier = new TelegramNotifier();
                    $notifier->sendErrorNotification($e, $request);
                } catch (Throwable $telegramException) {
                    Log::error('Failed to send Telegram notification: ' . $telegramException->getMessage());
                }
            }
        });
        
        // Обработка ошибок аутентификации
        $this->renderable(function (AuthenticationException $e, $request) {
            // В локальном окружении показываем стандартный отладочный вывод Laravel для аутентификации
            if (env('APP_ENV') === 'local') {
                return null; // Возвращаем null чтобы Laravel использовал стандартный обработчик
            }
            
            // Отправляем уведомление в Telegram только для важных ошибок аутентификации и не в локальном окружении
            if (env('APP_ENV') !== 'local' && config('telegram.enabled') && !in_array(AuthenticationException::class, config('telegram.excluded_exceptions', []))) {
                try {
                    $notifier = new TelegramNotifier();
                    $notifier->sendErrorNotification($e, $request);
                } catch (Throwable $telegramException) {
                    Log::error('Failed to send Telegram notification: ' . $telegramException->getMessage());
                }
            }
            
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Требуется авторизация'], 401);
            }
            
            return redirect()->guest(route('login'))->with('error', 'Срок сессии истек. Пожалуйста, авторизуйтесь снова.');
        });
        
        // Обработка ошибок CSRF токена
        $this->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            // В локальном окружении всегда показываем стандартный отладочный вывод Laravel
            if (env('APP_ENV') === 'local') {
                return null; // Возвращаем null чтобы Laravel использовал стандартный обработчик
            }
            Log::warning('CSRF token mismatch', [
                'ip' => $request->ip(),
                'uri' => $request->fullUrl(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'CSRF token expired. Please refresh the page.'], 419);
            }
            
            return redirect()->back()->with('error', 'Ваша сессия истекла. Пожалуйста, обновите страницу и повторите действие.');
        });
        
        // Обработка исключений для API запросов
        $this->renderable(function (Throwable $e, $request) {
            // В локальном окружении показываем стандартный отладочный вывод Laravel
            if (env('APP_ENV') === 'local') {
                return null;
            }
            
            if ($request->is('api/*') || $request->wantsJson()) {
                Log::error('API Error: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);

                // Аутентификация
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Необходима авторизация'
                    ], 401);
                }
                
                // Валидация
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ошибка валидации данных',
                        'errors' => $e->errors()
                    ], 422);
                }
                
                // Общие ошибки
                $statusCode = 500; // По умолчанию 500 для внутренней ошибки сервера
                
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Произошла ошибка на сервере',
                    'error_type' => get_class($e)
                ], $statusCode);
            }
            
            return null;
        });
        
        // Добавляем обработчик для всех исключений при Ajax-запросах
        $this->renderable(function (Throwable $e, Request $request) {
            // В локальном окружении показываем стандартный отладочный вывод Laravel
            if (env('APP_ENV') === 'local') {
                return null;
            }
            
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                Log::error('API Error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_id' => $request->user() ? $request->user()->id : null
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Произошла ошибка на сервере: ' . $e->getMessage(),
                    'error_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
        });
        
        // Общий обработчик для всех исключений с кастомными шаблонами
        $this->renderable(function (Throwable $e, Request $request) {
            // В локальном окружении всегда показываем стандартный отладочный вывод Laravel
            if (env('APP_ENV') === 'local') {
                return null; // Возвращаем null чтобы Laravel использовал стандартный обработчик
            }
            
            // Если это не API/Ajax запрос и не обработано другими обработчиками
            if (!$request->expectsJson() && !$request->ajax() && !$request->wantsJson()) {
                
                $statusCode = 500; // Код ошибки по умолчанию
                
                // Определяем код ошибки для основных типов
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $statusCode = 404;
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                    $statusCode = 403;
                } elseif ($e instanceof \Illuminate\Session\TokenMismatchException) {
                    $statusCode = 419;
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    $statusCode = 422;
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
                    $statusCode = 429;
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException) {
                    $statusCode = 503;
                }
                
                // Логируем ошибку
                Log::error('Error: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_id' => $request->user() ? $request->user()->id : null,
                    'status_code' => $statusCode
                ]);
                
                // Отправляем сообщение в Telegram (только для серьезных ошибок и не в локальном окружении)
                if (($statusCode >= 500 || $statusCode === 403) && env('APP_ENV') !== 'local') {
                    try {
                        $notifier = new TelegramNotifier();
                        $notifier->sendErrorNotification($e, $request);
                    } catch (Throwable $telegramException) {
                        Log::error('Failed to send Telegram notification: ' . $telegramException->getMessage());
                    }
                }
                
                // Проверяем существование шаблона для ошибки
                $view = 'errors.' . $statusCode;
                if (view()->exists($view)) {
                    return response()->view($view, [
                        'exception' => $e,
                        'error_id' => \Illuminate\Support\Str::uuid()
                    ], $statusCode);
                }
                
                // Если для данной ошибки нет специального шаблона, используем общий
                return response()->view('errors.500', [
                    'exception' => $e,
                    'error_id' => \Illuminate\Support\Str::uuid()
                ], $statusCode);
            }
            
            return null;
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e)
    {
        // В локальном окружении всегда отображаем подробную отладочную информацию
        if (env('APP_ENV') === 'local') {
            // Принудительно устанавливаем режим отладки
            config(['app.debug' => true]);
            
            // В локальном окружении просто используем родительский метод render
            return parent::render($request, $e);
        }
        
        return parent::render($request, $e);
    }
}
