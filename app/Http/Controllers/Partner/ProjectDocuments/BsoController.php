<?php

namespace App\Http\Controllers\Partner\ProjectDocuments;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;

class BsoController extends BaseDocumentController
{
    /**
     * Генерирует HTML для БСО (бланка строгой отчетности)
     *
     * @param  Project  $project
     * @param  User  $partner
     * @param  bool  $includeSignature
     * @param  bool  $includeStamp
     * @return string
     */
    protected function getDocumentHtml(Project $project, User $partner, bool $includeSignature, bool $includeStamp): string
    {
        $now = Carbon::now();
        $formattedDateTime = $now->format('d.m.Y, H:i');
        
        // Используем общую функцию для генерации HTML подписи и печати
        $signatureAndStamp = $this->generateSignatureAndStampHtml($partner, $includeSignature, $includeStamp);
        $signatureHtml = $signatureAndStamp['signature'];
        $stampHtml = $signatureAndStamp['stamp'];
        
        // Формируем данные для документа
        $companyName = $partner->company_name ?? 'ИП ' . $partner->name;
        $inn = $partner->inn ?? '---';
        $ogrnip = $partner->ogrnip ?? '---';
        $address = $partner->legal_address ?? '---';
        
        // Текущий номер БСО
        $bsoNumber = $project->id . '-БСО';
        
        // Генерируем HTML документа
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Бланк строгой отчетности</title>
    <style>
        body {
            font-family: "DejaVu Sans", "Arial", sans-serif;
            line-height: 1.4;
            font-size: 12pt;
        }
        .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 14pt;
        }
        .subheader {
            text-align: center;
            margin-bottom: 15px;
            font-style: italic;
        }
        .signature-container { 
            display: inline-block; 
            margin-right: 20px;
        }
        .stamp-container { 
            display: inline-block; 
            position: relative;
            top: -20px;
        }
        .border-box {
            border: 1px solid black;
            padding: 10px;
            margin-bottom: 15px;
        }
        .form-row {
            display: flex;
            margin-bottom: 10px;
        }
        .form-label {
            width: 200px;
            font-weight: bold;
        }
        .form-value {
            flex-grow: 1;
            border-bottom: 1px solid #999;
            padding-left: 5px;
        }
    </style>
</head>
<body>
    <div class="header">БЛАНК СТРОГОЙ ОТЧЕТНОСТИ № ' . $bsoNumber . '</div>
    <div class="subheader">Приравнивается к кассовому чеку</div>
    
    <div class="border-box">
        <div class="form-row">
            <div class="form-label">Дата оформления:</div>
            <div class="form-value">' . $now->format('d.m.Y') . '</div>
        </div>
        
        <div class="form-row">
            <div class="form-label">Исполнитель:</div>
            <div class="form-value">' . $companyName . '</div>
        </div>
        
        <div class="form-row">
            <div class="form-label">ИНН:</div>
            <div class="form-value">' . $inn . '</div>
        </div>
        
        <div class="form-row">
            <div class="form-label">ОГРНИП:</div>
            <div class="form-value">' . $ogrnip . '</div>
        </div>
        
        <div class="form-row">
            <div class="form-label">Адрес:</div>
            <div class="form-value">' . $address . '</div>
        </div>
    </div>
    
    <div class="border-box">
        <div class="form-row">
            <div class="form-label">Заказчик:</div>
            <div class="form-value">' . ($project->client_name ?? '___________') . '</div>
        </div>
        
        <div class="form-row">
            <div class="form-label">Предоставлены услуги:</div>
            <div class="form-value">Комплекс ремонтно-отделочных работ</div>
        </div>
        
        <div class="form-row">
            <div class="form-label">Адрес оказания услуг:</div>
            <div class="form-value">' . ($project->address ?? '___________') . '</div>
        </div>
        
        <div class="form-row">
            <div class="form-label">Сумма, руб.:</div>
            <div class="form-value">' . ($project->work_amount ?? '0') . '</div>
        </div>
        
        <div class="form-row">
            <div class="form-label">Сумма прописью:</div>
            <div class="form-value">' . $this->getAmountInWords($project->work_amount ?? 0) . ' рублей 00 копеек</div>
        </div>
        
        <div class="form-row">
            <div class="form-label">Без НДС</div>
            <div class="form-value"></div>
        </div>
    </div>
    
    <p>Оплата получена полностью. Претензий по объему, качеству и срокам оказания услуги не имею.</p>
    
    <table style="width: 100%; margin-top: 30px;">
        <tr>
            <td style="width: 50%;">
                <p><strong>Исполнитель:</strong></p>
                <p>_________________________ / ' . ($partner->name ?? '_____________') . ' /</p>
                ' . $signatureHtml . '
                ' . $stampHtml . '
            </td>
            <td style="width: 50%;">
                <p><strong>Заказчик:</strong></p>
                <p>_________________________ / ' . ($project->client_name ?? '___________') . ' /</p>
            </td>
        </tr>
    </table>
    
    <p style="margin-top: 50px; font-size: 10pt; color: #777;">
        Документ сгенерирован ' . $formattedDateTime . '
    </p>
</body>
</html>';
        
        return $html;
    }

    /**
     * Возвращает имя файла для документа
     *
     * @param  Project  $project
     * @return string
     */
    protected function getFileName(Project $project): string
    {
        return 'БСО_' . $project->id;
    }
}
