<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SmsService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = '6CDCE0B0-6091-278C-5145-360657FF0F9B'; // API ключ SMS.ru
        $this->apiUrl = 'https://sms.ru/sms/send';
    }

    /**
     * Генерация кода подтверждения
     * 
     * @return int
     */
    public function generateCode(): int
    {
        return rand(1000, 9999);
    }

    /**
     * Сохранение кода для телефона в кеше
     * 
     * @param string $phone
     * @param int $code
     * @param int $minutes время жизни кода в минутах
     * @return void
     */
    public function saveCode(string $phone, int $code, int $minutes = 5): void
    {
        Cache::put("sms_code:{$phone}", $code, now()->addMinutes($minutes));
    }

    /**
     * Проверка кода
     * 
     * @param string $phone
     * @param int $code
     * @return bool
     */
    public function verifyCode(string $phone, int $code): bool
    {
        $savedCode = Cache::get("sms_code:{$phone}");
        return $savedCode && (int)$savedCode === (int)$code;
    }

    /**
     * Отправка SMS через сервис sms.ru
     * 
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function send(string $phone, string $message): bool
    {
        try {
            // Очищаем номер от лишних символов
            $phone = $this->cleanPhone($phone);

            // Отправляем запрос к API
            $response = Http::get($this->apiUrl, [
                'api_id' => $this->apiKey,
                'to' => $phone,
                'msg' => $message,
                'json' => 1
            ]);

            $result = $response->json();
            
            // Логируем результат
            Log::info('SMS sending result', ['phone' => $phone, 'response' => $result]);
            
            // Проверяем успешность отправки
            return isset($result['status']) && $result['status'] === 'OK';
        } catch (\Exception $e) {
            Log::error('Error sending SMS: ' . $e->getMessage(), ['phone' => $phone]);
            return false;
        }
    }

    /**
     * Отправка SMS с кодом подтверждения
     * 
     * @param string $phone
     * @return int|null код подтверждения или null в случае ошибки
     */
    public function sendVerificationCode(string $phone): ?int
    {
        // Генерируем код
        $code = $this->generateCode();
        
        // Сохраняем код в кеше
        $this->saveCode($phone, $code);
        
        // Отправляем SMS
        $message = "Ваш код подтверждения: {$code}";
        $sent = $this->send($phone, $message);
        
        return $sent ? $code : null;
    }

    /**
     * Отправка SMS новому сотруднику о том, что его добавили к партнеру
     * 
     * @param string $phone номер телефона сотрудника
     * @param string $partnerName имя партнера
     * @param string $employeeRole роль сотрудника
     * @param bool $isNewUser создан ли новый пользователь
     * @return bool
     */
    public function sendEmployeeNotification(string $phone, string $partnerName, string $employeeRole = 'сметчик', bool $isNewUser = false): bool
    {
        $baseMessage = "Вы добавлены как {$employeeRole} к партнеру: {$partnerName}. ";
        
        if ($isNewUser) {
            $baseMessage .= "Ваш пароль: password123. ";
        }
        
        $loginUrl = config('app.url') . '/login';
        $message = $baseMessage . "Войти в систему: {$loginUrl}";
        
        return $this->send($phone, $message);
    }
    
    /**
     * Отправка SMS клиенту о создании объекта/сделки
     * 
     * @param string $phone номер телефона клиента
     * @param string $clientName имя клиента
     * @param string $address адрес объекта
     * @param string $workType тип работ
     * @param string $partnerName имя партнера
     * @return bool
     */
    public function sendProjectNotificationToClient(string $phone, string $clientName, string $address, string $workType, string $partnerName): bool
    {
        $workTypeNames = [
            'repair' => 'ремонт',
            'design' => 'дизайн',
            'construction' => 'строительство'
        ];
        
        $workTypeName = $workTypeNames[$workType] ?? $workType;
        
        $registerUrl = config('app.url') . '/register';
        $message = "Здравствуйте, {$clientName}! Для вас создан объект по адресу: {$address} (тип работ: {$workTypeName}). ".
                   "Партнер: {$partnerName}. ".
                   "Зарегистрируйтесь для отслеживания статуса: {$registerUrl}";
        
        return $this->send($phone, $message);
    }
    
    /**
     * Отправка SMS партнеру о создании сметы сметчиком
     * 
     * @param string $phone номер телефона партнера
     * @param string $estimatorName имя сметчика
     * @param string $estimateName название сметы
     * @param string $projectInfo информация о проекте
     * @return bool
     */
    public function sendEstimateNotificationToPartner(string $phone, string $estimatorName, string $estimateName, string $projectInfo = ''): bool
    {
        $message = "Сметчик {$estimatorName} создал смету: {$estimateName}";
        
        if ($projectInfo) {
            $message .= " для проекта: {$projectInfo}";
        }
        
        $message .= ". Проверьте в личном кабинете.";
        
        return $this->send($phone, $message);
    }
    
    /**
     * Очистка номера телефона от лишних символов
     * 
     * @param string $phone
     * @return string
     */
    private function cleanPhone(string $phone): string
    {
        // Убираем все символы кроме цифр
        $phone = preg_replace('/\D/', '', $phone);
        
        // Если номер начинается с 8, заменяем на 7
        if (strlen($phone) === 11 && $phone[0] === '8') {
            $phone = '7' . substr($phone, 1);
        }
        
        return $phone;
    }
}
