<?php

// Простейший тест Dompdf с использованием только Arial
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Функция для копирования шрифта Arial, если он отсутствует
function ensureArialFontExists() {
    $fontDir = __DIR__ . '/fonts/';
    if (!file_exists($fontDir)) {
        mkdir($fontDir, 0755, true);
    }
    
    $fontFile = $fontDir . 'Arial.ttf';
    if (!file_exists($fontFile)) {
        // Для Windows
        $winFontPath = 'C:\\Windows\\Fonts\\arial.ttf';
        if (file_exists($winFontPath)) {
            copy($winFontPath, $fontFile);
            echo "Скопирован шрифт Arial из Windows<br>";
        } else {
            echo "Шрифт Arial не найден!<br>";
        }
    }
    
    return file_exists($fontFile);
}

// Проверяем наличие шрифта
$arialExists = ensureArialFontExists();

if (!$arialExists) {
    die("Невозможно продолжить без шрифта Arial");
}

// Создаем минимальный HTML
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @font-face {
            font-family: Arial;
            src: url("fonts/Arial.ttf") format("truetype");
        }
        body {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <h1>Тест кириллицы в PDF</h1>
    <p>Русский текст: АБВГДЕЁЖЗ абвгдеёжз</p>
</body>
</html>';

// Настраиваем опции
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Arial');

// Важно указать директорию со шрифтами
$options->set('fontDir', __DIR__ . '/fonts/');
$options->set('fontCache', __DIR__ . '/fonts/');

// Генерируем PDF
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4');
$dompdf->render();

// Сохраняем файл
$outputFile = __DIR__ . '/ultra_simple_test.pdf';
file_put_contents($outputFile, $dompdf->output());

echo "PDF создан: <a href='ultra_simple_test.pdf' target='_blank'>Открыть PDF</a>";

// Выводим информацию о состоянии
echo "<h2>Информация о шрифтах:</h2>";
echo "Директория шрифтов: " . __DIR__ . '/fonts/<br>';
echo "Размер Arial.ttf: " . (file_exists(__DIR__ . '/fonts/Arial.ttf') ? filesize(__DIR__ . '/fonts/Arial.ttf') : 'файл не найден') . " байт<br>";

?>
