<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Partner\ProjectCalendarController as PartnerProjectCalendarController;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ProjectCalendarController extends Controller
{
    /**
     * Получает календарный вид для клиента
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */    public function getCalendarView(Request $request, Project $project)
    {
        // Добавляем логирование для отслеживания запросов
        \Illuminate\Support\Facades\Log::info('Client Calendar View API called', [
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ]);
          // Добавляем дополнительное логирование для отладки
        \Illuminate\Support\Facades\Log::info('Client Calendar Authorization Check', [
            'is_admin' => $request->user()->isAdmin(),
            'user_phone' => $request->user()->phone,
            'project_phone' => $project->phone,
        ]);

        // Проверяем, что проект принадлежит клиенту или пользователь - админ
        if (!$request->user()->isAdmin() && $project->phone !== $request->user()->phone) {
            \Illuminate\Support\Facades\Log::warning('Unauthorized access to calendar view', [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'user_phone' => $request->user()->phone,
                'project_phone' => $project->phone,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'У вас нет доступа к этому объекту.'
            ], 403);
        }
        
        // Используем тот же метод, что и в партнерском контроллере, путем создания экземпляра
        $partnerController = App::make(PartnerProjectCalendarController::class);
        
        // Вызываем метод из партнерского контроллера
        return $partnerController->getCalendarView($request, $project);
    }
}
