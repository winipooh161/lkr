<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectFileController extends Controller
{
    /**
     * Загрузить новый файл для проекта.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Project $project)
    {
        // Логируем информацию о попытке загрузки файла
        \Illuminate\Support\Facades\Log::debug('Попытка загрузки файла для проекта', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role,
            'project_id' => $project->id,
            'project_partner_id' => $project->partner_id ?? 'unknown'
        ]);
        
        // Проверяем права доступа
        $this->authorize('update', $project);

        // Валидация запроса
        $request->validate([
            'file' => 'required|file|max:10240', // Максимум 10MB
            'file_type' => 'required|in:design,scheme,document,contract,other',
            'description' => 'nullable|string|max:255',
            'document_type' => 'nullable|string|max:100',
        ]);

        // Получаем файл из запроса
        $file = $request->file('file');
        
        // Генерируем уникальное имя файла
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Сохраняем файл в хранилище
        $path = $file->storeAs(
            'project_files/' . $project->id,
            $filename,
            'public'
        );
        
        if (!$path) {
            return response()->json(['error' => 'Не удалось загрузить файл.'], 500);
        }
        
        // Создаем запись в базе данных
        $projectFile = new ProjectFile([
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'file_type' => $request->file_type,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => $request->description,
            'document_type' => $request->document_type ?? 'other', // Если document_type не указан, используем 'other'
        ]);
        
        $project->files()->save($projectFile);
        
        // Возвращаем успешный ответ с данными файла
        return response()->json([
            'file' => $projectFile,
            'success' => 'Файл успешно загружен',
        ]);
    }

    /**
     * Скачать файл проекта.
     *
     * @param  \App\Models\ProjectFile  $projectFile
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Project $project, ProjectFile $file)
    {
        // Логируем информацию о попытке скачивания файла
        \Illuminate\Support\Facades\Log::debug('Попытка скачивания файла проекта', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role,
            'project_id' => $project->id,
            'project_file_id' => $file->id,
            'project_partner_id' => $project->partner_id ?? 'unknown'
        ]);
        
        // Проверяем, что файл принадлежит проекту
        if ($file->project_id !== $project->id) {
            abort(404, 'Файл не найден или не принадлежит данному проекту.');
        }
        
        // Проверяем права доступа на просмотр проекта
        $this->authorize('view', $project);
        
        $path = storage_path('app/public/project_files/' . $file->project_id . '/' . $file->filename);
        
        if (!file_exists($path)) {
            abort(404, 'Файл не найден на диске.');
        }
        
        return response()->download($path, $file->original_name);
    }

    /**
     * Удалить файл проекта.
     *
     * @param  \App\Models\ProjectFile  $projectFile
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(Project $project, ProjectFile $file)
    {
        // Логируем информацию о попытке удаления файла
        \Illuminate\Support\Facades\Log::debug('Попытка удаления файла проекта', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role,
            'project_id' => $project->id,
            'project_file_id' => $file->id,
            'project_partner_id' => $project->partner_id ?? 'unknown'
        ]);
        
        // Проверяем, что файл принадлежит проекту
        if ($file->project_id !== $project->id) {
            abort(404, 'Файл не найден или не принадлежит данному проекту.');
        }
        
        // Проверяем права доступа
        $this->authorize('update', $project);
        
        // Получаем тип файла перед удалением
        $fileType = $file->file_type;
        
        // Удаляем файл из хранилища
        Storage::disk('public')->delete('project_files/' . $project->id . '/' . $file->filename);
        
        // Удаляем запись из базы данных
        $file->delete();
        
        // Если запрос AJAX, возвращаем JSON
        if (request()->ajax()) {
            return response()->json(['success' => 'Файл успешно удален']);
        }
        
        // Иначе перенаправляем обратно с сообщением
        return redirect()->back()->with('success', 'Файл успешно удален');
    }

    /**
     * Показать информацию о файле проекта.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\ProjectFile  $file
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function show(Project $project, ProjectFile $file)
    {
        // Логируем информацию о попытке просмотра файла
        \Illuminate\Support\Facades\Log::debug('Попытка просмотра файла проекта', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role,
            'project_id' => $project->id,
            'project_file_id' => $file->id,
            'project_partner_id' => $project->partner_id ?? 'unknown'
        ]);
        
        // Проверяем, что файл принадлежит проекту
        if ($file->project_id !== $project->id) {
            abort(404, 'Файл не найден или не принадлежит данному проекту.');
        }
        
        // Проверяем права доступа на просмотр проекта
        $this->authorize('view', $project);
        
        // Возвращаем представление с информацией о файле или JSON, в зависимости от запроса
        if (request()->ajax()) {
            return response()->json([
                'file' => $file,
                'downloadUrl' => route('partner.project-files.download', ['project' => $project->id, 'file' => $file->id])
            ]);
        }
        
        return view('partner.projects.files.show', [
            'project' => $project,
            'file' => $file
        ]);
    }
}
