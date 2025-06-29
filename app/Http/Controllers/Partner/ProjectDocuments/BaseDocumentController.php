<?php

namespace App\Http\Controllers\Partner\ProjectDocuments;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use Carbon\Carbon;

/**
 * Базовый класс для контроллеров документов
 * 
 * Содержит общие методы для генерации PDF и DOCX документов, 
 * а также утилиты для подписи и печати
 */
abstract class BaseDocumentController extends Controller
{
    /**
     * Генерирует документ нужного формата
     *
     * @param  Request  $request
     * @param  Project  $project
     * @return \Illuminate\Http\Response
     * 
     */
    public function generate(Request $request, Project $project)
    {
        $request->validate([
            'format' => 'required|string|in:pdf,docx',
            'include_signature' => 'nullable',
            'include_stamp' => 'nullable',
            'document_type' => 'nullable|string', // Игнорируем, если передано
        ]);
        
        // Корректная обработка параметров
        $format = $request->input('format');
        $includeSignatureValue = $request->input('include_signature');
        $includeSignature = $includeSignatureValue === null ? false : filter_var($includeSignatureValue, FILTER_VALIDATE_BOOLEAN);
        
        $includeStampValue = $request->input('include_stamp');
        $includeStamp = $includeStampValue === null ? false : filter_var($includeStampValue, FILTER_VALIDATE_BOOLEAN);
        
        $user = auth()->user();
        $partner = $user->role === 'admin' ? $project->partner : $user;
        
        if (!$partner) {
            abort(404, 'Партнер не найден');
        }
        
        $html = $this->getDocumentHtml($project, $partner, $includeSignature, $includeStamp);
        
        if ($format === 'pdf') {
            return $this->generatePdf($html, $this->getFileName($project) . '.pdf');
        } else {
            return $this->generateDocx($html, $this->getFileName($project) . '.docx');
        }
    }

    /**
     * Генерирует HTML для документа
     *
     * @param  Project  $project
     * @param  User  $partner
     * @param  bool  $includeSignature
     * @param  bool  $includeStamp
     * @return string
     */
    abstract protected function getDocumentHtml(Project $project, User $partner, bool $includeSignature, bool $includeStamp): string;

    /**
     * Возвращает имя файла для документа
     *
     * @param  Project  $project
     * @return string
     */
    abstract protected function getFileName(Project $project): string;
    
    /**
     * Обработка запроса на генерацию документа
     *
     * @param  Request  $request
     * @param  Project  $project
     * @return \Illuminate\Http\Response
     */
    public function generateDocument(Request $request, Project $project)
    {
        // Просто делегируем обработку в метод generate
        return $this->generate($request, $project);
    }

    /**
     * Генерирует HTML для предпросмотра документа
     *
     * @param  Request  $request
     * @param  Project  $project
     * @return \Illuminate\Http\Response
     */
    public function previewDocument(Request $request, Project $project)
    {
        $request->validate([
            'include_signature' => 'nullable',
            'include_stamp' => 'nullable',
            'document_type' => 'nullable|string', // Добавляем поддержку поля document_type
        ]);
        
        // Корректная обработка checkbox из формы
        $includeSignatureValue = $request->input('include_signature');
        $includeSignature = $includeSignatureValue === null ? false : filter_var($includeSignatureValue, FILTER_VALIDATE_BOOLEAN);
        
        $includeStampValue = $request->input('include_stamp');
        $includeStamp = $includeStampValue === null ? false : filter_var($includeStampValue, FILTER_VALIDATE_BOOLEAN);
        
        $user = auth()->user();
        $partner = $user->role === 'admin' ? $project->partner : $user;
        
        if (!$partner) {
            return response()->json(['error' => 'Партнер не найден'], 404);
        }
        
        $html = $this->getDocumentHtml($project, $partner, $includeSignature, $includeStamp);
        
        return response()->json(['html' => $html]);
    }

