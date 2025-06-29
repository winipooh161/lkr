<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap/app.php';

use App\Models\Project;
use App\Models\User;
use App\Http\Controllers\Partner\ProjectDocuments\CompletionActIpIpController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Запуск приложения
$app = $GLOBALS['app'];
$app->make('Illuminate\Contracts\Http\Kernel')->bootstrap();

try {
    // Получаем проект
    $projectId = 42; // ID проекта, который вызывает ошибку
    $project = Project::find($projectId);
    
    if (!$project) {
        echo "Проект с ID $projectId не найден";
        exit;
    }
    
    // Пытаемся авторизоваться как партнер проекта
    $partnerId = $project->partner_id;
    Auth::loginUsingId($partnerId);
    
    if (!Auth::check()) {
        echo "Не удалось авторизоваться как партнер";
        exit;
    }
    
    $partner = Auth::user();
    
    echo "<h2>Тестирование генерации DOCX с подписью и печатью</h2>";
    echo "<p><strong>Проект:</strong> {$project->id}</p>";
    echo "<p><strong>Партнер:</strong> {$partner->name}</p>";
    echo "<p><strong>Файл подписи:</strong> " . ($partner->signature_file ?: 'не загружен') . "</p>";
    echo "<p><strong>Файл печати:</strong> " . ($partner->stamp_file ?: 'не загружен') . "</p>";
    
    // Проверяем наличие файлов подписи и печати
    if ($partner->signature_file) {
        $signaturePath = storage_path('app/public/signatures/' . $partner->signature_file);
        echo "<p><strong>Путь к подписи:</strong> {$signaturePath} (" . (file_exists($signaturePath) ? 'найден' : 'не найден') . ")</p>";
    }
    
    if ($partner->stamp_file) {
        $stampPath = storage_path('app/public/stamps/' . $partner->stamp_file);
        echo "<p><strong>Путь к печати:</strong> {$stampPath} (" . (file_exists($stampPath) ? 'найден' : 'не найден') . ")</p>";
    }
    
    // Создаем тестовый запрос для генерации DOCX
    $requestData = [
        'format' => 'docx',
        'include_signature' => true,
        'include_stamp' => true
    ];
    $request = Request::create(
        "/partner/projects/{$projectId}/documents/completion-act-ip-ip/generate",
        'POST',
        $requestData
    );
    
    echo "<h3>Генерация DOCX документа...</h3>";
    
    // Создаем экземпляр контроллера и вызываем метод
    $controller = new CompletionActIpIpController();
    
    try {
        $response = $controller->generate($request, $project);
        
        // Проверяем тип ответа
        echo "<p><strong>Тип ответа:</strong> " . get_class($response) . "</p>";
        
        if (method_exists($response, 'getFile')) {
            $filePath = $response->getFile()->getPathname();
            $fileName = $response->getFile()->getClientOriginalName();
            echo "<p><strong>Файл:</strong> {$fileName}</p>";
            echo "<p><strong>Размер:</strong> " . filesize($filePath) . " байт</p>";
            
            // Копируем файл в публичную папку для скачивания
            $publicPath = __DIR__ . '/test_generated_docx.docx';
            copy($filePath, $publicPath);
            echo "<p><a href='/test_generated_docx.docx' download>Скачать сгенерированный DOCX</a></p>";
        } else {
            echo "<p>Неожиданный тип ответа</p>";
        }
        
    } catch (\Exception $genEx) {
        echo "<h3>Ошибка при генерации DOCX:</h3>";
        echo "<p><strong>Сообщение:</strong> " . $genEx->getMessage() . "</p>";
        echo "<p><strong>Файл:</strong> " . $genEx->getFile() . "</p>";
        echo "<p><strong>Строка:</strong> " . $genEx->getLine() . "</p>";
        echo "<h4>Трассировка:</h4><pre>" . $genEx->getTraceAsString() . "</pre>";
    }
    
} catch (\Exception $e) {
    echo "<h2>Произошла общая ошибка:</h2>";
    echo "<p><strong>Сообщение:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Файл:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Строка:</strong> " . $e->getLine() . "</p>";
    echo "<h3>Трассировка:</h3><pre>" . $e->getTraceAsString() . "</pre>";
    
    Log::error('DOCX test script: Ошибка', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

// Разлогиниваемся
Auth::logout();
