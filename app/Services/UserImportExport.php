<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserImportExport
{
    /**
     * Импортирует пользователей из CSV файла
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array
     */
    public function import($file)
    {
        $path = $file->getRealPath();
        $records = array_map('str_getcsv', file($path));
        
        // Проверяем заголовки
        $headers = array_shift($records);
        $requiredHeaders = ['name', 'email', 'role', 'phone'];
        $missingHeaders = array_diff($requiredHeaders, $headers);
        
        if (!empty($missingHeaders)) {
            return [
                'success' => false,
                'message' => 'Отсутствуют обязательные столбцы: ' . implode(', ', $missingHeaders),
                'imported' => 0
            ];
        }
        
        $imported = 0;
        $errors = [];
        
        foreach ($records as $index => $record) {
            if (count($record) !== count($headers)) {
                $errors[] = "Строка " . ($index + 2) . " содержит неверное количество столбцов";
                continue;
            }
            
            $userData = array_combine($headers, $record);
            
            // Валидация данных
            $validator = Validator::make($userData, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'nullable|string|max:20',
                'role' => 'required|in:admin,partner,client,estimator',
            ]);
            
            if ($validator->fails()) {
                $errors[] = "Строка " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                continue;
            }
            
            // Генерируем случайный пароль
            $password = Str::random(10);
            
            try {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'phone' => $userData['phone'] ?? null,
                    'role' => $userData['role'],
                    'password' => Hash::make($password),
                    'is_active' => true,
                    'partner_id' => ($userData['role'] === 'estimator' && isset($userData['partner_id'])) 
                        ? $userData['partner_id'] 
                        : null,
                ]);
                
                // Здесь в реальном проекте можно отправить уведомление с паролем пользователю
                // Mail::to($user->email)->send(new UserCreated($user, $password));
                
                Log::info('Импортирован пользователь', [
                    'id' => $user->id, 
                    'email' => $user->email,
                    'random_password' => $password,
                ]);
                
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Строка " . ($index + 2) . ": " . $e->getMessage();
                Log::error('Ошибка при импорте пользователя', [
                    'userData' => $userData,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'success' => $imported > 0,
            'message' => $imported > 0 
                ? "Успешно импортировано {$imported} пользователей" 
                : "Не удалось импортировать пользователей",
            'imported' => $imported,
            'errors' => $errors,
        ];
    }
    
    /**
     * Экспортирует пользователей в CSV файл
     *
     * @param bool $template Если true, возвращает только шаблон для импорта без данных
     * @return string
     */
    public function exportCsv($template = false)
    {
        $headers = ['name', 'email', 'phone', 'role', 'partner_id'];
        
        $output = fopen('php://temp', 'w');
        fputcsv($output, $headers);
        
        if (!$template) {
            $users = User::all();
            
            foreach ($users as $user) {
                fputcsv($output, [
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->role,
                    $user->partner_id,
                ]);
            }
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Экспортирует пользователей в XLSX файл (имитация для примера, в реальном проекте используется библиотека)
     *
     * @param bool $template Если true, возвращает только шаблон для импорта без данных
     * @return string
     */
    public function exportXlsx($template = false)
    {
        // В реальном проекте здесь будет код для экспорта в XLSX
        // Для простоты примера возвращаем CSV
        return $this->exportCsv($template);
    }
    
    /**
     * Генерирует шаблон для импорта
     *
     * @param string $format 'csv' или 'xlsx'
     * @return string
     */
    public function generateTemplate($format = 'csv')
    {
        if ($format === 'xlsx') {
            return $this->exportXlsx(true);
        }
        
        return $this->exportCsv(true);
    }
}