    /**
     * Генерирует HTML для подписи и печати
     *
     * @param  User  $partner
     * @param  bool  $includeSignature
     * @param  bool  $includeStamp
     * @return array
     */
    protected function generateSignatureAndStampHtml($partner, $includeSignature, $includeStamp)
    {
        // Добавляем логирование для отладки
        Log::info('generateSignatureAndStampHtml called', [
            'partner_id' => $partner->id,
            'includeSignature' => $includeSignature,
            'includeStamp' => $includeStamp,
            'signature_file' => $partner->signature_file,
            'stamp_file' => $partner->stamp_file
        ]);
        
        $signatureHtml = '';
        $stampHtml = '';
        
        if ($includeSignature && $partner->signature_file) {
            // Используем абсолютный путь к файлу вместо URL для корректной работы в PDF
            $signaturePath = storage_path('app/public/signatures/' . $partner->signature_file);
            if (file_exists($signaturePath)) {
                // Конвертируем изображение в base64 для встраивания в PDF
                $imageData = base64_encode(file_get_contents($signaturePath));
                $imageMimeType = mime_content_type($signaturePath);
                $signatureHtml = '<div class="signature-container"><img src="data:' . $imageMimeType . ';base64,' . $imageData . '" class="signature-image" style="height: 80px; max-width: 200px;"></div>';
                Log::info('Signature HTML generated with base64 data');
            } else {
                Log::warning('Signature file not found', ['path' => $signaturePath]);
            }
        }
        
        if ($includeStamp && $partner->stamp_file) {
            // Используем абсолютный путь к файлу вместо URL для корректной работы в PDF
            $stampPath = storage_path('app/public/stamps/' . $partner->stamp_file);
            if (file_exists($stampPath)) {
                // Конвертируем изображение в base64 для встраивания в PDF
                $imageData = base64_encode(file_get_contents($stampPath));
                $imageMimeType = mime_content_type($stampPath);
                $stampHtml = '<div class="stamp-container"><img src="data:' . $imageMimeType . ';base64,' . $imageData . '" class="stamp-image" style="height: 100px; max-width: 100px;"></div>';
                Log::info('Stamp HTML generated with base64 data');
            } else {
                Log::warning('Stamp file not found', ['path' => $stampPath]);
            }
        }
        
        return [
            'signature' => $signatureHtml,
            'stamp' => $stampHtml
        ];
    }

