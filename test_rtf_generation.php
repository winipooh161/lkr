<?php

require_once 'vendor/autoload.php';

echo "Тестирование RTF генерации для замены DOCX...\n\n";

// Тестовый HTML
$testHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Акт выполненных работ ФЛ-ИП</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; font-weight: bold; font-size: 14pt; margin-bottom: 20px; }
        table.border { border-collapse: collapse; width: 100%; margin: 15px 0; }
        table.border th, table.border td { border: 1px solid black; padding: 5px; }
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
            <th>Единица измерения</th>
            <th>Количество</th>
            <th>Цена за единицу, руб.</th>
            <th>Стоимость, руб.</th>
        </tr>
        <tr>
            <td>1</td>
            <td>Ремонтные работы согласно техническому заданию</td>
            <td>комплекс</td>
            <td>1</td>
            <td>50 000</td>
            <td>50 000</td>
        </tr>
        <tr>
            <td colspan="5"><strong>ИТОГО:</strong></td>
            <td><strong>50 000</strong></td>
        </tr>
    </table>
    
    <p>Всего выполнено работ на сумму: пятьдесят тысяч (50 000) рублей 00 копеек.</p>
    
    <p>Исполнитель: _________________________ ИП Иванов</p>
    <p>Заказчик: _________________________ Петров П.П.</p>
</body>
</html>';

echo "=== ПРОВЕРКА ДОСТУПНОСТИ РАСШИРЕНИЙ ===\n";
echo "ZipArchive: " . (class_exists('ZipArchive') ? '✓ Доступно' : '✗ Недоступно') . "\n";
echo "XML: " . (extension_loaded('xml') ? '✓ Доступно' : '✗ Недоступно') . "\n";
echo "DOMDocument: " . (class_exists('DOMDocument') ? '✓ Доступно' : '✗ Недоступно') . "\n\n";

echo "=== ТЕСТ RTF ГЕНЕРАЦИИ ===\n";
try {
    // Разбираем HTML структуру
    $structure = parseHtmlStructure($testHtml);
    echo "✓ HTML структура разобрана: " . count($structure) . " элементов\n";
    
    // Генерируем RTF контент
    $rtfContent = convertHtmlToRtf($testHtml);
    echo "✓ RTF контент сгенерирован, размер: " . strlen($rtfContent) . " символов\n";
    
    // Сохраняем тестовый RTF файл
    $testFile = __DIR__ . '/test_document.rtf';
    file_put_contents($testFile, $rtfContent);
    echo "✓ RTF файл сохранен: " . basename($testFile) . " (размер: " . filesize($testFile) . " байт)\n";
    
    // Показываем начало RTF файла
    echo "\nПревью RTF (первые 300 символов):\n";
    echo substr($rtfContent, 0, 300) . "...\n";
    
} catch (Exception $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n=== ИТОГИ ===\n";
echo "1. RTF генерация: " . (isset($rtfContent) ? "✓ Работает" : "✗ Ошибка") . "\n";
echo "2. Файл создан: " . (file_exists($testFile ?? '') ? "✓ Да" : "✗ Нет") . "\n";
echo "3. Размер файла: " . (isset($testFile) && file_exists($testFile) ? filesize($testFile) . " байт" : "Неизвестно") . "\n";

if (isset($testFile) && file_exists($testFile)) {
    echo "\n📋 Инструкции:\n";
    echo "1. Откройте файл {$testFile} в Microsoft Word\n";
    echo "2. Проверьте, что форматирование сохранилось\n";
    echo "3. Сохраните как .docx, если нужно\n";
}

// Вспомогательные функции для тестирования

function parseHtmlStructure($html) {
    $structure = [];
    
    // Нормализуем HTML
    $cleanHtml = normalizeHtml($html);
    
    // Ищем заголовок
    if (preg_match('/<div[^>]*class="header"[^>]*>(.*?)<\/div>/is', $cleanHtml, $matches)) {
        $structure[] = [
            'type' => 'header',
            'content' => strip_tags($matches[1])
        ];
    }
    
    // Ищем параграфы
    preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $cleanHtml, $paragraphs);
    foreach ($paragraphs[1] as $paragraph) {
        $text = strip_tags($paragraph);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = trim($text);
        
        if (!empty($text)) {
            $structure[] = [
                'type' => 'paragraph',
                'content' => $text
            ];
        }
    }
    
    // Ищем таблицы
    preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $cleanHtml, $tables);
    foreach ($tables[1] as $table) {
        $tableData = parseTableData($table);
        if (!empty($tableData)) {
            $structure[] = [
                'type' => 'table',
                'content' => $tableData
            ];
        }
    }
    
    return $structure;
}

function normalizeHtml($html) {
    $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
    $html = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $html);
    $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
    $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
    
    $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    $html = preg_replace('/\s+/', ' ', $html);
    
    return trim($html);
}

function parseTableData($tableHtml) {
    $tableData = [];
    
    preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tableHtml, $rows);
    
    foreach ($rows[1] as $row) {
        $rowData = [];
        
        preg_match_all('/<t[hd][^>]*>(.*?)<\/t[hd]>/is', $row, $cells);
        
        foreach ($cells[1] as $cell) {
            $cellText = strip_tags($cell);
            $cellText = html_entity_decode($cellText, ENT_QUOTES, 'UTF-8');
            $cellText = trim($cellText);
            $rowData[] = $cellText;
        }
        
        if (!empty($rowData)) {
            $tableData[] = $rowData;
        }
    }
    
    return $tableData;
}

