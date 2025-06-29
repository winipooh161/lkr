<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EstimateController extends Controller
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
     * Скачать файл сметы
     *
     * @param  \App\Models\Estimate  $estimate
     * @return \Illuminate\Http\Response
     */
    public function download(Estimate $estimate)
    {
        // Проверяем, что смета принадлежит проекту клиента
        $project = Project::findOrFail($estimate->project_id);
        
        if ($project->phone !== auth()->user()->phone) {
            abort(403, 'У вас нет доступа к этой смете');
        }
        
        // Проверяем, что файл сметы существует
        if (!$estimate->file_path || !Storage::disk('public')->exists($estimate->file_path)) {
            abort(404, 'Файл сметы не найден');
        }
        
        // Скачиваем файл
        return Storage::disk('public')->download($estimate->file_path, $estimate->file_name ?? 'Смета.xlsx');
    }
}
