<?php

require_once 'vendor/autoload.php';

use App\Http\Controllers\Partner\ProjectDocuments\ActFlIpController;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

echo "Тестирование реального контроллера генерации DOCX...\n\n";

// Создаем mock объекты
$mockProject = new class {
    public $id = 123;
    public $name = 'Тестовый проект';
    public $description = 'Описание тестового проекта';
    public $address = 'Москва, ул. Тестовая, д. 1';
    public $cost = 50000;
    public $contractor_type = 'ип';
    public $created_at;
    
    public function __construct() {
        $this->created_at = now();
    }
    
    public function executor() {
        return new class {
            public function first() {
                return new class {
                    public $name = 'ИП Иванов';
                    public $inn = '123456789012';
                    public $ogrn = '123456789012345';
                    public $address = 'Москва, ул. Исполнителя, д. 1';
                };
            }
        };
    }
    
    public function client() {
        return new class {
            public function first() {
                return new class {
                    public $name = 'Петров Петр Петрович';
                    public $passport_series = '1234';
                    public $passport_number = '567890';
                    public $passport_issued_by = 'ОВД Тестовое';
                    public $passport_issued_at = '2010-01-01';
                    public $passport_code = '123-456';
                };
            }
        };
    }
};

// Создаем mock Request
$mockRequest = new class {
    private $data = [
        'format' => 'docx',
        'include_signature' => false,
        'include_stamp' => false
    ];
    
    public function validate($rules) {
        return true;
    }
    
    public function input($key, $default = null) {
        return $this->data[$key] ?? $default;
    }
};

try {
    echo "1. Создаем контроллер...\n";
    $controller = new ActFlIpController();
    
    echo "2. Вызываем генерацию документа...\n";
    $response = $controller->generate($mockRequest, $mockProject);
    
    echo "3. Проверяем результат...\n";
    if ($response instanceof \Illuminate\Http\JsonResponse) {
        $data = $response->getData(true);
        if (isset($data['error'])) {
            echo "❌ ОШИБКА: " . $data['error'] . "\n";
            if (isset($data['suggestion'])) {
                echo "💡 ПРЕДЛОЖЕНИЕ: " . $data['suggestion'] . "\n";
            }
        } else {
            echo "✅ УСПЕХ: JSON ответ получен\n";
        }
    } elseif ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
        echo "✅ УСПЕХ: Файл готов к скачиванию\n";
        echo "📄 Имя файла: " . $response->getFile()->getFilename() . "\n";
        echo "📊 Размер файла: " . filesize($response->getFile()->getPathname()) . " байт\n";
    } else {
        echo "✅ УСПЕХ: Ответ получен (тип: " . get_class($response) . ")\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "📍 Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Показываем наиболее важные строки трассировки
    $trace = $e->getTrace();
    echo "\n📋 Трассировка (первые 3 вызова):\n";
    for ($i = 0; $i < min(3, count($trace)); $i++) {
        $item = $trace[$i];
        echo "  " . ($i + 1) . ". " . ($item['file'] ?? 'unknown') . ":" . ($item['line'] ?? 'unknown') 
             . " -> " . ($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? '') . "\n";
    }
}

echo "\nТестирование завершено!\n";
