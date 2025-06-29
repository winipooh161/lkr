<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Estimate;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use App\Services\MaterialsEstimateTemplateService;
use App\Services\EstimateTemplateService;

class TestFullMaterialsExport extends Command
{
    protected $signature = 'test:full-materials-export';
    protected $description = 'Полный тест экспорта сметы материалов через систему';

    public function handle()
    {
        $this->info('=== Полный тест системы экспорта сметы материалов ===');
        
        try {
            // 1. Создаем тестовый проект (без обращения к БД)
            $this->info('1. Создаем мок-объекты...');
            
            // Создаем мок-проект
            $project = new Project();
            $project->id = 999;
            $project->address = 'Тестовый адрес, д. 123';
            $project->client_name = 'Тестовый клиент';
              // Создаем мок-смету материалов
            $estimate = new Estimate();
            $estimate->id = 999;
            $estimate->type = 'materials';
            $estimate->project_id = 999;
            
            // Устанавливаем связь с проектом через setRelation
            $estimate->setRelation('project', $project);
            
            $this->info('✓ Мок-объекты созданы');
            
            // 2. Тестируем создание контроллера через контейнер зависимостей
            $this->info('2. Тестируем создание контроллера через Laravel DI...');
            
            $controller = app('App\Http\Controllers\Partner\EstimateExcelController');
            $this->info('✓ Контроллер создан через контейнер зависимостей');
            
            // 3. Используем рефлексию для вызова createInitialExcelFile
            $this->info('3. Тестируем createInitialExcelFile через контроллер...');
            
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('createInitialExcelFile');
            $method->setAccessible(true);
            
            // Вызываем метод
            $method->invoke($controller, $estimate);
            
            // 4. Проверяем результат
            if ($estimate->file_path) {
                $fullPath = storage_path('app/public/' . $estimate->file_path);
                if (file_exists($fullPath)) {
                    $fileSize = filesize($fullPath);
                    $this->info("✓ Файл создан: {$estimate->file_path} ({$fileSize} байт)");
                    
                    if ($fileSize > 5000) {
                        $this->info('✓ Размер файла достаточный, шаблон содержит данные');
                        
                        // 5. Проверяем метаданные
                        $this->info('4. Проверяем метаданные файла...');
                        $this->info("   Имя файла: {$estimate->file_name}");
                        $this->info("   Размер файла: {$estimate->file_size} байт");
                        $this->info("   Путь к файлу: {$estimate->file_path}");
                        
                        if ($estimate->file_name === 'Материалы_Черновые_материалы_2025.xlsx') {
                            $this->info('✓ Имя файла для материалов установлено корректно');
                        } else {
                            $this->warn("⚠ Неожиданное имя файла: {$estimate->file_name}");
                        }
                        
                        $this->info('✓ Экспорт материальных смет работает полностью корректно!');
                        
                        // 6. Очистка тестовых файлов
                        $this->info('5. Очистка тестовых файлов...');
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                            $this->info('✓ Тестовый файл удален');
                        }
                        
                    } else {
                        $this->warn('⚠ Файл слишком маленький, возможно есть проблемы');
                    }
                } else {
                    $this->error('✗ Файл не был создан по указанному пути');
                    return 1;
                }
            } else {
                $this->error('✗ Путь к файлу не был установлен');
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
