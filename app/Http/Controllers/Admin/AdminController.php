<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Project;
use App\Models\Estimate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Показать панель управления администратора с аналитикой.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Статистика пользователей по ролям
        try {
            $usersByRole = User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->get();
                
            // Активные пользователи
            $activeUsers = User::where('is_active', true)->count();
            $inactiveUsers = User::where('is_active', false)->count();
            
            // Статистика по регистрациям за последние 30 дней
            $newUsersLastMonth = User::where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();
                
            // Статистика по активности
            $recentlyActiveUsers = User::where('updated_at', '>=', Carbon::now()->subDays(7))
                ->count();
        } catch(\Exception $e) {
            $usersByRole = collect([
                (object)['role' => 'admin', 'count' => 1],
                (object)['role' => 'client', 'count' => 0],
                (object)['role' => 'partner', 'count' => 0],
                (object)['role' => 'estimator', 'count' => 0]
            ]);
            $activeUsers = 1;
            $inactiveUsers = 0;
            $newUsersLastMonth = 0;
            $recentlyActiveUsers = 1;
            
            \Illuminate\Support\Facades\Log::error('Error fetching user statistics: ' . $e->getMessage());
        }
        
        // Статистика проектов
        try {
            $totalProjects = Project::count();
            $projectsMonthly = Project::select(
                    DB::raw('MONTH(created_at) as month'), 
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereYear('created_at', date('Y'))
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
                
            // Статистика по статусам проектов
            $projectsByStatus = Project::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
                
            // Проекты за текущий месяц
            $projectsThisMonth = Project::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();
                
            // Проекты за прошлый месяц
            $projectsLastMonth = Project::whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->count();
                
            // Рассчитываем рост проектов в процентах
            $projectGrowthRate = $projectsLastMonth > 0 
                ? round((($projectsThisMonth - $projectsLastMonth) / $projectsLastMonth) * 100, 1)
                : 100;
        } catch(\Exception $e) {
            $totalProjects = 0;
            $projectsMonthly = collect([]);
            $projectsByStatus = ['new' => 0, 'in_progress' => 0, 'completed' => 0];
            $projectsThisMonth = 0;
            $projectsLastMonth = 0;
            $projectGrowthRate = 0;
            
            \Illuminate\Support\Facades\Log::error('Error fetching project statistics: ' . $e->getMessage());
        }
            
        // Преобразуем данные для графиков
        $chartLabels = [];
        $chartData = [];
        $monthNames = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];
        
        foreach($projectsMonthly as $stat) {
            $chartLabels[] = $monthNames[$stat->month];
            $chartData[] = $stat->count;
        }
        
        // Последние проекты
        try {
            $latestProjects = Project::with('client')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } catch(\Exception $e) {
            $latestProjects = collect([]);
            \Illuminate\Support\Facades\Log::error('Error fetching latest projects: ' . $e->getMessage());
        }
        
        // Финансовая статистика
        try {
            // Общая сумма
            $totalEstimateValue = Estimate::sum('total_amount') ?? 0;
            
            // Данные по месяцам для финансового графика
            $monthlyRevenue = Estimate::select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('SUM(total_amount) as total')
                )
                ->whereYear('created_at', date('Y'))
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
                
            $revenueData = [];
            foreach($monthlyRevenue as $item) {
                $revenueData[$monthNames[$item->month]] = $item->total;
            }
            
            // Средняя стоимость проекта (всегда округляем до целых для отображения в интерфейсе)
            $avgProjectValue = $totalProjects > 0 ? round($totalEstimateValue / $totalProjects, 0) : 0;
            
            // Максимальная стоимость проекта
            $maxEstimateValue = Estimate::max('total_amount') ?? 0;
            
            // Минимальная стоимость проекта
            $minEstimateValue = Estimate::min('total_amount') ?? 0;
        } catch(\Exception $e) {
            $totalEstimateValue = 0;
            $revenueData = [];
            $avgProjectValue = 0;
            $maxEstimateValue = 0;
            $minEstimateValue = 0;
            \Illuminate\Support\Facades\Log::error('Error calculating financial statistics: ' . $e->getMessage());
        }

        // Статистика по партнерам
        try {
            $partnerStats = User::where('role', 'partner')
                ->withCount('projects')
                ->orderBy('projects_count', 'desc')
                ->limit(5)
                ->get();
                
            // Топ-5 партнеров для таблицы
            $topPartners = $partnerStats;
                
            // Самый активный партнер текущего месяца (партнер месяца)
            $partnerOfTheMonth = DB::table('projects')
                ->join('users', 'projects.partner_id', '=', 'users.id')
                ->select('users.id', 'users.name', 'users.avatar', DB::raw('count(*) as projects_count'))
                ->whereMonth('projects.created_at', Carbon::now()->month)
                ->whereYear('projects.created_at', Carbon::now()->year)
                ->groupBy('users.id', 'users.name', 'users.avatar')
                ->orderBy('projects_count', 'desc')
                ->first();
                
            // Самый активный партнер текущего месяца (для других статистик)
            $topPartnerThisMonth = $partnerOfTheMonth;
                
            // Общее количество партнеров
            $totalPartners = User::where('role', 'partner')->count();
                
            // Коэффициент активности партнеров (% активных)
            $activePartnersRate = $totalPartners > 0 
                ? round((User::where('role', 'partner')->where('is_active', true)->count() / $totalPartners) * 100) 
                : 0;
        } catch(\Exception $e) {
            $partnerStats = collect([]);
            $topPartnerThisMonth = null;
            $totalPartners = 0;
            $activePartnersRate = 0;
            \Illuminate\Support\Facades\Log::error('Error fetching partner statistics: ' . $e->getMessage());
        }
        
        // Системная информация
        $systemInfo = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'cache_driver' => config('cache.default'),
            'db_connection' => config('database.default'),
            'server_time' => now(),
            'storage_usage' => [
                'total' => disk_total_space(base_path()),
                'free' => disk_free_space(base_path()),
            ],
        ];
            
        return view('admin.dashboard', compact(
            'usersByRole', 
            'activeUsers', 
            'inactiveUsers',
            'newUsersLastMonth',
            'recentlyActiveUsers',
            'totalProjects', 
            'chartLabels', 
            'chartData',
            'projectsByStatus',
            'projectsThisMonth',
            'projectsLastMonth',
            'projectGrowthRate',
            'latestProjects',
            'totalEstimateValue',
            'revenueData',
            'avgProjectValue',
            'maxEstimateValue',
            'minEstimateValue',
            'partnerStats',
            'topPartnerThisMonth',
            'totalPartners',
            'activePartnersRate',
            'systemInfo'
        ));
    }
}
