<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProjectCalendarController extends Controller
{    
    /**
     * Проверка корректности формата даты
     * 
     * @param string $date
     * @return bool
     */
    private function isValidDateFormat($date) {
        if (!$date) return false;
        
        try {
            // Проверяем соответствие формату YYYY-MM-DD
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return false;
            }
            
            // Проверяем, что дата существует
            $dt = Carbon::parse($date);
            return $dt instanceof Carbon;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Отображает страницу календарного вида графика
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        
        // Получаем параметры интервала дат, если они переданы
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        return view('partner.projects.tabs.calendar', [
            'project' => $project,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
    
    /**
     * Отображает календарный вид графика проекта
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function getCalendarView(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        
        // Получаем параметры интервала дат, если они переданы
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Если даты не указаны, берем текущий месяц
        if (!$startDate) {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        }
        
        if (!$endDate) {
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
        
        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);
        
        // Проверяем, что между датами не более 90 дней
        $daysDiff = $startDateObj->diffInDays($endDateObj);
        if ($daysDiff > 90) {
            $endDateObj = (clone $startDateObj)->addDays(90);
            $endDate = $endDateObj->format('Y-m-d');
        }
          // Проверяем корректность дат
        if (!$this->isValidDateFormat($startDate) || !$this->isValidDateFormat($endDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректный формат даты. Используйте формат YYYY-MM-DD.'
            ], 400);
        }
        
        $filePath = "project_schedules/{$project->id}/schedule.xlsx";
        $fullPath = storage_path("app/public/{$filePath}");
        
        // Проверяем существование директории
        $directoryPath = "project_schedules/{$project->id}";
        if (!Storage::disk('public')->exists($directoryPath)) {
            Storage::disk('public')->makeDirectory($directoryPath);
        }
        
        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Файл графика не найден'
            ], 404);
        }
        
        try {
            // Загружаем оригинальный файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Текущая дата для проверки просрочек
            $currentDate = Carbon::now();
            
            // Найдем столбцы с датами начала и окончания
            $startDateCol = null;
            $endDateCol = null;
            $statusCol = null;
            $nameCol = null;
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            // Определяем столбцы для извлечения данных
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $cellValue = $worksheet->getCell($colLetter . '1')->getValue();
                
                if (is_string($cellValue)) {
                    $cellValueLower = mb_strtolower($cellValue);
                    if (strpos($cellValueLower, 'наименование') !== false || strpos($cellValueLower, 'название') !== false || strpos($cellValueLower, 'задача') !== false) {
                        $nameCol = $col;
                    }
                    if (strpos($cellValueLower, 'статус') !== false) {
                        $statusCol = $col;
                    }
                    if (strpos($cellValueLower, 'начало') !== false || strpos($cellValueLower, 'старт') !== false) {
                        $startDateCol = $col;
                    }
                    if (strpos($cellValueLower, 'окончание') !== false || strpos($cellValueLower, 'конец') !== false) {
                        $endDateCol = $col;
                    }
                }
            }
            
            // Если не нашли нужные столбцы, возвращаем ошибку
            if (!$startDateCol || !$endDateCol || !$nameCol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось определить столбцы с датами или названиями задач'
                ], 400);
            }
            
            // Создаем массив всех дней в выбранном интервале
            $dateRange = CarbonPeriod::create($startDate, $endDate)->toArray();
            $days = [];
            $months = [];
            
            // Формируем массив дней и месяцев для отображения в шапке таблицы
            $currentMonth = null;
            $monthDayCount = 0;
            
            foreach ($dateRange as $date) {
                $days[] = [
                    'date' => $date->format('Y-m-d'),
                    'day' => $date->day,
                    'isWeekend' => $date->isWeekend(),
                ];
                
                // Группируем дни по месяцам для шапки таблицы
                $monthKey = $date->format('Y-m');
                if ($monthKey !== $currentMonth) {                    if ($currentMonth !== null) {
                        $russianMonths = [
                            'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
                        ];
                        $monthDate = Carbon::parse($currentMonth . '-01');
                        $monthName = $russianMonths[$monthDate->month - 1] . ' ' . $monthDate->year;
                        
                        $months[] = [
                            'name' => $monthName,
                            'days' => $monthDayCount
                        ];
                    }
                    $currentMonth = $monthKey;
                    $monthDayCount = 1;
                } else {
                    $monthDayCount++;
                }
            }
            
            // Добавляем последний месяц
            if ($currentMonth !== null) {                $russianMonths = [
                    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
                ];
                $monthDate = Carbon::parse($currentMonth . '-01');
                $monthName = $russianMonths[$monthDate->month - 1] . ' ' . $monthDate->year;
                $months[] = [
                    'name' => $monthName,
                    'days' => $monthDayCount
                ];
            }
            
            // Извлекаем данные графика
            $tasks = [];
            $highestRow = $worksheet->getHighestRow();
            
            for ($row = 2; $row <= $highestRow; $row++) {
                $nameColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nameCol);
                $taskName = $worksheet->getCell($nameColLetter . $row)->getValue();
                
                if (!$taskName) {
                    continue; // Пропускаем строки без названия задачи
                }
                
                // Получаем статус задачи
                $taskStatus = 'В работе'; // Значение по умолчанию
                if ($statusCol) {
                    $statusColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($statusCol);
                    $cellStatus = $worksheet->getCell($statusColLetter . $row)->getValue();
                    if ($cellStatus) {
                        $taskStatus = $cellStatus;
                    }
                }
                
                // Получаем даты начала и окончания
                $taskStartDate = null;
                $taskEndDate = null;
                
                $startColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startDateCol);
                $startCell = $worksheet->getCell($startColLetter . $row)->getValue();
                  if ($startCell) {
                    if ($startCell instanceof \DateTime) {
                        $taskStartDate = Carbon::instance($startCell);
                    } else {
                        try {
                            // Проверяем, является ли значение числом (Excel numeric date)
                            if (is_numeric($startCell)) {
                                $taskStartDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$startCell));
                            } else {
                                // Пытаемся разобрать как строковую дату
                                $taskStartDate = Carbon::parse($startCell);
                            }
                        } catch (\Exception $e) {
                            // Не удалось распознать дату
                            Log::warning("Не удалось распознать дату начала в строке {$row}: {$startCell}", ['error' => $e->getMessage()]);
                        }
                    }
                }
                
                $endColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endDateCol);
                $endCell = $worksheet->getCell($endColLetter . $row)->getValue();
                
                if ($endCell) {
                    if ($endCell instanceof \DateTime) {
                        $taskEndDate = Carbon::instance($endCell);
                    } else {
                        try {
                            // Проверяем, является ли значение числом (Excel numeric date)
                            if (is_numeric($endCell)) {
                                $taskEndDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$endCell));
                            } else {
                                // Пытаемся разобрать как строковую дату
                                $taskEndDate = Carbon::parse($endCell);
                            }
                        } catch (\Exception $e) {
                            // Не удалось распознать дату
                            Log::warning("Не удалось распознать дату окончания в строке {$row}: {$endCell}", ['error' => $e->getMessage()]);
                        }
                    }
                }
                  // Проверяем, что у нас есть обе даты и они являются экземплярами класса Carbon
                if ($taskStartDate instanceof Carbon && $taskEndDate instanceof Carbon) {
                    // Проверяем просрочку
                    $isOverdue = false;
                    $daysOverdue = 0;
                    
                    if ($taskEndDate && $taskEndDate->lt($currentDate) && $taskStatus !== 'Готово') {
                        $isOverdue = true;
                        $daysOverdue = $currentDate->diffInDays($taskEndDate);
                    }
                      // Пропускаем задачи, которые не пересекаются с выбранным диапазоном
                    try {
                        $filterStartDate = Carbon::parse($startDate);
                        $filterEndDate = Carbon::parse($endDate);
                        
                        if ($taskEndDate->lt($filterStartDate) || $taskStartDate->gt($filterEndDate)) {
                            continue;
                        }
                    } catch (\Exception $e) {
                        Log::warning("Ошибка при обработке фильтра дат", [
                            'start_date' => $startDate, 
                            'end_date' => $endDate,
                            'error' => $e->getMessage()
                        ]);
                        // Если произошла ошибка при фильтрации, показываем задачу
                    }
                    
                    // Определяем статус задачи для отображения
                    $displayStatus = $taskStatus;
                    $statusClass = 'bg-secondary';
                    
                    if ($taskStatus === 'Готово') {
                        $statusClass = 'bg-success';
                    } elseif ($taskStatus === 'В работе') {
                        $statusClass = 'bg-primary';
                    } elseif ($taskStatus === 'Ожидание') {
                        $statusClass = 'bg-warning';
                    } elseif ($taskStatus === 'Отменено') {
                        $statusClass = 'bg-danger';
                    }
                    
                    if ($isOverdue) {
                        $statusClass = 'bg-danger';
                        $displayStatus = "Просрочено ({$daysOverdue} дн.)";
                    }
                    
                    // Создаем запись о задаче
                    $tasks[] = [
                        'id' => 'task_' . $row,
                        'name' => $taskName,
                        'status' => $taskStatus,
                        'displayStatus' => $displayStatus,
                        'statusClass' => $statusClass,
                        'start_date' => $taskStartDate->format('Y-m-d'),
                        'end_date' => $taskEndDate->format('Y-m-d'),
                        'isOverdue' => $isOverdue,
                        'daysOverdue' => $daysOverdue,
                    ];
                }
            }
            
            // Сортируем задачи по дате начала
            usort($tasks, function($a, $b) {
                return strcmp($a['start_date'], $b['start_date']);
            });
            
            // Формируем данные для отображения
            return response()->json([
                'success' => true,
                'data' => [
                    'days' => $days,
                    'months' => $months,
                    'tasks' => $tasks,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ]
            ]);        } catch (\Exception $e) {
            Log::error('Ошибка при формировании календарного вида графика', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при формировании календарного вида: ' . $e->getMessage() . ' (строка ' . $e->getLine() . ')'
            ], 500);
        }
    }
}