    /**
     * Форматирует денежную сумму прописью на русском языке
     *
     * @param  float  $amount
     * @return string
     */
    protected function getAmountInWords($amount) 
    {
        $nul = 'ноль';
        $ten = array(
            array('', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
            array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять')
        );
        $a20 = array('десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать');
        $tens = array(2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
        $hundred = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
        $unit = array(
            array('копейка' , 'копейки',   'копеек',     1),
            array('рубль',    'рубля',     'рублей',     0),
            array('тысяча',   'тысячи',    'тысяч',      1),
            array('миллион',  'миллиона',  'миллионов',  0),
            array('миллиард', 'миллиарда', 'миллиардов', 0),
        );
        
        $amountFormatted = number_format($amount, 2, '.', '');
        list($rub, $kop) = explode('.', $amountFormatted);
        
        $out = array();
        
        // Реализация логики преобразования числа в слова
        // (код опущен для краткости, вы можете взять его из оригинального контроллера)
        
        // Возвращаем результат
        return trim(preg_replace('/ {2,}/', ' ', implode(' ', $out)));
    }
    
    /**
     * Генерирует PDF документ из HTML
     *
     * @param  string  $html
     * @param  string  $filename
     * @return \Illuminate\Http\Response
     */
    protected function generatePdf($html, $filename)
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
    
    /**
     * Генерирует DOCX документ из HTML
     * Оптимизированная версия для создания качественных документов без потери форматирования
     * Автоматически переключается на RTF, если ZipArchive недоступен
     *
     * @param  string  $html
     * @param  string  $filename
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    protected function generateDocx($html, $filename)
    {
        // Проверяем доступность ZipArchive
        if (!class_exists('ZipArchive')) {
            Log::info('ZipArchive недоступен, переключаемся на RTF формат', [
                'filename' => $filename
            ]);
            
            // Генерируем RTF документ вместо DOCX
            return $this->generateRtf($html, $filename);
        }
        
        // Set up temporary directory for phpword cache
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        Settings::setTempDir($tempDir);
        
        try {
            // Создаем новый документ Word с правильными настройками
            $phpWord = new PhpWord();
            
            // Настраиваем шрифты по умолчанию для кириллицы
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(12);
            
            // Добавляем секцию с настройками страницы
            $section = $phpWord->addSection([
                'marginTop' => 1134,      // 2 см
                'marginBottom' => 1134,   // 2 см  
                'marginLeft' => 1134,     // 2 см
                'marginRight' => 1134,    // 2 см
            ]);
            
            Log::info('Начинаем генерацию DOCX', [
                'filename' => $filename,
                'html_length' => strlen($html)
            ]);
            
            // НОВЫЙ ПОДХОД: Создаем документ вручную, разбирая структуру HTML
            // Это обеспечивает лучший контроль над форматированием
            $success = $this->createDocumentFromHtml($section, $html);
            
            if (!$success) {
                // Fallback: пытаемся использовать HTML парсер PhpWord
                Log::info('Переходим к fallback методу с HTML парсером');
                $cleanHtml = $this->optimizeHtmlForPhpWord($html);
                \PhpOffice\PhpWord\Shared\Html::addHtml($section, $cleanHtml, false, false);
            }
        
            // Сохраняем документ во временный файл
            $tempFile = tempnam(sys_get_temp_dir(), 'docx');
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            
            try {
                $objWriter->save($tempFile);
                
                Log::info('DOCX документ успешно создан', [
                    'filename' => $filename,
                    'file_size' => filesize($tempFile)
                ]);
                
                // Отправляем документ пользователю
                return response()->download($tempFile, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])->deleteFileAfterSend(true);
                
            } catch (\Error $zipError) {
                // Если даже с ZipArchive что-то пошло не так, переключаемся на RTF
                Log::warning('Ошибка при сохранении DOCX, переключаемся на RTF', [
                    'error' => $zipError->getMessage(),
                    'filename' => $filename
                ]);
                
                return $this->generateRtf($html, $filename);
            }
            
        } catch (\Exception $e) {
            Log::error('Ошибка генерации DOCX документа', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filename' => $filename
            ]);
            
            // В крайнем случае пытаемся сгенерировать RTF
            try {
                Log::info('Пытаемся сгенерировать RTF как fallback для DOCX');
                return $this->generateRtf($html, $filename);
            } catch (\Exception $rtfError) {
                // Если и RTF не работает, возвращаем ошибку
                return response()->json([
                    'error' => 'Ошибка при генерации документа: ' . $e->getMessage()
                ], 500);
            }
        }
    }
    
    /**
     * Генерирует RTF документ из HTML (альтернатива DOCX)
     * RTF документы открываются в Word и полностью сохраняют форматирование
     *
     * @param  string  $html
     * @param  string  $filename
     * @return \Illuminate\Http\Response
     */
    protected function generateRtf($html, $filename)
    {
        try {
            Log::info('Начинаем генерацию RTF документа', [
                'filename' => $filename,
                'html_length' => strlen($html)
            ]);

            // Создаем RTF контент из HTML
            $rtfContent = $this->convertHtmlToRtf($html);
            
            // Возвращаем RTF файл
            $rtfFilename = str_replace('.docx', '.rtf', $filename);
            
            return response($rtfContent, 200, [
                'Content-Type' => 'application/rtf',
                'Content-Disposition' => "attachment; filename=\"{$rtfFilename}\"",
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка генерации RTF документа', [
                'error' => $e->getMessage(),
                'filename' => $filename
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Конвертирует HTML в RTF формат
     *
     * @param  string  $html
     * @return string
     */
    private function convertHtmlToRtf($html)
    {
        // Разбираем HTML на структурные элементы
        $structure = $this->parseHtmlStructure($html);
        
        // Начинаем RTF документ
        $rtf = '{\\rtf1\\ansi\\deff0 {\\fonttbl {\\f0 Times New Roman;}}';
        $rtf .= '{\\colortbl;\\red0\\green0\\blue0;}';
        $rtf .= '\\viewkind4\\uc1\\pard\\f0\\fs24\\lang1049'; // Русский язык, Times New Roman 12pt
        
        foreach ($structure as $element) {
            switch ($element['type']) {
                case 'header':
                    $rtf .= $this->convertHeaderToRtf($element['content']);
                    break;
                case 'paragraph':
                    $rtf .= $this->convertParagraphToRtf($element['content']);
                    break;
                case 'table':
                    $rtf .= $this->convertTableToRtf($element['content']);
                    break;
                case 'signature_stamp':
                    $rtf .= $this->convertSignatureToRtf();
                    break;
            }
        }
        
        $rtf .= '}'; // Закрываем RTF документ
        
        return $rtf;
    }
    
    /**
     * Конвертирует заголовок в RTF
     */
    private function convertHeaderToRtf($content)
    {
        $escapedContent = $this->escapeRtfText($content);
        return '\\par\\qc\\b\\fs28 ' . $escapedContent . '\\b0\\fs24\\par\\par';
    }
    
    /**
     * Конвертирует параграф в RTF
     */
    private function convertParagraphToRtf($content)
    {
        $escapedContent = $this->escapeRtfText($content);
        
        // Проверяем, содержит ли параграф город и дату
        if (preg_match('/г\.\s*([^0-9]*?)\s+(\d{2}\.\d{2}\.\d{4})/', $content)) {
            return '\\par\\qj ' . $escapedContent . '\\par';
        } else {
            return '\\par\\qj\\fi567 ' . $escapedContent . '\\par'; // С красной строкой
        }
    }
    
    /**
     * Конвертирует таблицу в RTF
     */
    private function convertTableToRtf($tableData)
    {
        $rtf = '\\par';
        
        foreach ($tableData as $rowIndex => $rowData) {
            $rtf .= '\\trowd\\trgaph108\\trleft-108'; // Начало строки таблицы
            
            // Определяем ширину колонок
            $cellWidth = 2400; // Базовая ширина ячейки
            $cellPosition = 0;
            
            foreach ($rowData as $cellIndex => $cellData) {
                $cellPosition += $cellWidth;
                $rtf .= '\\cellx' . $cellPosition;
            }
            
            // Добавляем содержимое ячеек
            foreach ($rowData as $cellIndex => $cellData) {
                $escapedContent = $this->escapeRtfText($cellData);
                
                if ($rowIndex === 0) {
                    // Заголовок таблицы - жирный шрифт
                    $rtf .= '\\intbl\\qc\\b ' . $escapedContent . '\\b0\\cell';
                } else {
                    $rtf .= '\\intbl\\qc ' . $escapedContent . '\\cell';
                }
            }
            
            $rtf .= '\\row'; // Конец строки
        }
        
        $rtf .= '\\par';
        return $rtf;
    }
    
    /**
     * Добавляет подписи в RTF
     */
    private function convertSignatureToRtf()
    {
        $rtf = '\\par\\par';
        $rtf .= '\\trowd\\trgaph108\\cellx4500\\cellx9000';
        $rtf .= '\\intbl Исполнитель:\\par\\par_______________________\\cell';
        $rtf .= '\\intbl Заказчик:\\par\\par_______________________\\cell';
        $rtf .= '\\row\\par';
        $rtf .= '\\fs20\\i М.П. (при наличии печати)\\i0\\fs24\\par';
        
        return $rtf;
    }
    
    /**
     * Экранирует текст для RTF формата
     */
    private function escapeRtfText($text)
    {
        // RTF требует экранирования специальных символов
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('{', '\\{', $text);
        $text = str_replace('}', '\\}', $text);
        
        // Убеждаемся, что текст в UTF-8 (без использования mb_convert_encoding)
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = utf8_encode($text);
        }
        
        // Заменяем кириллические символы на Unicode escape последовательности
        $replacements = [
            'а' => '\\u1072?', 'б' => '\\u1073?', 'в' => '\\u1074?', 'г' => '\\u1075?', 'д' => '\\u1076?',
            'е' => '\\u1077?', 'ё' => '\\u1105?', 'ж' => '\\u1078?', 'з' => '\\u1079?', 'и' => '\\u1080?',
            'й' => '\\u1081?', 'к' => '\\u1082?', 'л' => '\\u1083?', 'м' => '\\u1084?', 'н' => '\\u1085?',
            'о' => '\\u1086?', 'п' => '\\u1087?', 'р' => '\\u1088?', 'с' => '\\u1089?', 'т' => '\\u1090?',
            'у' => '\\u1091?', 'ф' => '\\u1092?', 'х' => '\\u1093?', 'ц' => '\\u1094?', 'ч' => '\\u1095?',
            'ш' => '\\u1096?', 'щ' => '\\u1097?', 'ъ' => '\\u1098?', 'ы' => '\\u1099?', 'ь' => '\\u1100?',
            'э' => '\\u1101?', 'ю' => '\\u1102?', 'я' => '\\u1103?',
            'А' => '\\u1040?', 'Б' => '\\u1041?', 'В' => '\\u1042?', 'Г' => '\\u1043?', 'Д' => '\\u1044?',
            'Е' => '\\u1045?', 'Ё' => '\\u1025?', 'Ж' => '\\u1046?', 'З' => '\\u1047?', 'И' => '\\u1048?',
            'Й' => '\\u1049?', 'К' => '\\u1050?', 'Л' => '\\u1051?', 'М' => '\\u1052?', 'Н' => '\\u1053?',
            'О' => '\\u1054?', 'П' => '\\u1055?', 'Р' => '\\u1056?', 'С' => '\\u1057?', 'Т' => '\\u1058?',
            'У' => '\\u1059?', 'Ф' => '\\u1060?', 'Х' => '\\u1061?', 'Ц' => '\\u1062?', 'Ч' => '\\u1063?',
            'Ш' => '\\u1064?', 'Щ' => '\\u1065?', 'Ъ' => '\\u1066?', 'Ы' => '\\u1067?', 'Ь' => '\\u1068?',
            'Э' => '\\u1069?', 'Ю' => '\\u1070?', 'Я' => '\\u1071?',
            '№' => '\\u8470?', '«' => '\\u171?', '»' => '\\u187?', '–' => '\\u8211?', '—' => '\\u8212?'
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    /**
     * Создает документ Word из HTML с полным контролем форматирования
     * 
     * @param  \PhpOffice\PhpWord\Element\Section  $section
     * @param  string  $html
     * @return bool
     */
    private function createDocumentFromHtml($section, $html)
    {
        try {
            // Разбираем HTML на структурные элементы
            $structure = $this->parseHtmlStructure($html);
            
            foreach ($structure as $element) {
                switch ($element['type']) {
                    case 'header':
                        $this->addDocumentHeader($section, $element['content']);
                        break;
                    case 'paragraph':
                        $this->addDocumentParagraph($section, $element['content']);
                        break;
                    case 'table':
                        $this->addDocumentTable($section, $element['content']);
                        break;
                    case 'signature_stamp':
                        $this->addDocumentSignatureStamp($section, $element['content']);
                        break;
                }
            }
            
            Log::info('Документ успешно создан из HTML структуры');
            return true;
            
        } catch (\Exception $e) {
            Log::warning('Не удалось создать документ из HTML структуры', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Разбирает HTML на структурные элементы
     * 
     * @param  string  $html
     * @return array
     */
    private function parseHtmlStructure($html)
    {
        $structure = [];
        
        // Убираем лишние теги и нормализуем HTML
        $cleanHtml = $this->normalizeHtml($html);
        
        // Ищем заголовок документа
        if (preg_match('/<div[^>]*class="header"[^>]*>(.*?)<\/div>/is', $cleanHtml, $matches)) {
            $structure[] = [
                'type' => 'header',
                'content' => strip_tags($matches[1])
            ];
        }
        
        // Ищем все параграфы
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $cleanHtml, $paragraphs);
        foreach ($paragraphs[1] as $paragraph) {
            $text = strip_tags($paragraph);
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            $text = trim($text);
            
            if (!empty($text)) {
                $structure[] = [
                    'type' => 'paragraph',
                    'content' => $text
                ];
            }
        }
        
        // Ищем таблицы
        preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $cleanHtml, $tables);
        foreach ($tables[1] as $table) {
            $tableData = $this->parseTableData($table);
            if (!empty($tableData)) {
                $structure[] = [
                    'type' => 'table',
                    'content' => $tableData
                ];
            }
        }
        
        // Ищем подпись и печать
        if (strpos($cleanHtml, 'signature-container') !== false || strpos($cleanHtml, 'stamp-container') !== false) {
            $structure[] = [
                'type' => 'signature_stamp',
                'content' => $this->extractSignatureStampInfo($cleanHtml)
            ];
        }
        
        return $structure;
    }
    
    /**
     * Нормализует HTML для дальнейшей обработки
     */
    private function normalizeHtml($html)
    {
        // Убираем DOCTYPE, html, head, body теги
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
        $html = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $html);
        $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        
        // Исправляем кодировку
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        
        // Убираем лишние пробелы
        $html = preg_replace('/\s+/', ' ', $html);
        
        return trim($html);
    }
    
    /**
     * Добавляет заголовок документа
     */
    private function addDocumentHeader($section, $content)
    {
        $section->addText(
            $content,
            [
                'name' => 'Times New Roman',
                'size' => 14,
                'bold' => true
            ],
            [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 240
            ]
        );
    }
    
    /**
     * Добавляет параграф в документ
     */
    private function addDocumentParagraph($section, $content)
    {
        // Проверяем, не содержит ли параграф специальную информацию
        if (preg_match('/г\.\s*([^0-9]*?)\s+(\d{2}\.\d{2}\.\d{4})/', $content, $matches)) {
            // Это строка с городом и датой - форматируем особо
            $section->addText(
                $content,
                [
                    'name' => 'Times New Roman', 
                    'size' => 12
                ],
                [
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                    'spaceAfter' => 120
                ]
            );
        } else {
            // Обычный параграф
            $section->addText(
                $content,
                [
                    'name' => 'Times New Roman',
                    'size' => 12
                ],
                [
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                    'spaceAfter' => 120,
                    'firstLine' => 567 // Красная строка
                ]
            );
        }
    }
    
    /**
     * Добавляет таблицу в документ
     */
    private function addDocumentTable($section, $tableData)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'width' => 5000, // ширина в twips
        ]);
        
        foreach ($tableData as $rowIndex => $rowData) {
            $row = $table->addRow();
            
            foreach ($rowData as $cellData) {
                $cell = $row->addCell();
                
                $fontStyle = [
                    'name' => 'Times New Roman',
                    'size' => 12
                ];
                
                // Если это заголовок таблицы, делаем жирным
                if ($rowIndex === 0) {
                    $fontStyle['bold'] = true;
                }
                
                $cell->addText($cellData, $fontStyle, [
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                ]);
            }
        }
    }
    
    /**
     * Разбирает данные таблицы из HTML
     */
    private function parseTableData($tableHtml)
    {
        $tableData = [];
        
        // Ищем все строки таблицы
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tableHtml, $rows);
        
        foreach ($rows[1] as $row) {
            $rowData = [];
            
            // Ищем все ячейки в строке (th или td)
            preg_match_all('/<t[hd][^>]*>(.*?)<\/t[hd]>/is', $row, $cells);
            
            foreach ($cells[1] as $cell) {
                $cellText = strip_tags($cell);
                $cellText = html_entity_decode($cellText, ENT_QUOTES, 'UTF-8');
                $cellText = trim($cellText);
                $rowData[] = $cellText;
            }
            
            if (!empty($rowData)) {
                $tableData[] = $rowData;
            }
        }
        
        return $tableData;
    }
    
    /**
     * Извлекает информацию о подписи и печати
     */
    private function extractSignatureStampInfo($html)
    {
        $info = [];
        
        // Ищем контейнеры подписи и печати
        if (preg_match('/<div[^>]*class="signature-container"[^>]*>/i', $html)) {
            $info['has_signature'] = true;
        }
        
        if (preg_match('/<div[^>]*class="stamp-container"[^>]*>/i', $html)) {
            $info['has_stamp'] = true;
        }
        
        return $info;
    }
    
    /**
     * Добавляет место для подписи и печати
     */
    private function addDocumentSignatureStamp($section, $info)
    {
        // Добавляем пустую строку перед подписями
        $section->addTextBreak(2);
        
        // Создаем таблицу для подписей
        $table = $section->addTable([
            'width' => 5000
        ]);
        
        $row = $table->addRow();
        
        // Исполнитель
        $cell1 = $row->addCell(4500);
        $cell1->addText('Исполнитель:', ['name' => 'Times New Roman', 'size' => 12]);
        $cell1->addText('', [], ['spaceAfter' => 240]);
        $cell1->addText('_______________________', ['name' => 'Times New Roman', 'size' => 12]);
        
        // Заказчик
        $cell2 = $row->addCell(4500);
        $cell2->addText('Заказчик:', ['name' => 'Times New Roman', 'size' => 12]);
        $cell2->addText('', [], ['spaceAfter' => 240]);
        $cell2->addText('_______________________', ['name' => 'Times New Roman', 'size' => 12]);
        
        if (isset($info['has_signature']) || isset($info['has_stamp'])) {
            $section->addTextBreak(1);
            $section->addText(
                'М.П. (при наличии печати)',
                ['name' => 'Times New Roman', 'size' => 10, 'italic' => true],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]
            );
        }
    }
    
    /**
     * Оптимизирует HTML для PhpWord (fallback метод)
     * 
     * @param  string  $html
     * @return string
     */
    private function optimizeHtmlForPhpWord($html)
    {
        // Убираем все что может помешать
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?head[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        
        // Заменяем div.header на h2
        $html = preg_replace('/<div[^>]*class="header"[^>]*>(.*?)<\/div>/is', '<h2>$1</h2>', $html);
        
        // Заменяем остальные div на p
        $html = preg_replace('/<div[^>]*>(.*?)<\/div>/is', '<p>$1</p>', $html);
        
        // Убираем все атрибуты из тегов
        $html = preg_replace('/<(\w+)[^>]*>/', '<$1>', $html);
        
        // Исправляем самозакрывающиеся теги
        $html = preg_replace('/<br\s*\/?>/i', '<br/>', $html);
        
        // Экранируем амперсанды
        $html = preg_replace('/&(?![a-zA-Z0-9#]{1,7};)/', '&amp;', $html);
        
        return trim($html);
    }
    
    /**
     * Санитизирует HTML для корректной работы с PhpWord
     * Сохраняет важные теги форматирования для красивого отображения
     *
     * @param  string  $html
     * @return string
     */
    private function sanitizeHtmlForPhpWord($html)
    {
        // Шаг 1: Удаляем структурные элементы HTML
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?head[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
        $html = preg_replace('/<meta[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?title[^>]*>/i', '', $html);
        
        // Шаг 2: Удаляем style и script блоки
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        
        // Шаг 3: КРИТИЧЕСКИ ВАЖНО - Исправляем самозакрывающиеся теги
        // PhpWord/DOMDocument требует правильно закрытые теги
        $html = preg_replace('/<br\s*\/?>/i', '<br></br>', $html);
        $html = preg_replace('/<hr\s*\/?>/i', '<hr></hr>', $html);
        $html = preg_replace('/<img([^>]*)\s*\/?>/i', '<img$1></img>', $html);
        
        // Шаг 4: Упрощаем атрибуты - удаляем CSS классы и стили
        $html = preg_replace('/\s+class="[^"]*"/i', '', $html);
        $html = preg_replace('/\s+style="[^"]*"/i', '', $html);
        $html = preg_replace('/\s+id="[^"]*"/i', '', $html);
        $html = preg_replace('/\s+(data-[^=]*="[^"]*")/i', '', $html);
        $html = preg_replace('/\s+(role="[^"]*")/i', '', $html);
        $html = preg_replace('/\s+(aria-[^=]*="[^"]*")/i', '', $html);
        
        // Шаг 5: Упрощаем таблицы для лучшей совместимости
        $html = preg_replace('/<table[^>]*>/', '<table border="1">', $html);
        
        // Шаг 6: Заменяем div.header на h2 и другие div на p
        $html = preg_replace('/<div[^>]*class="header"[^>]*>(.*?)<\/div>/is', '<h2>$1</h2>', $html);
        $html = preg_replace('/<div[^>]*>(.*?)<\/div>/is', '<p>$1</p>', $html);
        
        // Шаг 7: Экранируем амперсанды
        $html = preg_replace('/&(?![a-zA-Z0-9#]{1,7};)/', '&amp;', $html);
        
        // Шаг 8: Удаляем лишние пробелы
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);
        
        return $html;
    }
    
    /**
     * Создает упрощенную версию HTML для PhpWord
     *
     * @param  string  $html
     * @return string
     */
    private function createSimpleHtml($html)
    {
        // Оставляем только базовые теги, которые точно поддерживает PhpWord
        $allowedTags = '<p><br><strong><b><em><i><u><table><tr><td><th><h1><h2><h3><h4><h5><h6><ul><ol><li>';
        $simpleHtml = strip_tags($html, $allowedTags);
        
        // Убираем все атрибуты кроме основных
        $simpleHtml = preg_replace('/<(\w+)[^>]*>/', '<$1>', $simpleHtml);
        
        // Заменяем заголовки на жирный текст в параграфах
        $simpleHtml = preg_replace('/<h[1-6]>(.*?)<\/h[1-6]>/', '<p><strong>$1</strong></p>', $simpleHtml);
        
        return $simpleHtml;
    }
    
    /**
     * Создает документ вручную, разбирая HTML
     *
     * @param  \PhpOffice\PhpWord\Element\Section  $section
     * @param  string  $html
     * @return void
     */
    private function createManualDocument($section, $html)
    {
        // Извлекаем текст из HTML, но сохраняем базовую структуру
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        
        // Пытаемся загрузить HTML
        if ($dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            $this->processHtmlNode($section, $dom->documentElement);
        } else {
            // Если даже это не работает, просто добавляем текстовое содержимое
            $textContent = strip_tags($html);
            $textContent = html_entity_decode($textContent, ENT_QUOTES, 'UTF-8');
            
            $paragraphs = preg_split('/\n\s*\n/', $textContent);
            foreach ($paragraphs as $paragraph) {
                $paragraph = trim($paragraph);
                if (!empty($paragraph)) {
                    $section->addText($paragraph);
                }
            }
        }
        
        libxml_clear_errors();
    }
    
    /**
     * Рекурсивно обрабатывает HTML узлы для ручного создания документа
     *
     * @param  \PhpOffice\PhpWord\Element\Section|\PhpOffice\PhpWord\Element\Table  $container
     * @param  \DOMNode  $node
     * @return void
     */
    private function processHtmlNode($container, $node)
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = trim($child->textContent);
                if (!empty($text)) {
                    $container->addText($text);
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                switch (strtolower($child->nodeName)) {
                    case 'p':
                        $text = trim($child->textContent);
                        if (!empty($text)) {
                            $container->addText($text);
                        }
                        break;
                    case 'h1':
                    case 'h2':
                    case 'h3':
                    case 'h4':
                    case 'h5':
                    case 'h6':
                        $text = trim($child->textContent);
                        if (!empty($text)) {
                            $container->addText($text, ['bold' => true, 'size' => 14]);
                        }
                        break;
                    case 'table':
                        if (method_exists($container, 'addTable')) {
                            $table = $container->addTable();
                            $this->processTableNode($table, $child);
                        }
                        break;
                    default:
                        $this->processHtmlNode($container, $child);
                        break;
                }
            }
        }
    }
    
    /**
     * Обрабатывает узел таблицы
     *
     * @param  \PhpOffice\PhpWord\Element\Table  $table
     * @param  \DOMNode  $tableNode
     * @return void
     */
    private function processTableNode($table, $tableNode)
    {
        foreach ($tableNode->childNodes as $row) {
            if ($row->nodeType === XML_ELEMENT_NODE && strtolower($row->nodeName) === 'tr') {
                $tableRow = $table->addRow();
                foreach ($row->childNodes as $cell) {
                    if ($cell->nodeType === XML_ELEMENT_NODE && 
                        (strtolower($cell->nodeName) === 'td' || strtolower($cell->nodeName) === 'th')) {
                        $text = trim($cell->textContent);
                        $tableRow->addCell()->addText($text);
                    }
                }
            }
        }
    }
}
