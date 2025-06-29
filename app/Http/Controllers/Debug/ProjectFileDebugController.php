<?php

namespace App\Http\Controllers\Debug;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectFileDebugController extends Controller
{
    /**
     * Страница для отладки загрузки файлов проекта
     */
    public function index()
    {
        // Получаем все проекты
        $projects = Project::with('files')->latest()->take(5)->get();
        
        // Проверяем наличие файлов в хранилище
        foreach ($projects as $project) {
            foreach ($project->files as $file) {
                $file->exists = Storage::disk('public')->exists($file->path);
                // Формируем URL для файла
                $file->url = asset('storage/' . $file->path);
            }
        }
        
        return view('debug.project-files', compact('projects'));
    }
}
