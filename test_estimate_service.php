<?php

require_once __DIR__ . '/vendor/autoload.php';

// Загружаем Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Тестирование EstimateTemplateService\n";

try {
    $service = app(\App\Services\EstimateTemplateService::class);
    $sections = $service->getWorkSections();
    
    echo "Количество разделов: " . count($sections) . "\n";
    
    if (!empty($sections)) {
        echo "Первый раздел: " . $sections[0]['title'] . " (" . count($sections[0]['items']) . " элементов)\n";
    }
    
    echo "✅ Сервис работает корректно\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
