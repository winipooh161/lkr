<?php

// Простой тест санитизации HTML без Laravel
function sanitizeHtmlForPhpWord($html)
{
    // Удаляем DOCTYPE и html теги
    $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?head[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
    
    // Удаляем style теги и их содержимое
    $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
    
    // Удаляем script теги и их содержимое
    $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
    
    // Удаляем meta теги
    $html = preg_replace('/<meta[^>]*>/i', '', $html);
    
    // Удаляем title теги
    $html = preg_replace('/<\/?title[^>]*>/i', '', $html);
    
    // Заменяем специальные HTML entities на обычные символы
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML401, 'UTF-8');
    
    // Заменяем некорректные символы в именах классов и атрибутах
    $html = preg_replace('/class="[^"]*"/i', '', $html);
    $html = preg_replace('/style="[^"]*"/i', '', $html);
    $html = preg_replace('/id="[^"]*"/i', '', $html);
    
    // Удаляем пустые атрибуты
    $html = preg_replace('/\s+[a-zA-Z-]+=""\s*/', ' ', $html);
    
    // Удаляем некорректные атрибуты
    $html = preg_replace('/\s+(data-[^=]*="[^"]*")/i', '', $html);
    $html = preg_replace('/\s+(role="[^"]*")/i', '', $html);
    $html = preg_replace('/\s+(aria-[^=]*="[^"]*")/i', '', $html);
    
    // Заменяем div на p для лучшей совместимости
    $html = str_replace(['<div', '</div>'], ['<p', '</p>'], $html);
    
    // Заменяем неподдерживаемые теги
    $html = preg_replace('/<(span|font)[^>]*>/i', '', $html);
    $html = preg_replace('/<\/(span|font)>/i', '', $html);
    
    // Удаляем лишние пробелы и переводы строк
    $html = preg_replace('/\s+/', ' ', $html);
    $html = trim($html);
    
    // Убираем проблематичные символы, которые могут нарушить XML
    $html = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $html);
    
    // Экранируем амперсанды, которые не являются частью entities
    $html = preg_replace('/&(?![a-zA-Z0-9#]{1,7};)/', '&amp;', $html);
    
    // Проверяем, что HTML содержимое корректно
    if (!empty($html)) {
        // Загружаем HTML в DOMDocument для проверки корректности
        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->encoding = 'UTF-8';
        
        // Оборачиваем в div для безопасности
        $wrappedHtml = '<?xml version="1.0" encoding="UTF-8"?><div>' . $html . '</div>';
        
        if ($dom->loadXML($wrappedHtml, LIBXML_NOCDATA)) {
            // Если загрузка успешна, получаем чистый HTML обратно
            $html = '';
            foreach ($dom->documentElement->childNodes as $node) {
                $html .= $dom->saveHTML($node);
            }
        } else {
            // Если загрузка неуспешна, возвращаем только текстовое содержимое
            $html = strip_tags($html);
            $html = '<p>' . htmlspecialchars($html, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        
        libxml_clear_errors();
    }
    
    return $html;
}

// Создаем простой HTML для тестирования
$testHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Тестовый документ</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">ТЕСТОВЫЙ ДОКУМЕНТ</div>
    <p>Это тестовый параграф с &nbsp; специальными символами &amp; знаками.</p>
    <table border="1">
        <tr>
            <td>Ячейка 1</td>
            <td>Ячейка 2</td>
        </tr>
    </table>
</body>
</html>';

echo "Тестовый HTML:\n";
echo $testHtml;
echo "\n\n" . str_repeat("=", 50) . "\n";
echo "Результат санитизации:\n";

$sanitizedHtml = sanitizeHtmlForPhpWord($testHtml);
echo $sanitizedHtml;
echo "\n\n" . str_repeat("=", 50) . "\n";
echo "Длина оригинального HTML: " . strlen($testHtml) . " символов\n";
echo "Длина санитизированного HTML: " . strlen($sanitizedHtml) . " символов\n";
