<?php

use App\Http\Controllers\Partner\ProjectDocuments\InvoiceIpController;
use Illuminate\Support\Facades\Route;

// Маршруты для конкретного типа документа: Счет ИП
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Генерация документа "Счет ИП"
    Route::post('/projects/{project}/documents/invoice-ip/generate', [InvoiceIpController::class, 'generateDocument'])
         ->name('projects.documents.invoice-ip.generate');
});
