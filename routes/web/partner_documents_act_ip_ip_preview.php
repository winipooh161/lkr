<?php

use App\Http\Controllers\Partner\ProjectDocuments\ActIpIpController;
use Illuminate\Support\Facades\Route;

// Маршруты для предпросмотра акта выполненных работ ИП-ИП
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Предпросмотр документа "Акт выполненных работ ИП-ИП"
    Route::post('/projects/{project}/documents/act-ip-ip/preview', [ActIpIpController::class, 'previewDocument'])
         ->name('projects.documents.act-ip-ip.preview');
});
