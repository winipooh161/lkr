<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Partner\ProjectDocuments\ActFlIpController;
use App\Http\Controllers\Partner\ProjectDocuments\ActIpIpController;
use App\Http\Controllers\Partner\ProjectDocuments\BsoController;
use App\Http\Controllers\Partner\ProjectDocuments\CompletionActFlIpController;
use App\Http\Controllers\Partner\ProjectDocuments\CompletionActIpIpController;
use App\Http\Controllers\Partner\ProjectDocuments\InvoiceFlController;
use App\Http\Controllers\Partner\ProjectDocuments\InvoiceIpController;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для работы с документами проектов
 * 
 * Делегирует обработку запросов специализированным контроллерам для каждого типа документа
 */
class ProjectDocumentController extends Controller
{
    /**
     * Генерирует документ для скачивания
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function generateDocument(Request $request, Project $project)
    {
        // Логирование запроса для отладки
        Log::info('Запрос на генерацию документа', [
            'user_id' => $request->user()->id ?? 'unknown',
            'project_id' => $project->id,
            'request_data' => $request->all()
        ]);
        
        $request->validate([
            'document_type' => 'required|string',
            'format' => 'required|in:pdf,docx',
            'include_signature' => 'nullable',
            'include_stamp' => 'nullable',
        ]);
        
        $documentType = $request->document_type;
        $format = $request->format;
        
        // Корректная обработка checkbox из формы (улучшенная версия)
        $includeSignature = filter_var(
            $request->input('include_signature'), 
            FILTER_VALIDATE_BOOLEAN, 
            FILTER_NULL_ON_FAILURE
        ) ?? false;
        
        $includeStamp = filter_var(
            $request->input('include_stamp'), 
            FILTER_VALIDATE_BOOLEAN, 
            FILTER_NULL_ON_FAILURE
        ) ?? false;
        
        // Логирование обработанных параметров
        Log::info('Обработанные параметры документа', [
            'document_type' => $documentType,
            'format' => $format,
            'include_signature' => $includeSignature,
            'include_stamp' => $includeStamp
        ]);
        
        $user = Auth::user();
        $partner = $user->role === 'admin' ? $project->partner : $user;
        
        if (!$partner) {
            return response()->json(['error' => 'Партнер не найден'], 404);
        }
        
        // Делегируем обработку специализированным контроллерам
        switch ($documentType) {
            case 'completion_act_ip_ip':
                $controller = new CompletionActIpIpController();
                return $controller->generate($request, $project);
                
            case 'completion_act_fl_ip':
                $controller = new CompletionActFlIpController();
                return $controller->generate($request, $project);
                
            case 'act_ip_ip':
                $controller = new ActIpIpController();
                return $controller->generate($request, $project);
                
            case 'act_fl_ip':
                $controller = new ActFlIpController();
                return $controller->generate($request, $project);
                
            case 'bso':
                $controller = new BsoController();
                return $controller->generate($request, $project);
                
            case 'invoice_ip':
                $controller = new InvoiceIpController();
                return $controller->generate($request, $project);
                
            case 'invoice_fl':
                $controller = new InvoiceFlController();
                return $controller->generate($request, $project);
                
            default:
                return response()->json(['error' => 'Неизвестный тип документа'], 400);
        }
    }
    
    /**
     * Генерирует предпросмотр документа
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function previewDocument(Request $request, Project $project)
    {
        $request->validate([
            'document_type' => 'required|string',
            'include_signature' => 'nullable|boolean',
            'include_stamp' => 'nullable|boolean',
        ]);
        
        // Возвращаем HTML содержимое документа для предпросмотра
        $documentType = $request->document_type;
        // Корректная обработка checkbox из формы
        $includeSignatureValue = $request->input('include_signature');
        $includeSignature = $includeSignatureValue === null ? false : filter_var($includeSignatureValue, FILTER_VALIDATE_BOOLEAN);
        
        $includeStampValue = $request->input('include_stamp');
        $includeStamp = $includeStampValue === null ? false : filter_var($includeStampValue, FILTER_VALIDATE_BOOLEAN);
        
        $user = Auth::user();
        $partner = $user->role === 'admin' ? $project->partner : $user;
        
        if (!$partner) {
            return response()->json(['error' => 'Партнер не найден'], 404);
        }
        
        // Делегируем обработку специализированным контроллерам
        switch ($documentType) {
            case 'completion_act_ip_ip':
                $controller = new CompletionActIpIpController();
                break;
                
            case 'completion_act_fl_ip':
                $controller = new CompletionActFlIpController();
                break;
                
            case 'act_ip_ip':
                $controller = new ActIpIpController();
                break;
                
            case 'act_fl_ip':
                $controller = new ActFlIpController();
                break;
                
            case 'bso':
                $controller = new BsoController();
                break;
                
            case 'invoice_ip':
                $controller = new InvoiceIpController();
                break;
                
            case 'invoice_fl':
                $controller = new InvoiceFlController();
                break;
                
            default:
                return response()->json(['error' => 'Неизвестный тип документа'], 400);
        }
        
        // Используем метод previewDocument из базового контроллера
        return $controller->previewDocument($request, $project);
    }
}
