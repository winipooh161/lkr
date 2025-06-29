<?php

namespace App\Http\Controllers\Partner\ProjectDocuments;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;

class InvoiceFlController extends BaseDocumentController
{
    /**
     * Генерирует HTML для счета ФЛ
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
        $bank = $partner->bank_name ?? '---';
        $bik = $partner->bank_bik ?? '---';
        $account = $partner->bank_account ?? '---';
        $corAccount = $partner->bank_cor_account ?? '---';
        
        // Генерируем HTML документа
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Счет на оплату</title>
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
            font-size: 16pt;
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
        table.border {
            border-collapse: collapse;
            width: 100%;
            margin: 15px 0;
        }
        table.border th, table.border td {
            border: 1px solid black;
            padding: 5px;
        }
        .bank-details {
            border: 1px solid black;
            padding: 10px;
            margin-bottom: 20px;
        }
        .bank-details table {
            width: 100%;
        }
        .bank-details td {
            padding: 3px;
        }
    </style>
</head>
<body>
    <div class="bank-details">
        <table>
            <tr>
                <td width="15%">ИНН ' . $inn . '</td>
                <td width="50%">КПП</td>
                <td width="35%" rowspan="2">Сч. № ' . $account . '</td>
            </tr>
            <tr>
                <td colspan="2">Получатель<br>' . $companyName . '</td>
            </tr>
            <tr>
                <td colspan="2">Банк получателя<br>' . $bank . '</td>
                <td>БИК ' . $bik . '<br>Сч. № ' . $corAccount . '</td>
            </tr>
        </table>
    </div>

    <div class="header">СЧЕТ № ' . $project->id . ' от ' . $now->format('d.m.Y') . '</div>
    
    <table style="width: 100%;">
        <tr>
            <td><strong>Поставщик:</strong></td>
            <td>' . $companyName . ', ИНН ' . $inn . ', ОГРНИП ' . $ogrnip . ',<br>' . $address . '</td>
        </tr>
        <tr>
            <td><strong>Покупатель:</strong></td>
            <td>' . ($project->client_name ?? '_______________') . ', паспорт: серия ' . ($project->client_passport_series ?? '____') . ' № ' . ($project->client_passport_number ?? '______') . ', выдан ' . ($project->client_passport_issued_by ?? '_________________') . ' ' . ($project->client_passport_date ?? '__.__.____') . ',<br>' . ($project->client_address ?? '___________') . '</td>
        </tr>
    </table>
    
    <table class="border">
        <tr>
            <th>№</th>
            <th>Наименование товара/услуги</th>
            <th>Кол-во</th>
            <th>Ед.</th>
            <th>Цена</th>
            <th>Сумма</th>
        </tr>
        <tr>
            <td>1</td>
            <td>Комплекс ремонтно-отделочных работ по адресу: ' . ($project->address ?? '___________') . '</td>
            <td>1</td>
            <td>усл.</td>
            <td>' . ($project->work_amount ?? '0') . '</td>
            <td>' . ($project->work_amount ?? '0') . '</td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: right;"><strong>Итого:</strong></td>
            <td>' . ($project->work_amount ?? '0') . '</td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: right;"><strong>Без налога (НДС):</strong></td>
            <td>-</td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: right;"><strong>Всего к оплате:</strong></td>
            <td>' . ($project->work_amount ?? '0') . '</td>
        </tr>
    </table>
    
    <p>Всего наименований 1, на сумму ' . ($project->work_amount ?? '0') . ' (' . $this->getAmountInWords($project->work_amount ?? 0) . ') рублей 00 копеек</p>
    <p>Без НДС</p>
    
    <div style="margin-top: 30px;">
        <p><strong>Руководитель предприятия</strong> _________________________ / ' . ($partner->name ?? '_____________') . ' /</p>
        ' . $signatureHtml . '
        
        <p><strong>Главный бухгалтер</strong> _________________________ / ' . ($partner->name ?? '_____________') . ' /</p>
        
        <div style="margin-top: 20px;">
            ' . $stampHtml . '
        </div>
    </div>
    
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
        return 'Счет_ФЛ_' . $project->id;
    }
}
