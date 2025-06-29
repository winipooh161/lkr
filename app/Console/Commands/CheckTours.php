<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckTours extends Command
{
    /**
     * Название команды.
     *
     * @var string
     */
    protected $signature = 'tours:check {userId? : ID пользователя (опционально)}';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Проверить статус туров для пользователя';

    /**
     * Выполнение команды.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->argument('userId');
        
        if (!$userId) {
            $this->error('Необходимо указать ID пользователя');
            return 1;
        }
        
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Пользователь с ID {$userId} не найден");
            return 1;
        }
        
        $this->info("Проверка туров для пользователя: {$user->name} (#{$user->id})");
        $this->info("Роль: {$user->role}");
        
        $completedTours = $user->completedTours()->get();
        
        if ($completedTours->isEmpty()) {
            $this->info("Пользователь не завершил ни одного тура");
            return 0;
        }
        
        $this->info("Завершенные туры:");
        
        $headers = ['ID', 'Ключ тура', 'Дата завершения'];
        $rows = [];
        
        foreach ($completedTours as $tour) {
            $rows[] = [
                $tour->id,
                $tour->tour_key,
                $tour->created_at->format('d.m.Y H:i:s')
            ];
        }
        
        $this->table($headers, $rows);
        
        return 0;
    }
}
