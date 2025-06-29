<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyToursMigration extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'tours:migrate';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Применить миграцию для таблицы user_completed_tours';

    /**
     * Выполнение консольной команды.
     */
    public function handle()
    {
        $this->info('Проверка существования таблицы user_completed_tours...');
        
        try {
            if (!$this->tableExists('user_completed_tours')) {
                $this->info('Таблица user_completed_tours не существует. Создаём её...');
                
                // Создаем таблицу
                DB::statement('
                    CREATE TABLE user_completed_tours (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        user_id BIGINT UNSIGNED NOT NULL,
                        tour_key VARCHAR(255) NOT NULL,
                        created_at TIMESTAMP NULL DEFAULT NULL,
                        updated_at TIMESTAMP NULL DEFAULT NULL,
                        CONSTRAINT user_completed_tours_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                        UNIQUE KEY user_completed_tours_user_id_tour_key_unique (user_id, tour_key)
                    )
                ');
                
                $this->info('Таблица user_completed_tours успешно создана.');
            } else {
                $this->info('Таблица user_completed_tours уже существует.');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Произошла ошибка при создании таблицы: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Проверить существование таблицы в базе данных.
     *
     * @param  string  $tableName
     * @return bool
     */
    protected function tableExists($tableName)
    {
        return DB::getSchemaBuilder()->hasTable($tableName);
    }
}
