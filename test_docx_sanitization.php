<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\Partner\ProjectDocuments\CompletionActIpIpController;
use App\Models\Project;
use App\Models\User;

// Создаем простой HTML для тестирования
$testHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Тестовый документ</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">ТЕСТОВЫЙ ДОКУМЕНТ</div>
    <p>Это тестовый параграф с &nbsp; специальными символами &amp; знаками.</p>
    <table border="1">
        <tr>
            <td>Ячейка 1</td>
            <td>Ячейка 2</td>
        </tr>
    </table>
</body>
</html>';

echo "Тестовый HTML:\n";
echo $testHtml;
echo "\n\nТестирование санитизации...\n";

// Создаем экземпляр контроллера для доступа к методу санитизации
$controller = new class extends \App\Http\Controllers\Partner\ProjectDocuments\BaseDocumentController {
    public function testSanitize($html) {
        return $this->sanitizeHtmlForPhpWord($html);
    }
    
    protected function getDocumentHtml($project, $partner, $includeSignature, $includeStamp): string {
        return '';
    }
    
    protected function getFileName($project): string {
        return 'test.docx';
    }
};

$sanitizedHtml = $controller->testSanitize($testHtml);
echo "Санитизированный HTML:\n";
echo $sanitizedHtml;
