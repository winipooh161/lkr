<?php

require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

echo "Тестирование парсинга HTML PhpWord (без сохранения файлов)...\n\n";

// Тестовый HTML из ActFlIpController (упрощенная версия)
$testHtml = '<!DOCTYPE html>
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

// Функция для удаления только DOCTYPE и основных HTML-тегов
function minimalCleanHtml($html) {
    // Удаляем DOCTYPE
    $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
    
    // Удаляем теги html, head, body
    $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?head[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
    
    // Удаляем meta и title теги
    $html = preg_replace('/<meta[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?title[^>]*>/i', '', $html);
    
    // Удаляем style и script блоки
    $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
    $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
    
    // Экранируем неэкранированные амперсанды
    $html = preg_replace('/&(?![a-zA-Z0-9#]{1,7};)/', '&amp;', $html);
    
    return trim($html);
}

// Функция для создания оптимизированного HTML для PhpWord
function optimizeHtmlForPhpWord($html) {
    // Базовая очистка
    $html = minimalCleanHtml($html);
    
    // Удаляем проблематичные атрибуты CSS
    $html = preg_replace('/\s+class="[^"]*"/i', '', $html);
    $html = preg_replace('/\s+style="[^"]*"/i', '', $html);
    $html = preg_replace('/\s+id="[^"]*"/i', '', $html);
    
    // Оптимизируем таблицы - упрощаем атрибуты
    $html = preg_replace('/<table[^>]*class="border"[^>]*>/', '<table border="1">', $html);
    $html = preg_replace('/<table[^>]*>/', '<table>', $html);
    
    // Заменяем div с классом header на h2
    $html = preg_replace('/<div[^>]*class="header"[^>]*>(.*?)<\/div>/is', '<h2>$1</h2>', $html);
    
    // Убираем colspan с style - PhpWord может не понимать комбинацию
    $html = preg_replace('/<td([^>]*colspan="[^"]*")[^>]*style="[^"]*"([^>]*)>/', '<td$1$2>', $html);
    
    return $html;
}

// Тест 1: Оригинальный HTML (только парсинг)
echo "=== ТЕСТ 1: Оригинальный HTML (только парсинг) ===\n";
try {
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    
    Html::addHtml($section, $testHtml);
    
    echo "✅ УСПЕХ: Оригинальный HTML успешно обработан\n";
    echo "ℹ️  Элементов в секции: " . count($section->getElements()) . "\n\n";
    
} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n\n";
}

// Тест 2: Минимальная очистка (только парсинг)
echo "=== ТЕСТ 2: Минимальная очистка HTML (только парсинг) ===\n";
try {
    $cleanHtml = minimalCleanHtml($testHtml);
    echo "Очищенный HTML (первые 200 символов):\n" . substr($cleanHtml, 0, 200) . "...\n\n";
    
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    
    Html::addHtml($section, $cleanHtml);
    
    echo "✅ УСПЕХ: HTML с минимальной очисткой успешно обработан\n";
    echo "ℹ️  Элементов в секции: " . count($section->getElements()) . "\n\n";
    
} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n\n";
}

// Тест 3: Оптимизированный HTML (только парсинг)
echo "=== ТЕСТ 3: Оптимизированный HTML (только парсинг) ===\n";
try {
    $optimizedHtml = optimizeHtmlForPhpWord($testHtml);
    echo "Оптимизированный HTML (первые 300 символов):\n" . substr($optimizedHtml, 0, 300) . "...\n\n";
    
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    
    Html::addHtml($section, $optimizedHtml);
    
    echo "✅ УСПЕХ: Оптимизированный HTML успешно обработан\n";
    echo "ℹ️  Элементов в секции: " . count($section->getElements()) . "\n\n";
    
} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n\n";
}

// Тест 4: Простейший HTML (только парсинг)
echo "=== ТЕСТ 4: Простейший HTML (только парсинг) ===\n";
$simpleHtml = '<h2>АКТ ВЫПОЛНЕННЫХ РАБОТ № 123</h2>
<p>г. Москва 01.01.2024</p>
<p>Индивидуальный предприниматель ИП Иванов выполнил следующие работы:</p>
<table border="1">
    <tr>
        <th>№</th>
        <th>Наименование работ</th>
        <th>Сумма</th>
    </tr>
    <tr>
        <td>1</td>
        <td>Комплекс ремонтно-отделочных работ</td>
        <td>50000</td>
    </tr>
</table>
<p><strong>Исполнитель:</strong> ИП Иванов</p>
<p><strong>Заказчик:</strong> Петров П.П.</p>';

try {
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    
    Html::addHtml($section, $simpleHtml);
    
    echo "✅ УСПЕХ: Простейший HTML успешно обработан\n";
    echo "ℹ️  Элементов в секции: " . count($section->getElements()) . "\n\n";
    
} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n\n";
}

echo "Тестирование парсинга завершено!\n";
echo "📋 Результат: Видим, какие варианты HTML успешно парсятся\n";
echo "💡 Следующий шаг: Нужно решить проблему с ZipArchive для сохранения файлов\n";
