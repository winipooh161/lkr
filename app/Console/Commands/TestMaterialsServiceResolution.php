<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestMaterialsServiceResolution extends Command
{
    protected $signature = 'test:materials-service-resolution';
    protected $description = 'Тест разрешения зависимостей для сервиса материалов';

    public function handle()
    {
        $this->info('=== Тест разрешения зависимостей для сервиса материалов ===');
        
        try {
            // 1. Проверяем, что MaterialsEstimateTemplateService разрешается
            $this->info('1. Проверяем разрешение MaterialsEstimateTemplateService...');
            $materialsService = app('App\Services\MaterialsEstimateTemplateService');
            $this->info('✓ MaterialsEstimateTemplateService разрешен: ' . get_class($materialsService));
            
            // 2. Проверяем, что EstimateTemplateService разрешается с инжекцией
            $this->info('2. Проверяем разрешение EstimateTemplateService...');
            $estimateService = app('App\Services\EstimateTemplateService');
            $this->info('✓ EstimateTemplateService разрешен: ' . get_class($estimateService));
            
            // 3. Проверяем инжекцию через рефлексию
            $this->info('3. Проверяем инжекцию MaterialsService в EstimateService...');
            $reflection = new \ReflectionClass($estimateService);
            $property = $reflection->getProperty('materialsTemplateService');
            $property->setAccessible(true);
            $injectedService = $property->getValue($estimateService);
            
            if ($injectedService !== null) {
                $this->info('✓ MaterialsTemplateService правильно инжектирован в EstimateTemplateService');
                $this->info('   Тип инжектированного сервиса: ' . get_class($injectedService));
            } else {
                $this->error('✗ MaterialsTemplateService не инжектирован в EstimateTemplateService');
                return 1;
            }
            
            // 4. Проверяем, что EstimateExcelController разрешается с обеими зависимостями
            $this->info('4. Проверяем разрешение EstimateExcelController...');
            $controller = app('App\Http\Controllers\Partner\EstimateExcelController');
            $this->info('✓ EstimateExcelController разрешен: ' . get_class($controller));
            
            // 5. Проверяем инжекцию в контроллер
            $this->info('5. Проверяем инжекцию сервисов в контроллер...');
            $controllerReflection = new \ReflectionClass($controller);
            
            $estimateServiceProperty = $controllerReflection->getProperty('estimateTemplateService');
            $estimateServiceProperty->setAccessible(true);
            $injectedEstimateService = $estimateServiceProperty->getValue($controller);
            
            $materialsServiceProperty = $controllerReflection->getProperty('materialsTemplateService');
            $materialsServiceProperty->setAccessible(true);
            $injectedMaterialsService = $materialsServiceProperty->getValue($controller);
            
            if ($injectedEstimateService !== null && $injectedMaterialsService !== null) {
                $this->info('✓ Оба сервиса правильно инжектированы в контроллер');
                $this->info('   EstimateTemplateService: ' . get_class($injectedEstimateService));
                $this->info('   MaterialsEstimateTemplateService: ' . get_class($injectedMaterialsService));
            } else {
                $this->error('✗ Не все сервисы инжектированы в контроллер');
                if ($injectedEstimateService === null) {
                    $this->error('   EstimateTemplateService не инжектирован');
                }
                if ($injectedMaterialsService === null) {
                    $this->error('   MaterialsEstimateTemplateService не инжектирован');
                }
                return 1;
            }
            
            // 6. Тестируем создание шаблона через инжектированный сервис
            $this->info('6. Тестируем создание шаблона через инжектированный сервис...');
            $testPath = sys_get_temp_dir() . '/test_materials_injection.xlsx';
            if (file_exists($testPath)) unlink($testPath);
            
            $result = $injectedMaterialsService->createTemplate($testPath);
            if ($result && file_exists($testPath)) {
                $fileSize = filesize($testPath);
                $this->info("✓ Шаблон создан через инжектированный сервис: {$fileSize} байт");
                unlink($testPath);
            } else {
                $this->error('✗ Не удалось создать шаблон через инжектированный сервис');
                return 1;
            }
            
            $this->info('✓ Все зависимости правильно разрешены и работают корректно!');
            
        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
