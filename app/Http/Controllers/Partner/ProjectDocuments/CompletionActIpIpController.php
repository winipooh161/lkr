<?php

namespace App\Http\Controllers\Partner\ProjectDocuments;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;

class CompletionActIpIpController extends BaseDocumentController
{
    /**
     * Генерирует HTML для акта завершения работ между ИП и ИП
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
        
        // Подготавливаем значения для вставки в документ
        $clientCompanyName = $project->client_company_name ?? '___________';
        $clientInn = $project->client_inn ?? '___________';
        $projectAddress = $project->address ?? '___________';
        $workAmount = $project->work_amount ?? '0';
        $amountInWords = $this->getAmountInWords($project->work_amount ?? 0);
        $partnerName = $partner->name ?? '_____________';
        $clientAddress = $project->client_address ?? '___________';
        $clientName = $project->client_name ?? '___________';
        $city = $project->city ?: 'Москва';
        
        // Генерируем HTML документа
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Акт завершения ремонта ИП-ИП</title>
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
            </style>
        </head>
        <body>
            <div class="header">АКТ ЗАВЕРШЕНИЯ РЕМОНТНЫХ РАБОТ</div>
            <p>г. {$city} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {$formattedDateTime}</p>
            
            <p>Индивидуальный предприниматель {$companyName}, ИНН {$inn}, ОГРНИП {$ogrnip}, адрес: {$address}, в дальнейшем «Исполнитель», с одной стороны, и</p>
            
            <p>Индивидуальный предприниматель {$clientCompanyName}, ИНН {$clientInn}, в дальнейшем «Заказчик», с другой стороны, составили настоящий Акт о том, что Исполнитель выполнил, а Заказчик принял следующие работы:</p>
            
            <p>1. Комплекс ремонтно-отделочных работ по адресу: {$projectAddress}</p>
            
            <p>2. Стоимость выполненных работ составляет {$workAmount} ({$amountInWords}) рублей 00 копеек, НДС не облагается в связи с применением Исполнителем упрощенной системы налогообложения.</p>
            
            <p>3. Претензий к качеству выполненных работ и используемых материалов Заказчик не имеет.</p>
            
            <p>4. Настоящий Акт составлен в двух экземплярах, имеющих одинаковую юридическую силу, по одному экземпляру для каждой из Сторон.</p>
            
            <table style="width: 100%; margin-top: 50px;">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <p><b>Исполнитель:</b></p>
                        <p>{$companyName}<br>
                        ИНН: {$inn}<br>
                        ОГРНИП: {$ogrnip}<br>
                        Адрес: {$address}</p>
                        
                        <p>_________________________ / {$partnerName} /</p>
                        {$signatureHtml}
                        {$stampHtml}
                    </td>
                    <td style="width: 50%; vertical-align: top;">
                        <p><b>Заказчик:</b></p>
                        <p>ИП {$clientCompanyName}<br>
                        ИНН: {$clientInn}<br>
                        Адрес: {$clientAddress}</p>
                        
                        <p>_________________________ / {$clientName} /</p>
                    </td>
                </tr>
            </table>
            
            <p style="margin-top: 50px; font-size: 10pt; color: #777;">
                Документ сгенерирован {$formattedDateTime}
            </p>
        </body>
        </html>
        HTML;
        
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
        return 'Акт_завершения_ремонта_ИП-ИП_' . $project->id;
    }
}
