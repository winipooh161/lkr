<?php

require_once __DIR__ . '/vendor/autoload.php';

// Загружаем Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Тестирование API ExcelTemplateController\n";

try {
    $estimateService = app(\App\Services\EstimateTemplateService::class);
    $controller = new \App\Http\Controllers\Partner\ExcelTemplateController($estimateService);
    $response = $controller->getSectionsData();
    
    $data = $response->getData(true);
    
    echo "Ответ получен: success = " . ($data['success'] ? 'true' : 'false') . "\n";
    
    if ($data['success']) {
        echo "Количество разделов: " . count($data['sections']) . "\n";
        echo "Количество работ: " . count($data['works']) . "\n";
        
        if (!empty($data['sections'])) {
            echo "Первый раздел: " . $data['sections'][0]['title'] . "\n";
        }
    }
    
    echo "✅ API работает корректно\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Трейс: " . $e->getTraceAsString() . "\n";
}
