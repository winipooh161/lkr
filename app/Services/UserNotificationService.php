<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserNotificationService
{
    /**
     * Отправляет уведомление пользователю
     *
     * @param User $user
     * @param string $subject
     * @param string $message
     * @param string $channel email|sms|both
     * @return bool
     */
    public function sendNotification(User $user, string $subject, string $message, string $channel = 'email')
    {
        $success = false;
        
        if ($channel === 'email' || $channel === 'both') {
            $success = $this->sendEmail($user, $subject, $message);
        }
        
        if ($channel === 'sms' || $channel === 'both') {
            $smsSuccess = $this->sendSms($user, $message);
            // Если хотя бы один из каналов сработал, считаем успешным
            $success = $success || $smsSuccess;
        }
        
        return $success;
    }
    
    /**
     * Отправляет Email пользователю
     *
     * @param User $user
     * @param string $subject
     * @param string $message
     * @return bool
     */
    protected function sendEmail(User $user, string $subject, string $message)
    {
        if (empty($user->email)) {
            Log::warning('Попытка отправки email пользователю без email', [
                'user_id' => $user->id,
                'subject' => $subject
            ]);
            return false;
        }
        
        try {
            // Отправляем email через Laravel Mail
            Mail::to($user->email)->send(new \App\Mail\UserNotification($subject, $message));
            
            Log::info('Отправка email уведомления пользователю', [
                'user_id' => $user->id,
                'email' => $user->email,
                'subject' => $subject
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Ошибка при отправке email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Отправляет СМС пользователю
     *
     * @param User $user
     * @param string $message
     * @return bool
     */
    protected function sendSms(User $user, string $message)
    {
        if (empty($user->phone)) {
            Log::warning('Попытка отправки SMS пользователю без номера телефона', [
                'user_id' => $user->id
            ]);
            return false;
        }
        
        try {
            // Используем наш сервис для отправки SMS
            $smsService = new SmsService();
            $result = $smsService->send($user->phone, $message);
            
            Log::info('Результат отправки SMS уведомления пользователю', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'message_length' => mb_strlen($message),
                'success' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Ошибка при отправке SMS', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Массовая отправка уведомлений группе пользователей
     *
     * @param array $userIds
     * @param string $subject
     * @param string $message
     * @param string $channel
     * @return array
     */
    public function sendBulkNotifications(array $userIds, string $subject, string $message, string $channel = 'email')
    {
        $users = User::whereIn('id', $userIds)->get();
        $successCount = 0;
        $failCount = 0;
        
        foreach ($users as $user) {
            if ($this->sendNotification($user, $subject, $message, $channel)) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
        
        Log::info('Массовая рассылка уведомлений', [
            'total' => count($users),
            'success' => $successCount,
            'fail' => $failCount
        ]);
        
        return [
            'total' => count($users),
            'success' => $successCount,
            'fail' => $failCount
        ];
    }
}
