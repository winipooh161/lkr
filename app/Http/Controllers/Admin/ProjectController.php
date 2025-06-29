<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * Отображает список всех проектов.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $query = Project::query();
        
        // Фильтрация по статусу
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        
        // Фильтрация по партнеру
        if ($request->has('partner_id') && $request->partner_id != 'all') {
            $query->where('partner_id', $request->partner_id);
        }
        
        // Фильтрация по клиенту
        if ($request->has('client_id') && $request->client_id != 'all') {
            $query->where('client_id', $request->client_id);
        }
        
        // Поиск по названию проекта
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // Сортировка
        $sortField = $request->sort ?? 'created_at';
        $sortDirection = $request->direction ?? 'desc';
        $query->orderBy($sortField, $sortDirection);
        
        $projects = $query->with(['client', 'partner'])->paginate(15);
        $projects->appends($request->query());
        
        // Получение списка партнеров и клиентов для фильтров
        $partners = User::where('role', 'partner')->orderBy('name')->get();
        $clients = User::where('role', 'client')->orderBy('name')->get();
        
        // Статистика проектов
        $stats = [
            'total' => Project::count(),
            'by_status' => [
                'new' => Project::where('status', 'new')->count(),
                'in_progress' => Project::where('status', 'in_progress')->count(),
                'completed' => Project::where('status', 'completed')->count(),
            ],
            'this_month' => Project::whereMonth('created_at', now()->month)->count(),
            'last_month' => Project::whereMonth('created_at', now()->subMonth()->month)->count(),
        ];
        
        return view('admin.projects.index', compact('projects', 'partners', 'clients', 'stats'));
    }

    /**
     * Отображает информацию о конкретном проекте.
     *
     * @param Project $project
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Project $project)
    {
        $project->load(['client', 'partner', 'estimates', 'files']);
        
        return view('admin.projects.show', compact('project'));
    }
}
