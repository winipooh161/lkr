<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class MaterialsEstimateTemplateService
{
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
     * Получает список категорий материалов
     * 
     * @return array Массив категорий материалов и их элементов
     */
    public function getMaterialsList()
    {
        $filePath = base_path('app/Services/Data/MaterialsList.php');
        
        if (file_exists($filePath)) {
            return require $filePath;
        }
        
        // Возвращаем пустой массив, если файл не найден
        return [];
    }
    
    /**
     * Создает шаблон сметы материалов и сохраняет его по указанному пути
     *
     * @param string $savePath Путь для сохранения файла шаблона
     * @return bool Результат операции
     */
    public function createTemplate($savePath)
    {
        // Создаем экземпляр Spreadsheet
        $spreadsheet = new Spreadsheet();
        
        // Настраиваем свойства документа (как в основной смете)
        $spreadsheet->getProperties()
            ->setCreator('Ремонтная компания')
            ->setLastModifiedBy('Система смет')
            ->setTitle('Смета на материалы')
            ->setSubject('Смета на материалы')
            ->setDescription('Комплексная смета материалов по категориям');

        // Создаем листы для разных категорий материалов
        $this->createGeneralMaterialsSheet($spreadsheet);
        $this->createElectricalMaterialsSheet($spreadsheet);
        $this->createPlumbingMaterialsSheet($spreadsheet);
        $this->createHeatingMaterialsSheet($spreadsheet);

        // Сохраняем файл
        $writer = new Xlsx($spreadsheet);
        
        // Создаем директорию при необходимости
        $directory = dirname($savePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Сохраняем файл
        $writer->save($savePath);
        
        return true;
    }/**
     * Создает лист общестроительных материалов
     * @param Spreadsheet $spreadsheet
     */
    private function createGeneralMaterialsSheet(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('1. Общестроительные');
        
        // Заголовок сметы (как в основной смете работ)
        $sheet->setCellValue('A1', 'СМЕТА НА МАТЕРИАЛЫ - 1. Общестроительные материалы');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления: ' . date('d.m.Y'));
        
        // Заголовки таблицы (строка 5 как в основной смете)
        $this->setTableHeaders($sheet, 5);
        
        // Получаем список категорий материалов из файла
        $sections = $this->getMaterialsList();
        
        // Материалы для общестроительных работ
        $materials = [];
        
        // Ищем раздел с общестроительными материалами
        foreach ($sections as $section) {
            if (!isset($section['title']) || !isset($section['items']) || empty($section['items'])) {
                continue;
            }
            
            // Отбираем только общестроительные материалы
            if (strpos($section['title'], '1. Общестроительные') !== false) {
                // Материалы из этого раздела
                $materials = $section['items'];
                break;
            }
        }
        
        // Заполняем данные в таблицу
        $this->fillMaterialsData($sheet, $materials, 6);
        
        // Применяем форматирование
        $this->applySheetFormatting($sheet, count($materials) + 6);
    }
      /**
     * Создает лист электромонтажных материалов
     * @param Spreadsheet $spreadsheet
     */
    private function createElectricalMaterialsSheet(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('2. Электромонтажные');
        
        // Заголовок сметы (как в основной смете работ)
        $sheet->setCellValue('A1', 'СМЕТА НА МАТЕРИАЛЫ - 2. Электромонтажные материалы');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления: ' . date('d.m.Y'));
        
        // Заголовки таблицы (строка 5 как в основной смете)
        $this->setTableHeaders($sheet, 5);
        
        // Получаем список категорий материалов из файла
        $sections = $this->getMaterialsList();
        
        // Материалы для электромонтажных работ
        $materials = [];
        
        // Ищем раздел с электромонтажными материалами
        foreach ($sections as $section) {
            if (!isset($section['title']) || !isset($section['items']) || empty($section['items'])) {
                continue;
            }
            
            // Отбираем только электромонтажные материалы
            if (strpos($section['title'], '2. Электромонтажные') !== false) {
                // Материалы из этого раздела
                $materials = $section['items'];
                break;
            }
        }
        
        // Заполняем данные в таблицу
        $this->fillMaterialsData($sheet, $materials, 6);
        
        // Применяем форматирование
        $this->applySheetFormatting($sheet, count($materials) + 6);
    }
      /**
     * Создает лист сантехнических материалов
     * @param Spreadsheet $spreadsheet
     */
    private function createPlumbingMaterialsSheet(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('3. Сантехнические');
        
        // Заголовок сметы (как в основной смете работ)
        $sheet->setCellValue('A1', 'СМЕТА НА МАТЕРИАЛЫ - 3. Сантехнический материал');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления: ' . date('d.m.Y'));
        
        // Заголовки таблицы (строка 5 как в основной смете)
        $this->setTableHeaders($sheet, 5);
        
        // Получаем список категорий материалов из файла
        $sections = $this->getMaterialsList();
        
        // Материалы для сантехнических работ
        $materials = [];
        
        // Ищем раздел с сантехническими материалами
        foreach ($sections as $section) {
            if (!isset($section['title']) || !isset($section['items']) || empty($section['items'])) {
                continue;
            }
            
            // Отбираем только сантехнические материалы
            if (strpos($section['title'], '3. Сантехнические') !== false) {
                // Материалы из этого раздела
                $materials = $section['items'];
                break;
            }
        }
        
        // Заполняем данные в таблицу
        $this->fillMaterialsData($sheet, $materials, 6);
        
        // Применяем форматирование
        $this->applySheetFormatting($sheet, count($materials) + 6);
    }
      /**
     * Создает лист материалов для отопления
     * @param Spreadsheet $spreadsheet
     */
    private function createHeatingMaterialsSheet(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('4. Отопление');
        
        // Заголовок сметы (как в основной смете работ)
        $sheet->setCellValue('A1', 'СМЕТА НА МАТЕРИАЛЫ - 4. Отопление');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления: ' . date('d.m.Y'));
        
        // Заголовки таблицы (строка 5 как в основной смете)
        $this->setTableHeaders($sheet, 5);
        
        // Получаем список категорий материалов из файла
        $sections = $this->getMaterialsList();
        
        // Материалы для отопительных работ
        $materials = [];
        
        // Ищем раздел с отопительными материалами
        foreach ($sections as $section) {
            if (!isset($section['title']) || !isset($section['items']) || empty($section['items'])) {
                continue;
            }            // Отбираем только отопительные материалы
            if (strpos($section['title'], '4. Материалы для отопления') !== false) {
                // Материалы из этого раздела
                $materials = $section['items'];
                break;
            }
        }
        
        // Заполняем данные в таблицу
        $this->fillMaterialsData($sheet, $materials, 6);
        
        // Применяем форматирование
        $this->applySheetFormatting($sheet, count($materials) + 6);
    }
      /**
     * Устанавливает заголовки таблицы (одинаковые с основной сметой работ)
     * @param $sheet
     * @param int $row
     */
    private function setTableHeaders($sheet, $row)
    {
        $headers = [
            'A' => '№',
            'B' => 'Наименование материалов',
            'C' => 'Ед. изм.',
            'D' => 'Кол-во',
            'E' => 'Цена, руб.',
            'F' => 'Стоимость, руб.',
            'G' => 'Наценка, %',
            'H' => 'Скидка, %',
            'I' => 'Цена для заказчика',
            'J' => 'Стоимость для заказчика'
        ];

        foreach ($headers as $col => $value) {
            $sheet->setCellValue($col . $row, $value);
        }
    }    /**
     * Заполняет данные материалов в таблицу
     * @param $sheet
     * @param array $materials
     * @param int $startRow
     */
    private function fillMaterialsData($sheet, $materials, $startRow)
    {
        $row = $startRow;
        
        foreach ($materials as $material) {
            // Заполняем ячейки данными
            $sheet->setCellValue('A' . $row, isset($material['number']) ? $material['number'] : '');
            $sheet->setCellValue('B' . $row, isset($material['name']) ? $material['name'] : '');
            $sheet->setCellValue('C' . $row, isset($material['unit']) ? $material['unit'] : '');
            
            // Проверяем, является ли текущая строка заголовком раздела (обычно имеют "-" в качестве единицы измерения)
            $isHeaderRow = (isset($material['unit']) && $material['unit'] === '-');
            
            // Для заголовков разделов не добавляем формулы расчета
            if ($isHeaderRow) {
                $sheet->setCellValue('D' . $row, isset($material['quantity']) ? $material['quantity'] : '');
                $sheet->setCellValue('E' . $row, isset($material['price']) ? $material['price'] : '');
                $sheet->setCellValue('F' . $row, ''); // Не вставляем формулу для заголовков
                $sheet->setCellValue('G' . $row, isset($material['markup']) ? $material['markup'] : '');
                $sheet->setCellValue('H' . $row, isset($material['discount']) ? $material['discount'] : '');
                $sheet->setCellValue('I' . $row, ''); // Не вставляем формулу для заголовков
                $sheet->setCellValue('J' . $row, ''); // Не вставляем формулу для заголовков
            } else {
                // Для обычных строк с материалами добавляем формулы и значения
                $sheet->setCellValue('D' . $row, isset($material['quantity']) ? $material['quantity'] : '');
                $sheet->setCellValue('E' . $row, isset($material['price']) ? $material['price'] : '');
                
                // Стоимость - проверяем, есть ли уже значение или нужно вычислить
                if (isset($material['cost']) && is_numeric($material['cost']) && $material['cost'] > 0) {
                    $sheet->setCellValue('F' . $row, $material['cost']);
                } else if (is_numeric($material['quantity']) && is_numeric($material['price'])) {
                    $sheet->setCellValue('F' . $row, '=D' . $row . '*E' . $row);
                } else {
                    $sheet->setCellValue('F' . $row, '');
                }
                
                $sheet->setCellValue('G' . $row, isset($material['markup']) ? $material['markup'] : '');
                $sheet->setCellValue('H' . $row, isset($material['discount']) ? $material['discount'] : '0');
                
                // Цена для заказчика - проверяем, есть ли уже значение или нужно вычислить
                if (isset($material['client_price']) && is_numeric($material['client_price']) && $material['client_price'] > 0) {
                    $sheet->setCellValue('I' . $row, $material['client_price']);
                } else if (is_numeric($material['price']) && isset($material['markup']) && is_numeric($material['markup'])) {
                    $sheet->setCellValue('I' . $row, '=E' . $row . '*(1+G' . $row . '/100)*(1-H' . $row . '/100)');
                } else {
                    $sheet->setCellValue('I' . $row, '');
                }
                
                // Стоимость для заказчика - проверяем, есть ли уже значение или нужно вычислить
                if (isset($material['client_cost']) && is_numeric($material['client_cost']) && $material['client_cost'] > 0) {
                    $sheet->setCellValue('J' . $row, $material['client_cost']);
                } else if (is_numeric($material['quantity']) && 
                          ((isset($material['client_price']) && is_numeric($material['client_price'])) || 
                          (is_numeric($material['price']) && isset($material['markup']) && is_numeric($material['markup'])))) {
                    $sheet->setCellValue('J' . $row, '=D' . $row . '*I' . $row);
                } else {
                    $sheet->setCellValue('J' . $row, '');
                }
            }
            
            $row++;
        }
        
        // Добавляем итоговую строку с использованием SUMIF для игнорирования нечисловых значений
        $sheet->setCellValue('B' . $row, 'ИТОГО:');
        
        // Используем SUMPRODUCT для суммирования только числовых значений
        $sheet->setCellValue('F' . $row, '=SUMPRODUCT(ISNUMBER(F' . $startRow . ':F' . ($row - 1) . ')*F' . $startRow . ':F' . ($row - 1) . ')');
        $sheet->setCellValue('J' . $row, '=SUMPRODUCT(ISNUMBER(J' . $startRow . ':J' . ($row - 1) . ')*J' . $startRow . ':J' . ($row - 1) . ')');
    }

    /**
     * Применяет форматирование к листу (как в основной смете)
     * @param $sheet
     * @param int $lastRow
     */
    private function applySheetFormatting($sheet, $lastRow)
    {
        // Форматирование заголовка (строка 1)
        $sheet->getStyle('A1:J1')->getFont()->setBold(true)->setSize(16);
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Форматирование информационных строк (2-4)
        $sheet->getStyle('A2:A4')->getFont()->setBold(true);

        // Форматирование заголовков таблицы (строка 5)
        $sheet->getStyle('A5:J5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6E6FA'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Форматирование данных таблицы
        $sheet->getStyle('A6:J' . ($lastRow - 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Форматирование итоговой строки
        $sheet->getStyle('A' . $lastRow . ':J' . $lastRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F8FF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                ],
            ],
        ]);

        // Устанавливаем ширину столбцов (как в основной смете)
        $sheet->getColumnDimension('A')->setWidth(8);     // №
        $sheet->getColumnDimension('B')->setWidth(50);    // Наименование
        $sheet->getColumnDimension('C')->setWidth(12);    // Ед. изм.
        $sheet->getColumnDimension('D')->setWidth(12);    // Кол-во
        $sheet->getColumnDimension('E')->setWidth(15);    // Цена, руб.        $sheet->getColumnDimension('F')->setWidth(18);    // Стоимость, руб.
        $sheet->getColumnDimension('G')->setWidth(15);    // Наценка, %
        $sheet->getColumnDimension('H')->setWidth(15);    // Скидка, %
        $sheet->getColumnDimension('I')->setWidth(20);    // Цена для заказчика
        $sheet->getColumnDimension('J')->setWidth(25);    // Стоимость для заказчика
    }
}