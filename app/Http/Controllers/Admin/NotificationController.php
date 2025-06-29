<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\UserNotificationService;

class NotificationController extends Controller
{
    /**
     * Отображает форму для массовой отправки уведомлений
     */
    public function showForm()
    {
        // Получаем статистику по пользователям для выбора групп
        $stats = [
            'total' => User::count(),
            'by_role' => [
                'admins' => User::where('role', 'admin')->count(),
                'partners' => User::where('role', 'partner')->count(),
                'clients' => User::where('role', 'client')->count(),
                'estimators' => User::where('role', 'estimator')->count(),
            ],
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];
        
        return view('admin.notifications.send', compact('stats'));
    }
    
    /**
     * Отправляет массовые уведомления выбранным пользователям
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'channel' => ['required', 'string', 'in:email,sms,both'],
            'target_type' => ['required', 'string', 'in:all,role,selected'],
            'role' => ['required_if:target_type,role', 'nullable', 'string', 'in:admin,partner,client,estimator'],
            'user_ids' => ['required_if:target_type,selected', 'nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);
        
        $query = User::query();
        
        // Определяем целевую аудиторию
        switch ($validated['target_type']) {
            case 'role':
                $query->where('role', $validated['role']);
                break;
                
            case 'selected':
                $query->whereIn('id', $validated['user_ids'] ?? []);
                break;
                
            case 'all':
            default:
                // Все пользователи
                break;
        }
        
        // Получаем список ID пользователей
        $userIds = $query->pluck('id')->toArray();
        
        if (empty($userIds)) {
            return redirect()->back()
                ->with('error', 'Не выбраны пользователи для отправки уведомления');
        }
        
        // Отправляем уведомления
        $notificationService = new UserNotificationService();
        $result = $notificationService->sendBulkNotifications(
            $userIds,
            $validated['subject'],
            $validated['message'],
            $validated['channel']
        );
        
        $message = sprintf(
            'Уведомление отправлено %d из %d пользователей', 
            $result['success'], 
            $result['total']
        );
        
        return redirect()->back()
            ->with('success', $message);
    }
}
