<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Notification Settings
    |--------------------------------------------------------------------------
    |
    | Эти настройки используются для отправки уведомлений через Telegram
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN', '8161282478:AAGB8PSzNjM9mmGTl8fi9L-y0PiQ6xxf0lw'),
    'chat_id' => env('TELEGRAM_CHAT_ID', '-4810796860'),
    'enabled' => env('TELEGRAM_NOTIFICATIONS_ENABLED', true),
    
    /*
    |--------------------------------------------------------------------------
    | Настройки троттлинга
    |--------------------------------------------------------------------------
    |
    | Чтобы избежать спама, ограничиваем частоту отправки сообщений об одинаковых ошибках
    |
    */
    'throttle_time' => env('TELEGRAM_THROTTLE_TIME', 60), // секунды
    
    /*
    |--------------------------------------------------------------------------
    | Исключенные типы ошибок
    |--------------------------------------------------------------------------
    |
    | Список типов исключений, для которых не нужно отправлять уведомления
    | Например: \Illuminate\Auth\AuthenticationException::class
    |
    */
    'excluded_exceptions' => [
        // \Illuminate\Auth\AuthenticationException::class,
        // \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],
];