function convertHtmlToRtf($html) {
    // Разбираем HTML на структурные элементы
    $structure = parseHtmlStructure($html);
    
    // Начинаем RTF документ
    $rtf = '{\\rtf1\\ansi\\deff0 {\\fonttbl {\\f0 Times New Roman;}}';
    $rtf .= '{\\colortbl;\\red0\\green0\\blue0;}';
    $rtf .= '\\viewkind4\\uc1\\pard\\f0\\fs24\\lang1049'; // Русский язык, Times New Roman 12pt
    
    foreach ($structure as $element) {
        switch ($element['type']) {
            case 'header':
                $rtf .= convertHeaderToRtf($element['content']);
                break;
            case 'paragraph':
                $rtf .= convertParagraphToRtf($element['content']);
                break;
            case 'table':
                $rtf .= convertTableToRtf($element['content']);
                break;
        }
    }
    
    // Добавляем подписи
    $rtf .= '\\par\\par';
    $rtf .= '\\trowd\\trgaph108\\cellx4500\\cellx9000';
    $rtf .= '\\intbl Исполнитель:\\par\\par_______________________\\cell';
    $rtf .= '\\intbl Заказчик:\\par\\par_______________________\\cell';
    $rtf .= '\\row\\par';
    $rtf .= '\\fs20\\i М.П. (при наличии печати)\\i0\\fs24\\par';
    
    $rtf .= '}'; // Закрываем RTF документ
    
    return $rtf;
}

function convertHeaderToRtf($content) {
    $escapedContent = escapeRtfText($content);
    return '\\par\\qc\\b\\fs28 ' . $escapedContent . '\\b0\\fs24\\par\\par';
}

function convertParagraphToRtf($content) {
    $escapedContent = escapeRtfText($content);
    
    // Проверяем, содержит ли параграф город и дату
    if (preg_match('/г\.\s*([^0-9]*?)\s+(\d{2}\.\d{2}\.\d{4})/', $content)) {
        return '\\par\\qj ' . $escapedContent . '\\par';
    } else {
        return '\\par\\qj\\fi567 ' . $escapedContent . '\\par'; // С красной строкой
    }
}

function convertTableToRtf($tableData) {
    $rtf = '\\par';
    
    foreach ($tableData as $rowIndex => $rowData) {
        $rtf .= '\\trowd\\trgaph108\\trleft-108'; // Начало строки таблицы
        
        // Определяем ширину колонок
        $cellWidth = 2400; // Базовая ширина ячейки
        $cellPosition = 0;
        
        foreach ($rowData as $cellIndex => $cellData) {
            $cellPosition += $cellWidth;
            $rtf .= '\\cellx' . $cellPosition;
        }
        
        // Добавляем содержимое ячеек
        foreach ($rowData as $cellIndex => $cellData) {
            $escapedContent = escapeRtfText($cellData);
            
            if ($rowIndex === 0) {
                // Заголовок таблицы - жирный шрифт
                $rtf .= '\\intbl\\qc\\b ' . $escapedContent . '\\b0\\cell';
            } else {
                $rtf .= '\\intbl\\qc ' . $escapedContent . '\\cell';
            }
        }
        
        $rtf .= '\\row'; // Конец строки
    }
    
    $rtf .= '\\par';
    return $rtf;
}

function escapeRtfText($text) {
    // RTF требует экранирования специальных символов
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace('{', '\\{', $text);
    $text = str_replace('}', '\\}', $text);
    
    // Простая обработка русских символов для RTF
    $replacements = [
        'а' => '\\u1072?', 'б' => '\\u1073?', 'в' => '\\u1074?', 'г' => '\\u1075?', 'д' => '\\u1076?',
        'е' => '\\u1077?', 'ё' => '\\u1105?', 'ж' => '\\u1078?', 'з' => '\\u1079?', 'и' => '\\u1080?',
        'й' => '\\u1081?', 'к' => '\\u1082?', 'л' => '\\u1083?', 'м' => '\\u1084?', 'н' => '\\u1085?',
        'о' => '\\u1086?', 'п' => '\\u1087?', 'р' => '\\u1088?', 'с' => '\\u1089?', 'т' => '\\u1090?',
        'у' => '\\u1091?', 'ф' => '\\u1092?', 'х' => '\\u1093?', 'ц' => '\\u1094?', 'ч' => '\\u1095?',
        'ш' => '\\u1096?', 'щ' => '\\u1097?', 'ъ' => '\\u1098?', 'ы' => '\\u1099?', 'ь' => '\\u1100?',
        'э' => '\\u1101?', 'ю' => '\\u1102?', 'я' => '\\u1103?',
        'А' => '\\u1040?', 'Б' => '\\u1041?', 'В' => '\\u1042?', 'Г' => '\\u1043?', 'Д' => '\\u1044?',
        'Е' => '\\u1045?', 'Ё' => '\\u1025?', 'Ж' => '\\u1046?', 'З' => '\\u1047?', 'И' => '\\u1048?',
        'Й' => '\\u1049?', 'К' => '\\u1050?', 'Л' => '\\u1051?', 'М' => '\\u1052?', 'Н' => '\\u1053?',
        'О' => '\\u1054?', 'П' => '\\u1055?', 'Р' => '\\u1056?', 'С' => '\\u1057?', 'Т' => '\\u1058?',
        'У' => '\\u1059?', 'Ф' => '\\u1060?', 'Х' => '\\u1061?', 'Ц' => '\\u1062?', 'Ч' => '\\u1063?',
        'Ш' => '\\u1064?', 'Щ' => '\\u1065?', 'Ъ' => '\\u1066?', 'Ы' => '\\u1067?', 'Ь' => '\\u1068?',
        'Э' => '\\u1069?', 'Ю' => '\\u1070?', 'Я' => '\\u1071?'
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

echo "\nТестирование завершено!\n";
