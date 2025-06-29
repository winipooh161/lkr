<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProjectController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('client');
    }
    
    /**
     * Display a listing of the client's projects.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->only(['search', 'status', 'work_type', 'branch']);
        
        // Получаем проекты, связанные с номером телефона клиента
        $query = Project::where('phone', $user->phone);
        
        // Применяем фильтры
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('contract_number', 'like', "%{$search}%");
            });
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['work_type'])) {
            $query->where('work_type', $filters['work_type']);
        }
        
        if (!empty($filters['branch'])) {
            $query->where('branch', $filters['branch']);
        }
        
        $projects = $query->orderBy('updated_at', 'desc')
                         ->paginate(9)
                         ->withQueryString();
        
        return view('client.projects.index', compact('projects', 'filters'));
    }
    
    /**
     * Display the specified project details.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */    public function show(Request $request, Project $project)
    {
        // Проверяем, что проект принадлежит клиенту
        if ($project->phone !== $request->user()->phone) {
            abort(403, 'У вас нет доступа к этому объекту.');
        }
        
        // Загружаем все необходимые связи
        $project->load([
            'financeItems',
            'mainWorks',
            'mainMaterials', 
            'additionalWorks',
            'additionalMaterials',
            'transportationItems',
            'designFiles',
            'schemeFiles',
            'documentFiles',
            'contractFiles',
            'otherFiles',
            'photos',
            'checks'
        ]);
        
        // Получаем данные расписания, если они есть
        $scheduleItems = [];
        $scheduleMetadata = null;
        
        $scheduleFile = $this->getScheduleFile($project->id);
        if ($scheduleFile) {
            $scheduleData = $this->getScheduleData($project->id);
            if ($scheduleData && isset($scheduleData['data'])) {
                $scheduleItems = $scheduleData['data'];
                $scheduleMetadata = $scheduleData['metadata'] ?? null;
            }
        }
        
        return view('client.projects.show', compact('project', 'scheduleItems', 'scheduleMetadata'));
    }
    
    /**
     * Get schedule file path if exists.
     *
     * @param int $projectId
     * @return string|null
     */
    protected function getScheduleFile($projectId)
    {
        $paths = [
            'project_schedules/' . $projectId . '/schedule.xlsx',
            'project_schedules/' . $projectId . '/schedule.xls'
        ];
        
        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
      /**
     * Get schedule data from storage.
     *
     * @param int $projectId
     * @return array|null
     */
    protected function getScheduleData($projectId)
    {
        $path = 'project_schedules/' . $projectId . '/data.json';
        
        if (Storage::disk('public')->exists($path)) {
            $jsonData = Storage::disk('public')->get($path);
            $data = json_decode($jsonData, true);
            
            // Проверяем, что данные валидны
            if ($data && isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
                return $data;
            }
        }
        
        // Если data.json отсутствует или пуст, пытаемся создать его из Excel файла
        $this->generateDataJsonFromExcel($projectId);
        
        // Повторно пытаемся прочитать data.json
        if (Storage::disk('public')->exists($path)) {
            $jsonData = Storage::disk('public')->get($path);
            return json_decode($jsonData, true);
        }
        
        return null;
    }
    
    /**
     * Generate data.json from Excel schedule file if it exists
     *
     * @param int $projectId
     * @return void
     */
    protected function generateDataJsonFromExcel($projectId)
    {
        try {
            // Найти Excel файл
            $excelPath = null;
            $possiblePaths = [
                "project_schedules/{$projectId}/schedule.xlsx",
                "project_schedules/{$projectId}/schedule.xls"
            ];
            
            foreach ($possiblePaths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    $excelPath = $path;
                    break;
                }
            }
            
            if (!$excelPath) {
                return; // Нет Excel файла
            }
            
            // Используем контроллер партнера для генерации данных
            $project = new \stdClass();
            $project->id = $projectId;
            
            $scheduleController = new \App\Http\Controllers\Partner\ProjectScheduleController();
            
            // Создаем фиктивный объект проекта для авторизации
            $fakeProject = new \App\Models\Project();
            $fakeProject->id = $projectId;
            
            // Вызываем метод генерации без проверки авторизации
            $this->generateDataJsonFromExcelFile($projectId, $excelPath);
              } catch (\Exception $e) {
            Log::warning('Не удалось автоматически сгенерировать data.json', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Generate data.json directly from Excel file
     *
     * @param int $projectId
     * @param string $excelPath
     * @return void
     */
    protected function generateDataJsonFromExcelFile($projectId, $excelPath)
    {
        $fullPath = storage_path("app/public/{$excelPath}");
        
        if (!file_exists($fullPath)) {
            return;
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
            $typeCol = null;
            $daysCol = null;
            
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
            
            if (!$nameCol) {
                return; // Не найден столбец с наименованием
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
                    continue;
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
            $jsonPath = "project_schedules/{$projectId}/data.json";
            Storage::disk('public')->put($jsonPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
              } catch (\Exception $e) {
            Log::warning('Ошибка при автоматической генерации data.json', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Parse Excel date value
     *
     * @param mixed $cellValue
     * @return \Carbon\Carbon|null
     */
    protected function parseExcelDate($cellValue)
    {
        if (!$cellValue) {
            return null;
        }
        
        try {
            if ($cellValue instanceof \DateTime) {
                return Carbon::instance($cellValue);
            }
            
            // Проверяем, является ли значение числом (Excel numeric date)
            if (is_numeric($cellValue)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$cellValue));
            }
            
            // Пытаемся разобрать как строковую дату
            return Carbon::parse($cellValue);
            
        } catch (\Exception $e) {
            return null;
        }
    }
}
