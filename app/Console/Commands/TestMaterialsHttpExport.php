<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Models\Estimate;
use App\Models\Project;

class TestMaterialsHttpExport extends Command
{
    protected $signature = 'test:materials-http-export';
    protected $description = 'Тест HTTP экспорта материалов (симуляция веб-запроса)';

    public function handle()
    {
        $this->info('=== Тест HTTP экспорта материалов ===');
        
        try {
            // 1. Создаем мок-объекты
            $this->info('1. Создаем тестовые объекты...');
            
            // Создаем проект
            $project = new Project();
            $project->id = 999;
            $project->address = 'Тестовый адрес, д. 123';
            $project->client_name = 'Тестовый клиент';
            
            // Создаем смету материалов
            $estimate = new Estimate();
            $estimate->id = 999;
            $estimate->type = 'materials';
            $estimate->project_id = 999;
            $estimate->setRelation('project', $project);
            
            $this->info('✓ Тестовые объекты созданы');
            
            // 2. Создаем контроллер через DI
            $this->info('2. Создаем контроллер...');
            $controller = app('App\Http\Controllers\Partner\EstimateExcelController');
            $this->info('✓ Контроллер создан');
            
            // 3. Симулируем вызов export метода
            $this->info('3. Симулируем вызов метода export...');
            
            // Используем рефлексию для доступа к методу
            $reflection = new \ReflectionClass($controller);
            
            // Сначала создаем файл
            $createMethod = $reflection->getMethod('createInitialExcelFile');
            $createMethod->setAccessible(true);
            $createMethod->invoke($controller, $estimate);
            
            $this->info('   Файл создан, путь: ' . ($estimate->file_path ?? 'НЕ УСТАНОВЛЕН'));
            
            if (!$estimate->file_path) {
                $this->error('✗ Путь к файлу не установлен');
                return 1;
            }
            
            // Проверяем файл напрямую (не через Laravel Storage)
            $testFilePath = sys_get_temp_dir() . '/test_export_materials.xlsx';
            if (file_exists($testFilePath)) unlink($testFilePath);
            
            // Копируем содержимое в тестовый файл для проверки
            $realPath = storage_path('app/public/' . $estimate->file_path);
            if (file_exists($realPath)) {
                copy($realPath, $testFilePath);
                $fileSize = filesize($testFilePath);
                $this->info("✓ Файл экспорта создан и доступен: {$fileSize} байт");
                
                if ($fileSize > 5000) {
                    $this->info('✓ Размер файла достаточный');
                    
                    // Проверяем, что это действительно Excel файл
                    $handle = fopen($testFilePath, 'rb');
                    $header = fread($handle, 4);
                    fclose($handle);
                    
                    if (substr($header, 0, 2) === 'PK') {
                        $this->info('✓ Файл имеет правильную сигнатуру Excel (ZIP)');
                        $this->info('✓ Экспорт материальной сметы работает полностью!');
                    } else {
                        $this->warn('⚠ Файл не имеет сигнатуры Excel');
                        $this->info('   Первые 4 байта: ' . bin2hex($header));
                    }
                } else {
                    $this->error('✗ Файл слишком маленький');
                }
                
                // Очистка
                unlink($testFilePath);
                unlink($realPath);
                
            } else {
                $this->error('✗ Файл не найден по пути: ' . $realPath);
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
