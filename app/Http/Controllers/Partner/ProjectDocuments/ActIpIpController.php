<?php

namespace App\Http\Controllers\Partner\ProjectDocuments;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;

class ActIpIpController extends BaseDocumentController
{
    /**
     * Генерирует HTML для акта выполненных работ между ИП и ИП
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
        
        // Генерируем HTML документа
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Акт выполненных работ ИП-ИП</title>
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
    </style>
</head>
<body>
    <div class="header">АКТ ВЫПОЛНЕННЫХ РАБОТ № ' . $project->id . '</div>
    <p>г. ' . ($project->city ?? 'Москва') . '                                                         ' . $now->format('d.m.Y') . '</p>
    
    <p>Индивидуальный предприниматель ' . $companyName . ', ИНН ' . $inn . ', ОГРНИП ' . $ogrnip . ', адрес: ' . $address . ', в дальнейшем «Исполнитель», с одной стороны, и</p>
    
    <p>Индивидуальный предприниматель ' . ($project->client_company_name ?? '___________') . ', ИНН ' . ($project->client_inn ?? '___________') . ', в дальнейшем «Заказчик», с другой стороны, составили настоящий Акт о том, что Исполнитель выполнил следующие работы:</p>
    
    <table class="border">
        <tr>
            <th>№</th>
            <th>Наименование работ</th>
            <th>Ед. изм.</th>
            <th>Кол-во</th>
            <th>Цена</th>
            <th>Сумма</th>
        </tr>
        <tr>
            <td>1</td>
            <td>Комплекс ремонтно-отделочных работ по адресу: ' . ($project->address ?? '___________') . '</td>
            <td>усл.</td>
            <td>1</td>
            <td>' . ($project->work_amount ?? '0') . '</td>
            <td>' . ($project->work_amount ?? '0') . '</td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: right;"><strong>Итого:</strong></td>
            <td>' . ($project->work_amount ?? '0') . '</td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: right;"><strong>Без НДС:</strong></td>
            <td>-</td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: right;"><strong>Всего с НДС:</strong></td>
            <td>' . ($project->work_amount ?? '0') . '</td>
        </tr>
    </table>
    
    <p>Всего оказано услуг на сумму ' . ($project->work_amount ?? '0') . ' (' . $this->getAmountInWords($project->work_amount ?? 0) . ') рублей 00 копеек, НДС не облагается в связи с применением Исполнителем упрощенной системы налогообложения.</p>
    
    <p>Вышеперечисленные работы выполнены полностью и в срок. Заказчик претензий по объему, качеству и срокам оказания услуг не имеет.</p>
    
    <table style="width: 100%; margin-top: 50px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <p><b>Исполнитель:</b></p>
                <p>' . $companyName . '<br>
                ИНН: ' . $inn . '<br>
                ОГРНИП: ' . $ogrnip . '<br>
                Адрес: ' . $address . '</p>
                
                <p>_________________________ / ' . ($partner->name ?? '_____________') . ' /</p>
                ' . $signatureHtml . '
                ' . $stampHtml . '
            </td>
            <td style="width: 50%; vertical-align: top;">
                <p><b>Заказчик:</b></p>
                <p>ИП ' . ($project->client_company_name ?? '___________') . '<br>
                ИНН: ' . ($project->client_inn ?? '___________') . '<br>
                Адрес: ' . ($project->client_address ?? '___________') . '</p>
                
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
        return 'Акт_выполненных_работ_ИП-ИП_' . $project->id;
    }
}
