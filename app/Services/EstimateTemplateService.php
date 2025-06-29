<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facade;

class EstimateTemplateService
{
    /**
     * @var MaterialsEstimateTemplateService
     */
    protected $materialsTemplateService;

    /**
     * Конструктор с внедрением зависимости
     *
     * @param MaterialsEstimateTemplateService $materialsTemplateService
     */
    public function __construct(MaterialsEstimateTemplateService $materialsTemplateService = null)
    {
        $this->materialsTemplateService = $materialsTemplateService;
    }
    
    /**
     * Получает список разделов работ
     * 
     * @return array Массив разделов работ и их элементов
     */
    public function getWorkSections()
    {
        $filePath = base_path('app/Services/Data/WorkSectionsList.php');
        
        if (file_exists($filePath)) {
            return require $filePath;
        }
        
        // Возвращаем пустой массив, если файл не найден
        return [];
    }

    /**
     * Создает шаблон сметы в зависимости от типа
     * 
     * @param string $type Тип сметы
     * @param string $savePath Путь для сохранения файла
     * @return bool Результат операции
     */
    public function createDefaultTemplate($type = 'main', $savePath = null)
    {
        // Если путь не указан, используем стандартный
        if (!$savePath) {
            $savePath = storage_path("app/templates/estimates/{$type}.xlsx");
        }
        
        // Создаем директорию при необходимости
        $directory = dirname($savePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // В зависимости от типа сметы используем разные шаблоны
        switch ($type) {
            case 'materials':
                // Используем специальный сервис для материалов, если он доступен
                if ($this->materialsTemplateService) {
                    return $this->materialsTemplateService->createTemplate($savePath);
                }
                // Если специального сервиса нет, используем базовый шаблон
                $spreadsheet = new Spreadsheet();
                $this->createMaterialsTemplate($spreadsheet);
                break;
                
            case 'additional':
                $spreadsheet = new Spreadsheet();
                $this->createAdditionalTemplate($spreadsheet);
                break;
                
            case 'main':
            default:
                $spreadsheet = new Spreadsheet();
                $this->createMainTemplate($spreadsheet);
                break;
        }
        
        // Устанавливаем общие свойства документа
        $spreadsheet->getProperties()
            ->setCreator('Ремонтная компания')
            ->setLastModifiedBy('Система смет')
            ->setTitle('Смета')
            ->setSubject('Смета на ремонтные работы')
            ->setDescription('Смета на ремонтные работы');
            
        // Применяем стандартное форматирование для любого типа сметы
        $this->formatSpreadsheet($spreadsheet);
        
        // Сохраняем файл
        $writer = new Xlsx($spreadsheet);
        $writer->save($savePath);
        
        return true;
    }
      /**
     * Создает шаблон основной сметы
     * 
     * @param Spreadsheet $spreadsheet Объект таблицы
     * @return void
     */
    private function createMainTemplate(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Работы');
        
        // Заголовок сметы
        $sheet->setCellValue('A1', 'СМЕТА НА ПРОВЕДЕНИЕ РАБОТ');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления:');
        $sheet->setCellValue('B4', Carbon::now()->format('d.m.Y'));

        // Заголовки таблицы
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
        
        // Итоговая строка будет добавлена после всех работ
        
        // Получаем список разделов работ из файла
        $sections = $this->getWorkSections();
        
        $row = 7;
        $itemNumber = 1;
        
        if (!empty($sections)) {
            // Если данные из файла успешно загружены
            foreach ($sections as $section) {
                // Проверяем структуру раздела
                if (!isset($section['title']) || !isset($section['items']) || !is_array($section['items'])) {
                    Log::warning('Неправильный формат раздела в файле WorkSectionsList.php');
                    continue;
                }
                
                // Добавляем заголовок раздела
                $sheet->setCellValue('B' . $row, $section['title']);
                
                // Форматируем заголовок раздела
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F0F0'],
                    ],
                ]);
                
                $row++;
                  // Добавляем все работы из раздела
                foreach ($section['items'] as $item) {
                    
                    if (!isset($item['name']) || !isset($item['unit'])) {
                        Log::warning('Неправильный формат работы в файле WorkSectionsList.php');
                        continue;
                    }
                    
                    // Примерные значения для сметы
                    $quantity = rand(1, 20);
                    $price = rand(100, 2000);
                    $markup = 20; // Стандартная наценка 20%
                    $discount = 0; // Без скидки
                    
                    $sheet->setCellValue('A' . $row, $itemNumber++);
                    $sheet->setCellValue('B' . $row, $item['name']);
                    $sheet->setCellValue('C' . $row, $item['unit']);
                    $sheet->setCellValue('D' . $row, $quantity);
                    $sheet->setCellValue('E' . $row, $price);
                    $sheet->setCellValue('F' . $row, '=D' . $row . '*E' . $row);
                    $sheet->setCellValue('G' . $row, $markup);
                    $sheet->setCellValue('H' . $row, $discount);
                    $sheet->setCellValue('I' . $row, '=E' . $row . '*(1+G' . $row . '/100)*(1-H' . $row . '/100)');                    $sheet->setCellValue('J' . $row, '=D' . $row . '*I' . $row);
                    
                    $row++;
                }
            }
        } else {
            // Если данные не загружены, добавляем стандартные примеры
            $works = $this->getWorksFromTemplateList();
            
            foreach ($works as $work) {
                // Определяем, является ли это заголовком раздела
                $isHeader = (!isset($work[1]) || empty($work[1]));
                
                if ($isHeader) {
                    $sheet->setCellValue('A' . $row, '');
                    $sheet->setCellValue('B' . $row, $work[0]);
                    
                    // Форматируем заголовок раздела
                    $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F0F0F0'],
                        ],
                    ]);
                } else {
                    $sheet->setCellValue('A' . $row, $itemNumber++);
                    $sheet->setCellValue('B' . $row, $work[0]);
                    $sheet->setCellValue('C' . $row, $work[1]);
                    $sheet->setCellValue('D' . $row, $work[2]);
                    $sheet->setCellValue('E' . $row, $work[3]);
                    $sheet->setCellValue('F' . $row, '=D' . $row . '*E' . $row);
                    $sheet->setCellValue('G' . $row, $work[5]);
                    $sheet->setCellValue('H' . $row, $work[6]);
                    $sheet->setCellValue('I' . $row, '=E' . $row . '*(1+G' . $row . '/100)*(1-H' . $row . '/100)');
                    $sheet->setCellValue('J' . $row, '=D' . $row . '*I' . $row);
                }
                
                $row++;
            }
        }
        
        // Обновляем формулы итогов
        $lastRow = $row - 1;
        
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('B' . $row, 'ИТОГО:');
        $sheet->setCellValue('F' . $row, '=SUM(F7:F' . $lastRow . ')');
        $sheet->setCellValue('J' . $row, '=SUM(J7:J' . $lastRow . ')');
        
        // Форматируем итоговую строку
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
        ]);
    }
    
    /**
     * Получает работы из списка
     * 
     * @return array Массив работ для сметы
     */
    private function getWorksFromTemplateList()
    {
        $works = [];
        
        // Получаем список разделов работ из внешнего файла
        $sections = $this->getWorkSections();
        
        // Проверяем, что массив секций не пустой
        if (empty($sections)) {
            // Логируем ошибку для отладки
            Log::warning('Не удалось загрузить секции работ из файла');
            return $works;
        }
        
        foreach ($sections as $section) {
            // Проверяем структуру раздела
            if (!isset($section['title']) || !isset($section['items']) || !is_array($section['items'])) {
                Log::warning('Неправильный формат раздела в файле WorkSectionsList.php');
                continue;
            }
              // Добавляем заголовок раздела
            $works[] = [$section['title'], '', '', '', '', '', '', '', ''];
              // Добавляем все работы из раздела
            foreach ($section['items'] as $item) {
                
                if (!isset($item['name']) || !isset($item['unit'])) {
                    Log::warning('Неправильный формат работы в файле WorkSectionsList.php');
                    continue;
                }
                
                // Примерные значения для сметы
                $quantity = rand(1, 20);
                $price = rand(100, 2000);
                $markup = rand(10, 25);
                $discount = 0;
                
                $works[] = [
                    $item['name'],
                    $item['unit'],
                    $quantity,
                    $price,
                    '', // Формула будет добавлена динамически
                    $markup,
                    $discount,
                    '', // Формула будет добавлена динамически
                    ''  // Формула будет добавлена динамически
                ];
                  // Убрано увеличение счетчика - теперь в шаблон попадают все работы
            }
        }
        
        return $works;
    }
      /**
     * Создает шаблон дополнительной сметы
     * 
     * @param Spreadsheet $spreadsheet Объект таблицы
     * @return void
     */
    private function createAdditionalTemplate(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Дополнительные работы');
        
        // Заголовок сметы
        $sheet->setCellValue('A1', 'ДОПОЛНИТЕЛЬНАЯ СМЕТА');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления:');
        $sheet->setCellValue('B4', Carbon::now()->format('d.m.Y'));

        // Заголовки таблицы
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
        
        // Получаем список секций работ
        $sections = $this->getWorkSections();
        
        // Начинаем с 7-й строки для данных
        $row = 7;
        $itemNumber = 1;
        
        // Добавляем примеры для дополнительной сметы
        $categories = ['ДОПОЛНИТЕЛЬНЫЕ РАБОТЫ'];
        $sampleWorks = [];
        
        if (!empty($sections)) {
            // Выберем несколько случайных работ из разных разделов для примера
            $selectedWorks = [];
            foreach ($sections as $section) {
                if (!isset($section['items']) || empty($section['items'])) continue;
                
                // Выбираем одну случайную работу из каждого раздела
                $randomIndex = array_rand($section['items']);
                $selectedWorks[] = $section['items'][$randomIndex];
            }
            
            // Перемешаем выбранные работы
            shuffle($selectedWorks);
              // Возьмем только первые 3 для примера
            $sampleWorks = array_slice($selectedWorks, 0, 3);
        } else {            // Если данные не загружены, используем дефолтные примеры (максимум 3)
            $sampleWorks = [
                ['name' => 'Дополнительная шпатлевка стен', 'unit' => 'м²'],
                ['name' => 'Установка дополнительных розеток', 'unit' => 'шт'],
                ['name' => 'Монтаж точечных светильников', 'unit' => 'шт']
            ];
        }
        
        // Добавляем заголовок категории
        $sheet->setCellValue('B' . $row, 'ДОПОЛНИТЕЛЬНЫЕ РАБОТЫ');
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
        ]);
        $row++;
        
        // Добавляем примеры работ
        foreach ($sampleWorks as $work) {
            $quantity = rand(1, 10);
            $price = rand(500, 3000);
            
            $sheet->setCellValue('A' . $row, $itemNumber++);
            $sheet->setCellValue('B' . $row, $work['name']);
            $sheet->setCellValue('C' . $row, $work['unit']);
            $sheet->setCellValue('D' . $row, $quantity);
            $sheet->setCellValue('E' . $row, $price);
            $sheet->setCellValue('F' . $row, '=D' . $row . '*E' . $row);
            $sheet->setCellValue('G' . $row, '20'); // Стандартная наценка 20%
            $sheet->setCellValue('H' . $row, '0');  // Без скидки
            $sheet->setCellValue('I' . $row, '=E' . $row . '*(1+G' . $row . '/100)*(1-H' . $row . '/100)');
            $sheet->setCellValue('J' . $row, '=D' . $row . '*I' . $row);
            
            $row++;
        }
        
        // Обновляем формулы итогов
        $lastRow = $row - 1;
        
        // Итоговая строка
        $sheet->setCellValue('B6', 'ИТОГО:');
        $sheet->setCellValue('F6', '=SUM(F7:F' . $lastRow . ')');
        $sheet->setCellValue('J6', '=SUM(J7:J' . $lastRow . ')');
        
        // Добавляем примеры дополнительных работ
            $itemNumber = 1;
            $row = 3;
            
            // Получаем все доступные работы из всех разделов
            $allItems = [];
            foreach ($this->getWorkSections() as $section) {
                if (isset($section['items'])) {
                    foreach ($section['items'] as $item) {
                        if (isset($item['name']) && isset($item['unit'])) {
                            $allItems[] = $item;
                        }
                    }
                }
            }
            
            // Если есть работы, то добавляем до 3 примеров
            if (count($allItems) > 0) {
                // Перемешиваем массив для случайного выбора
                shuffle($allItems);
                
                // Добавляем до 3 примеров работ
                $maxItems = min(count($allItems), 3);
                for ($i = 0; $i < $maxItems; $i++) {
                    $work = $allItems[$i];
                    
                    $quantity = rand(1, 5);
                    $price = rand(500, 5000);
                    
                    $sheet->setCellValue('A' . $row, $itemNumber++);
                    $sheet->setCellValue('B' . $row, $work['name'] . ' (дополнительно)');
                    $sheet->setCellValue('C' . $row, $work['unit']);
                    $sheet->setCellValue('D' . $row, $quantity);
                    $sheet->setCellValue('E' . $row, $price);
                    $sheet->setCellValue('F' . $row, '=D' . $row . '*E' . $row);
                    
                    $row++;
                }
            } else {
                // Если работы не найдены, добавляем примеры по умолчанию
                $defaultItems = [
                    ['name' => 'Дополнительная работа 1', 'unit' => 'шт'],
                    ['name' => 'Дополнительная работа 2', 'unit' => 'м²'],
                    ['name' => 'Дополнительная работа 3', 'unit' => 'м']
                ];
                
                foreach ($defaultItems as $item) {
                    $sheet->setCellValue('A' . $row, $itemNumber++);
                    $sheet->setCellValue('B' . $row, $item['name'] . ' (дополнительно)');
                    $sheet->setCellValue('C' . $row, $item['unit']);
                    $sheet->setCellValue('D' . $row, $quantity);
                    $sheet->setCellValue('E' . $row, $price);
                    $sheet->setCellValue('F' . $row, '=D' . $row . '*E' . $row);
                    
                    $row++;
                }
            }
        
        // Обновляем формулы итогов
        $lastRow = $row - 1;
        
        // Итоговая строка
        $sheet->setCellValue('B' . $row, 'ИТОГО:');
        $sheet->setCellValue('F' . $row, '=SUM(F7:F' . $lastRow . ')');
        $sheet->setCellValue('J' . $row, '=SUM(J7:J' . $lastRow . ')');
    }    /**
     * Создает шаблон сметы на материалы
     * 
     * @param Spreadsheet $spreadsheet Объект таблицы
     * @return void
     */
    private function createMaterialsTemplate(Spreadsheet $spreadsheet)
    {
        // Если специальный сервис для материалов доступен, используем его
        if ($this->materialsTemplateService) {
            // Вместо создания заготовки здесь, позволяем специализированному сервису делать всю работу
            return;
        }
        
        // Если специализированного сервиса нет, создаем базовый шаблон с одним листом
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Все материалы');
        
        // Заголовок сметы
        $sheet->setCellValue('A1', 'СМЕТА НА МАТЕРИАЛЫ');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления:');
        $sheet->setCellValue('B4', Carbon::now()->format('d.m.Y'));

        // Заголовки таблицы
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
        
        // Итоговая строка (первоначально пустая)
        $sheet->setCellValue('B6', 'ИТОГО:');
        
        // Базовые примеры материалов, если специализированный сервис недоступен
        $defaultMaterials = [
            ['Цемент М500', 'мешок', 10, 450],
            ['Песок строительный', 'м³', 3, 900],
            ['Штукатурка гипсовая', 'кг', 50, 18],
            ['Грунтовка глубокого проникновения', 'л', 5, 350],
            ['Плитка напольная', 'м²', 15, 1200]
        ];
        
        $row = 7;
        $itemNumber = 1;
        
        foreach ($defaultMaterials as $material) {
            $sheet->setCellValue('A' . $row, $itemNumber++);
            $sheet->setCellValue('B' . $row, $material[0]);
            $sheet->setCellValue('C' . $row, $material[1]);
            $sheet->setCellValue('D' . $row, $material[2]);
            $sheet->setCellValue('E' . $row, $material[3]);
            $sheet->setCellValue('F' . $row, '=D' . $row . '*E' . $row);
            $sheet->setCellValue('G' . $row, '10'); // Стандартная наценка для материалов 10%
            $sheet->setCellValue('H' . $row, '0');  // Без скидки
            $sheet->setCellValue('I' . $row, '=E' . $row . '*(1+G' . $row . '/100)*(1-H' . $row . '/100)');
            $sheet->setCellValue('J' . $row, '=D' . $row . '*I' . $row);
            $row++;
        }
        
        // Обновляем формулы итогов после добавления всех материалов
        $lastRow = $row - 1;
        $sheet->setCellValue('F6', '=SUM(F7:F' . $lastRow . ')');
        $sheet->setCellValue('J6', '=SUM(J7:J' . $lastRow . ')');
    }
    
    /**
     * Применяет форматирование к таблице
     * 
     * @param Spreadsheet $spreadsheet Объект таблицы
     * @param bool $applyBordersOnly Применять только границы (без изменения структуры)
     * @param int $sheetIndex Индекс листа для форматирования
     * @return void
     */
    public function formatSpreadsheet(Spreadsheet $spreadsheet, $applyBordersOnly = false, $sheetIndex = 0)
    {
        // Выбираем лист для форматирования
        $spreadsheet->setActiveSheetIndex($sheetIndex);
        $sheet = $spreadsheet->getActiveSheet();
        
        if (!$applyBordersOnly) {
            // Форматирование заголовка
            $sheet->getStyle('A1:J1')->getFont()->setBold(true)->setSize(2);
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
            // Находим итоговую строку (ищем текст "ИТОГО:")
            $lastRow = 6; // По умолчанию это строка 6
            for ($row = 6; $row < 50; $row++) {
                if ($sheet->getCell('B' . $row)->getValue() == 'ИТОГО:') {
                    $lastRow = $row;
                    break;
                }
            }
            
            // Форматируем итоговую строку
            $sheet->getStyle('A' . $lastRow . ':J' . $lastRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0F0F0'],
                ],
            ]);
        }
        
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
        $numericColumns = ['D', 'E', 'F', 'G', 'H', 'I', 'J'];
        foreach ($numericColumns as $column) {
            $sheet->getStyle($column . '6:' . $column . $lastDataRow)
                ->getNumberFormat()
                ->setFormatCode('#,##0.00_-');
        }
        
        // Центрирование в определенных столбцах
        $centerColumns = ['A', 'C', 'D', 'G', 'H'];
        foreach ($centerColumns as $column) {
            $sheet->getStyle($column . '6:' . $column . $lastDataRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }
}