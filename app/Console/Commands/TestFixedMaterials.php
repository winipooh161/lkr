<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MaterialsEstimateTemplateService;

class TestFixedMaterials extends Command
{
    protected $signature = 'test:fixed-materials';
    protected $description = 'Тест исправленного шаблона материалов';

    public function handle()
    {
        $this->info('=== Тест исправленного шаблона материалов ===');
        
        try {
            $service = new MaterialsEstimateTemplateService();
            $testPath = sys_get_temp_dir() . '/fixed_materials_test.xlsx';
            
            // Удаляем файл если он существует
            if (file_exists($testPath)) {
                unlink($testPath);
            }
            
            $this->info('1. Создаем исправленный шаблон...');
            $result = $service->createTemplate($testPath);
            
            if ($result && file_exists($testPath)) {
                $fileSize = filesize($testPath);
                $this->info("✓ Шаблон создан: {$fileSize} байт");
                
                $this->info('2. Проверяем данные...');
                
                // Проверяем данные общестроительных материалов
                $reflection = new \ReflectionClass($service);
                $method = $reflection->getMethod('getGeneralMaterialsData');
                $method->setAccessible(true);
                $data = $method->invoke($service);
                
                $this->info('3. Анализируем данные на предмет ошибок #ЗНАЧ!...');
                
                $hasEmptyHeaderCosts = false;
                $hasNumericItemCosts = false;
                
                foreach ($data as $item) {
                    // Проверяем заголовки разделов (должны иметь пустые cost и client_cost)
                    if (in_array($item['name'], ['1-я закупка', '2-я закупка', '3-я закупка', '4-я закупка'])) {
                        if (empty($item['cost']) && empty($item['client_cost'])) {
                            $hasEmptyHeaderCosts = true;
                            $this->info("✓ Заголовок '{$item['name']}' имеет пустые cost/client_cost");
                        } else {
                            $this->warn("⚠ Заголовок '{$item['name']}' имеет значения: cost={$item['cost']}, client_cost={$item['client_cost']}");
                        }
                    } else {
                        // Проверяем обычные элементы (должны иметь числовые значения)
                        if (is_numeric($item['cost']) && is_numeric($item['client_cost'])) {
                            $hasNumericItemCosts = true;
                        }
                    }
                }
                
                if ($hasEmptyHeaderCosts && $hasNumericItemCosts) {
                    $this->info('✓ Данные исправлены корректно - заголовки пустые, элементы числовые');
                    $this->info('✓ Проблема #ЗНАЧ! должна быть решена!');
                } else {
                    $this->warn('⚠ Не все данные исправлены корректно');
                }
                
                // Очистка
                unlink($testPath);
                
            } else {
                $this->error('✗ Не удалось создать шаблон');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
