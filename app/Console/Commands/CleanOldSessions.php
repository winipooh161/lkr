<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CleanOldSessions extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'session:clean';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Clean old session files';

    /**
     * Выполнить консольную команду.
     */
    public function handle()
    {
        $this->info('Начало очистки устаревших сессий...');
        
        $path = storage_path('framework/sessions');
        $files = File::files($path);
        $count = 0;
        
        // Получаем время жизни сессии из конфига и добавляем запас в 1 день
        $lifetimeInMinutes = config('session.lifetime', 4320);
        $lifetimeInDays = ceil($lifetimeInMinutes / (60 * 24)) + 1;
        
        $this->info("Удаляем сессии старше {$lifetimeInDays} дней...");
        
        foreach ($files as $file) {
            // Проверяем время последнего изменения файла
            $lastModified = Carbon::createFromTimestamp(File::lastModified($file));
            $daysDiff = $lastModified->diffInDays(Carbon::now());
            
            // Если файл не изменялся более чем время жизни сессии, удаляем его
            if ($daysDiff > $lifetimeInDays) {
                File::delete($file);
                $count++;
            }
        }
        
        $this->info("Очистка завершена. Удалено {$count} устаревших сессий.");
        
        return 0;
    }
}
