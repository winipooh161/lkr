<?php

use App\Http\Controllers\Partner\ProjectDocumentController;
use Illuminate\Support\Facades\Route;

// Маршруты для работы с документами проектов
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Генерация документов
    Route::post('/projects/{project}/documents/generate', [ProjectDocumentController::class, 'generateDocument'])->name('projects.documents.generate');
    
    // Предпросмотр документов
    Route::post('/projects/{project}/documents/preview', [ProjectDocumentController::class, 'previewDocument'])->name('projects.documents.preview');
});
