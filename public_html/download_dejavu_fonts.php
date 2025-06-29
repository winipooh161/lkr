<?php

/**
 * Скрипт для загрузки и установки шрифтов DejaVu для корректной работы PDF с кириллицей
 */

// Директория для шрифтов
$dejavu_dir = __DIR__ . '/fonts/dejavu/';

// Создаем директорию, если она не существует
if (!is_dir($dejavu_dir)) {
    mkdir($dejavu_dir, 0755, true);
    echo "Создана директория для шрифтов: {$dejavu_dir}\n";
}

// Список необходимых шрифтов DejaVu
$fonts = [
    'DejaVuSans.ttf' => 'https://github.com/dejavu-fonts/dejavu-fonts/raw/master/ttf/DejaVuSans.ttf',
    'DejaVuSans-Bold.ttf' => 'https://github.com/dejavu-fonts/dejavu-fonts/raw/master/ttf/DejaVuSans-Bold.ttf',
    'DejaVuSerif.ttf' => 'https://github.com/dejavu-fonts/dejavu-fonts/raw/master/ttf/DejaVuSerif.ttf',
    'DejaVuSerif-Bold.ttf' => 'https://github.com/dejavu-fonts/dejavu-fonts/raw/master/ttf/DejaVuSerif-Bold.ttf',
];

// Загружаем шрифты
foreach ($fonts as $font_name => $font_url) {
    $font_path = $dejavu_dir . $font_name;
    
    // Проверяем, если файл уже существует
    if (file_exists($font_path)) {
        echo "Файл {$font_name} уже существует.\n";
        continue;
    }
    
    // Скачиваем шрифт
    echo "Скачивание {$font_name}...\n";
    
    $font_data = @file_get_contents($font_url);
    if ($font_data === false) {
        echo "Ошибка при скачивании: {$font_name}\n";
        continue;
    }
    
    // Сохраняем шрифт
    if (file_put_contents($font_path, $font_data)) {
        echo "Шрифт {$font_name} успешно скачан и сохранен.\n";
    } else {
        echo "Ошибка при сохранении шрифта {$font_name}\n";
    }
}

// Создаем файл для информации о шрифтах
$info_file = $dejavu_dir . 'dejavu-info.txt';
$info_content = "DejaVu Fonts\n";
$info_content .= "Установлены: " . date('Y-m-d H:i:s') . "\n";
$info_content .= "Для использования в PDF документах с кириллицей\n";

file_put_contents($info_file, $info_content);

// Выводим результат
echo "\nУстановка шрифтов завершена.\n";
echo "Установлены следующие шрифты:\n";

foreach ($fonts as $font_name => $font_url) {
    $font_path = $dejavu_dir . $font_name;
    if (file_exists($font_path)) {
        $size = filesize($font_path);
        echo "- {$font_name} (" . round($size / 1024, 2) . " KB)\n";
    } else {
        echo "- {$font_name} (не установлен)\n";
    }
}
