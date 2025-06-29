<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsService;

class TestSmsNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test {phone} {--type=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование SMS уведомлений';

    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $type = $this->option('type');

        $this->info("Тестирование SMS уведомлений для номера: {$phone}");
        
        if ($type === 'all' || $type === 'employee') {
            $this->info("\n=== Тест: Уведомление новому сотруднику ===");
            $result = $this->smsService->sendEmployeeNotification(
                $phone,
                'ООО "Тест Партнер"',
                'сметчик',
                true
            );
            $this->info("Результат: " . ($result ? 'Успешно' : 'Ошибка'));
        }

        if ($type === 'all' || $type === 'project') {
            $this->info("\n=== Тест: Уведомление клиенту о проекте ===");
            $result = $this->smsService->sendProjectNotificationToClient(
                $phone,
                'Иван Иванов',
                'г. Москва, ул. Тестовая, д. 1',
                'repair',
                'ООО "Тест Партнер"'
            );
            $this->info("Результат: " . ($result ? 'Успешно' : 'Ошибка'));
        }

        if ($type === 'all' || $type === 'estimate') {
            $this->info("\n=== Тест: Уведомление партнеру о смете ===");
            $result = $this->smsService->sendEstimateNotificationToPartner(
                $phone,
                'Петр Сметчиков',
                'Смета на ремонт кухни',
                'Иван Иванов (г. Москва, ул. Тестовая, д. 1)'
            );
            $this->info("Результат: " . ($result ? 'Успешно' : 'Ошибка'));
        }

        $this->info("\n=== Тестирование завершено ===");
    }
}
