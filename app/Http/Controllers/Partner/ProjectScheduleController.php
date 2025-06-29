<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProjectScheduleController extends Controller
{    /**
     * Получает файл расписания для проекта.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function getFile(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        
        // Получаем параметры интервала дат, если они переданы
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $filePath = "project_schedules/{$project->id}/schedule.xlsx";
        $fullPath = storage_path("app/public/{$filePath}");
        
        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не найден'
            ], 404);
        }
        
        // Если указан интервал дат, то создаем копию файла с фильтром по датам
        if ($startDate && $endDate) {
            try {
                // Загружаем оригинальный файл
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
                $worksheet = $spreadsheet->getActiveSheet();
                
                // Текущая дата для проверки просрочек
                $currentDate = new \DateTime();
                
                // Найдем столбцы с датами начала и окончания
                $startDateCol = null;
                $endDateCol = null;
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $cellValue = $worksheet->getCell($colLetter . '1')->getValue();
                    
                    if (is_string($cellValue)) {
                        if (stripos($cellValue, 'начало') !== false || stripos($cellValue, 'старт') !== false) {
                            $startDateCol = $col;
                        }
                        if (stripos($cellValue, 'окончание') !== false || stripos($cellValue, 'конец') !== false) {
                            $endDateCol = $col;
                        }
                    }
                }
                
                if (!$startDateCol || !$endDateCol) {
                    // Если не нашли нужные столбцы, возвращаем оригинальный файл
                    return response()->download($fullPath, "График_проекта_{$project->id}.xlsx");
                }
                
                // Параметры фильтрации
                $filterStartDate = new \DateTime($startDate);
                $filterEndDate = new \DateTime($endDate);
                
                // Обрабатываем все строки и выделяем просроченные
                $highestRow = $worksheet->getHighestRow();
                $rowsToRemove = [];
                
                for ($row = 2; $row <= $highestRow; $row++) {
                    // Получаем даты из строки
                    $rowStartDate = null;
                    $rowEndDate = null;
                    
                    // Пытаемся получить даты
                    $startColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startDateCol);
                    $endColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endDateCol);
                    
                    $startCell = $worksheet->getCell($startColLetter . $row)->getValue();
                    $endCell = $worksheet->getCell($endColLetter . $row)->getValue();
                    
                    if ($startCell) {
                        if ($startCell instanceof \DateTime) {
                            $rowStartDate = $startCell;
                        } else {
                            try {
                                $rowStartDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($startCell);
                            } catch (\Exception $e) {
                                try {
                                    $rowStartDate = new \DateTime($startCell);
                                } catch (\Exception $e) {
                                    // Не удалось распознать дату
                                }
                            }
                        }
                    }
                    
                    if ($endCell) {
                        if ($endCell instanceof \DateTime) {
                            $rowEndDate = $endCell;
                        } else {
                            try {
                                $rowEndDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($endCell);
                            } catch (\Exception $e) {
                                try {
                                    $rowEndDate = new \DateTime($endCell);
                                } catch (\Exception $e) {
                                    // Не удалось распознать дату
                                }
                            }
                        }
                    }
                    
                    // Проверяем, попадает ли запись в диапазон фильтрации
                    $inRange = false;
                    if ($rowStartDate && $rowEndDate) {
                        // Задача попадает в диапазон, если она хотя бы частично перекрывается с фильтром
                        $inRange = ($rowStartDate <= $filterEndDate && $rowEndDate >= $filterStartDate);
                    } elseif ($rowStartDate) {
                        $inRange = ($rowStartDate >= $filterStartDate && $rowStartDate <= $filterEndDate);
                    } elseif ($rowEndDate) {
                        $inRange = ($rowEndDate >= $filterStartDate && $rowEndDate <= $filterEndDate);
                    }
                    
                    if (!$inRange) {
                        // Если строка не попадает в диапазон, добавляем в список на удаление
                        $rowsToRemove[] = $row;
                        continue;
                    }
                    
                    // Выделяем просроченные задачи
                    if ($rowEndDate && $rowEndDate < $currentDate) {
                        // Подсветка просроченной строки
                        $range = 'A'.$row.':'.$highestColumn.$row;
                        $worksheet->getStyle($range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $worksheet->getStyle($range)->getFill()->getStartColor()->setRGB('FFCCCC'); // Светло-красный фон
                        
                        // Добавляем информацию о просрочке
                        $diff = $currentDate->diff($rowEndDate);
                        $daysOverdue = $diff->days;
                        
                        // Находим последний столбец с данными для добавления информации о просрочке
                        $lastCol = $highestColumnIndex + 1;
                        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol);
                        
                        // Проверяем, есть ли уже заголовок 'Просрочка'
                        $hasOverdueHeader = false;
                        for ($col = 1; $col <= $highestColumnIndex; $col++) {
                            $headerValue = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
                            if (stripos($headerValue, 'просроч') !== false) {
                                $hasOverdueHeader = true;
                                $lastCol = $col;
                                break;
                            }
                        }
                        
                        if (!$hasOverdueHeader) {
                              // Добавляем заголовок столбца
                            $worksheet->setCellValueByColumnAndRow($lastCol, 1, 'Просрочено (дней)');
                            $worksheet->getColumnDimensionByColumn($lastCol)->setAutoSize(true);
                        }
                        
                        // Заполняем ячейку с просрочкой
                        $worksheet->setCellValueByColumnAndRow($lastCol, $row, $daysOverdue);
                    }
                }
                
                // Сохраняем временный файл
                $tempFilePath = "project_schedules/{$project->id}/schedule_filtered.xlsx";
                $writer = new Xlsx($spreadsheet);
                $writer->save(Storage::disk('public')->path($tempFilePath));
                
                return response()->download(
                    Storage::disk('public')->path($tempFilePath),
                    "График_проекта_{$project->id}_с_фильтром.xlsx"
                )->deleteFileAfterSend(true);
            } catch (\Exception $e) {
                Log::error('Ошибка при обработке файла расписания', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при обработке файла: ' . $e->getMessage()
                ], 500);
            }
        }
        
        return response()->download(
            Storage::disk('public')->path($filePath),
            "График_проекта_{$project->id}.xlsx"
        );
    }    /**
     * Создает шаблон графика для проекта.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function createTemplate(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        
        try {
            // Создаем новую книгу Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Устанавливаем заголовки
            $headers = [
                'Наименование работы',
                'Статус',
                'Дата начала',
                'Дата окончания',
                'Ответственный',
                'Примечания'
            ];
            
            // Записываем заголовки
            foreach ($headers as $i => $header) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue($column . '1', $header);
                
                // Устанавливаем стили для заголовков
                $sheet->getStyle($column . '1')->getFont()->setBold(true);
                $sheet->getStyle($column . '1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E2EFDA');
                
                // Устанавливаем границы
                $sheet->getStyle($column . '1')->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                    
                // Устанавливаем автоширину столбца
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Создаем образец данных
            $sampleData = [
                [
                    'Демонтаж старой отделки',
                    'В работе',
                    date('Y-m-d'),
                    date('Y-m-d', strtotime('+7 days')),
                    'Иванов И.И.',
                    'Необходим вывоз мусора'
                ],
                [
                    'Подготовка стен',
                    'Ожидание',
                    date('Y-m-d', strtotime('+8 days')),
                    date('Y-m-d', strtotime('+14 days')),
                    'Петров П.П.',
                    ''
                ],
                [
                    'Электромонтажные работы',
                    'Ожидание',
                    date('Y-m-d', strtotime('+15 days')),
                    date('Y-m-d', strtotime('+20 days')),
                    'Сидоров С.С.',
                    'Требуется согласование схемы'
                ],
            ];
            
            // Записываем примеры данных
            foreach ($sampleData as $rowIndex => $rowData) {
                foreach ($rowData as $colIndex => $cellValue) {
                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                    $row = $rowIndex + 2; // +2 потому что первая строка - заголовки
                    $sheet->setCellValue($column . $row, $cellValue);
                    
                    // Устанавливаем границы для ячеек данных
                    $sheet->getStyle($column . $row)->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);
                }
            }
            
            // Создаем директорию для хранения, если её нет
            $dirPath = "project_schedules/{$project->id}";
            if (!Storage::disk('public')->exists($dirPath)) {
                Storage::disk('public')->makeDirectory($dirPath);
            }
            
            // Сохраняем файл
            $filePath = "{$dirPath}/schedule.xlsx";
            $writer = new Xlsx($spreadsheet);
            $writer->save(storage_path("app/public/{$filePath}"));
              // Формируем URL для публичного доступа к файлу
            $fileUrl = asset('storage/' . $filePath);
            
            return response()->json([
                'success' => true,
                'message' => 'Шаблон графика успешно создан',
                'file_url' => $fileUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при создании шаблона графика', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании шаблона графика: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Сохраняет файл графика проекта.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function saveFile(Request $request, Project $project) 
    {
        $this->authorize('update', $project);
        
        // Проверяем наличие файла
        if (!$request->hasFile('schedule_file')) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не был загружен'
            ], 400);
        }
          $file = $request->file('schedule_file');
        
        // Проверяем тип файла
        $allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
            'application/vnd.ms-excel', // xls
            'application/octet-stream', // Некоторые браузеры могут отправлять Excel файлы как octet-stream
            'application/excel',
            'application/binary' // Иногда браузеры отправляют как binary
        ];
          $fileMimeType = $file->getMimeType();
        
        if (!in_array($fileMimeType, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Недопустимый тип файла: ' . $fileMimeType . '. Пожалуйста, загрузите файл Excel (.xlsx или .xls)'
            ], 400);
        }
          try {
            // Логируем информацию о файле для отладки
            Log::info('Получен файл графика для сохранения', [
                'project_id' => $project->id,
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize()
            ]);
            
            // Создаем директорию для хранения, если её нет
            $dirPath = "project_schedules/{$project->id}";
            if (!Storage::disk('public')->exists($dirPath)) {
                Storage::disk('public')->makeDirectory($dirPath);
            }
              // Сохраняем файл
            $path = $file->storeAs($dirPath, 'schedule.xlsx', 'public');
              // Формируем URL для публичного доступа к файлу
            $fileUrl = asset('storage/' . $path);
            
            // Автоматически генерируем data.json для клиентского интерфейса
            try {
                $this->generateDataJson($project);
                Log::info('Автоматически сгенерирован data.json после загрузки файла', [
                    'project_id' => $project->id
                ]);
            } catch (\Exception $e) {
                Log::warning('Не удалось автоматически сгенерировать data.json', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Файл графика успешно сохранен',
                'file_url' => $fileUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при сохранении файла графика', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сохранении файла: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Генерирует data.json файл из Excel план-графика для клиентского интерфейса
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function generateDataJson(Project $project)
    {
        $this->authorize('view', $project);
        
        $filePath = "project_schedules/{$project->id}/schedule.xlsx";
        $fullPath = storage_path("app/public/{$filePath}");
        
        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Файл графика не найден'
            ], 404);
        }
        
        try {
            // Загружаем файл Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Найдем необходимые столбцы
            $nameCol = null;
            $statusCol = null;
            $startDateCol = null;
            $endDateCol = null;
            $typeCol = null; // Вид работы
            $daysCol = null; // Количество дней
            
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            // Определяем столбцы для извлечения данных
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $cellValue = $worksheet->getCell($colLetter . '1')->getValue();
                
                if (is_string($cellValue)) {
                    $cellValueLower = mb_strtolower($cellValue);
                    
                    if (strpos($cellValueLower, 'наименование') !== false || 
                        strpos($cellValueLower, 'название') !== false || 
                        strpos($cellValueLower, 'работа') !== false) {
                        $nameCol = $col;
                    }
                    if (strpos($cellValueLower, 'статус') !== false) {
                        $statusCol = $col;
                    }
                    if (strpos($cellValueLower, 'начало') !== false || 
                        strpos($cellValueLower, 'старт') !== false) {
                        $startDateCol = $col;
                    }
                    if (strpos($cellValueLower, 'окончание') !== false || 
                        strpos($cellValueLower, 'конец') !== false) {
                        $endDateCol = $col;
                    }
                    if (strpos($cellValueLower, 'вид') !== false || 
                        strpos($cellValueLower, 'тип') !== false) {
                        $typeCol = $col;
                    }
                    if (strpos($cellValueLower, 'дней') !== false || 
                        strpos($cellValueLower, 'дни') !== false ||
                        strpos($cellValueLower, 'продолжительность') !== false) {
                        $daysCol = $col;
                    }
                }
            }
            
            // Если не нашли основные столбцы, возвращаем ошибку
            if (!$nameCol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось найти столбец с наименованием работ'
                ], 400);
            }
            
            // Извлекаем данные из Excel
            $scheduleItems = [];
            $minDate = null;
            $maxDate = null;
            $totalDays = 0;
            
            $highestRow = $worksheet->getHighestRow();
            
            for ($row = 2; $row <= $highestRow; $row++) {
                $nameColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nameCol);
                $taskName = $worksheet->getCell($nameColLetter . $row)->getValue();
                
                if (!$taskName || trim($taskName) === '') {
                    continue; // Пропускаем пустые строки
                }
                
                $item = [
                    'Наименование' => trim($taskName),
                    'Статус' => 'Ожидание',
                    'Начало' => '',
                    'Конец' => '',
                    'Вид' => 'Работа',
                    'Дней' => ''
                ];
                
                // Получаем статус
                if ($statusCol) {
                    $statusColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($statusCol);
                    $status = $worksheet->getCell($statusColLetter . $row)->getValue();
                    if ($status && trim($status) !== '') {
                        $item['Статус'] = trim($status);
                    }
                }
                
                // Получаем тип/вид работы
                if ($typeCol) {
                    $typeColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($typeCol);
                    $type = $worksheet->getCell($typeColLetter . $row)->getValue();
                    if ($type && trim($type) !== '') {
                        $item['Вид'] = trim($type);
                    }
                }
                
                // Получаем количество дней
                if ($daysCol) {
                    $daysColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($daysCol);
                    $days = $worksheet->getCell($daysColLetter . $row)->getValue();
                    if ($days && is_numeric($days)) {
                        $item['Дней'] = (int)$days;
                        $totalDays += (int)$days;
                    }
                }
                
                // Получаем дату начала
                if ($startDateCol) {
                    $startColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startDateCol);
                    $startCell = $worksheet->getCell($startColLetter . $row)->getValue();
                    
                    if ($startCell) {
                        $startDate = $this->parseExcelDate($startCell);
                        if ($startDate) {
                            $item['Начало'] = $startDate->format('Y-m-d');
                            if (!$minDate || $startDate->lt($minDate)) {
                                $minDate = $startDate;
                            }
                        }
                    }
                }
                
                // Получаем дату окончания
                if ($endDateCol) {
                    $endColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endDateCol);
                    $endCell = $worksheet->getCell($endColLetter . $row)->getValue();
                    
                    if ($endCell) {
                        $endDate = $this->parseExcelDate($endCell);
                        if ($endDate) {
                            $item['Конец'] = $endDate->format('Y-m-d');
                            if (!$maxDate || $endDate->gt($maxDate)) {
                                $maxDate = $endDate;
                            }
                        }
                    }
                }
                
                $scheduleItems[] = $item;
            }
            
            // Формируем метаданные
            $metadata = [
                'total_days' => $totalDays,
                'total_weeks' => $totalDays > 0 ? ceil($totalDays / 7) : 0,
                'total_months' => $totalDays > 0 ? ceil($totalDays / 30) : 0,
                'min_date' => $minDate ? $minDate->format('Y-m-d') : '',
                'max_date' => $maxDate ? $maxDate->format('Y-m-d') : '',
                'items_count' => count($scheduleItems),
                'generated_at' => now()->toISOString()
            ];
            
            // Формируем итоговые данные
            $data = [
                'data' => $scheduleItems,
                'metadata' => $metadata
            ];
            
            // Сохраняем data.json
            $jsonPath = "project_schedules/{$project->id}/data.json";
            Storage::disk('public')->put($jsonPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            
            Log::info('Сгенерирован data.json для проекта', [
                'project_id' => $project->id,
                'items_count' => count($scheduleItems),
                'total_days' => $totalDays
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Данные план-графика успешно обновлены',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка при генерации data.json для план-графика', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обработке план-графика: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Парсит дату из Excel ячейки
     *
     * @param mixed $cellValue
     * @return \Carbon\Carbon|null
     */
    private function parseExcelDate($cellValue)
    {
        if (!$cellValue) {
            return null;
        }
        
        try {
            if ($cellValue instanceof \DateTime) {
                return \Carbon\Carbon::instance($cellValue);
            }
            
            // Проверяем, является ли значение числом (Excel numeric date)
            if (is_numeric($cellValue)) {
                return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$cellValue));
            }
            
            // Пытаемся разобрать как строковую дату
            return \Carbon\Carbon::parse($cellValue);
            
        } catch (\Exception $e) {
            Log::warning("Не удалось распознать дату: {$cellValue}", ['error' => $e->getMessage()]);
            return null;
        }
    }
}
