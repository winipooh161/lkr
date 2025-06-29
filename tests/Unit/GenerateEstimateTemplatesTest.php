<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Console\Commands\GenerateEstimateTemplates;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GenerateEstimateTemplatesTest extends TestCase
{
    /**
     * Тест стандартизированной генерации шаблонов смет.
     *
     * @return void
     */
    public function testGenerateEstimateTemplatesCommand()
    {
        // Запускаем команду
        $this->artisan('estimates:generate-templates --force')
            ->expectsOutput('Генерация шаблонов смет со стандартизированной структурой...')
            ->expectsOutput('Все шаблоны успешно сгенерированы!')
            ->assertExitCode(0);

        // Проверяем наличие сгенерированных файлов
        $templates = ['main', 'materials', 'additional'];
        foreach ($templates as $type) {
            $filePath = storage_path("app/templates/estimates/{$type}.xlsx");
            $this->assertFileExists($filePath, "Шаблон {$type} не был создан");
        }
    }    /**
     * Тест структуры сгенерированных шаблонов.
     *
     * @return void
     */
    public function testTemplatesStructure()
    {
        // Генерируем шаблоны перед проверкой
        $this->artisan('estimates:generate-templates --force');

        // Проверяем структуру для каждого типа шаблона
        $templates = ['main', 'materials', 'additional'];
        
        foreach ($templates as $type) {
            $filePath = storage_path("app/templates/estimates/{$type}.xlsx");
            
            // Открываем файл через PhpSpreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            
            // Проверяем наличие общих заголовков для всех шаблонов
            $this->assertNotEmpty($sheet->getCell('A1')->getValue(), "Заголовок отсутствует в шаблоне {$type}");
            $this->assertEquals('Объект:', $sheet->getCell('A2')->getValue(), "Метка 'Объект:' отсутствует в шаблоне {$type}");
            $this->assertEquals('Заказчик:', $sheet->getCell('A3')->getValue(), "Метка 'Заказчик:' отсутствует в шаблоне {$type}");
            $this->assertEquals('Дата составления:', $sheet->getCell('A4')->getValue(), "Метка 'Дата составления:' отсутствует в шаблоне {$type}");
            
            // Проверяем заголовки таблицы
            $this->assertEquals('№', $sheet->getCell('A5')->getValue(), "Заголовок '№' отсутствует в шаблоне {$type}");
            
            // Проверяем наличие итоговой строки
            $this->assertEquals('ИТОГО:', $sheet->getCell('B6')->getValue(), "Итоговая строка отсутствует в шаблоне {$type}");
            
            // Проверяем наличие хотя бы одного примера данных
            $this->assertNotEmpty($sheet->getCell('B7')->getValue(), "Примеры данных отсутствуют в шаблоне {$type}");
            
            // Проверка данных из файла WorkSectionsList.php
            if ($type === 'main') {
                // Проверяем, есть ли данные из WorkSectionsList.php
                $firstSectionFound = false;
                
                // Проверяем строки с 7 по 20, ищем "Демонтажные работы" или другой раздел из файла
                for ($row = 7; $row <= 20; $row++) {
                    $cellValue = $sheet->getCell('B' . $row)->getValue();
                    if (strpos(strtoupper($cellValue), 'ДЕМОНТАЖНЫЕ РАБОТЫ') !== false) {
                        $firstSectionFound = true;
                        break;
                    }
                }
                
                $this->assertTrue($firstSectionFound, "Данные из WorkSectionsList.php не найдены в шаблоне main");
            }
            
            // Проверка формул в примерах данных (пропустим для заголовков разделов)
            for ($row = 8; $row <= 15; $row++) {
                $cellValue = $sheet->getCell('A' . $row)->getValue();
                if (is_numeric($cellValue)) {
                    $this->assertStringStartsWith('=', $sheet->getCell('F' . $row)->getValue(), "Формула стоимости отсутствует в строке {$row} шаблона {$type}");
                    $this->assertStringStartsWith('=', $sheet->getCell('I' . $row)->getValue(), "Формула цены для заказчика отсутствует в строке {$row} шаблона {$type}");
                    $this->assertStringStartsWith('=', $sheet->getCell('J' . $row)->getValue(), "Формула стоимости для заказчика отсутствует в строке {$row} шаблона {$type}");
                    // Нашли хотя бы одну строку с данными - достаточно
                    break;
                }
            }
            
            // Проверяем защиту листа
            $this->assertTrue($sheet->getProtection()->isProtectionEnabled(), "Защита листа отключена в шаблоне {$type}");
        }
    }
    
    /**
     * Тест содержимого шаблона, созданного на основе данных из WorkSectionsList.php
     *
     * @return void
     */
    public function testWorkSectionsListData()
    {
        // Генерируем шаблоны перед проверкой
        $this->artisan('estimates:generate-templates --force');
        
        // Путь к файлу WorkSectionsList.php
        $filePath = base_path('app/Services/Data/WorkSectionsList.php');
        
        // Проверяем существование файла
        $this->assertFileExists($filePath, "Файл WorkSectionsList.php не найден");
        
        // Загружаем данные из файла
        $sections = require $filePath;
        
        // Проверяем, что в файле есть данные
        $this->assertNotEmpty($sections, "Файл WorkSectionsList.php не содержит данных");
        
        // Проверяем, что в файле есть хотя бы один раздел с работами
        $hasValidSection = false;
        foreach ($sections as $section) {
            if (isset($section['title']) && isset($section['items']) && !empty($section['items'])) {
                $hasValidSection = true;
                break;
            }
        }
        
        $this->assertTrue($hasValidSection, "В файле WorkSectionsList.php нет валидных разделов с работами");
        
        // Проверяем, что данные из файла попали в шаблон
        $excelPath = storage_path("app/templates/estimates/main.xlsx");
        $spreadsheet = IOFactory::load($excelPath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Ищем хотя бы один раздел из файла в шаблоне
        $sectionFoundInTemplate = false;
        foreach ($sections as $section) {
            if (!isset($section['title'])) continue;
            
            $sectionTitle = strtoupper($section['title']);
            
            for ($row = 7; $row <= 50; $row++) {
                $cellValue = strtoupper($sheet->getCell('B' . $row)->getValue());
                if (strpos($cellValue, substr($sectionTitle, 0, 10)) !== false) {
                    $sectionFoundInTemplate = true;
                    break 2;
                }
            }
        }
        
        $this->assertTrue($sectionFoundInTemplate, "Ни один из разделов из WorkSectionsList.php не найден в шаблоне");
    }
}
