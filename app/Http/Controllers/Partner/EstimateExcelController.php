<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Services\EstimateTemplateService;
use App\Services\MaterialsEstimateTemplateService;
use App\Http\Controllers\Partner\ExcelTemplateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class EstimateExcelController extends Controller
{
    protected $estimateTemplateService;
    protected $materialsTemplateService;
    
    /**
     * Конструктор контроллера
     */
    public function __construct(
        EstimateTemplateService $estimateTemplateService,
        MaterialsEstimateTemplateService $materialsTemplateService
    ) {
        $this->estimateTemplateService = $estimateTemplateService;
        $this->materialsTemplateService = $materialsTemplateService;
    }
      /**
     * Экспортирует смету в файл Excel
     */
    public function export(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        // Проверяем, существует ли файл
        if (!$estimate->file_path || !Storage::disk('public')->exists($estimate->file_path)) {
            // Если файла нет, создаем его
            $this->createInitialExcelFile($estimate);
        }
        
        // В любом случае применяем улучшенное форматирование и исправляем формулы
        $this->enhanceExistingFileFormatting($estimate);
        
        // Получаем путь к файлу
        $filePath = storage_path('app/public/' . $estimate->file_path);
        
        // Формируем имя файла для загрузки
        $fileName = $estimate->file_name ?? ('Смета_' . $estimate->id . '.xlsx');
        
        return response()->download($filePath, $fileName);
    }
    
    /**
     * Получает данные из Excel-файла сметы
     */
    public function getData(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        try {
            $filePath = storage_path('app/public/' . $estimate->file_path);
            
            // Проверка наличия файла
            if (!file_exists($filePath) || !is_file($filePath)) {
                // Если файла нет, создаем его
                $this->createInitialExcelFile($estimate);
                $filePath = storage_path('app/public/' . $estimate->file_path);
                
                if (!file_exists($filePath)) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Не удалось создать файл сметы'
                    ], 404);
                }
            }
            
            // Если запрашивается только структура файла
            if (request()->has('structure')) {
                // Определяем структуру файла
                $structure = $this->getExcelFileStructure($filePath, $estimate->type);
                
                return response()->json([
                    'success' => true,
                    'structure' => $structure
                ]);
            }
            
            // Безопасное чтение файла в бинарном режиме
            $excelData = @file_get_contents($filePath);
            
            if ($excelData === false) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Ошибка при чтении файла сметы'
                ], 500);
            }
            
            // Проверяем, что файл действительно является Excel-файлом
            // Excel файлы начинаются с сигнатуры PK
            if (substr($excelData, 0, 2) !== 'PK') {
                \Log::warning('Файл не является валидным Excel-файлом: ' . $filePath);
                
                // Пытаемся пересоздать файл
                $this->createInitialExcelFile($estimate);
                $filePath = storage_path('app/public/' . $estimate->file_path);
                $excelData = @file_get_contents($filePath);
                
                if ($excelData === false || substr($excelData, 0, 2) !== 'PK') {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Файл не является валидным Excel-документом'
                    ], 500);
                }
            }
            
            // Если файл слишком большой для обработки в браузере
            if (strlen($excelData) > 10 * 1024 * 1024) { // более 10МБ
                return response()->json([
                    'success' => false, 
                    'message' => 'Файл слишком большой для отображения в браузере'
                ], 413); // 413 - Payload Too Large
            }
            
            // Кодируем данные в base64 для передачи через JSON
            $base64Data = base64_encode($excelData);
            
            // Дополнительно определяем структуру для первичной инициализации
            $structure = $this->getExcelFileStructure($filePath, $estimate->type);
            
            return response()->json([
                'success' => true, 
                'data' => $base64Data,
                'structure' => $structure
            ]);
        } 
        catch (\Exception $e) {
            \Log::error('Ошибка при получении данных Excel: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false, 
                'message' => 'Произошла ошибка: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Сохраняет данные Excel из редактора
     */
    public function saveExcelData(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);
        
        // Более подробная проверка данных с логированием
        if (!$request->has('excel_data')) {
            \Log::warning('Запрос не содержит поля excel_data');
            return response()->json([
                'success' => false,
                'message' => 'Данные Excel не предоставлены (поле отсутствует)'
            ], 422);
        }
        
        if (empty($request->excel_data)) {
            \Log::warning('Поле excel_data пустое');
            return response()->json([
                'success' => false,
                'message' => 'Данные Excel не предоставлены (поле пустое)'
            ], 422);
        }
        
        \Log::info('Получены Excel данные размером: ' . strlen($request->excel_data));
        
        try {
            // Декодируем данные из base64
            $base64Data = $request->excel_data;
            $binaryData = base64_decode($base64Data, true);
            
            if ($binaryData === false) {
                \Log::warning('Некорректное base64 кодирование данных');
                return response()->json([
                    'success' => false,
                    'message' => 'Некорректный формат данных Base64'
                ], 422);
            }
            
            // Проверяем минимальный размер файла Excel
            if (strlen($binaryData) < 100) {
                \Log::warning('Слишком маленький размер данных: ' . strlen($binaryData) . ' байт');
                return response()->json([
                    'success' => false,
                    'message' => 'Недостаточный размер данных, возможно файл поврежден'
                ], 422);
            }
            
            // Дополнительная проверка валидности xlsx данных
            // Проверяем, что файл действительно является xlsx (начинается с PK)
            if (substr($binaryData, 0, 2) !== 'PK') {
                \Log::warning('Данные не соответствуют формату XLSX (отсутствует сигнатура PK)');
                return response()->json([
                    'success' => false,
                    'message' => 'Некорректный формат XLSX файла'
                ], 422);
            }
            
            // Определяем путь для сохранения файла
            if (!$estimate->file_path) {
                $filePath = "estimates/" . ($estimate->project_id ?? 'no_project') . "/{$estimate->id}.xlsx";
                Storage::disk('public')->makeDirectory("estimates/" . ($estimate->project_id ?? 'no_project'));
            } else {
                $filePath = $estimate->file_path;
            }
            
            // Создаем директорию, если она не существует
            $dir = dirname(storage_path('app/public/' . $filePath));
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Сохраняем файл
            Storage::disk('public')->put($filePath, $binaryData);
            
            // Обновляем информацию о файле
            $fileSize = Storage::disk('public')->size($filePath);
            $fileName = $estimate->file_name ?: 'Смета_' . $estimate->id . '.xlsx';
            $now = now();
            
            $estimate->update([
                'file_path' => $filePath,
                'file_updated_at' => $now,
                'file_size' => $fileSize,
                'file_name' => $fileName
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Файл успешно сохранен',
                'updated_at' => $now->format('d.m.Y H:i'),
                'filesize' => $fileSize
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка при сохранении Excel-данных: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сохранении файла: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загружает файл Excel для сметы
     */
    public function upload(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);
        
        // Валидация входных данных
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB максимум
        ]);
        
        try {
            // Получаем загружаемый файл
            $file = $request->file('file');
            
            // Проверяем, что файл действительно является Excel
            if (!in_array($file->getClientMimeType(), ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
                return back()->with('error', 'Файл должен быть в формате Excel (.xlsx или .xls)');
            }
            
            // Определяем путь для сохранения файла
            $filePath = "estimates/" . ($estimate->project_id ?? 'no_project') . "/{$estimate->id}.xlsx";
            
            // Создаем директорию, если она не существует
            Storage::disk('public')->makeDirectory("estimates/" . ($estimate->project_id ?? 'no_project'));
            
            // Сохраняем файл с оригинальным названием
            $fileName = $file->getClientOriginalName();
            $file->storeAs('public/' . dirname($filePath), basename($filePath));
            
            // Обновляем информацию о файле
            $fileSize = Storage::disk('public')->size($filePath);
            $estimate->update([
                'file_path' => $filePath,
                'file_updated_at' => now(),
                'file_size' => $fileSize,
                'file_name' => $fileName
            ]);
            
            // Улучшаем форматирование файла
            $this->enhanceExistingFileFormatting($estimate);
            
            return back()->with('success', 'Файл успешно загружен и обработан');
        } catch (\Exception $e) {
            \Log::error('Ошибка при загрузке Excel файла: ' . $e->getMessage());
            return back()->with('error', 'Ошибка при загрузке файла: ' . $e->getMessage());
        }
    }

    /**
     * Определяет структуру Excel-файла
     * @param string $filePath Путь к файлу
     * @param string $estimateType Тип сметы
     * @return array Структура файла
     */
    protected function getExcelFileStructure($filePath, $estimateType)
    {
        try {
            // Загружаем файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            
            // Определяем количество колонок
            $highestColumn = $sheet->getHighestColumn();
            $columnCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            // Определяем защищенные колонки (формулы) на основе типа сметы
            $readOnlyColumns = [];
            
            // Стандартный набор для всех типов смет
            switch ($estimateType) {
                case 'main':
                    // Стоимость и цены для заказчика (с формулами)
                    $readOnlyColumns = [5, 8, 9];
                    break;
                    
                case 'materials':
                    // Материалы могут иметь другие столбцы с формулами
                    $readOnlyColumns = [6, 9, 10];
                    break;
                    
                case 'additional':
                    // Дополнительная смета
                    $readOnlyColumns = [5, 8, 9];
                    break;
                    
                default:
                    // По умолчанию
                    $readOnlyColumns = [];
                    
                    // Ищем столбцы с формулами, анализируя строки данных
                    $rowCount = min($sheet->getHighestRow(), 20); // Анализируем до 20 строк
                    
                    for ($col = 1; $col <= $columnCount; $col++) {
                        $hasFormulas = false;
                          for ($row = 6; $row <= $rowCount; $row++) {
                            $cellCoordinate = Coordinate::stringFromColumnIndex($col) . $row;
                            $cell = $sheet->getCell($cellCoordinate);
                            if ($cell->isFormula()) {
                                $hasFormulas = true;
                                break;
                            }
                        }
                        
                        if ($hasFormulas) {
                            $readOnlyColumns[] = $col - 1; // Колонки в JS начинаются с 0
                        }
                    }
            }
            
            // Определяем ширины колонок
            $columnWidths = [];
            for ($i = 1; $i <= $columnCount; $i++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $columnWidths[$i-1] = $sheet->getColumnDimension($columnLetter)->getWidth() * 7.5; // Примерный перевод из единиц Excel в пиксели
            }
            
            return [
                'columnCount' => $columnCount,
                'readOnlyColumns' => $readOnlyColumns,
                'hasHeaders' => true, // Предполагаем, что в файле есть заголовки
                'columnWidths' => $columnWidths
            ];
        } catch (\Exception $e) {
            \Log::error('Ошибка при определении структуры Excel-файла: ' . $e->getMessage());
            
            // Возвращаем стандартную структуру в случае ошибки
            return [
                'columnCount' => 10,
                'readOnlyColumns' => [5, 8, 9],
                'hasHeaders' => true
            ];
        }
    }

    /**
     * Создает исходный Excel файл для сметы с заданной структурой
     */
    public function createInitialExcelFile(Estimate $estimate)
    {
        // Определяем тип сметы и соответствующий шаблон
        $type = $estimate->type;
        
        // Получаем путь к файлу шаблона
        $templatePath = ExcelTemplateController::getEstimateTemplatePath($type);
        
        // Проверяем существование шаблона
        if (!file_exists($templatePath)) {
            // Если шаблон не найден, создаем директорию для шаблонов
            $templateDir = storage_path('app/templates/estimates');
            if (!File::isDirectory($templateDir)) {
                File::makeDirectory($templateDir, 0755, true);
            }
            
            // Создаем базовый шаблон с помощью PhpSpreadsheet и сохраняем его
            $this->createDefaultTemplate($type, $templatePath);
        }
        
        // Создаем директорию для сохранения файла сметы
        $directory = 'estimates/' . ($estimate->project_id ?? 'no_project');
        Storage::disk('public')->makeDirectory($directory);
        
        // Путь к новому файлу сметы
        $filePath = $directory . '/' . $estimate->id . '.xlsx';
        $fullPath = storage_path('app/public/' . $filePath);
        
        // Копируем шаблон в директорию смет пользователя
        copy($templatePath, $fullPath);
        
        // Загружаем файл для обновления метаданных
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Обновляем информацию о документе с учетом типа сметы
        $sheet->setCellValue('B2', $estimate->project ? $estimate->project->address : 'Не указан');
        $sheet->setCellValue('B3', $estimate->project ? $estimate->project->client_name : 'Не указан');
        $sheet->setCellValue('B4', Carbon::now()->format('d.m.Y'));
        
        // Сохраняем файл с метаданными
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($fullPath);
        
        // Обновляем информацию о файле в базе данных
        $fileSize = filesize($fullPath);
        $fileName = '';
        
        // Формируем название файла в зависимости от типа сметы
        switch ($estimate->type) {
            case 'main':
                $fileName = 'Работы_Смета_производства_работ_2025.xlsx';
                break;
            case 'additional':
                $fileName = 'Дополнительная_смета_' . $estimate->id . '.xlsx';
                break;
            case 'materials':
                $fileName = 'Материалы_Черновые_материалы_2025.xlsx';
                break;
            default:
                $fileName = 'Смета_' . $estimate->id . '.xlsx';
        }
        
        $estimate->update([
            'file_path' => $filePath,
            'file_updated_at' => now(),
            'file_size' => $fileSize,
            'file_name' => $fileName
        ]);

        return true;
    }

    /**
     * Создает и сохраняет базовый шаблон сметы
     * @param string $type Тип сметы
     * @param string $savePath Путь для сохранения файла
     */    protected function createDefaultTemplate($type, $savePath)
    {
        // Для материальных смет используем специальный сервис
        if ($type === 'materials') {
            return $this->materialsTemplateService->createTemplate($savePath);
        }
        
        // Для остальных типов используем основной сервис
        return $this->estimateTemplateService->createDefaultTemplate($type, $savePath);
    }    /**
     * Улучшает форматирование существующего файла Excel перед экспортом
     */
    protected function enhanceExistingFileFormatting(Estimate $estimate)
    {
        try {
            $filePath = storage_path('app/public/' . $estimate->file_path);
            
            // Загружаем существующий файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            
            // Получаем все листы и форматируем каждый из них
            $sheetCount = $spreadsheet->getSheetCount();
            
            for ($i = 0; $i < $sheetCount; $i++) {
                // Переключаемся на текущий лист
                $sheet = $spreadsheet->getSheet($i);
                
                // Проверяем и корректируем формулы для текущего листа
                $this->enhanceSheetFormatting($sheet);
            }
            
            // Применяем красивый синий стиль ко всему документу
            $this->applyBlueTheme($spreadsheet);
            
            // Возвращаемся к первому листу
            $spreadsheet->setActiveSheetIndex(0);
            
            // Сохраняем файл с улучшенным форматированием, сохраняя формулы
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save($filePath);
            
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при улучшении форматирования Excel: ' . $e->getMessage());
            return false;
        }
    }    /**
     * Улучшает форматирование и формулы для конкретного листа
     */
    protected function enhanceSheetFormatting($sheet)
    {
        try {
            // Проверяем и корректируем формулы в файле
            $lastRow = $sheet->getHighestRow();
            $headerRow = 5; // Строка с заголовками
            $startDataRow = $headerRow + 1;
            $totalRow = null; // Индекс итоговой строки
            $hasDataRows = false; // Флаг наличия строк с данными (не заголовки)
            $dataRows = []; // Массив для хранения индексов строк с данными
            
            // Массив для хранения данных итоговой строки, если найдем ее не в конце таблицы
            $totalRowData = [];
            
            // Сначала проходим по всей таблице и собираем информацию
            for ($row = $startDataRow; $row <= $lastRow; $row++) {
                $valueB = $sheet->getCell('B' . $row)->getValue();
                $valueC = $sheet->getCell('C' . $row)->getValue();
                $valueD = $sheet->getCell('D' . $row)->getValue();
                
                // Проверяем, является ли это строкой ИТОГО
                if (is_string($valueB) && stripos($valueB, 'ИТОГО') !== false) {
                    $totalRow = $row;
                    
                    // Сохраняем данные итоговой строки
                    $totalRowData['B'] = $valueB; // Сохраняем текст "ИТОГО..."
                      // Сохраняем все значения из строки ИТОГО
                    $highestColumn = $sheet->getHighestColumn();
                    $lastColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                    
                    for ($colIndex = 1; $colIndex <= $lastColIndex; $colIndex++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                        $totalRowData[$colLetter] = $sheet->getCell($colLetter . $row)->getValue();
                    }
                } 
                // Если это не строка ИТОГО, проверяем есть ли данные
                else {
                    // Если есть значение в колонке C или D, считаем строку строкой данных
                    if (!empty($valueC) || !empty($valueD)) {
                        $hasDataRows = true;
                        $dataRows[] = $row;
                    }
                }
            }
            
            // Удаляем строку ИТОГО, если она есть не в самом конце
            if ($totalRow !== null && $totalRow < $lastRow) {
                $sheet->removeRow($totalRow);
                $lastRow--; // Уменьшаем общее количество строк
                $totalRow = null; // Сбрасываем, так как удалили строку
            }
            
            // Если не нашли строку ИТОГО, но есть данные, добавляем ее в конец
            if ($totalRow === null && $hasDataRows) {
                $lastRow++;
                $sheet->setCellValue('B' . $lastRow, 'ИТОГО:');
                $totalRow = $lastRow;
            } 
            // Если строка ИТОГО была удалена, добавляем ее в конец с сохраненными данными
            else if ($totalRow === null && !empty($totalRowData)) {
                $lastRow++;
                foreach ($totalRowData as $col => $value) {
                    $sheet->setCellValue($col . $lastRow, $value);
                }
                $totalRow = $lastRow;
            }
              // Последняя строка данных (перед ИТОГО)
            $lastDataRow = max($startDataRow, $totalRow - 1); // Учитываем случай, если строка ИТОГО - единственная после заголовка
              // Если нашли итоговую строку, устанавливаем правильные формулы для итогов
            if ($totalRow) {
                // Очищаем ячейки, где не нужны итоговые значения
                $sheet->setCellValue('D' . $totalRow, '');  // Количество - не суммируем
                $sheet->setCellValue('E' . $totalRow, '');  // Цена - не суммируем
                
                // Создаем массив строк с числовыми данными для суммирования
                $rowsToSum = [];
                for ($row = $startDataRow; $row < $totalRow; $row++) {
                    $valueB = $sheet->getCell('B' . $row)->getValue();
                    $valueC = $sheet->getCell('C' . $row)->getValue();
                    $valueD = $sheet->getCell('D' . $row)->getValue();
                    
                    // Проверяем, является ли строка заголовком раздела
                    $isHeader = is_string($valueB) && (
                        stripos($valueB, 'раздел') !== false || 
                        stripos($valueB, '.') === 0 ||
                        preg_match('/^\d+\./', $valueB)
                    );
                    
                    // Если это не заголовок и есть данные в колонках C или D, добавляем строку для суммирования
                    if (!$isHeader && (!empty($valueC) || !empty($valueD))) {
                        $rowsToSum[] = $row;
                    }
                }
                
                // Если есть строки для суммирования, создаем формулы СУММ
                if (count($rowsToSum) > 0) {
                    // Более надежная формула суммирования для стоимости с проверкой на числа
                    $sumFormulaParts = [];
                    foreach ($rowsToSum as $rowNum) {
                        $sumFormulaParts[] = "IF(ISNUMBER(F{$rowNum}),F{$rowNum},0)";
                    }
                    $sumFormulaF = "=SUM(" . implode(",", $sumFormulaParts) . ")";
                    $sheet->setCellValue('F' . $totalRow, $sumFormulaF);
                    
                    // Аналогично для стоимости заказчика
                    $sumFormulaPartsJ = [];
                    foreach ($rowsToSum as $rowNum) {
                        $sumFormulaPartsJ[] = "IF(ISNUMBER(J{$rowNum}),J{$rowNum},0)";
                    }
                    $sumFormulaJ = "=SUM(" . implode(",", $sumFormulaPartsJ) . ")";
                    $sheet->setCellValue('J' . $totalRow, $sumFormulaJ);
                } else {
                    // Если нет строк для суммирования, просто используем стандартные формулы
                    $sumRangeF = "F{$startDataRow}:F" . ($totalRow - 1);
                    $sheet->setCellValue('F' . $totalRow, "=SUMIF({$sumRangeF},\">0\")");
                    
                    $sumRangeJ = "J{$startDataRow}:J" . ($totalRow - 1);
                    $sheet->setCellValue('J' . $totalRow, "=SUMIF({$sumRangeJ},\">0\")");
                }
                
                // Очищаем ячейки для остальных колонок
                $sheet->setCellValue('G' . $totalRow, '');  // Наценка - не суммируем
                $sheet->setCellValue('H' . $totalRow, '');  // Скидка - не суммируем
                $sheet->setCellValue('I' . $totalRow, '');  // Цена для заказчика - не суммируем
                
                // Выделяем итоговую строку жирным шрифтом
                $sheet->getStyle("A{$totalRow}:J{$totalRow}")->getFont()->setBold(true);
                
                // Устанавливаем числовой формат для итогов
                $sheet->getStyle("F{$totalRow}")->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet->getStyle("J{$totalRow}")->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }
              // Для всех данных проверяем и устанавливаем корректные формулы
            if ($totalRow) {
                for ($row = $startDataRow; $row < $totalRow; $row++) {
                    // Проверяем, есть ли данные в этой строке и не является ли она заголовком раздела
                    $valueB = $sheet->getCell('B' . $row)->getValue();
                    $valueC = $sheet->getCell('C' . $row)->getValue();
                    $valueD = $sheet->getCell('D' . $row)->getValue();
                    
                    // Проверяем, является ли строка заголовком раздела
                    $isHeader = is_string($valueB) && (
                        stripos($valueB, 'раздел') !== false || 
                        stripos($valueB, '.') === 0 ||
                        preg_match('/^\d+\./', $valueB)
                    );
                    
                    // Если это не заголовок и есть данные
                    $hasData = !$isHeader && (!empty($valueC) || !empty($valueD)) && !empty($valueB);
                    
                    if ($hasData) {
                        // Убедимся, что у нас числовые значения для количества и цены
                        // Если в ячейках не числа, попробуем преобразовать их
                        $qty = is_numeric($valueD) ? $valueD : 0;
                        $price = is_numeric($sheet->getCell('E' . $row)->getValue()) ? 
                            $sheet->getCell('E' . $row)->getValue() : 0;
                            
                        // Установим значения явно, чтобы гарантировать числовой тип
                        $sheet->setCellValue('D' . $row, $qty);
                        $sheet->setCellValue('E' . $row, $price);
                        
                        // Применяем форматы для числовых значений
                        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        
                        // Устанавливаем формулу для стоимости с проверкой на числа и нули
                        $sheet->setCellValue('F' . $row, "=IF(AND(ISNUMBER(D{$row}),ISNUMBER(E{$row}),OR(D{$row}<>0,E{$row}<>0)),D{$row}*E{$row},0)");
                        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        
                        // Получаем значения наценки и скидки
                        $markup = is_numeric($sheet->getCell('G' . $row)->getValue()) ? 
                            $sheet->getCell('G' . $row)->getValue() : 0;
                        $discount = is_numeric($sheet->getCell('H' . $row)->getValue()) ? 
                            $sheet->getCell('H' . $row)->getValue() : 0;
                            
                        // Устанавливаем значения явно
                        $sheet->setCellValue('G' . $row, $markup);
                        $sheet->setCellValue('H' . $row, $discount);
                        
                        // Устанавливаем формат для наценки и скидки
                        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        
                        // Устанавливаем формулу для цены для заказчика с дополнительными проверками
                        $sheet->setCellValue('I' . $row, "=IF(AND(ISNUMBER(E{$row}),ISNUMBER(G{$row}),ISNUMBER(H{$row})),E{$row}*(1+IF(G{$row}>0,G{$row}/100,0))*(1-IF(H{$row}>0,H{$row}/100,0)),E{$row})");
                        $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        
                        // Устанавливаем формулу для стоимости для заказчика с дополнительными проверками
                        $sheet->setCellValue('J' . $row, "=IF(AND(ISNUMBER(D{$row}),ISNUMBER(I{$row}),OR(D{$row}<>0,I{$row}<>0)),D{$row}*I{$row},0)");
                        $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    }
                }
            }
            
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при улучшении форматирования листа: ' . $e->getMessage());
            return false;
        }
    }

    /**     * Экспорт сметы в формате Excel
     *
     * @param Estimate $estimate
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportOld(Estimate $estimate)
    {
        // Проверка доступа
        $this->authorize('view', $estimate);

        try {
            // Получаем путь к файлу
            $filePath = storage_path('app/estimates/' . $estimate->id . '/excel.xlsx');

            // Проверка существования файла
            if (!Storage::disk('local')->exists('estimates/' . $estimate->id . '/excel.xlsx')) {
                return back()->with('error', 'Файл сметы не найден.');
            }

            // Создаем копию файла с правильными формулами
            $tempFilePath = storage_path('app/temp/' . uniqid('estimate_') . '.xlsx');
            
            // Убедимся, что директория существует
            if (!file_exists(dirname($tempFilePath))) {
                mkdir(dirname($tempFilePath), 0755, true);
            }
            
            // Загружаем исходный файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            
            // Проходим по всем листам
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Проходим по всем ячейкам и фиксируем формулы
                for ($row = 6; $row <= $highestRow; $row++) {
                    for ($col = 'F'; $col <= 'J'; $col++) {
                        $cellCoordinate = $col . $row;
                        $cellValue = $sheet->getCell($cellCoordinate)->getValue();
                        
                        // Если значение начинается с '=', это формула
                        if (is_string($cellValue) && strpos($cellValue, '=') === 0) {
                            // Устанавливаем значение как формулу
                            $sheet->getCell($cellCoordinate)->setValueExplicit(
                                $cellValue,
                                DataType::TYPE_FORMULA
                            );
                            
                            // Устанавливаем числовой формат для формул
                            $sheet->getStyle($cellCoordinate)->getNumberFormat()
                                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        } 
                        // Если это число, убедимся что оно обрабатывается как число
                        elseif (is_numeric($cellValue)) {
                            $sheet->getCell($cellCoordinate)->setValueExplicit(
                                $cellValue,
                                DataType::TYPE_NUMERIC
                            );
                            
                            // Устанавливаем числовой формат
                            $sheet->getStyle($cellCoordinate)->getNumberFormat()
                                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        }
                    }
                }
                
                // Убедимся, что итоговые строки имеют правильные формулы
                for ($row = 6; $row <= $highestRow; $row++) {
                    // Если это итоговая строка (имеет "ИТОГО" в колонке B)
                    $cellValue = $sheet->getCell('B' . $row)->getValue();
                    if (is_string($cellValue) && strpos($cellValue, 'ИТОГО') !== false) {
                        // Диапазон для суммирования (от начала до текущей строки)
                        $startRow = 6;
                        $endRow = $row - 1;
                        
                        // Формула для колонки F (Стоимость)
                        $sheet->setCellValue(
                            'F' . $row, 
                            "=SUM(F$startRow:F$endRow)"
                        );
                        
                        // Формула для колонки J (Стоимость для заказчика)
                        $sheet->setCellValue(
                            'J' . $row, 
                            "=SUM(J$startRow:J$endRow)"
                        );
                        
                        // Устанавливаем числовой формат для итоговых сумм
                        $sheet->getStyle('F' . $row)->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        $sheet->getStyle('J' . $row)->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    }
                }
                
                // Убедимся, что формулы в обычных строках правильные
                for ($row = 6; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('B' . $row)->getValue();
                    // Пропускаем строки заголовков разделов и итоговую строку
                    if (!is_string($cellValue) || strpos($cellValue, 'ИТОГО') !== false || 
                        $sheet->getCell('C' . $row)->getValue() === '') {
                        continue;
                    }
                    
                    // Формула для колонки F (Стоимость = Количество * Цена)
                    $sheet->setCellValue(
                        'F' . $row, 
                        "=D$row*E$row"
                    );
                    
                    // Формула для колонки I (Цена для заказчика)
                    $sheet->setCellValue(
                        'I' . $row, 
                        "=E$row*(1+G$row/100)*(1-H$row/100)"
                    );
                    
                    // Формула для колонки J (Стоимость для заказчика)
                    $sheet->setCellValue(
                        'J' . $row, 
                        "=D$row*I$row"
                    );
                    
                    // Устанавливаем числовой формат
                    $sheet->getStyle($row)->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                }
            }
            
            // Сохраняем файл с правильными формулами
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false); // Важно! Не пересчитываем формулы
            $writer->save($tempFilePath);
            
            // Возвращаем файл для скачивания
            $fileName = $estimate->name . '.xlsx';
            $fileName = preg_replace('/[^a-zA-Zа-яА-Я0-9_\- ]/u', '', $fileName);
            $fileName = str_replace(' ', '_', $fileName);
            
            return response()->download($tempFilePath, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при экспорте Excel файла: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'exception' => $e
            ]);
            
            return back()->with('error', 'Произошла ошибка при экспорте: ' . $e->getMessage());
        }
    }    /**
     * Экспортирует смету в файл PDF
     */
    public function exportPdf(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        // Проверяем, существует ли файл
        if (!$estimate->file_path || !Storage::disk('public')->exists($estimate->file_path)) {
            // Если файла нет, создаем его
            $this->createInitialExcelFile($estimate);
        }
        
        // В любом случае применяем улучшенное форматирование и исправляем формулы
        $this->enhanceExistingFileFormatting($estimate);
        
        try {
            // Получаем путь к файлу Excel
            $excelFilePath = storage_path('app/public/' . $estimate->file_path);
            
            // Загружаем данные из Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFilePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray(null, true, true, true);
              // Задаем стили для PDF
            $styles = '
                <style>
                    body { font-family: "sans-serif", "DejaVu Sans", Arial, sans-serif; font-size: 10pt; line-height: 1.3; }
                    h1 { text-align: center; color: #2F75B5; margin-bottom: 20px; font-size: 16pt; }
                    .estimate-info { margin-bottom: 15px; }
                    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
                    table, th, td { border: 1px solid #ddd; }
                    th { background-color: #2F75B5; color: white; padding: 10px; text-align: center; }
                    td { padding: 8px; text-align: left; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .bold { font-weight: bold; }
                    .section-header { background-color: #366092; color: white; font-weight: bold; }
                    .total-row { background-color: #BDD7EE; font-weight: bold; }
                </style>
            ';
              // Формируем HTML для PDF с UTF-8 кодировкой
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <link href="' . public_path('css/pdf-fonts.css') . '" rel="stylesheet" type="text/css" />
                ' . $styles . '
                <title>' . $estimate->name . '</title>
            </head>
            <body>
                <h1>' . $estimate->name . '</h1>
                <div class="estimate-info">
                    <p><strong>Дата:</strong> ' . now()->format('d.m.Y') . '</p>
                    ' . ($estimate->project ? '<p><strong>Объект:</strong> ' . $estimate->project->address . '</p>' : '') . '
                </div>
                <table>';
            
            // Определим первую строку как заголовок
            $isFirstRow = true;
            
            foreach ($data as $rowIndex => $row) {
                // Пропускаем пустые строки
                $isEmpty = true;
                foreach ($row as $cell) {
                    if (!empty($cell)) {
                        $isEmpty = false;
                        break;
                    }
                }
                if ($isEmpty) continue;
                
                // Определяем стиль строки
                $rowClass = '';
                $cellB = isset($row['B']) ? $row['B'] : '';
                
                if (is_string($cellB)) {
                    if (mb_stripos($cellB, 'ИТОГО') !== false) {
                        $rowClass = 'class="total-row"';
                    } elseif (mb_stripos($cellB, 'раздел') !== false || mb_stripos($cellB, '.') === 0 || preg_match('/^\d+\./', $cellB)) {
                        $rowClass = 'class="section-header"';
                    }
                }
                
                if ($isFirstRow) {
                    $html .= '<tr>';
                    foreach ($row as $cell) {
                        $html .= '<th>' . htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') . '</th>';
                    }
                    $html .= '</tr>';
                    $isFirstRow = false;
                } else {
                    $html .= '<tr ' . $rowClass . '>';
                    foreach ($row as $colIndex => $cell) {
                        // Выравнивание для числовых колонок вправо
                        $class = '';
                        if (in_array($colIndex, ['D', 'E', 'F', 'G', 'H', 'I', 'J']) && is_numeric($cell)) {
                            $class = ' class="text-right"';
                        }
                        
                        $html .= '<td' . $class . '>' . htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') . '</td>';
                    }
                    $html .= '</tr>';
                }
            }
            
            $html .= '</table></body></html>';
              // Создаем PDF с помощью Dompdf с указанием UTF-8 кодировки
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'landscape');
            
            // Настраиваем параметры для корректного отображения русских символов
            $pdf->getDomPDF()->set_option('defaultFont', 'sans-serif');
            $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
            $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
            $pdf->getDomPDF()->set_option('isFontSubsettingEnabled', true);
            $pdf->getDomPDF()->set_option('unicode_enabled', true);
            
            // Формируем имя файла для загрузки
            $fileName = $estimate->file_name ?? ('Смета_' . $estimate->id . '.pdf');
            $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
            
            return $pdf->download($fileName);
            
        } catch (\Exception $e) {
            Log::error('Ошибка при экспорте сметы в PDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Не удалось экспортировать смету в PDF: ' . $e->getMessage());
        }
    }

    /**
     * Применяет синий стиль к смете для улучшения ее внешнего вида
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @return void
     */
    protected function applyBlueTheme($spreadsheet)
    {
        try {
            // Применяем синий стиль ко всем листам
            $sheetCount = $spreadsheet->getSheetCount();
            
            for ($sheetIndex = 0; $sheetIndex < $sheetCount; $sheetIndex++) {
                $sheet = $spreadsheet->getSheet($sheetIndex);
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();
                
                // Заголовок таблицы (строка 5) - синий фон с белым текстом
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => '2F75B5'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ];
                
                $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray($headerStyle);
                $sheet->getRowDimension(5)->setRowHeight(30); // Увеличиваем высоту заголовка
                
                // Строка с наименованием объекта
                $titleStyle = [
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => '2F75B5'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    ],
                ];
                $sheet->getStyle('A1:B1')->applyFromArray($titleStyle);
                
                // Стиль для основной таблицы - светло-синие полосы чередуются
                for ($row = 6; $row <= $lastRow; $row++) {
                    // Проверяем, является ли строка заголовком раздела или ИТОГО
                    $value = $sheet->getCell("B{$row}")->getValue();
                    $isHeader = is_string($value) && (
                        stripos($value, 'раздел') !== false || 
                        stripos($value, '.') === 0 ||
                        preg_match('/^\d+\./', $value)
                    );
                    
                    $isTotalRow = is_string($value) && stripos($value, 'ИТОГО') !== false;
                    
                    if ($isHeader) {
                        // Заголовки разделов - темно-синий фон с белым текстом
                        $sectionHeaderStyle = [
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => 'FFFFFF'],
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'color' => ['rgb' => '366092'],
                            ],
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                            ],
                        ];
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray($sectionHeaderStyle);
                        
                    } elseif ($isTotalRow) {
                        // Итоговая строка - синий фон с жирным черным текстом
                        $totalRowStyle = [
                            'font' => [
                                'bold' => true,
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'color' => ['rgb' => 'BDD7EE'],
                            ],                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                            ],
                            'borders' => [
                                'top' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                ],
                                'bottom' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE,
                                ],
                            ],
                        ];
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray($totalRowStyle);
                        
                        // Специальные стили для конкретных ячеек в строке итогов
                        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                        $sheet->getStyle("J{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                        
                    } else {
                        // Чередующиеся полосы для обычных строк данных
                        if ($row % 2 == 0) {
                            $rowStyle = [
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'color' => ['rgb' => 'DDEBF7'],
                                ],
                            ];
                            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray($rowStyle);
                        }
                        
                        // Применяем стили для числовых данных
                        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                        $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                        $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                        $sheet->getStyle("J{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                }
                
                // Выравнивание для всей таблицы
                $sheet->getStyle("A6:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                // Устанавливаем оптимальную ширину столбцов
                $sheet->getColumnDimension('A')->setWidth(5); // №
                $sheet->getColumnDimension('B')->setWidth(40); // Позиция
                $sheet->getColumnDimension('C')->setWidth(10); // Ед. изм.
                $sheet->getColumnDimension('D')->setWidth(12); // Кол-во
                $sheet->getColumnDimension('E')->setWidth(12); // Цена
                $sheet->getColumnDimension('F')->setWidth(15); // Стоимость
                $sheet->getColumnDimension('G')->setWidth(12); // Наценка
                $sheet->getColumnDimension('H')->setWidth(12); // Скидка
                $sheet->getColumnDimension('I')->setWidth(15); // Цена для заказчика
                $sheet->getColumnDimension('J')->setWidth(18); // Стоимость для заказчика
                
                // Задаем границы для всей таблицы данных
                $tableBorders = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'AAAAAA'],
                        ],
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '366092'],
                        ],
                    ],
                ];
                $sheet->getStyle("A5:{$lastColumn}{$lastRow}")->applyFromArray($tableBorders);
            }
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при применении синего стиля: ' . $e->getMessage());
        }
    }
    
    /**
     * Экспортирует смету в файл Excel для заказчика (без колонок для мастера)
     */
    public function exportClient(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        // Проверяем, существует ли файл
        if (!$estimate->file_path || !Storage::disk('public')->exists($estimate->file_path)) {
            // Если файла нет, создаем его
            $this->createInitialExcelFile($estimate);
        }
        
        // В любом случае применяем улучшенное форматирование
        $this->enhanceExistingFileFormatting($estimate);
        
        try {
            // Загружаем файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/public/' . $estimate->file_path));
            $sheet = $spreadsheet->getActiveSheet();
            
            // Показываем только столбцы A-E и J для заказчика (согласно требованиям)
            $sheet->getColumnDimension('F')->setVisible(false); // Стоимость для мастера
            $sheet->getColumnDimension('G')->setVisible(false); // Наценка, %
            $sheet->getColumnDimension('H')->setVisible(false); // Скидка, %
            $sheet->getColumnDimension('I')->setVisible(false); // Цена для заказчика 
            
            // Переименовываем заголовок J (Стоимость для заказчика)
            $headerRow = 5;
            $sheet->setCellValue('J' . $headerRow, 'Стоимость для заказчика');
            
            // Создаем временный файл
            $tempFilePath = tempnam(sys_get_temp_dir(), 'client_estimate_');
            
            // Сохраняем в файл
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false); // Не пересчитываем формулы
            $writer->save($tempFilePath);
            
            // Возвращаем файл для скачивания
            $fileName = 'Смета_для_заказчика_' . preg_replace('/[^a-zA-Zа-яА-Я0-9_\- ]/u', '', $estimate->name) . '.xlsx';
            $fileName = str_replace(' ', '_', $fileName);
            
            return response()->download($tempFilePath, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при экспорте Excel файла для заказчика: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'exception' => $e
            ]);
            
            return back()->with('error', 'Произошла ошибка при экспорте для заказчика: ' . $e->getMessage());
        }
    }
    
    /**
     * Экспортирует смету в файл Excel для мастера (подрядчика)
     */
    public function exportContractor(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        // Проверяем, существует ли файл
        if (!$estimate->file_path || !Storage::disk('public')->exists($estimate->file_path)) {
            // Если файла нет, создаем его
            $this->createInitialExcelFile($estimate);
        }
        
        // В любом случае применяем улучшенное форматирование
        $this->enhanceExistingFileFormatting($estimate);
        
        try {
            // Загружаем файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/public/' . $estimate->file_path));
            $sheet = $spreadsheet->getActiveSheet();
            
            // Показываем только столбцы A-F для мастера (согласно требованиям)
            $sheet->getColumnDimension('G')->setVisible(false); // Наценка, %
            $sheet->getColumnDimension('H')->setVisible(false); // Скидка, %
            $sheet->getColumnDimension('I')->setVisible(false); // Цена для заказчика
            $sheet->getColumnDimension('J')->setVisible(false); // Стоимость для заказчика
            
            // Переименовываем заголовки для удобства мастера
            $headerRow = 5;
            $sheet->setCellValue('E' . $headerRow, 'Цена, руб.');
            $sheet->setCellValue('F' . $headerRow, 'Стоимость');
            
            // Создаем временный файл
            $tempFilePath = tempnam(sys_get_temp_dir(), 'master_estimate_');
            
            // Сохраняем в файл
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false); // Не пересчитываем формулы
            $writer->save($tempFilePath);
            
            // Возвращаем файл для скачивания
            $fileName = 'Смета_для_мастера_' . preg_replace('/[^a-zA-Zа-яА-Я0-9_\- ]/u', '', $estimate->name) . '.xlsx';
            $fileName = str_replace(' ', '_', $fileName);
            
            return response()->download($tempFilePath, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при экспорте Excel файла для мастера: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'exception' => $e
            ]);
            
            return back()->with('error', 'Произошла ошибка при экспорте для мастера: ' . $e->getMessage());
        }
    }
    
    /**
     * Экспортирует смету в файл PDF для заказчика
     */
    public function exportPdfClient(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        // Проверяем, существует ли файл
        if (!$estimate->file_path || !Storage::disk('public')->exists($estimate->file_path)) {
            // Если файла нет, создаем его
            $this->createInitialExcelFile($estimate);
        }
        
        // В любом случае применяем улучшенное форматирование
        $this->enhanceExistingFileFormatting($estimate);
        
        try {
            // Получаем путь к файлу Excel
            $excelFilePath = storage_path('app/public/' . $estimate->file_path);
            
            // Загружаем данные из Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFilePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray(null, true, true, true);
            
            // Задаем стили для PDF
            $styles = '
                <style>
                    body { font-family: "sans-serif", "DejaVu Sans", Arial, sans-serif; font-size: 10pt; line-height: 1.3; }
                    h1 { text-align: center; color: #2F75B5; margin-bottom: 20px; font-size: 16pt; }
                    .estimate-info { margin-bottom: 15px; }
                    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
                    table, th, td { border: 1px solid #ddd; }
                    th { background-color: #2F75B5; color: white; padding: 10px; text-align: center; }
                    td { padding: 8px; text-align: left; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .bold { font-weight: bold; }
                    .section-header { background-color: #366092; color: white; font-weight: bold; }
                    .total-row { background-color: #BDD7EE; font-weight: bold; }
                </style>
            ';
            
            // Формируем HTML для PDF с UTF-8 кодировкой
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <link href="' . public_path('css/pdf-fonts.css') . '" rel="stylesheet" type="text/css" />
                ' . $styles . '
                <title>Смета для заказчика - ' . $estimate->name . '</title>
            </head>
            <body>
                <h1>Смета для заказчика - ' . $estimate->name . '</h1>
                <div class="estimate-info">
                    <p><strong>Дата:</strong> ' . now()->format('d.m.Y') . '</p>
                    ' . ($estimate->project ? '<p><strong>Объект:</strong> ' . $estimate->project->address . '</p>' : '') . '
                </div>
                <table>';
            
            // Определим первую строку как заголовок
            $isFirstRow = true;
            $headerRow = 5; // Обычно заголовки находятся в строке 5
            
            foreach ($data as $rowIndex => $row) {
                // Пропускаем пустые строки или строки до заголовков
                $isEmpty = true;
                foreach ($row as $cell) {
                    if (!empty($cell)) {
                        $isEmpty = false;
                        break;
                    }
                }
                if ($isEmpty || $rowIndex < $headerRow) continue;
                
                // Определяем стиль строки
                $rowClass = '';
                $cellB = isset($row['B']) ? $row['B'] : '';
                
                if (is_string($cellB)) {
                    if (mb_stripos($cellB, 'ИТОГО') !== false) {
                        $rowClass = 'class="total-row"';
                    } elseif (mb_stripos($cellB, 'раздел') !== false || mb_stripos($cellB, '.') === 0 || preg_match('/^\d+\./', $cellB)) {
                        $rowClass = 'class="section-header"';
                    }
                }
                
                if ($rowIndex == $headerRow) {
                    // Это заголовок таблицы
                    $html .= '<tr>';
                    $html .= '<th>' . (isset($row['A']) ? htmlspecialchars($row['A']) : '№') . '</th>';
                    $html .= '<th>' . (isset($row['B']) ? htmlspecialchars($row['B']) : 'Наименование материалов') . '</th>';
                    $html .= '<th>' . (isset($row['C']) ? htmlspecialchars($row['C']) : 'Ед. изм.') . '</th>';
                    $html .= '<th>' . (isset($row['D']) ? htmlspecialchars($row['D']) : 'Кол-во') . '</th>';
                    $html .= '<th>' . (isset($row['E']) ? htmlspecialchars($row['E']) : 'Цена, руб.') . '</th>';
                    // Показываем только столбец J (Стоимость для заказчика)
                    $html .= '<th>' . (isset($row['J']) ? htmlspecialchars($row['J']) : 'Стоимость для заказчика') . '</th>';
                    $html .= '</tr>';
                } else {
                    $html .= "<tr $rowClass>";
                    $html .= '<td class="text-center">' . (isset($row['A']) ? htmlspecialchars($row['A']) : '') . '</td>';
                    $html .= '<td>' . (isset($row['B']) ? htmlspecialchars($row['B']) : '') . '</td>';
                    $html .= '<td class="text-center">' . (isset($row['C']) ? htmlspecialchars($row['C']) : '') . '</td>';
                    $html .= '<td class="text-center">' . (isset($row['D']) ? htmlspecialchars($row['D']) : '') . '</td>';
                    $html .= '<td class="text-right">' . (isset($row['E']) ? htmlspecialchars($row['E']) : '') . '</td>';
                    // Показываем только столбец J (Стоимость для заказчика)
                    $html .= '<td class="text-right">' . (isset($row['J']) ? htmlspecialchars($row['J']) : '') . '</td>';
                    $html .= '</tr>';
                }
            }
            
            $html .= '
                </table>
                <div class="signature">
                    <p><strong>Подпись заказчика: __________________________</strong></p>
                </div>
            </body></html>';
            
            // Генерируем PDF
            $pdf = Pdf::loadHTML($html);
            
            // Устанавливаем размеры и ориентацию
            $pdf->setPaper('a4', 'landscape');
            
            // Возвращаем PDF для скачивания
            $fileName = 'Смета_для_заказчика_' . preg_replace('/[^a-zA-Zа-яА-Я0-9_\- ]/u', '', $estimate->name) . '.pdf';
            $fileName = str_replace(' ', '_', $fileName);
            
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при экспорте PDF файла для заказчика: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'exception' => $e
            ]);
            
            return back()->with('error', 'Произошла ошибка при экспорте в PDF для заказчика: ' . $e->getMessage());
        }
    }
    
    /**
     * Экспортирует смету в файл PDF для мастера (подрядчика)
     */
    public function exportPdfContractor(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        // Проверяем, существует ли файл
        if (!$estimate->file_path || !Storage::disk('public')->exists($estimate->file_path)) {
            // Если файла нет, создаем его
            $this->createInitialExcelFile($estimate);
        }
        
        // В любом случае применяем улучшенное форматирование
        $this->enhanceExistingFileFormatting($estimate);
        
        try {
            // Получаем путь к файлу Excel
            $excelFilePath = storage_path('app/public/' . $estimate->file_path);
            
            // Загружаем данные из Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFilePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray(null, true, true, true);
            
            // Задаем стили для PDF
            $styles = '
                <style>
                    body { font-family: "sans-serif", "DejaVu Sans", Arial, sans-serif; font-size: 10pt; line-height: 1.3; }
                    h1 { text-align: center; color: #2F75B5; margin-bottom: 20px; font-size: 16pt; }
                    .estimate-info { margin-bottom: 15px; }
                    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
                    table, th, td { border: 1px solid #ddd; }
                    th { background-color: #2F75B5; color: white; padding: 10px; text-align: center; }
                    td { padding: 8px; text-align: left; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .bold { font-weight: bold; }
                    .section-header { background-color: #366092; color: white; font-weight: bold; }
                    .total-row { background-color: #BDD7EE; font-weight: bold; }
                </style>
            ';
            
            // Формируем HTML для PDF с UTF-8 кодировкой
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <link href="' . public_path('css/pdf-fonts.css') . '" rel="stylesheet" type="text/css" />
                ' . $styles . '
                <title>Смета для мастера - ' . $estimate->name . '</title>
            </head>
            <body>
                <h1>Смета для мастера - ' . $estimate->name . '</h1>
                <div class="estimate-info">
                    <p><strong>Дата:</strong> ' . now()->format('d.m.Y') . '</p>
                    ' . ($estimate->project ? '<p><strong>Объект:</strong> ' . $estimate->project->address . '</p>' : '') . '
                </div>
                <table>';
            
            // Определим первую строку как заголовок
            $isFirstRow = true;
            $headerRow = 5; // Обычно заголовки находятся в строке 5
            
            foreach ($data as $rowIndex => $row) {
                // Пропускаем пустые строки или строки до заголовков
                $isEmpty = true;
                foreach ($row as $cell) {
                    if (!empty($cell)) {
                        $isEmpty = false;
                        break;
                    }
                }
                if ($isEmpty || $rowIndex < $headerRow) continue;
                
                // Определяем стиль строки
                $rowClass = '';
                $cellB = isset($row['B']) ? $row['B'] : '';
                
                if (is_string($cellB)) {
                    if (mb_stripos($cellB, 'ИТОГО') !== false) {
                        $rowClass = 'class="total-row"';
                    } elseif (mb_stripos($cellB, 'раздел') !== false || mb_stripos($cellB, '.') === 0 || preg_match('/^\d+\./', $cellB)) {
                        $rowClass = 'class="section-header"';
                    }
                }
                
                if ($rowIndex == $headerRow) {
                    // Это заголовок таблицы
                    $html .= '<tr>';
                    $html .= '<th>' . (isset($row['A']) ? htmlspecialchars($row['A']) : '№') . '</th>';
                    $html .= '<th>' . (isset($row['B']) ? htmlspecialchars($row['B']) : 'Наименование материалов') . '</th>';
                    $html .= '<th>' . (isset($row['C']) ? htmlspecialchars($row['C']) : 'Ед. изм.') . '</th>';
                    $html .= '<th>' . (isset($row['D']) ? htmlspecialchars($row['D']) : 'Кол-во') . '</th>';
                    $html .= '<th>' . (isset($row['E']) ? htmlspecialchars($row['E']) : 'Цена, руб.') . '</th>';
                    $html .= '<th>' . (isset($row['F']) ? htmlspecialchars($row['F']) : 'Стоимость') . '</th>';
                    $html .= '</tr>';
                } else {
                    $html .= "<tr $rowClass>";
                    $html .= '<td class="text-center">' . (isset($row['A']) ? htmlspecialchars($row['A']) : '') . '</td>';
                    $html .= '<td>' . (isset($row['B']) ? htmlspecialchars($row['B']) : '') . '</td>';
                    $html .= '<td class="text-center">' . (isset($row['C']) ? htmlspecialchars($row['C']) : '') . '</td>';
                    $html .= '<td class="text-center">' . (isset($row['D']) ? htmlspecialchars($row['D']) : '') . '</td>';
                    $html .= '<td class="text-right">' . (isset($row['E']) ? htmlspecialchars($row['E']) : '') . '</td>';
                    $html .= '<td class="text-right">' . (isset($row['F']) ? htmlspecialchars($row['F']) : '') . '</td>';
                    $html .= '</tr>';
                }
            }
            
            $html .= '
                </table>
                <div class="signature">
                    <p><strong>Подпись мастера: __________________________</strong></p>
                </div>
            </body></html>';
            
            // Генерируем PDF
            $pdf = Pdf::loadHTML($html);
            
            // Устанавливаем размеры и ориентацию
            $pdf->setPaper('a4', 'landscape');
            
            // Возвращаем PDF для скачивания
            $fileName = 'Смета_для_мастера_' . preg_replace('/[^a-zA-Zа-яА-Я0-9_\- ]/u', '', $estimate->name) . '.pdf';
            $fileName = str_replace(' ', '_', $fileName);
            
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при экспорте PDF файла для мастера: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'exception' => $e
            ]);
            
            return back()->with('error', 'Произошла ошибка при экспорте в PDF для мастера: ' . $e->getMessage());
        }
    }
}
