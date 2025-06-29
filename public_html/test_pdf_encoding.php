<?php

require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Устанавливаем кодировку страницы
header('Content-Type: text/html; charset=utf-8');

// Путь для сохранения тестового PDF
$outputPath = __DIR__ . '/test_encoding.pdf';

// Создаем простой HTML с русскими символами
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Тест кириллицы</title>
    <style>
        body {
            font-family: DejaVuSans, Arial, sans-serif;
            font-size: 14px;
        }
        h1 {
            font-size: 18px;
            color: #333;
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
$options->set('defaultFont', 'DejaVuSans');
$options->set('isFontSubsettingEnabled', true);
$options->set('defaultMediaType', 'screen');

// Настройка шрифтов с поддержкой кириллицы
$options->set('fontDir', __DIR__ . '/../storage/fonts/');
$options->set('fontCache', __DIR__ . '/../storage/fonts/');

// Создаем PDF
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4');
$dompdf->render();

// Сохраняем PDF
file_put_contents($outputPath, $dompdf->output());

echo "PDF создан и сохранен как: " . $outputPath;
echo "<br><br>";
echo "<a href='/test_encoding.pdf' target='_blank'>Просмотреть PDF</a>";
