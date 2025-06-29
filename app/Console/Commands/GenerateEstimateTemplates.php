<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EstimateTemplateService;
use App\Services\MaterialsEstimateTemplateService;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use Carbon\Carbon;

class GenerateEstimateTemplates extends Command
{
    /**
     * Название и сигнатура команды.
     *
     * @var string
     */
    protected $signature = 'estimates:generate-templates {--force : Перезаписать существующие шаблоны}';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Генерирует стандартизированные шаблоны смет для различных типов работ';

    /**
     * Сервис шаблонов смет
     * 
     * @var EstimateTemplateService
     */
    protected $templateService;

    /**
     * Сервис шаблонов смет материалов
     * 
     * @var MaterialsEstimateTemplateService
     */
    protected $materialsTemplateService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(EstimateTemplateService $templateService, MaterialsEstimateTemplateService $materialsTemplateService)
    {
        parent::__construct();
        $this->templateService = $templateService;
        $this->materialsTemplateService = $materialsTemplateService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Генерация шаблонов смет со стандартизированной структурой...');
        
        // Создаем директорию для шаблонов, если её нет
        $templatesPath = storage_path('app/templates/estimates');
        if (!is_dir($templatesPath)) {
            mkdir($templatesPath, 0755, true);
        }

        // Типы шаблонов для генерации
        $templateTypes = ['main', 'materials', 'additional'];

        // Проверка наличия существующих шаблонов
        $force = $this->option('force');
        
        foreach ($templateTypes as $type) {
            $filePath = "{$templatesPath}/{$type}.xlsx";
            
            if (file_exists($filePath) && !$force) {
                if (!$this->confirm("Шаблон $type уже существует. Перезаписать?", false)) {
                    $this->warn("Пропуск генерации шаблона $type.");
                    continue;
                }
            }            $this->info("Создание шаблона {$type}...");
            
            // Специальная обработка для материалов
            if ($type === 'materials') {
                try {
                    // Используем специализированный сервис напрямую
                    $result = $this->materialsTemplateService->createTemplate($filePath);
                    if ($result) {
                        $this->info("Шаблон {$type} успешно создан по пути: {$filePath}");
                    } else {
                        $this->error("Не удалось создать шаблон {$type}.");
                    }
                } catch (\Exception $e) {
                    $this->error("Ошибка при сохранении шаблона {$type}: " . $e->getMessage());
                }
                continue; // Переходим к следующему типу
            }
            
            // Для других типов создаем стандартный шаблон
            $spreadsheet = $this->createStandardizedTemplate($type);
            
            if ($spreadsheet) {
                // Сохраняем файл
                try {
                    $writer = new Xlsx($spreadsheet);
                    $writer->save($filePath);
                    $this->info("Шаблон {$type} успешно создан по пути: {$filePath}");
                } catch (\Exception $e) {
                    $this->error("Ошибка при сохранении шаблона {$type}: " . $e->getMessage());
                }
            } else {
                $this->error("Ошибка при создании шаблона {$type}.");
            }
        }

        $this->info('Все шаблоны успешно сгенерированы!');
        return 0;
    }

    /**
     * Создает стандартизированный шаблон сметы
     * 
     * @param string $type Тип сметы
     * @return Spreadsheet|null
     */    private function createStandardizedTemplate($type)
    {
        $spreadsheet = new Spreadsheet();
        
        // В зависимости от типа сметы используем разные базовые шаблоны
        switch ($type) {
            case 'materials':
                // Для материалов используем специализированный сервис
                // Создаем новый экземпляр и сохраняем пустой файл временно
                $tempPath = storage_path('app/templates/estimates/temp_materials.xlsx');
                
                // Используем специализированный сервис для создания шаблона материалов с разными листами
                $this->materialsTemplateService->createTemplate($tempPath);
                
                // Загружаем созданный файл как объект Spreadsheet
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempPath);
                
                // Удаляем временный файл
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
                break;
                
            case 'additional':
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Доп. работы');
                $this->setupWorkHeader($sheet, 'ДОПОЛНИТЕЛЬНАЯ СМЕТА НА РАБОТЫ');
                break;
                
            case 'main':
            default:
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Работы');
                $this->setupWorkHeader($sheet, 'СМЕТА НА ПРОВЕДЕНИЕ РАБОТ');
                break;
        }
        
