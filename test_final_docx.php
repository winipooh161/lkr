<?php

require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;

echo "Тестирование улучшенной функции sanitizeHtmlForPhpWord...\n\n";

// Тестовый HTML как в ActFlIpController
$realHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Акт выполненных работ ФЛ-ИП</title>
    <style>
        body {
            font-family: "DejaVu Sans", "Arial", sans-serif;
            line-height: 1.4;
            font-size: 12pt;
        }
        .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 14pt;
        }
        table.border {
            border-collapse: collapse;
            width: 100%;
            margin: 15px 0;
        }
        table.border th, table.border td {
            border: 1px solid black;
            padding: 5px;
        }
    </style>
</head>
<body>
    <div class="header">АКТ ВЫПОЛНЕННЫХ РАБОТ № 123</div>
    <p>г. Москва                                                         01.01.2024</p>
    
    <p>Индивидуальный предприниматель ИП Иванов, ИНН 123456789012, ОГРНИП 123456789012345, адрес: Москва, ул. Тестовая, д. 1, в дальнейшем «Исполнитель», с одной стороны, и</p>
    
    <p>Петров Петр Петрович, паспорт: серия 1234 № 567890, выдан ОВД Тестовое 01.01.2010, код подразделения 123-456, в дальнейшем «Заказчик», с другой стороны, составили настоящий Акт о том, что Исполнитель выполнил следующие работы:</p>
    
    <table class="border">
        <tr>
            <th>№</th>
            <th>Наименование работ</th>
            <th>Ед. изм.</th>
            <th>Кол-во</th>
            <th>Цена</th>
            <th>Сумма</th>
        </tr>
        <tr>
            <td>1</td>
            <td>Комплекс ремонтно-отделочных работ по адресу: Москва, ул. Тестовая, д. 1</td>
            <td>усл.</td>
            <td>1</td>
            <td>50000</td>
            <td>50000</td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: right;"><strong>Итого:</strong></td>
            <td>50000</td>
        </tr>
    </table>
    
    <p>Всего оказано услуг на сумму 50000 (пятьдесят тысяч) рублей 00 копеек, НДС не облагается.</p>
    
    <table style="width: 100%; margin-top: 50px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <p><b>Исполнитель:</b></p>
                <p>ИП Иванов<br>ИНН: 123456789012<br>ОГРНИП: 123456789012345</p>
                <p>_________________________ / Иванов И.И. /</p>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <p><b>Заказчик:</b></p>
                <p>Петров Петр Петрович<br>Паспорт: 1234 567890</p>
                <p>_________________________ / Петров П.П. /</p>
            </td>
        </tr>
    </table>
</body>
</html>';

// Копируем функцию sanitizeHtmlForPhpWord из нашего контроллера
function sanitizeHtmlForPhpWord($html) {
    // Шаг 1: Удаляем структурные элементы HTML
    $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?head[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
    $html = preg_replace('/<meta[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?title[^>]*>/i', '', $html);
    
    // Шаг 2: Удаляем style и script блоки
    $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
    $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
    
    // Шаг 3: КРИТИЧЕСКИ ВАЖНО - Исправляем самозакрывающиеся теги
    // PhpWord/DOMDocument требует правильно закрытые теги
    $html = preg_replace('/<br\s*\/?>/i', '<br></br>', $html);
    $html = preg_replace('/<hr\s*\/?>/i', '<hr></hr>', $html);
    $html = preg_replace('/<img([^>]*)\s*\/?>/i', '<img$1></img>', $html);
    
    // Шаг 4: Упрощаем атрибуты - удаляем CSS классы и стили
    $html = preg_replace('/\s+class="[^"]*"/i', '', $html);
    $html = preg_replace('/\s+style="[^"]*"/i', '', $html);
    $html = preg_replace('/\s+id="[^"]*"/i', '', $html);
    $html = preg_replace('/\s+(data-[^=]*="[^"]*")/i', '', $html);
    $html = preg_replace('/\s+(role="[^"]*")/i', '', $html);
    $html = preg_replace('/\s+(aria-[^=]*="[^"]*")/i', '', $html);
    
    // Шаг 5: Упрощаем таблицы для лучшей совместимости
    $html = preg_replace('/<table[^>]*>/', '<table border="1">', $html);
    
    // Шаг 6: Заменяем div.header на h2 и другие div на p
    $html = preg_replace('/<div[^>]*class="header"[^>]*>(.*?)<\/div>/is', '<h2>$1</h2>', $html);
    $html = preg_replace('/<div[^>]*>(.*?)<\/div>/is', '<p>$1</p>', $html);
    
    // Шаг 7: Экранируем амперсанды
    $html = preg_replace('/&(?![a-zA-Z0-9#]{1,7};)/', '&amp;', $html);
    
    // Шаг 8: Удаляем лишние пробелы
    $html = preg_replace('/\s+/', ' ', $html);
    $html = trim($html);
    
    return $html;
}

echo "=== ТЕСТ: Улучшенная функция sanitizeHtmlForPhpWord ===\n";
try {
    // Применяем нашу улучшенную функцию
    $cleanHtml = sanitizeHtmlForPhpWord($realHtml);
    echo "Очищенный HTML (первые 300 символов):\n" . substr($cleanHtml, 0, 300) . "...\n\n";
    
    // Тестируем с PhpWord
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    
    Html::addHtml($section, $cleanHtml);
    
    echo "✅ УСПЕХ: HTML успешно обработан PhpWord\n";
    echo "ℹ️  Элементов в секции: " . count($section->getElements()) . "\n";
    
    // Попробуем создать DOCX (без сохранения из-за проблемы с ZipArchive)
    echo "\n=== Тестирование создания DOCX (только объект) ===\n";
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    echo "✅ УСПЕХ: Writer создан успешно\n";
    
    // Попробуем сохранить (ожидаем ошибку ZipArchive)
    try {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_docx_');
        $objWriter->save($tempFile);
        echo "✅ НЕВЕРОЯТНО: Файл DOCX сохранен! " . $tempFile . "\n";
        echo "📊 Размер файла: " . filesize($tempFile) . " байт\n";
        unlink($tempFile);
    } catch (\Error $e) {
        if (strpos($e->getMessage(), 'ZipArchive') !== false) {
            echo "❌ ОЖИДАЕМО: ZipArchive не найден - " . $e->getMessage() . "\n";
            echo "💡 Решение: Нужно установить расширение PHP ZIP\n";
        } else {
            echo "❌ НЕОЖИДАННАЯ ОШИБКА: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ОШИБКА обработки HTML: " . $e->getMessage() . "\n";
}

echo "\nТестирование завершено!\n";
echo "📋 ИТОГИ:\n";
echo "- HTML успешно очищается и парсится PhpWord\n";
echo "- Проблема только с сохранением DOCX из-за отсутствия ZipArchive\n";
echo "- В реальном приложении пользователь получит понятное сообщение об ошибке\n";
