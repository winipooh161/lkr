<?php

// Принудительная загрузка библиотеки dompdf для проверки совместимости версии
require_once '../vendor/dompdf/dompdf/src/Dompdf.php';

// Выводим информацию о версии dompdf и настройках
echo "<h1>Информация о версии и настройках DOMPDF</h1>";

echo "<h2>Версия DOMPDF:</h2>";
echo "<p>Проверяем используемую версию DOMPDF...</p>";

// Проверяем наличие директорий для шрифтов
$directories = [
    'storage/fonts' => '../storage/fonts',
    'public/fonts' => './fonts'
];

echo "<h2>Проверка директорий для шрифтов:</h2>";
echo "<ul>";
foreach ($directories as $name => $path) {
    if (file_exists($path)) {
        echo "<li>$name: <span style='color: green;'>Существует</span></li>";
        
        // Проверка содержимого
        $files = scandir($path);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            echo "<li>$file</li>";
        }
        echo "</ul>";
    } else {
        echo "<li>$name: <span style='color: red;'>Не существует</span></li>";
    }
}
echo "</ul>";

// Проверка наличия системных шрифтов
$systemFonts = [
    'C:\\Windows\\Fonts\\arial.ttf',
    'C:\\Windows\\Fonts\\arialbd.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
];

echo "<h2>Проверка доступности системных шрифтов:</h2>";
echo "<ul>";
foreach ($systemFonts as $font) {
    if (file_exists($font)) {
        echo "<li>$font: <span style='color: green;'>Доступен</span></li>";
    } else {
        echo "<li>$font: <span style='color: red;'>Недоступен</span></li>";
    }
}
echo "</ul>";

// Создание тестовых директорий
echo "<h2>Создание тестовых директорий:</h2>";
$testDirs = [
    '../storage/fonts',
    './fonts'
];

foreach ($testDirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p>Директория создана: $dir</p>";
        } else {
            echo "<p>Ошибка создания директории: $dir</p>";
        }
    } else {
        echo "<p>Директория уже существует: $dir</p>";
    }
}

// Копирование системных шрифтов
echo "<h2>Копирование шрифтов:</h2>";

$winFonts = [
    'arial.ttf' => ['Arial.ttf'],
    'arialbd.ttf' => ['Arial-Bold.ttf']
];

$winFontsDir = 'C:\\Windows\\Fonts\\';
foreach ($winFonts as $src => $dests) {
    $srcPath = $winFontsDir . $src;
    if (file_exists($srcPath)) {
        foreach ($dests as $dest) {
            $destPaths = [
                '../storage/fonts/' . $dest,
                './fonts/' . $dest
            ];
            
            foreach ($destPaths as $destPath) {
                if (copy($srcPath, $destPath)) {
                    echo "<p style='color: green;'>Скопирован: $srcPath -> $destPath</p>";
                } else {
                    echo "<p style='color: red;'>Ошибка копирования: $srcPath -> $destPath</p>";
                }
            }
        }
    } else {
        echo "<p style='color: red;'>Исходный файл не найден: $srcPath</p>";
    }
}

// Инструкции по исправлению проблемы с кириллицей
echo "<h2>Исправление проблемы с кириллицей в PDF:</h2>";
echo "<ol>";
echo "<li>Убедитесь, что директории storage/fonts и public/fonts существуют и доступны для записи.</li>";
echo "<li>Скопируйте шрифты Arial.ttf и Arial-Bold.ttf в обе директории.</li>";
echo "<li>В файле ProjectDocumentController.php убедитесь, что fontDir указывает на одну директорию (строка, а не массив).</li>";
echo "<li>Проверьте, что в методе generatePdf() кодировка UTF-8 указана явно.</li>";
echo "</ol>";

echo "<p><a href='/test_pdf_encoding.php'>Запустить тест генерации PDF</a></p>";
?>
