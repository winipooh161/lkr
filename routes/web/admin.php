<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// Маршруты для администраторов
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
    
    // Маршруты управления пользователями
    Route::resource('users', UserController::class)->names([
        'index' => 'admin.users.index',
        'show' => 'admin.users.show',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
        'create' => 'admin.users.create',
        'store' => 'admin.users.store',
    ]);
    
    // Дополнительные маршруты для управления пользователями
    Route::prefix('users')->name('admin.users.')->group(function() {
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{user}/change-role', [UserController::class, 'changeRole'])->name('change-role');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{user}/send-notification', [UserController::class, 'sendNotification'])->name('send-notification');
        Route::post('/{user}/assign-projects', [UserController::class, 'assignProjects'])->name('assign-projects');
        Route::post('/bulk-action', [UserController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/import', [UserController::class, 'import'])->name('import');
        Route::get('/export', [UserController::class, 'export'])->name('export');
        Route::get('/search', [UserController::class, 'search'])->name('search');
    });
    
    // Маршруты для массовой отправки уведомлений
    Route::prefix('notifications')->name('admin.notifications.')->group(function() {
        Route::get('/send', [\App\Http\Controllers\Admin\NotificationController::class, 'showForm'])->name('form');
        Route::post('/send', [\App\Http\Controllers\Admin\NotificationController::class, 'send'])->name('send');
        Route::get('/test', [\App\Http\Controllers\Admin\NotificationController::class, 'testNotification'])->name('test');
    });
    
    // Маршруты для управления проектами
    Route::prefix('projects')->name('admin.projects.')->group(function() {
        Route::get('/', [\App\Http\Controllers\Admin\ProjectController::class, 'index'])->name('index');
        Route::get('/{project}', [\App\Http\Controllers\Admin\ProjectController::class, 'show'])->name('show');
    });
    
    // Маршрут для обновления шаблонов смет (доступен только админам)
    Route::get('/refresh-estimate-templates', function() {
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('home')->with('error', 'У вас нет доступа к этой функции');
        }
        
        Artisan::call('estimates:generate-templates');
        return redirect()->back()->with('success', 'Шаблоны смет успешно обновлены!');
    })->name('admin.refresh-estimate-templates');
});
