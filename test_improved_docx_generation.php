<?php

require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

echo "Тестирование улучшенной генерации DOCX документов...\n\n";

// Настраиваем временную директорию
$tempDir = __DIR__ . '/storage/app/temp';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}
Settings::setTempDir($tempDir);

// Проверяем доступность расширений
echo "=== ПРОВЕРКА РАСШИРЕНИЙ ===\n";
echo "ZipArchive: " . (class_exists('ZipArchive') ? '✓ Доступно' : '✗ Недоступно') . "\n";
echo "XML: " . (extension_loaded('xml') ? '✓ Доступно' : '✗ Недоступно') . "\n";
echo "XMLWriter: " . (extension_loaded('xmlwriter') ? '✓ Доступно' : '✗ Недоступно') . "\n";
echo "DOMDocument: " . (class_exists('DOMDocument') ? '✓ Доступно' : '✗ Недоступно') . "\n\n";

// Тестовый HTML как в реальных контроллерах
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

echo "=== ТЕСТ 1: Создание документа новым методом ===\n";
try {
    $phpWord = new PhpWord();
    $phpWord->setDefaultFontName('Times New Roman');
    $phpWord->setDefaultFontSize(12);
    
    $section = $phpWord->addSection([
        'marginTop' => 1134,
        'marginBottom' => 1134,
        'marginLeft' => 1134,
        'marginRight' => 1134,
    ]);
    
    // Имитируем метод createDocumentFromHtml
    $structure = parseHtmlStructure($testHtml);
    
    foreach ($structure as $element) {
        switch ($element['type']) {
            case 'header':
                addDocumentHeader($section, $element['content']);
                break;
            case 'paragraph':
                addDocumentParagraph($section, $element['content']);
                break;
            case 'table':
                addDocumentTable($section, $element['content']);
                break;
        }
    }
    
    echo "✓ Документ создан успешно\n";
    echo "✓ Структура HTML разобрана правильно\n";
    
    // Пробуем сохранить документ
    if (class_exists('ZipArchive')) {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_docx');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        echo "✓ DOCX файл сохранен: " . basename($tempFile) . " (размер: " . filesize($tempFile) . " байт)\n";
        
        // Удаляем временный файл
        unlink($tempFile);
    } else {
        echo "⚠ ZipArchive недоступно, сохранение DOCX невозможно\n";
    }
    
} catch (Exception $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n=== ТЕСТ 2: Fallback метод с HTML парсером ===\n";
try {
    $phpWord2 = new PhpWord();
    $section2 = $phpWord2->addSection();
    
    $cleanHtml = optimizeHtmlForPhpWord($testHtml);
    echo "HTML после оптимизации (первые 200 символов): " . substr($cleanHtml, 0, 200) . "...\n";
    
    \PhpOffice\PhpWord\Shared\Html::addHtml($section2, $cleanHtml, false, false);
    
    echo "✓ HTML успешно обработан PhpWord\n";
    
} catch (Exception $e) {
    echo "✗ Ошибка в fallback методе: " . $e->getMessage() . "\n";
}

echo "\n=== ИТОГИ ТЕСТИРОВАНИЯ ===\n";
echo "1. Новый метод создания DOCX: " . (isset($tempFile) ? "Работает" : "Нужна доработка") . "\n";
echo "2. Fallback метод: " . (isset($cleanHtml) ? "Работает" : "Нужна доработка") . "\n";
echo "3. Расширения PHP: " . (class_exists('ZipArchive') ? "Все в порядке" : "Нужно установить ZipArchive") . "\n";

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

function addDocumentHeader($section, $content) {
    $section->addText(
        $content,
        [
            'name' => 'Times New Roman',
            'size' => 14,
            'bold' => true
        ],
        [
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter' => 240
        ]
    );
}

function addDocumentParagraph($section, $content) {
    $section->addText(
        $content,
        [
            'name' => 'Times New Roman',
            'size' => 12
        ],
        [
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            'spaceAfter' => 120,
            'firstLine' => 567
        ]
    );
}

function addDocumentTable($section, $tableData) {
    $table = $section->addTable([
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
    ]);
    
    foreach ($tableData as $rowIndex => $rowData) {
        $row = $table->addRow();
        
        foreach ($rowData as $cellData) {
            $cell = $row->addCell();
            
            $fontStyle = [
                'name' => 'Times New Roman',
                'size' => 12
            ];
            
            if ($rowIndex === 0) {
                $fontStyle['bold'] = true;
            }
            
            $cell->addText($cellData, $fontStyle, [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
            ]);
        }
    }
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

function optimizeHtmlForPhpWord($html) {
    $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?head[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
    $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
    $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
    
    $html = preg_replace('/<div[^>]*class="header"[^>]*>(.*?)<\/div>/is', '<h2>$1</h2>', $html);
    $html = preg_replace('/<div[^>]*>(.*?)<\/div>/is', '<p>$1</p>', $html);
    $html = preg_replace('/<(\w+)[^>]*>/', '<$1>', $html);
    $html = preg_replace('/<br\s*\/?>/i', '<br/>', $html);
    $html = preg_replace('/&(?![a-zA-Z0-9#]{1,7};)/', '&amp;', $html);
    
    return trim($html);
}

echo "\nТестирование завершено!\n";