        // Применяем стандартные стили и защиту (только для не-материальных смет)
        if ($type !== 'materials') {
            $this->applyStandardStyles($spreadsheet, $type);
            $this->applyStandardProtection($spreadsheet, $type);
        }
        
        // Устанавливаем свойства документа
        if ($type === 'materials') {
            // Для материалов уже установлены свойства в MaterialsEstimateTemplateService
        } else {
            $spreadsheet->getProperties()
                ->setCreator('Ремонтная компания')
                ->setLastModifiedBy('Система смет')
                ->setTitle('Смета')
                ->setSubject('Смета на ремонтные работы ' . ucfirst($type))
                ->setDescription('Автоматически сгенерированный шаблон сметы ' . ucfirst($type))
                ->setKeywords('смета, ремонт, ' . $type)
                ->setCategory('Сметы');
        }
            
        return $spreadsheet;
    }

    /**
     * Настраивает заголовок для смет работ
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param string $title
     * @return void
     */
    private function setupWorkHeader($sheet, $title)
    {
        // Заголовок сметы
        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления:');
        $sheet->setCellValue('B4', Carbon::now()->format('d.m.Y'));

        // Заголовки таблицы (единые для всех смет работ)
        $headers = [
            'A5' => '№',
            'B5' => 'Наименование работ',
            'C5' => 'Ед. изм.',
            'D5' => 'Кол-во',
            'E5' => 'Цена, руб.',
            'F5' => 'Стоимость, руб.',
            'G5' => 'Наценка, %',
            'H5' => 'Скидка, %',
            'I5' => 'Цена для заказчика',
            'J5' => 'Стоимость для заказчика'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Добавляем шапку для итоговой строки
        $sheet->setCellValue('B6', 'ИТОГО:');
        $sheet->setCellValue('F6', '=SUM(F7:F1000)');
        $sheet->setCellValue('J6', '=SUM(J7:J1000)');
        
        // Добавляем стандартные примеры работ
        $this->addStandardWorkExamples($sheet);
    }

    /**
     * Настраивает заголовок для смет материалов
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */    private function setupMaterialsHeader($sheet)
    {
        // Заголовок сметы материалов
        $sheet->setCellValue('A1', 'СМЕТА НА МАТЕРИАЛЫ');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления:');
        $sheet->setCellValue('B4', Carbon::now()->format('d.m.Y'));

        // Заголовки таблицы для материалов
        $headers = [
            'A5' => '№',
            'B5' => 'Наименование материала',
            'C5' => 'Ед. изм.',
            'D5' => 'Кол-во',
            'E5' => 'Цена, руб.',
            'F5' => 'Стоимость, руб.',
            'G5' => 'Наценка, %',
            'H5' => 'Скидка, %',
            'I5' => 'Цена для заказчика',
            'J5' => 'Стоимость для заказчика'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Добавляем стандартные примеры материалов
        $this->addStandardMaterialExamples($sheet);
        
        // После заполнения данными, добавляем итоговую строку
        // Определяем последнюю строку с данными
        $lastRow = $sheet->getHighestRow();
        $totalRow = $lastRow + 1;
        
        // Добавляем шапку для итоговой строки
        $sheet->setCellValue('B' . $totalRow, 'ИТОГО:');
        $sheet->setCellValue('F' . $totalRow, '=SUM(F7:F' . $lastRow . ')');
        $sheet->setCellValue('J' . $totalRow, '=SUM(J7:J' . $lastRow . ')');
        
        // Форматируем итоговую строку
        $sheet->getStyle('A' . $totalRow . ':J' . $totalRow)->getFont()->setBold(true);
        $sheet->getStyle('F' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('J' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
    }/**
     * Добавляет стандартные примеры работ из файла WorkSectionsList.php
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    private function addStandardWorkExamples($sheet)
    {
        // Получаем список разделов работ из файла
        $sections = $this->getWorkSectionsFromFile();
        
        if (empty($sections)) {
            // Если данные не загружены, добавляем стандартные примеры
            $this->addDefaultWorkExamples($sheet);
            return;
        }
        
        $row = 7;
        $itemNumber = 1;
        
        foreach ($sections as $section) {
            // Пропускаем пустые разделы
            if (!isset($section['title']) || !isset($section['items']) || empty($section['items'])) {
                continue;
            }
            
            // Добавляем заголовок раздела
            $sheet->setCellValue('B' . $row, strtoupper($section['title']));
            
            // Форматируем заголовок раздела
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0F0F0'],
                ],
            ]);
            
            $row++;
              // Добавляем все работы из раздела (убрано ограничение на количество)
            foreach ($section['items'] as $item) {
                // Убрано ограничение на максимальное количество работ
                
                $sheet->setCellValue('A' . $row, $itemNumber++);
                $sheet->setCellValue('B' . $row, $item['name']);
                $sheet->setCellValue('C' . $row, $item['unit']);
                $sheet->setCellValue('D' . $row, rand(1, 20)); // Случайное количество
                $sheet->setCellValue('E' . $row, rand(100, 2000)); // Случайная цена
                $sheet->setCellValue('F' . $row, '=D' . $row . '*E' . $row);
                $sheet->setCellValue('G' . $row, '20'); // Стандартная наценка
                $sheet->setCellValue('H' . $row, '0');  // Без скидки
                $sheet->setCellValue('I' . $row, '=E' . $row . '*(1+G' . $row . '/100)*(1-H' . $row . '/100)');
                $sheet->setCellValue('J' . $row, '=D' . $row . '*I' . $row);
                  $row++;
                // Убрано увеличение счетчика количества работ
            }
        }
    }
    
    /**
     * Загружает данные из файла WorkSectionsList.php
     * 
     * @return array
     */
    private function getWorkSectionsFromFile()
    {
        $filePath = base_path('app/Services/Data/WorkSectionsList.php');
        
        if (file_exists($filePath)) {
            return require $filePath;
        }
          // Если файл не найден, логируем ошибку
        $this->warn('Файл WorkSectionsList.php не найден в ' . $filePath);
        return [];
    }
    
    /**
     * Добавляет стандартные примеры работ, если данные не загружены из файла
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    private function addDefaultWorkExamples($sheet)
    {
        // Пример заголовка раздела
        $sheet->setCellValue('B7', 'ДЕМОНТАЖНЫЕ РАБОТЫ');
        
        // Примеры работ с формулами
        $sheet->setCellValue('A8', '1');
        $sheet->setCellValue('B8', 'Демонтаж напольного покрытия');
        $sheet->setCellValue('C8', 'м²');
        $sheet->setCellValue('D8', '10');
        $sheet->setCellValue('E8', '250');
        $sheet->setCellValue('F8', '=D8*E8');
        $sheet->setCellValue('G8', '20');
        $sheet->setCellValue('H8', '0');
        $sheet->setCellValue('I8', '=E8*(1+G8/100)*(1-H8/100)');
        $sheet->setCellValue('J8', '=D8*I8');
        
        $sheet->setCellValue('A9', '2');
        $sheet->setCellValue('B9', 'Демонтаж плинтуса');
        $sheet->setCellValue('C9', 'м.п.');
        $sheet->setCellValue('D9', '15');
        $sheet->setCellValue('E9', '120');
        $sheet->setCellValue('F9', '=D9*E9');
        $sheet->setCellValue('G9', '20');
        $sheet->setCellValue('H9', '0');
        $sheet->setCellValue('I9', '=E9*(1+G9/100)*(1-H9/100)');
        $sheet->setCellValue('J9', '=D9*I9');
        
        // Еще один раздел
        $sheet->setCellValue('B10', 'ОТДЕЛОЧНЫЕ РАБОТЫ');
        
        // Примеры отделочных работ
        $sheet->setCellValue('A11', '3');
        $sheet->setCellValue('B11', 'Укладка ламината');
        $sheet->setCellValue('C11', 'м²');
        $sheet->setCellValue('D11', '10');
        $sheet->setCellValue('E11', '500');
        $sheet->setCellValue('F11', '=D11*E11');
        $sheet->setCellValue('G11', '20');
        $sheet->setCellValue('H11', '0');
        $sheet->setCellValue('I11', '=E11*(1+G11/100)*(1-H11/100)');
        $sheet->setCellValue('J11', '=D11*I11');
        
        $sheet->setCellValue('A12', '4');
        $sheet->setCellValue('B12', 'Установка плинтуса');
        $sheet->setCellValue('C12', 'м.п.');
        $sheet->setCellValue('D12', '15');
        $sheet->setCellValue('E12', '180');
        $sheet->setCellValue('F12', '=D12*E12');
        $sheet->setCellValue('G12', '20');
        $sheet->setCellValue('H12', '0');
        $sheet->setCellValue('I12', '=E12*(1+G12/100)*(1-H12/100)');
        $sheet->setCellValue('J12', '=D12*I12');
    }    /**
     * Добавляет материалы из файла MaterialsList.php
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    private function addStandardMaterialExamples($sheet)
    {
        // Создаем экземпляр сервиса для получения материалов из файла
        $materialsTemplateService = app(MaterialsEstimateTemplateService::class);
        $materialsList = $materialsTemplateService->getMaterialsList();
        
        if (empty($materialsList)) {
            // Если данные не загружены, добавляем стандартные примеры
            $this->addDefaultMaterialExamples($sheet);
            return;
        }
        
        $row = 7;
        $itemNumber = 1;
        $totalMaterialsCost = 0;
        $totalClientCost = 0;
        
        // Перебираем все разделы материалов и добавляем их на лист
        foreach ($materialsList as $section) {
            if (!isset($section['title']) || !isset($section['items']) || empty($section['items'])) {
                continue;
            }
            
            // Добавляем заголовок раздела материалов
            $sheet->setCellValue('B' . $row, strtoupper($section['title']));
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0F0F0'],
                ],
            ]);
            
            $row++;
            
            // Добавляем все материалы из раздела
            foreach ($section['items'] as $item) {
                // Проверяем наличие всех необходимых полей
                if (!isset($item['name']) || !isset($item['unit']) || !isset($item['quantity']) || !isset($item['price'])) {
                    continue;
                }
                
                // Число для отображения в таблице
                $displayNumber = isset($item['number']) ? $item['number'] : $itemNumber++;
                
                $sheet->setCellValue('A' . $row, $displayNumber);
                $sheet->setCellValue('B' . $row, $item['name']);
                $sheet->setCellValue('C' . $row, $item['unit']);
                $sheet->setCellValue('D' . $row, $item['quantity']);
                $sheet->setCellValue('E' . $row, $item['price']);
                
                // Стоимость - проверяем, есть ли уже значение или нужно вычислить
                if (isset($item['cost']) && is_numeric($item['cost']) && $item['cost'] > 0) {
                    $sheet->setCellValue('F' . $row, $item['cost']);
                    // Если есть числовое значение стоимости, добавляем к общей сумме
                    $totalMaterialsCost += $item['cost'];
                } else {
                    $sheet->setCellValue('F' . $row, '=D' . $row . '*E' . $row);
                    // Вычисляем стоимость для общей суммы
                    if (is_numeric($item['quantity']) && is_numeric($item['price'])) {
                        $totalMaterialsCost += ($item['quantity'] * $item['price']);
                    }
                }
                
                $sheet->setCellValue('G' . $row, isset($item['markup']) ? $item['markup'] : '10');
                $sheet->setCellValue('H' . $row, isset($item['discount']) ? $item['discount'] : '0');
                
                // Цена для заказчика - проверяем, есть ли уже значение или нужно вычислить
                if (isset($item['client_price']) && is_numeric($item['client_price']) && $item['client_price'] > 0) {
                    $sheet->setCellValue('I' . $row, $item['client_price']);
                } else {
                    $sheet->setCellValue('I' . $row, '=E' . $row . '*(1+G' . $row . '/100)*(1-H' . $row . '/100)');
                }
                
                // Стоимость для заказчика - проверяем, есть ли уже значение или нужно вычислить
                if (isset($item['client_cost']) && is_numeric($item['client_cost']) && $item['client_cost'] > 0) {
                    $sheet->setCellValue('J' . $row, $item['client_cost']);
                    $totalClientCost += $item['client_cost'];
                } else {
                    $sheet->setCellValue('J' . $row, '=D' . $row . '*I' . $row);
                    // Вычисляем стоимость для клиента для общей суммы
                    if (is_numeric($item['quantity']) && isset($item['client_price']) && is_numeric($item['client_price'])) {
                        $totalClientCost += ($item['quantity'] * $item['client_price']);
                    } elseif (is_numeric($item['quantity']) && is_numeric($item['price']) && isset($item['markup']) && is_numeric($item['markup'])) {
                        $clientPrice = $item['price'] * (1 + $item['markup']/100);
                        if (isset($item['discount']) && is_numeric($item['discount'])) {
                            $clientPrice *= (1 - $item['discount']/100);
                        }
                        $totalClientCost += ($item['quantity'] * $clientPrice);
                    }
                }
                
                // Форматирование ячеек с числовыми значениями
                $sheet->getStyle('E' . $row . ':F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('I' . $row . ':J' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                
                $row++;
            }
        }
        
        // Добавляем итоговую строку
        $sheet->setCellValue('B6', 'ИТОГО:');
        $sheet->setCellValue('F6', $totalMaterialsCost);
        $sheet->setCellValue('J6', $totalClientCost);
        
        // Форматируем итоговую строку
        $sheet->getStyle('A6:J6')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
        ]);
    }
    
    /**
     * Добавляет стандартные примеры материалов, если данные не загружены из файла
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    private function addDefaultMaterialExamples($sheet)
    {
        // Пример заголовка раздела
        $sheet->setCellValue('B7', 'РАСХОДНЫЕ МАТЕРИАЛЫ');
        
        // Примеры материалов с формулами
        $sheet->setCellValue('A8', '1');
        $sheet->setCellValue('B8', 'Грунтовка универсальная');
        $sheet->setCellValue('C8', 'л');
        $sheet->setCellValue('D8', '5');
        $sheet->setCellValue('E8', '150');
        $sheet->setCellValue('F8', '=D8*E8');
        $sheet->setCellValue('G8', '10');
        $sheet->setCellValue('H8', '0');
        $sheet->setCellValue('I8', '=E8*(1+G8/100)*(1-H8/100)');
        $sheet->setCellValue('J8', '=D8*I8');
        
        $sheet->setCellValue('A9', '2');
        $sheet->setCellValue('B9', 'Штукатурка гипсовая');
        $sheet->setCellValue('C9', 'кг');
        $sheet->setCellValue('D9', '25');
        $sheet->setCellValue('E9', '20');
        $sheet->setCellValue('F9', '=D9*E9');
        $sheet->setCellValue('G9', '10');
        $sheet->setCellValue('H9', '0');
        $sheet->setCellValue('I9', '=E9*(1+G9/100)*(1-H9/100)');
        $sheet->setCellValue('J9', '=D9*I9');
        
        // Еще один раздел
        $sheet->setCellValue('B10', 'ОТДЕЛОЧНЫЕ МАТЕРИАЛЫ');
        
        // Примеры отделочных материалов
        $sheet->setCellValue('A11', '3');
        $sheet->setCellValue('B11', 'Ламинат');
        $sheet->setCellValue('C11', 'м²');
        $sheet->setCellValue('D11', '12');
        $sheet->setCellValue('E11', '800');
        $sheet->setCellValue('F11', '=D11*E11');
        $sheet->setCellValue('G11', '10');
        $sheet->setCellValue('H11', '0');
        $sheet->setCellValue('I11', '=E11*(1+G11/100)*(1-H11/100)');
        $sheet->setCellValue('J11', '=D11*I11');
        
        $sheet->setCellValue('A12', '4');
        $sheet->setCellValue('B12', 'Плинтус');
        $sheet->setCellValue('C12', 'м.п.');
        $sheet->setCellValue('D12', '18');
        $sheet->setCellValue('E12', '120');
        $sheet->setCellValue('F12', '=D12*E12');
        $sheet->setCellValue('G12', '10');
        $sheet->setCellValue('H12', '0');
        $sheet->setCellValue('I12', '=E12*(1+G12/100)*(1-H12/100)');
        $sheet->setCellValue('J12', '=D12*I12');
    }

    /**
     * Применяет стандартные стили к шаблону
     * 
     * @param Spreadsheet $spreadsheet
     * @param string $type
     * @return void
     */
    private function applyStandardStyles(Spreadsheet $spreadsheet, $type)
    {
        $sheet = $spreadsheet->getActiveSheet();
        
        // Форматирование заголовка
        $sheet->getStyle('A1:J1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Форматирование информации об объекте
        $sheet->getStyle('A2:A4')->getFont()->setBold(true);
        $sheet->getStyle('B2:B4')->getFont()->setItalic(true);
        
        // Форматирование заголовков таблицы
        $sheet->getStyle('A5:J5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        
        // Устанавливаем ширину столбцов
        $sheet->getColumnDimension('A')->setWidth(5);     // №
        $sheet->getColumnDimension('B')->setWidth(40);    // Наименование
        $sheet->getColumnDimension('C')->setWidth(10);    // Ед. изм.
        $sheet->getColumnDimension('D')->setWidth(10);    // Кол-во
        $sheet->getColumnDimension('E')->setWidth(15);    // Цена
        $sheet->getColumnDimension('F')->setWidth(15);    // Стоимость
        $sheet->getColumnDimension('G')->setWidth(12);    // Наценка, %
        $sheet->getColumnDimension('H')->setWidth(12);    // Скидка, %
        $sheet->getColumnDimension('I')->setWidth(15);    // Цена для заказчика
        $sheet->getColumnDimension('J')->setWidth(15);    // Стоимость для заказчика
        
        // Форматирование итоговой строки
        $sheet->getStyle('A6:J6')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
        ]);
        
        // Применяем границы ко всем ячейкам в таблице
        // Определяем последнюю используемую строку
        $lastDataRow = $sheet->getHighestRow();
        
        // Применяем границы ко всем ячейкам данных
        $sheet->getStyle('A5:J' . $lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);
        
        // Жирные границы для заголовков и итогов
        $sheet->getStyle('A5:J5')->applyFromArray([
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        
        // Форматирование цифровых столбцов
        for ($row = 7; $row <= $lastDataRow; $row++) {
            // Проверяем, является ли строка заголовком раздела
            $value = $sheet->getCell('B' . $row)->getValue();
            $isHeader = (strpos($value, 'РАБОТЫ') !== false || strpos($value, 'МАТЕРИАЛЫ') !== false);
            
            if ($isHeader) {
                // Форматирование заголовка раздела
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F0F0'],
                    ],
                ]);
            } else {
                // Форматирование числовых ячеек
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            }
        }
    }

    /**
     * Применяет стандартную защиту к шаблону
     * 
     * @param Spreadsheet $spreadsheet
     * @param string $type
     * @return void
     */
    private function applyStandardProtection(Spreadsheet $spreadsheet, $type)
    {
        $sheet = $spreadsheet->getActiveSheet();
        
        // Защищаем лист с паролем
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('remont');
        
        // Разрешаем редактирование ячеек с информацией о проекте
        $sheet->getStyle('B2')->getProtection()->setLocked(false); // Объект
        $sheet->getStyle('B3')->getProtection()->setLocked(false); // Заказчик
        $sheet->getStyle('B4')->getProtection()->setLocked(false); // Дата
        
        // Определяем последнюю строку
        $lastDataRow = $sheet->getHighestRow();
        
        // Разрешаем редактирование определенных столбцов во всех строках данных
        for ($row = 7; $row <= $lastDataRow; $row++) {
            // Проверяем, является ли строка заголовком раздела
            $value = $sheet->getCell('B' . $row)->getValue();
            $isHeader = (strpos($value, 'РАБОТЫ') !== false || strpos($value, 'МАТЕРИАЛЫ') !== false);
            
            if (!$isHeader) {
                // Разрешаем редактирование имени, ед. изм., количества, цены, наценки и скидки
                $sheet->getStyle('B' . $row)->getProtection()->setLocked(false); // Наименование
                $sheet->getStyle('C' . $row)->getProtection()->setLocked(false); // Ед. изм.
                $sheet->getStyle('D' . $row)->getProtection()->setLocked(false); // Кол-во
                $sheet->getStyle('E' . $row)->getProtection()->setLocked(false); // Цена
                $sheet->getStyle('G' . $row)->getProtection()->setLocked(false); // Наценка
                $sheet->getStyle('H' . $row)->getProtection()->setLocked(false); // Скидка
                
                // Запрещаем редактирование формул
                // F (стоимость), I (цена для заказчика), J (стоимость для заказчика) защищены по умолчанию
            }
        }
    }
}
