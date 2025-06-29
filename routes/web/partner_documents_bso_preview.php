<?php

use App\Http\Controllers\Partner\ProjectDocuments\BsoController;
use Illuminate\Support\Facades\Route;

// Маршруты для предпросмотра БСО
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Предпросмотр документа "БСО"
    Route::post('/projects/{project}/documents/bso/preview', [BsoController::class, 'previewDocument'])
         ->name('projects.documents.bso.preview');
});
