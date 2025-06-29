<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Estimate;
use App\Http\Controllers\Partner\EstimateExcelController;
use App\Services\EstimateTemplateService;
use App\Services\MaterialsEstimateTemplateService;

class TestMaterialsExport extends Command
{
    protected $signature = 'test:materials-export {estimate_id?}';
    protected $description = 'Test materials estimate export functionality';

    public function handle()
    {
        $estimateId = $this->argument('estimate_id');
        
        // Если ID не указан, создаем тестовую смету материалов
        if (!$estimateId) {
            $this->info('Создаем тестовую смету материалов...');
            
            $estimate = new Estimate();
            $estimate->type = 'materials';
            $estimate->project_id = 1; // предполагаем, что проект с ID 1 существует
            $estimate->save();
            
            $this->info('Создана тестовая смета с ID: ' . $estimate->id);
            $estimateId = $estimate->id;
        }
        
        // Загружаем смету
        $estimate = Estimate::find($estimateId);
        if (!$estimate) {
            $this->error('Смета с ID ' . $estimateId . ' не найдена');
            return 1;
        }
        
        $this->info('Тестируем экспорт сметы типа: ' . $estimate->type);
        
        // Создаем контроллер с правильными зависимостями
        $materialsService = new MaterialsEstimateTemplateService();
        $estimateService = new EstimateTemplateService($materialsService);
        $controller = new EstimateExcelController($estimateService, $materialsService);
        
        try {
            // Пытаемся создать файл
            $this->info('Создаем файл Excel...');
            $controller->createInitialExcelFile($estimate);
            
            if ($estimate->file_path && file_exists(storage_path('app/public/' . $estimate->file_path))) {
                $fileSize = filesize(storage_path('app/public/' . $estimate->file_path));
                $this->info('Файл успешно создан: ' . $estimate->file_path);
                $this->info('Размер файла: ' . $fileSize . ' байт');
                
                if ($fileSize > 1000) {
                    $this->info('✓ Файл не пустой, экспорт работает корректно');
                } else {
                    $this->warn('⚠ Файл слишком маленький, возможно есть проблемы');
                }
            } else {
                $this->error('✗ Файл не был создан');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('Ошибка при создании файла: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
