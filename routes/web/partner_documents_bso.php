<?php

use App\Http\Controllers\Partner\ProjectDocuments\BsoController;
use Illuminate\Support\Facades\Route;

// Маршруты для конкретного типа документа: БСО
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Генерация документа "БСО"
    Route::post('/projects/{project}/documents/bso/generate', [BsoController::class, 'generateDocument'])
         ->name('projects.documents.bso.generate');
});
