<?php

namespace App\Console\Commands;

use App\Models\UserCompletedTour;
use Illuminate\Console\Command;

class ResetTours extends Command
{
    /**
     * Название команды.
     *
     * @var string
     */
    protected $signature = 'tours:reset {userId? : ID пользователя (опционально)}';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Сбросить прогресс туров для пользователя или всех пользователей';

    /**
     * Выполнение команды.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->argument('userId');
        
        if ($userId) {
            // Сброс туров для конкретного пользователя
            $count = UserCompletedTour::where('user_id', $userId)->delete();
            $this->info("Сброшено {$count} туров для пользователя #{$userId}");
        } else {
            // Подтверждение перед сбросом для всех пользователей
            if ($this->confirm('Вы действительно хотите сбросить туры для ВСЕХ пользователей?')) {
                UserCompletedTour::truncate();
                $this->info('Все туры для всех пользователей сброшены');
            } else {
                $this->info('Операция отменена');
                return 0;
            }
        }
        
        return 0;
    }
}
