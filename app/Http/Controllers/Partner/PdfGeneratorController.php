<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;

class PdfGeneratorController extends Controller
{
    /**
     * Генерирует PDF из HTML-контента
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */    
    public function generatePdf(Request $request)
    {
        try {
            // Устанавливаем увеличенные лимиты для выполнения
            set_time_limit(300); // 5 минут
            ini_set('memory_limit', '512M');
            
            // Логируем пользователя и маршрут, чтобы понять, откуда пришел запрос
            Log::info('PDF Generation Request', [
                'user_id' => $request->user() ? $request->user()->id : 'unknown',
                'user_role' => $request->user() ? $request->user()->role : 'unauthenticated',
                'route' => $request->route() ? $request->route()->getName() : 'unknown',
                'referer' => $request->header('Referer'),
                'ip' => $request->ip()
            ]);

            $html = $request->input('html');
            $filename = $request->input('filename', 'document.pdf');
            
            // Если HTML не передан, возвращаем ошибку
            if (!$html) {
                return response()->json([
                    'success' => false,
                    'message' => 'HTML-контент не передан'
                ], 400);
            }
            
            // Логируем информацию о входящем запросе для отладки
            Log::info('PDF request info', [
                'filename' => $filename,
                'html_length' => strlen($html),
                'content_type' => $request->header('Content-Type')
            ]);
              // Настройка опций DOMPDF с улучшенными параметрами
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true); // Включаем HTML5 парсер для лучшей поддержки современного HTML
            $options->set('isRemoteEnabled', true); // Разрешаем загрузку удаленных ресурсов (изображений, стилей)
            $options->set('defaultFont', 'Arial'); // Шрифт с поддержкой кириллицы
            $options->set('isFontSubsettingEnabled', true); // Включаем подмножества шрифтов для уменьшения размера
            $options->set('isPhpEnabled', false); // Отключаем PHP для безопасности
            $options->set('dpi', 150); // Увеличиваем разрешение для лучшего качества
            $options->set('defaultMediaType', 'screen'); // Используем экранные стили
            $options->set('defaultPaperSize', 'A4'); // Устанавливаем размер бумаги A4 вместо A3
            $options->set('fontDir', [public_path('fonts')]); // Указываем директорию с шрифтами
            $options->set('fontCache', storage_path('fonts')); // Указываем кэш для шрифтов
            
            // Добавляем базовые стили для компактной таблицы
            $baseStyles = '<style>
                @page { size: A3 landscape; margin: 5mm; }
                body { font-family: "DejaVu Sans", sans-serif; font-size: 8pt; margin: 0; padding: 5px; }
                table { border-collapse: collapse; width: 100%; page-break-inside: avoid; }
                thead { display: table-header-group; }
                tbody { display: table-row-group; }
                tr { page-break-inside: avoid; height: 18px; }
                th, td { border: 1px solid #000; padding: 1px; height: 16px; overflow: hidden; }
                th:first-child, td:first-child { width: 200px; font-size: 6pt; }
                .task-cell { width: 15px; padding: 0; }
            </style>';
            
            // Добавляем стили в начало HTML
            $html = $baseStyles . $html;
            
            // Создание экземпляра DOMPDF с улучшенными настройками
            $dompdf = new Dompdf($options);
            
            // Загружаем HTML с кодировкой UTF-8
            $dompdf->loadHtml($html, 'UTF-8');
            
            // Устанавливаем альбомную ориентацию для большего пространства для календаря
            $dompdf->setPaper('A3', 'landscape');
            
            // Рендеринг PDF с увеличенным лимитом времени
            set_time_limit(120); // Увеличиваем лимит времени до 2 минут для обработки сложных документов
            $dompdf->render();
              // Получение содержимого PDF
            $output = $dompdf->output();
            
            // Проверяем размер файла, чтобы убедиться, что PDF сгенерирован правильно
            $fileSize = strlen($output);
            Log::info('PDF generated successfully', [
                'filename' => $filename,
                'size' => $fileSize,
                'pages' => $dompdf->getCanvas()->get_page_count()
            ]);
            
            if ($fileSize < 1000) {
                // Очень маленький размер файла может указывать на проблему
                Log::warning('Подозрительно маленький файл PDF', ['size' => $fileSize]);
            }
            
            // Проверка корректности выходного файла
            if (strpos($output, '%PDF') !== 0) {
                Log::error('Сгенерированный PDF не имеет правильной сигнатуры', [
                    'filename' => $filename,
                    'output_start' => substr(bin2hex($output), 0, 100)
                ]);
                
                // Если файл PDF не корректен, создаем простой PDF как запасной вариант
                $backupHtml = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Ошибка генерации документа</title>
                </head>
                <body>
                    <h1>Произошла ошибка при создании документа</h1>
                    <p>Пожалуйста, сообщите администратору системы об этой проблеме.</p>
                    <p>Код ошибки: ' . uniqid() . '</p>
                    <p>Дата и время: ' . date('Y-m-d H:i:s') . '</p>
                </body>
                </html>';
                
                $newDompdf = new Dompdf();
                $newDompdf->loadHtml($backupHtml, 'UTF-8');
                $newDompdf->render();
                $output = $newDompdf->output();
                $fileSize = strlen($output);
            }
            
            // Возвращаем PDF в качестве ответа с улучшенными заголовками
            return response($output, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', $fileSize)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('Ошибка при генерации PDF', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при генерации PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
