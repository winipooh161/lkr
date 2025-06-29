<?php

use App\Http\Controllers\Partner\ProjectDocuments\InvoiceFlController;
use Illuminate\Support\Facades\Route;

// Маршруты для предпросмотра счета ФЛ
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Предпросмотр документа "Счет ФЛ"
    Route::post('/projects/{project}/documents/invoice-fl/preview', [InvoiceFlController::class, 'previewDocument'])
         ->name('projects.documents.invoice-fl.preview');
});
