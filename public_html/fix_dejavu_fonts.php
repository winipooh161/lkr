<?php

// Скрипт для копирования шрифтов DejaVu Sans из известных системных директорий или загрузки их из сети
require_once '../vendor/autoload.php';

// Определяем директории для сохранения
$targetDirectories = [
    '../storage/fonts/',
    './fonts/'
];

// Создаем директории, если они не существуют
foreach ($targetDirectories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Создана директория: $dir<br>";
        } else {
            echo "Ошибка создания директории: $dir<br>";
        }
    }
}

// Список шрифтов и возможных источников
$fonts = [
    'DejaVuSans.ttf' => [
        'C:/Windows/Fonts/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        'https://github.com/dejavu-fonts/dejavu-fonts/raw/master/ttf/DejaVuSans.ttf'
    ],
    'DejaVuSans-Bold.ttf' => [
        'C:/Windows/Fonts/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        'https://github.com/dejavu-fonts/dejavu-fonts/raw/master/ttf/DejaVuSans-Bold.ttf'
    ]
];

// Функция для загрузки файла из интернета
function downloadFile($url, $path) {
    $ch = curl_init($url);
    $fp = fopen($path, 'w');
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
    $success = curl_exec($ch);
    
    if ($success) {
        curl_close($ch);
        fclose($fp);
        echo "Успешно загружен файл с $url в $path<br>";
        return true;
    } else {
        echo "Ошибка загрузки файла с $url: " . curl_error($ch) . "<br>";
        curl_close($ch);
        fclose($fp);
        return false;
    }
}

// Пытаемся скопировать или загрузить каждый шрифт
foreach ($fonts as $fontName => $sources) {
    $fontCopied = false;
    
    // Сначала проверяем локальные пути
    foreach ($sources as $source) {
        if (strpos($source, 'http') !== 0 && file_exists($source)) {
            foreach ($targetDirectories as $dir) {
                $targetPath = $dir . $fontName;
                if (copy($source, $targetPath)) {
                    echo "Скопирован $fontName из $source в $targetPath<br>";
                    $fontCopied = true;
                }
            }
            if ($fontCopied) break;
        }
    }
    
    // Если не удалось скопировать локально, пробуем загрузить из сети
    if (!$fontCopied) {
        foreach ($sources as $source) {
            if (strpos($source, 'http') === 0) {
                foreach ($targetDirectories as $dir) {
                    $targetPath = $dir . $fontName;
                    if (downloadFile($source, $targetPath)) {
                        $fontCopied = true;
                        break;
                    }
                }
            }
            if ($fontCopied) break;
        }
    }
    
    if (!$fontCopied) {
        echo "Не удалось скопировать или загрузить $fontName<br>";
    }
}

// Обновляем кэш шрифтов в dompdf
echo "<h2>Проверяем шрифты после загрузки:</h2>";
foreach ($targetDirectories as $dir) {
    if (file_exists($dir)) {
        echo "<h3>Содержимое директории $dir:</h3>";
        $files = scandir($dir);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "<li>$file - " . filesize($dir . $file) . " байт</li>";
            }
        }
        echo "</ul>";
    }
}

echo "<p>Процесс загрузки шрифтов DejaVu Sans завершен.</p>";
echo "<p>Теперь нужно закомментировать упоминания DejaVuSans в контроллере и изменить defaultFont на Arial.</p>";
echo "<a href='test_pdf_encoding.php'>Запустить тест PDF с кириллицей</a>";
?>
