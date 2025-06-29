<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectPhoto;
use App\Models\ProjectCheck;
use App\Models\Estimate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PartnerController extends Controller
{
    /**
     * Показать аналитическую панель управления партнера.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        $partnerId = $user->id;
        
        // Общие показатели
        $totalProjects = Project::where('partner_id', $partnerId)->count();
        $activeProjects = Project::where('partner_id', $partnerId)
            ->whereIn('status', ['new', 'in_progress'])
            ->count();
        $completedProjects = Project::where('partner_id', $partnerId)
            ->where('status', 'completed')
            ->count();
        
        // Финансовые показатели
        $totalAmount = Project::where('partner_id', $partnerId)
            ->sum(DB::raw('work_amount + materials_amount'));
        
        // Количество файлов и фотографий
        $totalFiles = ProjectFile::whereIn('project_id', function($query) use ($partnerId) {
            $query->select('id')->from('projects')->where('partner_id', $partnerId);
        })->count();
        
        $totalFiles += ProjectPhoto::whereIn('project_id', function($query) use ($partnerId) {
            $query->select('id')->from('projects')->where('partner_id', $partnerId);
        })->count();
        
        // Получение последних проектов
        $recentProjects = Project::where('partner_id', $partnerId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        // Статистика по статусам проектов
        $statusStats = [
            'Новый' => Project::where('partner_id', $partnerId)->where('status', 'new')->count(),
            'В работе' => Project::where('partner_id', $partnerId)->where('status', 'in_progress')->count(),
            'Завершен' => Project::where('partner_id', $partnerId)->where('status', 'completed')->count(),
            'Приостановлен' => Project::where('partner_id', $partnerId)->where('status', 'on_hold')->count(),
            'Отменен' => Project::where('partner_id', $partnerId)->where('status', 'cancelled')->count(),
        ];
        
        // Статистика по типам объектов
        $objectTypeStats = Project::where('partner_id', $partnerId)
            ->select('object_type', DB::raw('count(*) as count'))
            ->groupBy('object_type')
            ->pluck('count', 'object_type')
            ->toArray();
        
        // Преобразуем ключи к читаемому виду
        $objectTypeLabels = [
            'apartment' => 'Квартира',
            'house' => 'Дом',
            'office' => 'Офис',
            'commercial' => 'Коммерческий',
            'other' => 'Другое',
        ];
        
        $formattedObjectTypeStats = [];
        foreach ($objectTypeStats as $key => $value) {
            $label = $objectTypeLabels[$key] ?? ucfirst($key);
            $formattedObjectTypeStats[$label] = $value;
        }
        $objectTypeStats = $formattedObjectTypeStats;
        
        // Цвета для графика типов объектов
        $objectTypeColors = [
            'rgba(13, 110, 253, 0.8)',   // Синий
            'rgba(25, 135, 84, 0.8)',    // Зелёный
            'rgba(255, 193, 7, 0.8)',    // Желтый
            'rgba(108, 117, 125, 0.8)',  // Серый
            'rgba(220, 53, 69, 0.8)',    // Красный
            'rgba(111, 66, 193, 0.8)',   // Фиолетовый
        ];
        
        // Данные для графика динамики проектов за последние 6 месяцев
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->startOfMonth());
        }
        
        $projectChartLabels = $months->map(function ($month) {
            return $month->format('M Y');
        })->toArray();
        
        $projectChartActive = [];
        $projectChartCompleted = [];
        
        foreach ($months as $month) {
            $nextMonth = (clone $month)->addMonth();
            
            // Активные проекты на конец месяца
            $projectChartActive[] = Project::where('partner_id', $partnerId)
                ->whereIn('status', ['new', 'in_progress'])
                ->where('created_at', '<', $nextMonth)
                ->where(function ($query) use ($month) {
                    $query->where('work_end_date', '>=', $month)
                        ->orWhereNull('work_end_date');
                })
                ->count();
            
            // Завершенные проекты за месяц
            $projectChartCompleted[] = Project::where('partner_id', $partnerId)
                ->where('status', 'completed')
                ->whereBetween('updated_at', [$month, $nextMonth])
                ->count();
        }
        
        // Данные для графика финансов
        $financeChartLabels = $projectChartLabels;
        $financeChartWork = [];
        $financeChartMaterials = [];
        
        foreach ($months as $month) {
            $nextMonth = (clone $month)->addMonth();
            
            // Суммы работ и материалов по проектам, созданным за месяц
            $financeSums = Project::where('partner_id', $partnerId)
                ->whereBetween('created_at', [$month, $nextMonth])
                ->select(
                    DB::raw('SUM(work_amount) as work_sum'),
                    DB::raw('SUM(materials_amount) as materials_sum')
                )
                ->first();
                
            $financeChartWork[] = $financeSums->work_sum ?? 0;
            $financeChartMaterials[] = $financeSums->materials_sum ?? 0;
        }
        
        // Данные для календаря активности
        $activityData = [];
        $startDate = Carbon::now()->subMonths(3)->startOfMonth();
        $endDate = Carbon::now();
        
        // События (создание проектов, загрузка файлов, проверки)
        $projectCreationDates = Project::where('partner_id', $partnerId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('count', 'date')
            ->toArray();
            
        $fileUploadDates = ProjectFile::whereIn('project_id', function($query) use ($partnerId) {
            $query->select('id')->from('projects')->where('partner_id', $partnerId);
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
        ->groupBy(DB::raw('DATE(created_at)'))
        ->pluck('count', 'date')
        ->toArray();
        
        $checkDates = ProjectCheck::whereIn('project_id', function($query) use ($partnerId) {
            $query->select('id')->from('projects')->where('partner_id', $partnerId);
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
        ->groupBy(DB::raw('DATE(created_at)'))
        ->pluck('count', 'date')
        ->toArray();
        
        // Объединяем все события по датам
        $allDates = array_unique(array_merge(
            array_keys($projectCreationDates),
            array_keys($fileUploadDates),
            array_keys($checkDates)
        ));
        
        foreach ($allDates as $date) {
            $projectCount = $projectCreationDates[$date] ?? 0;
            $fileCount = $fileUploadDates[$date] ?? 0;
            $checkCount = $checkDates[$date] ?? 0;
            
            $activityData[] = [
                'date' => $date,
                'count' => $projectCount + $fileCount + $checkCount
            ];
        }
        
        return view('partner.dashboard', compact(
            'totalProjects',
            'activeProjects',
            'completedProjects',
            'totalAmount',
            'totalFiles',
            'recentProjects',
            'statusStats',
            'objectTypeStats',
            'objectTypeColors',
            'projectChartLabels',
            'projectChartActive',
            'projectChartCompleted',
            'financeChartLabels',
            'financeChartWork',
            'financeChartMaterials',
            'activityData'
        ));
    }

    /**
     * Показать страницу профиля партнера.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function profile()
    {
        $user = Auth::user();
        
        return view('partner.profile.index', compact('user'));
    }
}
