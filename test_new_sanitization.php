<?php

// Тест новой санитизации HTML для DOCX
function sanitizeHtmlForPhpWord($html)
{
    // Удаляем только мета-теги и структурные элементы документа
    $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?head[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
    $html = preg_replace('/<meta[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?title[^>]*>/i', '', $html);
    
    // Удаляем style и script теги, но сохраняем остальное форматирование
    $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
    $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
    
    // Убираем только проблематичные атрибуты, но сохраняем базовые
    $html = preg_replace('/\s+class="[^"]*"/i', '', $html);
    $html = preg_replace('/\s+style="[^"]*"/i', '', $html);
    $html = preg_replace('/\s+id="[^"]*"/i', '', $html);
    $html = preg_replace('/\s+(data-[^=]*="[^"]*")/i', '', $html);
    $html = preg_replace('/\s+(role="[^"]*")/i', '', $html);
    $html = preg_replace('/\s+(aria-[^=]*="[^"]*")/i', '', $html);
    
    // Заменяем HTML entities для лучшей совместимости
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML401, 'UTF-8');
    
    // Убираем только действительно проблематичные символы для XML
    $html = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $html);
    
    // Экранируем неэкранированные амперсанды
    $html = preg_replace('/&(?![a-zA-Z0-9#]{1,7};)/', '&amp;', $html);
    
    // Удаляем лишние пробелы, но сохраняем структуру
    $html = preg_replace('/\s{2,}/', ' ', $html);
    $html = trim($html);
    
    return $html;
}

// Создаем HTML похожий на документы договоров
$contractHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Акт выполненных работ</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.4; }
        .header { text-align: center; font-weight: bold; font-size: 14pt; }
        table { width: 100%; border-collapse: collapse; }
        td { border: 1px solid black; padding: 5px; }
    </style>
</head>
<body>
    <div class="header">АКТ ВЫПОЛНЕННЫХ РАБОТ № 42</div>
    <p>г. Москва &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 28.06.2025</p>
    
    <p><strong>Индивидуальный предприниматель</strong> Иванов И.И., ИНН 123456789, 
    ОГРНИП 987654321, адрес: г. Москва, ул. Примерная, д. 1, в дальнейшем «Исполнитель», 
    с одной стороны, и</p>
    
    <p><strong>Петров П.П.</strong>, паспорт: серия 1234 № 567890, 
    в дальнейшем «Заказчик», с другой стороны, составили настоящий Акт о том, 
    что Исполнитель выполнил следующие работы:</p>
    
    <table>
        <tr>
            <td><strong>№ п/п</strong></td>
            <td><strong>Наименование работ</strong></td>
            <td><strong>Кол-во</strong></td>
            <td><strong>Цена, руб.</strong></td>
            <td><strong>Сумма, руб.</strong></td>
        </tr>
        <tr>
            <td>1</td>
            <td>Ремонтные работы</td>
            <td>1</td>
            <td>50000</td>
            <td>50000</td>
        </tr>
    </table>
    
    <p><strong>Всего оказано услуг на сумму:</strong> 50000 (пятьдесят тысяч) рублей 00 копеек</p>
    
    <p>Вышеперечисленные работы выполнены полностью и в срок. 
    Заказчик претензий по объему, качеству и срокам оказания услуг не имеет 
    и принимает выполненные работы.</p>
</body>
</html>';

echo "Оригинальный HTML документа:\n";
echo str_repeat("=", 80) . "\n";
echo $contractHtml;
echo "\n\n";

echo "После санитизации для PhpWord:\n";
echo str_repeat("=", 80) . "\n";
$sanitized = sanitizeHtmlForPhpWord($contractHtml);
echo $sanitized;

echo "\n\n";
echo str_repeat("=", 80) . "\n";
echo "Сравнение размеров:\n";
echo "Оригинал: " . strlen($contractHtml) . " символов\n";
echo "После санитизации: " . strlen($sanitized) . " символов\n";
echo "Удалено: " . (strlen($contractHtml) - strlen($sanitized)) . " символов\n";
