<?php

use Illuminate\Support\Facades\Route;

// Маршруты для тестирования страниц ошибок
Route::middleware(['auth'])->prefix('test-errors')->group(function () {
    // Тест 404
    Route::get('/404', function () {
        abort(404);
    });

    // Тест 403
    Route::get('/403', function () {
        abort(403, 'Доступ запрещен');
    });

    // Тест 500
    Route::get('/500', function () {
        throw new Exception('Тестовая ошибка сервера');
    });

    // Тест 419 (CSRF)
    Route::get('/419', function () {
        throw new \Illuminate\Session\TokenMismatchException();
    });

    // Тест 422 (Валидация)
    Route::get('/422', function () {
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['email' => 'not-an-email'],
            ['email' => 'email']
        );

        throw new \Illuminate\Validation\ValidationException($validator);
    });

    // Тест 429 (Слишком много запросов)
    Route::get('/429', function () {
        throw new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException();
    });

    // Тест 503 (Сервис недоступен)
    Route::get('/503', function () {
        throw new \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException();
    });
});
