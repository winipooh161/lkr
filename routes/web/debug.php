<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Debug\ProjectFileDebugController;
use App\Http\Middleware\LocalEnvironmentOnly;

// Маршруты только для локального окружения
Route::middleware(['local_only'])->prefix('debug')->group(function () {
    // Маршрут для просмотра файлов проекта в целях отладки
    Route::get('/project-files', [ProjectFileDebugController::class, 'index'])->name('debug.project-files');
    
    // Тестовый маршрут для демонстрации обработки ошибок
    Route::get('/error-test', function () {
        // Имитируем ошибку, которая покажет дебаг страницу в локальном окружении
        throw new \Exception('Это тестовая ошибка для демонстрации работы APP_DEBUG в локальном окружении');
    });
});
