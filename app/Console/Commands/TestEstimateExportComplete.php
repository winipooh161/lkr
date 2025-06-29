<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MaterialsEstimateTemplateService;
use App\Services\EstimateTemplateService;
use App\Http\Controllers\Partner\EstimateExcelController;

class TestEstimateExportComplete extends Command
{
    protected $signature = 'test:estimate-export-complete';
    protected $description = 'Полный тест экспорта сметы материалов';

    public function handle()
    {
        $this->info('=== Полный тест экспорта сметы материалов ===');
        
        try {
            // 1. Тестируем создание сервиса материалов
            $this->info('1. Создаем MaterialsEstimateTemplateService...');
            $materialsService = new MaterialsEstimateTemplateService();
            $this->info('✓ MaterialsEstimateTemplateService создан');
            
            // 2. Тестируем создание основного сервиса с инжекцией
            $this->info('2. Создаем EstimateTemplateService с инжекцией...');
            $estimateService = new EstimateTemplateService($materialsService);
            $this->info('✓ EstimateTemplateService создан с инжекцией');
            
            // 3. Тестируем создание контроллера
            $this->info('3. Создаем EstimateExcelController...');
            $controller = new EstimateExcelController($estimateService, $materialsService);
            $this->info('✓ EstimateExcelController создан');
            
            // 4. Тестируем создание шаблона напрямую через MaterialsService
            $this->info('4. Тестируем создание шаблона материалов...');
            $testPath1 = storage_path('app/test_materials_direct.xlsx');
            if (file_exists($testPath1)) unlink($testPath1);
            
            $result1 = $materialsService->createTemplate($testPath1);
            if ($result1 && file_exists($testPath1)) {
                $size1 = filesize($testPath1);
                $this->info("✓ Прямой шаблон создан: {$size1} байт");
            } else {
                $this->error('✗ Не удалось создать прямой шаблон');
            }
            
            // 5. Тестируем создание шаблона через EstimateTemplateService
            $this->info('5. Тестируем создание шаблона через EstimateTemplateService...');
            $testPath2 = storage_path('app/test_materials_via_estimate.xlsx');
            if (file_exists($testPath2)) unlink($testPath2);
            
            $result2 = $estimateService->createDefaultTemplate('materials', $testPath2);
            if ($result2 && file_exists($testPath2)) {
                $size2 = filesize($testPath2);
                $this->info("✓ Шаблон через EstimateService создан: {$size2} байт");
            } else {
                $this->error('✗ Не удалось создать шаблон через EstimateService');
            }
            
            // 6. Создаем мок-объект Estimate для тестирования контроллера
            $this->info('6. Тестируем createDefaultTemplate в контроллере...');
            $testPath3 = storage_path('app/test_materials_controller.xlsx');
            if (file_exists($testPath3)) unlink($testPath3);
            
            // Используем рефлексию для доступа к защищенному методу
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('createDefaultTemplate');
            $method->setAccessible(true);
            
            $result3 = $method->invoke($controller, 'materials', $testPath3);
            if ($result3 && file_exists($testPath3)) {
                $size3 = filesize($testPath3);
                $this->info("✓ Шаблон через контроллер создан: {$size3} байт");
            } else {
                $this->error('✗ Не удалось создать шаблон через контроллер');
            }
            
            // 7. Сравниваем размеры файлов
            $this->info('7. Сравнение результатов:');
            if (isset($size1)) $this->info("   Прямой шаблон: {$size1} байт");
            if (isset($size2)) $this->info("   Через EstimateService: {$size2} байт");
            if (isset($size3)) $this->info("   Через контроллер: {$size3} байт");
            
            if (isset($size1, $size2, $size3) && $size1 > 5000 && $size2 > 5000 && $size3 > 5000) {
                $this->info('✓ Все файлы имеют достаточный размер');
                $this->info('✓ Экспорт материальных смет работает корректно!');
            } else {
                $this->warn('⚠ Некоторые файлы слишком маленькие');
            }
            
        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
