<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotifier
{
    protected string $token;
    protected string $chatId;
    protected bool $isEnabled;
    protected array $throttledErrors = [];
    protected int $throttleTime = 60; // время в секундах для защиты от спама

    public function __construct()
    {
        $this->token = env('TELEGRAM_BOT_TOKEN', '8161282478:AAGB8PSzNjM9mmGTl8fi9L-y0PiQ6xxf0lw');
        $this->chatId = env('TELEGRAM_CHAT_ID', '-4810796860');
        // Отключаем уведомления для локального окружения
        $this->isEnabled = env('TELEGRAM_NOTIFICATIONS_ENABLED', true) && env('APP_ENV') !== 'local';
    }

    /**
     * Отправка сообщения об ошибке в Telegram
     * 
     * @param Throwable $exception Исключение
     * @param Request|null $request Объект запроса
     * @return bool Результат отправки
     */
    public function sendErrorNotification(Throwable $exception, ?Request $request = null): bool
    {
        if (!$this->isEnabled) {
            return false;
        }

        // Формируем уникальный ключ ошибки для защиты от спама
        $errorKey = md5($exception->getMessage() . $exception->getFile() . $exception->getLine());
        
        // Проверяем, не отправлялась ли такая ошибка недавно
        if ($this->isThrottled($errorKey)) {
            Log::info('Telegram notification throttled for error: ' . $errorKey);
            return false;
        }
        
        $message = $this->formatErrorMessage($exception, $request);
        return $this->sendMessage($message);
    }

    /**
     * Отправка произвольного сообщения в Telegram
     * 
     * @param string $message Текст сообщения
     * @return bool Результат отправки
     */
    public function sendMessage(string $message): bool
    {
        if (!$this->isEnabled || empty($this->token) || empty($this->chatId)) {
            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ]);

            return $response->successful();
        } catch (Throwable $e) {
            Log::error('Failed to send Telegram notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Форматирование сообщения об ошибке
     * 
     * @param Throwable $exception Исключение
     * @param Request|null $request Объект запроса
     * @return string Отформатированное сообщение
     */
    protected function formatErrorMessage(Throwable $exception, ?Request $request): string
    {
        $message = "<b>🚨 Ошибка на сайте!</b>\n\n";
        
        // Информация об ошибке
        $message .= "<b>Тип:</b> " . get_class($exception) . "\n";
        $message .= "<b>Код:</b> " . $exception->getCode() . "\n";
        $message .= "<b>Сообщение:</b> " . $exception->getMessage() . "\n";
        $message .= "<b>Файл:</b> " . $exception->getFile() . ":" . $exception->getLine() . "\n\n";
        
        // Информация о запросе
        if ($request) {
            $message .= "<b>URL:</b> " . $request->fullUrl() . "\n";
            $message .= "<b>Метод:</b> " . $request->method() . "\n";
            $message .= "<b>IP:</b> " . $request->ip() . "\n";
            
            // Информация о пользователе
            if ($request->user()) {
                $message .= "<b>Пользователь:</b> ID:" . $request->user()->id . 
                            " | " . $request->user()->email . 
                            " | Роль: " . ($request->user()->role ?? 'N/A') . "\n";
            } else {
                $message .= "<b>Пользователь:</b> Гость\n";
            }
            
            $message .= "<b>User Agent:</b> " . $request->userAgent() . "\n";
            $message .= "<b>Реферер:</b> " . ($request->header('referer') ?? 'N/A') . "\n\n";
            
            // Параметры запроса
            $message .= "<b>Параметры запроса:</b>\n";
            $safeParams = $request->except(['password', 'password_confirmation', 'token']);
            $message .= !empty($safeParams) ? json_encode($safeParams, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : 'Нет параметров';
            $message .= "\n\n";
        }
        
        // Стек вызовов
        $message .= "<b>Stack Trace (краткий):</b>\n";
        $trace = $exception->getTraceAsString();
        // Ограничиваем длину трейса, так как Telegram имеет ограничение на длину сообщения
        if (strlen($trace) > 1000) {
            $trace = substr($trace, 0, 1000) . "\n... (обрезано для соответствия лимиту Telegram)";
        }
        $message .= "<pre>" . $trace . "</pre>\n";
        
        $message .= "\n<b>Время:</b> " . now()->format('Y-m-d H:i:s');
        
        return $message;
    }

    /**
     * Проверяет, работает ли приложение в локальном окружении
     * 
     * @return bool
     */
    protected function isLocalEnvironment(): bool
    {
        return env('APP_ENV') === 'local';
    }

    /**
     * Проверка троттлинга ошибки
     * 
     * @param string $errorKey Ключ ошибки
     * @return bool True если ошибка троттлится (не нужно отправлять)
     */
    protected function isThrottled(string $errorKey): bool
    {
        $currentTime = time();
        
        // Очищаем устаревшие записи
        foreach ($this->throttledErrors as $key => $time) {
            if ($currentTime - $time > $this->throttleTime) {
                unset($this->throttledErrors[$key]);
            }
        }
        
        // Проверяем, есть ли ошибка в списке троттлинга
        if (isset($this->throttledErrors[$errorKey])) {
            return true;
        }
        
        // Добавляем ошибку в список троттлинга
        $this->throttledErrors[$errorKey] = $currentTime;
        return false;
    }
}
