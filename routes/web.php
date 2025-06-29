<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\NotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Маршруты для административной панели
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
    
    // Маршруты для управления пользователями
    Route::resource('users', UserController::class, ['as' => 'admin']);
    
    // Маршруты для управления уведомлениями
    Route::get('notifications', [NotificationController::class, 'showForm'])->name('admin.notifications.form');
    Route::post('notifications/send', [NotificationController::class, 'send'])->name('admin.notifications.send');
});

require __DIR__ . '/web/profile.php';

// Подключаем маршруты для отладки (только для локального окружения)
if (app()->environment('local')) {
    require __DIR__ . '/web/debug.php';
}

Route::get('/', function () {
    return view('home');
});

Auth::routes([
    'login' => false, // Отключаем стандартный маршрут входа
]);

// Маршруты для SMS авторизации
Route::get('/login', [\App\Http\Controllers\Auth\SmsAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [\App\Http\Controllers\Auth\SmsAuthController::class, 'login']);
Route::post('/login/send-code', [\App\Http\Controllers\Auth\SmsAuthController::class, 'sendCode'])->name('login.send-code');
Route::post('/login/verify-code', [\App\Http\Controllers\Auth\SmsAuthController::class, 'verifyCode'])->name('login.verify-code');

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Подключение разделенных маршрутов
require __DIR__ . '/web/admin.php';
require __DIR__ . '/web/partner.php';
require __DIR__ . '/web/partner_extensions.php';
require __DIR__ . '/web/partner_project_features.php';
require __DIR__ . '/web/partner_tools.php';
require __DIR__ . '/web/partner_finance.php';
require __DIR__ . '/web/partner_documents.php';

// Маршруты для генерации документов
require __DIR__ . '/web/partner_documents_completion_act_ip_ip.php';
require __DIR__ . '/web/partner_documents_completion_act_fl_ip.php';
require __DIR__ . '/web/partner_documents_act_ip_ip.php';
require __DIR__ . '/web/partner_documents_act_fl_ip.php';
require __DIR__ . '/web/partner_documents_bso.php';
require __DIR__ . '/web/partner_documents_invoice_ip.php';
require __DIR__ . '/web/partner_documents_invoice_fl.php';

// Маршруты для предпросмотра документов
require __DIR__ . '/web/partner_documents_completion_act_ip_ip_preview.php';
require __DIR__ . '/web/partner_documents_completion_act_fl_ip_preview.php';
require __DIR__ . '/web/partner_documents_act_ip_ip_preview.php';
require __DIR__ . '/web/partner_documents_act_fl_ip_preview.php';
require __DIR__ . '/web/partner_documents_bso_preview.php';
require __DIR__ . '/web/partner_documents_invoice_ip_preview.php';
require __DIR__ . '/web/partner_documents_invoice_fl_preview.php';

require __DIR__ . '/web/client.php';
require __DIR__ . '/web/estimator.php';
require __DIR__ . '/web/profile.php';

// Подключаем маршруты для тестирования страниц ошибок
if (env('APP_DEBUG', false)) {
    require __DIR__ . '/web/test-errors.php';
}