<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Http\Controllers\Partner\ProjectScheduleController;

class GenerateScheduleData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:generate-data {project_id : ID проекта для генерации данных}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерирует data.json файл из Excel план-графика для клиентского интерфейса';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projectId = $this->argument('project_id');
        
        $this->info("Генерация данных план-графика для проекта $projectId...");
        
        // Находим проект
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->error("Проект с ID $projectId не найден");
            return 1;
        }
        
        $this->info("Найден проект: {$project->client_name}");
        
        // Создаем контроллер
        $controller = new ProjectScheduleController();
        
        try {
            // Генерируем данные
            $result = $controller->generateDataJson($project);
            $data = json_decode($result->getContent(), true);
            
            if ($data && $data['success']) {
                $this->info("✅ Данные успешно сгенерированы!");
                
                if (isset($data['data']['metadata'])) {
                    $metadata = $data['data']['metadata'];
                    $this->line("   Элементов: " . ($metadata['items_count'] ?? 'не указано'));
                    $this->line("   Общие дни: " . ($metadata['total_days'] ?? 'не указано'));
                    $this->line("   Период: " . ($metadata['min_date'] ?? 'не указано') . " - " . ($metadata['max_date'] ?? 'не указано'));
                }
                
                // Проверяем созданный файл
                $dataPath = storage_path("app/public/project_schedules/$projectId/data.json");
                if (file_exists($dataPath)) {
                    $this->info("✅ Файл data.json создан: $dataPath");
                    $fileSize = filesize($dataPath);
                    $this->line("   Размер файла: " . round($fileSize / 1024, 2) . " KB");
                } else {
                    $this->warn("⚠️  Файл data.json не найден");
                }
                
                return 0;
            } else {
                $this->error("❌ Ошибка при генерации данных: " . ($data['message'] ?? 'Неизвестная ошибка'));
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Исключение при генерации данных: " . $e->getMessage());
            $this->line("   Файл: " . $e->getFile());
            $this->line("   Строка: " . $e->getLine());
            return 1;
        }
    }
}
