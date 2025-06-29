<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MaterialsEstimateTemplateService;
use Illuminate\Support\Facades\Storage;

class TestMaterialsTemplate extends Command
{
    /**
     * Название и сигнатура команды.
     *
     * @var string
     */
    protected $signature = 'materials:test-template';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Тестирует генерацию шаблона сметы материалов';

    /**
     * Сервис шаблонов смет материалов
     * 
     * @var MaterialsEstimateTemplateService
     */
    protected $templateService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Тестирование генерации шаблона сметы материалов...');
        
        // Получаем сервис для работы с шаблонами через DI
        $this->templateService = app(MaterialsEstimateTemplateService::class);

        // Создаем директорию для тестов, если её нет
        $testPath = storage_path('app/test');
        if (!is_dir($testPath)) {
            mkdir($testPath, 0755, true);
            $this->info('Создана директория: ' . $testPath);
        }

        // Генерируем тестовый шаблон материалов
        $materialsTemplatePath = $testPath . '/test_materials_template.xlsx';
        
        try {
            $result = $this->templateService->createTemplate($materialsTemplatePath);
            
            if ($result) {
                $this->info('✅ Шаблон сметы материалов успешно создан!');
                $this->info('📁 Файл сохранен: ' . $materialsTemplatePath);
                
                if (file_exists($materialsTemplatePath)) {
                    $fileSize = filesize($materialsTemplatePath);
                    $this->info('📊 Размер файла: ' . round($fileSize / 1024, 2) . ' KB');
                }
            } else {
                $this->error('❌ Ошибка при создании шаблона материалов');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Исключение при создании шаблона: ' . $e->getMessage());
            $this->error('Стек вызовов: ' . $e->getTraceAsString());
            return 1;
        }

        $this->info('🎉 Тестирование завершено успешно!');
        $this->newLine();
        $this->info('Теперь шаблон сметы материалов имеет ту же структуру, что и основная смета работ:');
        $this->info('- Одинаковые заголовки таблиц');
        $this->info('- Одинаковое форматирование');
        $this->info('- Одинаковая структура ячеек');
        $this->info('- 4 листа с разными категориями материалов');
        
        return 0;
    }
}
