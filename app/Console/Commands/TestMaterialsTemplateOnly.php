<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MaterialsEstimateTemplateService;

class TestMaterialsTemplateOnly extends Command
{
    protected $signature = 'test:materials-template-only';
    protected $description = 'Test materials template generation only';

    public function handle()
    {
        $this->info('Тестируем создание шаблона материалов...');
        
        try {
            $service = new MaterialsEstimateTemplateService();
            $testPath = storage_path('app/test_materials_template.xlsx');
            
            // Удаляем файл если он существует
            if (file_exists($testPath)) {
                unlink($testPath);
            }
            
            $this->info('Создаем шаблон...');
            $result = $service->createTemplate($testPath);
            
            if ($result && file_exists($testPath)) {
                $fileSize = filesize($testPath);
                $this->info('✓ Шаблон успешно создан: ' . $testPath);
                $this->info('Размер файла: ' . $fileSize . ' байт');
                
                if ($fileSize > 5000) {
                    $this->info('✓ Файл достаточно большой, шаблон содержит данные');
                } else {
                    $this->warn('⚠ Файл маленький, возможно не все данные записались');
                }
                
                // Попробуем прочитать файл с помощью PhpSpreadsheet
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($testPath);
                $sheetsCount = $spreadsheet->getSheetCount();
                $this->info('Количество листов: ' . $sheetsCount);
                
                for ($i = 0; $i < $sheetsCount; $i++) {
                    $sheet = $spreadsheet->getSheet($i);
                    $sheetName = $sheet->getTitle();
                    $this->info('- Лист ' . ($i + 1) . ': ' . $sheetName);
                }
                
            } else {
                $this->error('✗ Не удалось создать шаблон');
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
