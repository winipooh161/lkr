<?php

// Скрипт для проверки и настройки шрифтов для DOMPDF

// Устанавливаем кодировку страницы
header('Content-Type: text/html; charset=utf-8');

// Подключаем autoloader
require_once __DIR__ . '/../vendor/autoload.php';

echo "<h1>Проверка и установка шрифтов для DOMPDF</h1>";

// Директории для шрифтов
$storageDir = __DIR__ . '/../storage/fonts/';
$publicDir = __DIR__ . '/fonts/';
$winFontsDir = 'C:\\Windows\\Fonts\\';

// Создаем директории, если их нет
if (!file_exists($storageDir)) {
    if (mkdir($storageDir, 0755, true)) {
        echo "<p style='color: green;'>Создана директория: $storageDir</p>";
    } else {
        echo "<p style='color: red;'>Ошибка при создании директории: $storageDir</p>";
    }
}

if (!file_exists($publicDir)) {
    if (mkdir($publicDir, 0755, true)) {
        echo "<p style='color: green;'>Создана директория: $publicDir</p>";
    } else {
        echo "<p style='color: red;'>Ошибка при создании директории: $publicDir</p>";
    }
}

// Список необходимых шрифтов
$requiredFonts = [
    'arial.ttf' => ['Arial.ttf', 'arial.ttf'],
    'arialbd.ttf' => ['Arial-Bold.ttf', 'arialbd.ttf'],
];

// Копируем шрифты
foreach ($requiredFonts as $winFont => $destinations) {
    $sourcePath = $winFontsDir . $winFont;
    
    if (file_exists($sourcePath)) {
        // Копируем в storage/fonts
        $destPath = $storageDir . $destinations[0];
        if (copy($sourcePath, $destPath)) {
            echo "<p style='color: green;'>Шрифт скопирован: $sourcePath -> $destPath</p>";
        } else {
            echo "<p style='color: red;'>Ошибка при копировании шрифта: $sourcePath -> $destPath</p>";
        }
        
        // Копируем в public/fonts
        $destPath = $publicDir . $destinations[0];
        if (copy($sourcePath, $destPath)) {
            echo "<p style='color: green;'>Шрифт скопирован: $sourcePath -> $destPath</p>";
        } else {
            echo "<p style='color: red;'>Ошибка при копировании шрифта: $sourcePath -> $destPath</p>";
        }
    } else {
        echo "<p style='color: red;'>Шрифт не найден: $sourcePath</p>";
    }
}

// Проверяем существующие шрифты
echo "<h2>Проверка установленных шрифтов</h2>";

echo "<h3>В storage/fonts:</h3>";
echo "<ul>";
$files = scandir($storageDir);
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    echo "<li>$file</li>";
}
echo "</ul>";

echo "<h3>В public/fonts:</h3>";
echo "<ul>";
$files = scandir($publicDir);
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    echo "<li>$file</li>";
}
echo "</ul>";

echo "<p><a href='/test_pdf_encoding.php'>Запустить тест генерации PDF с кириллицей</a></p>";
?>
