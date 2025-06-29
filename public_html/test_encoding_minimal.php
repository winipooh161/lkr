<?php

require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Устанавливаем кодировку страницы
header('Content-Type: text/html; charset=utf-8');

// Путь для сохранения тестового PDF
$outputPath = __DIR__ . '/test_encoding_minimal.pdf';

// Создаем простой HTML с русскими символами
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Тест кириллицы</title>
    <style>
        @font-face {
            font-family: "Arial";
            font-weight: normal;
            font-style: normal;
            src: url("fonts/Arial.ttf") format("truetype");
        }
        @font-face {
            font-family: "Arial";
            font-weight: bold;
            font-style: normal;
            src: url("fonts/Arial-Bold.ttf") format("truetype");
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        h1 {
            font-family: Arial, sans-serif;
            font-size: 18px;
            color: #333;
        }
        p {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <h1>Тест поддержки кириллицы в PDF</h1>
    <p>Это тестовый документ для проверки отображения русских символов в PDF.</p>
    <p>Русский текст: Проверка кодировки UTF-8, поддержка кириллических символов.</p>
    <p>Числа прописью: сто двадцать три тысячи четыреста пятьдесят шесть рублей.</p>
    <p>Дата: ' . date('d.m.Y, H:i') . '</p>
</body>
</html>
';

// Настраиваем DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$options->set('isFontSubsettingEnabled', true);
$options->set('defaultMediaType', 'screen');

// Настройка шрифтов с поддержкой кириллицы
$options->set('fontDir', __DIR__ . '/fonts/');
$options->set('fontCache', __DIR__ . '/fonts/');

// Выводим информацию о настройках
echo "<h2>Настройки DOMPDF:</h2>";
echo "<ul>";
echo "<li>fontDir: " . $options->get('fontDir') . "</li>";
echo "<li>fontCache: " . $options->get('fontCache') . "</li>";
echo "<li>defaultFont: " . $options->get('defaultFont') . "</li>";
echo "</ul>";

// Проверяем наличие файлов шрифтов
echo "<h2>Проверка наличия файлов шрифтов:</h2>";
echo "<ul>";
$fontFiles = ['Arial.ttf', 'Arial-Bold.ttf'];
foreach ($fontFiles as $font) {
    $fontPath = __DIR__ . '/fonts/' . $font;
    if (file_exists($fontPath)) {
        echo "<li>$fontPath: <span style='color:green'>Существует</span> (" . filesize($fontPath) . " байт)</li>";
    } else {
        echo "<li>$fontPath: <span style='color:red'>Отсутствует</span></li>";
    }
}
echo "</ul>";

// Создаем PDF
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4');
$dompdf->render();

// Сохраняем PDF
file_put_contents($outputPath, $dompdf->output());

echo "<h2>PDF создан и сохранен:</h2>";
echo "<a href='/test_encoding_minimal.pdf' target='_blank'>Просмотреть минимальный тест PDF</a>";

// Предложение исправить контроллер
echo "<h2>Рекомендации для исправления контроллера:</h2>";
echo "<ol>";
echo "<li>Замените defaultFont на 'Arial' вместо 'DejaVuSans'</li>";
echo "<li>Используйте только шрифты, которые точно существуют (Arial, Arial-Bold)</li>";
echo "<li>Убедитесь, что в HTML шаблонах используется font-family: Arial, sans-serif</li>";
echo "<li>Убедитесь, что fontDir указывает на директорию с существующими шрифтами</li>";
echo "<li>Проверьте наличие метатегов charset=utf-8 в HTML</li>";
echo "</ol>";

?>
