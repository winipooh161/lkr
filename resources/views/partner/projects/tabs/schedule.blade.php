<div class="schedule-container mb-4">
    <!-- Информация о сроках -->
    <div class="schedule-info mb-4">
        <div class="card">
            <div class="card-body">
                <h5>План график проекта</h5>
                <div class="d-flex flex-wrap align-items-center mb-3">
                    <div class="me-4 mb-2 mb-md-0">
                        <strong>Срок ремонта:</strong> 
                        <span id="project-duration">
                            @if($project->work_start_date && $project->work_end_date)
                                @php
                                    $start = strtotime($project->work_start_date);
                                    $end = strtotime($project->work_end_date);
                                    $datediff = $end - $start;
                                    $days = round($datediff / (60 * 60 * 24));
                                    $weeks = round($days / 7, 1);
                                    $months = round($days / 30, 1);
                                    echo "$days дней, $weeks недель, $months месяца";
                                @endphp
                            @else
                                Не задан срок окончания
                            @endif
                        </span>
                    </div>                    <div class="d-flex flex-wrap">
                        <a href="#" id="download-schedule" class="btn btn-outline-primary btn-sm me-2 mb-2 mb-md-0">
                            <i class="fas fa-download me-1"></i> Скачать
                        </a>
                        <a href="{{ route('partner.projects.calendar', ['project' => $project->id]) }}" class="btn btn-outline-info btn-sm me-2 mb-2 mb-md-0">
                            <i class="fas fa-calendar-alt me-1"></i> Календарный вид
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm me-2 mb-2 mb-md-0" 
                                data-bs-toggle="modal" data-bs-target="#scheduleUrlModal">
                            <i class="fas fa-link me-1"></i> Указать ссылку на линейный график
                        </button>                        <button type="button" id="create-template" class="btn btn-outline-success btn-sm me-2 mb-2 mb-md-0">
                            <i class="fas fa-file-excel me-1"></i> Пересоздать шаблон
                        </button>
                        <button type="button" id="generate-client-data" class="btn btn-outline-warning btn-sm mb-2 mb-md-0">
                            <i class="fas fa-sync me-1"></i> Обновить данные для клиента
                        </button>
                    </div>
                </div>
                
                @if(isset($project->schedule_link) && $project->schedule_link)
                <div class="mb-3">
                    <strong>Внешний линейный график:</strong> 
                    <a href="{{ $project->schedule_link }}" target="_blank">{{ $project->schedule_link }}</a>
                </div>
                @endif
                
                <!-- Фильтры -->
                <div class="schedule-filters card mt-3">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <div class="d-flex flex-wrap">
                                    <div class="input-group input-group-sm me-2 mb-2" style="max-width: 200px;">
                                        <span class="input-group-text">С</span>
                                        <input type="date" class="form-control" id="date-from" value="{{ date('Y-m-01') }}">
                                    </div>
                                    <div class="input-group input-group-sm me-2 mb-2" style="max-width: 200px;">
                                        <span class="input-group-text">По</span>
                                        @if($project->work_end_date)
                                            <input type="date" class="form-control" id="date-to" value="{{ date('Y-m-d', strtotime($project->work_end_date)) }}">
                                        @else
                                            <input type="date" class="form-control" id="date-to" value="{{ date('Y-m-t') }}">
                                        @endif
                                    </div>
                                    <button class="btn btn-sm btn-primary mb-2" id="apply-filter">Применить</button>
                                    <button class="btn btn-sm btn-outline-secondary mb-2 ms-2" id="filter-this-month">Этот месяц</button>
                                    <button class="btn btn-sm btn-outline-secondary mb-2 ms-2" id="filter-next-month">Следующий месяц</button>
                                    <button class="btn btn-sm btn-outline-secondary mb-2 ms-2" id="filter-this-year">Весь год</button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-wrap justify-content-md-end">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="filter-completed" checked>
                                        <label class="form-check-label" for="filter-completed">
                                            <span class="badge bg-success">Завершено</span>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="filter-in-progress" checked>
                                        <label class="form-check-label" for="filter-in-progress">
                                            <span class="badge bg-primary">В работе</span>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="filter-pending" checked>
                                        <label class="form-check-label" for="filter-pending">
                                            <span class="badge bg-warning">Ожидание</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Excel редактор -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Редактирование графика работ</strong>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-success" id="save-excel">
                    <i class="fas fa-save me-1"></i> Сохранить изменения
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="loading-indicator" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-2">Загрузка редактора...</p>
            </div>
            <div id="excel-editor" style="width: 100%; height: 600px; overflow: auto; display: none;"></div>
        </div>
    </div>
</div>

<!-- Модальное окно для указания ссылки на линейный график -->
<div class="modal fade" id="scheduleUrlModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ссылка на линейный график</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('partner.projects.update', $project) }}" method="POST" id="schedule-link-form">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="schedule_link" class="form-label">Укажите ссылку на внешний график:</label>
                        <input type="url" class="form-control" id="schedule_link" name="schedule_link" 
                               value="{{ $project->schedule_link ?? '' }}" placeholder="https://...">
                        <div class="form-text">Например, ссылка на Google Sheets или Microsoft Project Online</div>
                    </div>
                    <input type="hidden" name="update_type" value="schedule_link">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно предпросмотра графика удалено в пользу календарного вида -->

<!-- Подключаем библиотеки для Excel в браузере -->
<!-- Важно: добавляем версии, чтобы исключить проблемы с кэшированием -->
<link href="https://cdn.jsdelivr.net/npm/handsontable@12.4.0/dist/handsontable.full.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
<script src="https://cdn.jsdelivr.net/npm/handsontable@12.4.0/dist/handsontable.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.3.0/dist/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>

<script>
// Проверка наличия зависимостей
function checkDependencies() {
    // Проверяем наличие jQuery
    if (typeof jQuery === 'undefined') {
        console.error('Ошибка: jQuery не загружен!');
        return false;
    }
    
    // Проверяем наличие flatpickr
    if (typeof flatpickr === 'undefined') {
        console.error('Ошибка: flatpickr не загружен!');
        return false;
    }
    
    return true;
}

// Проверка загрузки библиотек
function checkLibrariesLoaded() {
    if (typeof Handsontable === 'undefined') {
        console.error('Ошибка: Библиотека Handsontable не загружена');
        alert('Ошибка загрузки редактора таблиц. Пожалуйста, обновите страницу.');
        return false;
    }
    if (typeof ExcelJS === 'undefined') {
        console.error('Ошибка: Библиотека ExcelJS не загружена');
        alert('Ошибка загрузки модуля Excel. Пожалуйста, обновите страницу.');
        return false;
    }
    if (typeof saveAs === 'undefined') {
        console.error('Ошибка: Библиотека FileSaver не загружена');
        alert('Ошибка загрузки модуля сохранения файлов. Пожалуйста, обновите страницу.');
        return false;
    }
    return true;
}

// Ждем загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем зависимости и инициализируем страницу
    if (checkDependencies()) {
        // Инициализируем поля выбора дат с помощью flatpickr
        initDatePickers();
        
        // Инициализируем Excel-редактор
        initExcelEditor();
    } else {
        console.error("Необходимые зависимости не загружены. Перезагрузите страницу.");
        document.getElementById('loading-indicator').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Ошибка загрузки необходимых библиотек. Пожалуйста, обновите страницу.
            </div>
            <button class="btn btn-primary mt-2" onclick="window.location.reload()">
                Обновить страницу
            </button>
        `;
    }
});

// Функция для инициализации datepicker
function initDatePickers() {
    // Инициализируем flatpickr для полей выбора дат
    try {
        const dateConfig = {
            dateFormat: "Y-m-d",
            locale: "ru",
            allowInput: true
        };
        
        if (document.getElementById('date-from')) {
            flatpickr('#date-from', dateConfig);
        }
        
        if (document.getElementById('date-to')) {
            flatpickr('#date-to', dateConfig);
        }
        
        console.log('Поля выбора дат инициализированы');
    } catch (e) {
        console.error('Ошибка при инициализации полей выбора дат:', e);
    }
}

// Функция инициализации Excel-редактора
function initExcelEditor() {
    // Проверяем загрузку библиотек
    if (!checkLibrariesLoaded()) return;
    
    console.log('Инициализация Excel-редактора...');
    
    // Отображаем элемент загрузки
    const loadingIndicator = document.getElementById('loading-indicator');
    const container = document.getElementById('excel-editor');
    
    if (!container) {
        console.error('Ошибка: Элемент #excel-editor не найден');
        return;
    }
    
    console.log('Контейнер excel-editor найден:', container);
    
    // Пример данных для таблицы
    const initialData = [
        ['Наименование', 'Статус', 'Начало', 'Конец', 'Дней', 'Комментарий'],
        ['Завоз инструмента и заезд ремонтной бригады', 'Готово', '25.08.2023', '25.08.2023', 0, ''],
        ['Демонтаж перегородок и стен', 'Готово', '26.08.2023', '28.08.2023', 2, ''],
        ['Демонтаж напольного покрытия', 'Готово', '29.08.2023', '30.08.2023', 1, ''],
        ['Демонтаж дверей', 'В работе', '31.08.2023', '31.08.2023', 0, ''],
        ['Демонтаж электрики', 'Ожидание', '01.09.2023', '03.09.2023', 2, ''],
        ['Возведение перегородок', 'Ожидание', '04.09.2023', '10.09.2023', 6, ''],
        ['Электромонтажные работы', 'Ожидание', '11.09.2023', '20.09.2023', 9, ''],
        ['Сантехнические работы', 'Ожидание', '21.09.2023', '30.09.2023', 9, ''],
        ['Выравнивание стен', 'Ожидание', '01.10.2023', '15.10.2023', 14, '']
    ];
    
    try {
        // Создаем экземпляр таблицы Handsontable
        const hot = new Handsontable(container, {
            data: initialData,
            rowHeaders: true,
            colHeaders: true,
            columnSorting: true,
            contextMenu: true,
            manualRowResize: true,
            manualColumnResize: true,
            licenseKey: 'non-commercial-and-evaluation',
            stretchH: 'all',
            autoWrapRow: true,
            height: '100%',
            colHeaders: ['Наименование', 'Статус', 'Начало', 'Конец', 'Дней', 'Комментарий'],
            columns: [
                { type: 'text' },
                { 
                    type: 'dropdown', 
                    source: ['Готово', 'В работе', 'Ожидание', 'Отменено']
                },
                { type: 'date', dateFormat: 'DD.MM.YYYY', correctFormat: true },
                { type: 'date', dateFormat: 'DD.MM.YYYY', correctFormat: true },
                { type: 'numeric' },
                { type: 'text' }
            ],
            dropdownMenu: true,
            filters: true,
            cell: [
                { row: 0, col: 0, className: 'htCenter htMiddle' },
                { row: 0, col: 1, className: 'htCenter htMiddle' },
                { row: 0, col: 2, className: 'htCenter htMiddle' },
                { row: 0, col: 3, className: 'htCenter htMiddle' },
                { row: 0, col: 4, className: 'htCenter htMiddle' },
                { row: 0, col: 5, className: 'htCenter htMiddle' }
            ],
            // Используем render, а не afterRender для предотвращения рекурсивных вызовов
            afterLoadData: function(firstTime) {
                if (firstTime) {
                    console.log('Таблица загружена впервые');
                    if (loadingIndicator) loadingIndicator.style.display = 'none';
                    if (container) container.style.display = 'block';
                }
            },
            beforeRenderer: function(isForced) {
                // Защита от слишком частых рендеров
                this._renderStartTime = Date.now();
            },
            afterRenderer: function(isForced) {
                // Профилирование времени рендера для оптимизации
                const renderTime = Date.now() - this._renderStartTime;
                if (renderTime > 500) {
                    console.warn(`Рендеринг таблицы занял ${renderTime}ms. Возможно, требуется оптимизация.`);
                }
            }
        });
          console.log('Таблица инициализирована');
        
        // Сохраняем оригинальные данные при загрузке
        let originalData = [];
        let isDataLoading = false;
        
        // Используем защиту от повторных событий
        hot.addHook('beforeLoadData', function() {
            isDataLoading = true;
        });
        
        hot.addHook('afterLoadData', function() {
            if (isDataLoading) {
                // Сохраняем копию данных
                try {
                    originalData = JSON.parse(JSON.stringify(hot.getData())); // Глубокое клонирование
                    console.log('Данные сохранены в originalData');
                } catch (e) {
                    console.error('Ошибка при сохранении копии данных:', e);
                }
                isDataLoading = false;
                
                // Сбрасываем кеш просроченных задач
                overdueTasksCache.clear();
            }
        });
        
        // Функция для загрузки Excel файла, если он существует
        function loadExcelFile() {
            console.log('Попытка загрузить файл расписания...');
            
            fetch(`{{ route('partner.projects.schedule-file', $project->id) }}`, {
                headers: {
                    'Accept': 'application/json, application/octet-stream',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    if (response.headers.get('content-type') && response.headers.get('content-type').includes('application/json')) {
                        return response.json().then(data => {
                            console.log('Файл не найден, создаем новый шаблон');
                            hot.loadData(initialData);
                            if (loadingIndicator) loadingIndicator.style.display = 'none';
                            container.style.display = 'block';
                            throw new Error('Файл не найден');
                        });
                    }
                    return response.arrayBuffer();
                } else if (response.status === 404) {
                    console.log('Файл не найден, создаем новый шаблон');
                    hot.loadData(initialData);
                    if (loadingIndicator) loadingIndicator.style.display = 'none';
                    container.style.display = 'block';
                    throw new Error('Файл не найден');
                } else {
                    throw new Error('Ошибка сервера: ' + response.status);
                }
            })
            .then(buffer => {
                // Парсим Excel и заполняем таблицу
                const workbook = new ExcelJS.Workbook();
                return workbook.xlsx.load(buffer).then(workbook => {
                    const worksheet = workbook.getWorksheet(1);
                    
                    if (!worksheet) {
                        console.error('Рабочий лист не найден в файле Excel');
                        hot.loadData(initialData);
                        return;
                    }
                    
                    const data = [];
                    worksheet.eachRow((row, rowIndex) => {
                        const rowData = [];
                        row.eachCell((cell, colIndex) => {
                            rowData.push(cell.value);
                        });
                        data.push(rowData);
                    });
                    
                    if (data.length === 0) {
                        console.log('Файл Excel пуст, используем шаблон');
                        hot.loadData(initialData);
                    } else {
                        hot.loadData(data);
                    }
                });
            })
            .catch(error => {
                console.error('Ошибка загрузки файла:', error);
                if (hot) {
                    hot.loadData(initialData);
                }
            })
            .finally(() => {
                if (loadingIndicator) loadingIndicator.style.display = 'none';
                if (container) container.style.display = 'block';
            });
        }
        
        // Вызываем функцию загрузки файла при инициализации
        loadExcelFile();
        
        // Обработчик кнопки "Создать шаблон"
        document.getElementById('create-template')?.addEventListener('click', function() {
            if (confirm('Вы уверены, что хотите создать новый пустой шаблон? Текущие данные будут потеряны, если вы их не сохранили.')) {
                // Создаем шаблон для плана-графика работ
                const templateData = [
                    ['Наименование', 'Статус', 'Начало', 'Конец', 'Дней', 'Комментарий'],
                    ['Завоз инструмента и заезд ремонтной бригады', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Общестроительные материалы', 'В работе', '', '', '', ''],
                    ['Подготовка объекта', 'В работе', '', '', '', ''],
                    ['Демонтажные работы + временные коммуникации', 'В работе', '', '', '', ''],
                    ['Возведение перегородок', 'В работе', '', '', '', ''],
                    ['Оштукатуривание стен', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Электромонтажные и сантехнические материалы, отопление', 'В работе', '', '', '', ''],
                    ['Электромонтажные работы', 'В работе', '', '', '', ''],
                    ['Сантехнические работы', 'В работе', '', '', '', ''],
                    ['Стяжка', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Материалы для малярных работ', 'В работе', '', '', '', ''],
                    ['Подготовка стен под финишную отделку', 'В работе', '', '', '', ''],
                    ['Подготовка откосов под отделку', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Краска, расходники к покраске', 'В работе', '', '', '', ''],
                    ['Завершение всех пыльных и грязных работ', 'В работе', '', '', '', ''],
                    ['Финишная отделка стен', 'В работе', '', '', '', ''],
                    ['Конструкции ГКЛ', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Плитка и расходники', 'В работе', '', '', '', ''],
                    ['Финишная отделка откосов', 'В работе', '', '', '', ''],
                    ['Уборка объекта (предчистовая)', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Сантехническое оборудование', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Чистовая электрика', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Напольное покрытие и расходники', 'В работе', '', '', '', ''],
                    ['Душевой поддон + трап', 'В работе', '', '', '', ''],
                    ['Напольные покрытия', 'В работе', '', '', '', ''],
                    ['Плинтус и стыки', 'В работе', '', '', '', ''],
                    ['Монтаж электрики чистовой', 'В работе', '', '', '', ''],
                    ['Монтаж сантехники - чистовой', 'В работе', '', '', '', ''],
                    ['Напольная плитка + теплый пол', 'В работе', '', '', '', ''],
                    ['Настенная плитка + инсталляция', 'В работе', '', '', '', ''],
                    ['Финишная доработка объекта', 'В работе', '', '', '', ''],
                    ['Передача ключей', 'В работе', '', '', '', '']
                ];
                
                // Загружаем шаблон в таблицу
                hot.loadData(templateData);
                
                // Показываем уведомление
                alert('Шаблон плана-графика создан. При необходимости укажите даты и сохраните изменения.');
            }
        });
        
        // Сохранение изменений в Excel
        document.getElementById('save-excel')?.addEventListener('click', function() {
            const data = hot.getData();
            
            const workbook = new ExcelJS.Workbook();
            workbook.creator = 'Remont Admin';
            workbook.created = new Date();
            
            const worksheet = workbook.addWorksheet('План-график');
            
            data.forEach(rowData => {
                if (rowData) {
                    worksheet.addRow(rowData);
                }
            });
            
            worksheet.getRow(1).font = { bold: true };
            worksheet.getRow(1).alignment = { vertical: 'middle', horizontal: 'center' };
            
            worksheet.columns.forEach((column, index) => {
                let maxLength = 0;
                column.eachCell({ includeEmpty: true }, (cell, rowIndex) => {
                    const length = cell.value ? cell.value.toString().length : 10;
                    if (length > maxLength) {
                        maxLength = length;
                    }
                });
                worksheet.getColumn(index + 1).width = Math.min(maxLength + 2, 50);
            });
            
            workbook.xlsx.writeBuffer().then(buffer => {
                const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                  const formData = new FormData();
                formData.append('schedule_file', blob, 'schedule.xlsx');
                formData.append('_token', '{{ csrf_token() }}');
                
                fetch(`{{ route('partner.projects.schedule-file.store', $project->id) }}`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('График успешно сохранен');
                    } else {
                        alert('Ошибка при сохранении: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Ошибка при сохранении файла:', error);
                    alert('Ошибка при сохранении файла');
                });
            });
        });
          // Обработчик для скачивания файла
        document.getElementById('download-schedule')?.addEventListener('click', function(e) {
            e.preventDefault();
            
            try {
                const dateFrom = document.getElementById('date-from')?.value || '';
                const dateTo = document.getElementById('date-to')?.value || '';
                
                // Используем серверный API для скачивания с фильтрацией
                if (dateFrom && dateTo) {
                    // Создаем URL для запроса с параметрами фильтра
                    const downloadUrl = `{{ route('partner.projects.schedule-file', $project->id) }}?start_date=${dateFrom}&end_date=${dateTo}`;
                    window.location.href = downloadUrl;
                    return;
                }
                
                // Если даты не указаны, делаем клиентский экспорт
                const data = [];
                
                // Добавляем заголовок
                data.push(hot.getDataAtRow(0));
                
                // Получаем видимые данные (с учетом фильтрации)
                try {
                    const plugin = hot.getPlugin('filters');
                    const visibleRows = plugin.filteredRows;
                    
                    // Добавляем только видимые строки
                    if (plugin && plugin.enabled && visibleRows && visibleRows.length > 0) {
                        // Если фильтр активен
                        visibleRows.forEach(rowIndex => {
                            if (rowIndex > 0) { // Пропускаем заголовок
                                data.push(hot.getDataAtRow(rowIndex));
                            }
                        });
                    } else {
                        // Если фильтр не активен, берем все строки
                        for (let i = 1; i < hot.countRows(); i++) {
                            data.push(hot.getDataAtRow(i));
                        }
                    }
                } catch (filterErr) {
                    console.error('Ошибка при фильтрации строк:', filterErr);
                    // Если возникла ошибка при работе с фильтром, экспортируем все данные
                    for (let i = 1; i < hot.countRows(); i++) {
                        data.push(hot.getDataAtRow(i));
                    }
                }
                
                // Создаем новую книгу Excel
                const workbook = new ExcelJS.Workbook();
                workbook.creator = 'Remont Admin';
                workbook.created = new Date();
                
                // Добавляем новый лист
                const worksheet = workbook.addWorksheet('План-график');
                
                // Если установлен фильтр дат, добавляем информацию о периоде
                if (dateFrom || dateTo) {
                    const infoRow = worksheet.addRow(['Период:', `с ${dateFrom || 'начала'} по ${dateTo || 'окончание'}`]);
                    infoRow.font = { italic: true };
                    worksheet.addRow([]); // Пустая строка для разделения
                }
                
                // Заполняем данными
                data.forEach(rowData => {
                    if (rowData) { // Проверка на null или undefined
                        worksheet.addRow(rowData);
                    }
                });
                
                // Стилизуем заголовок
                const headerRow = dateFrom || dateTo ? 3 : 1;
                worksheet.getRow(headerRow).font = { bold: true };
                worksheet.getRow(headerRow).alignment = { vertical: 'middle', horizontal: 'center' };
                worksheet.getRow(headerRow).fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFE0E0E0' }
                };
                
                // Автоматическая ширина столбцов
                worksheet.columns.forEach((column, index) => {
                    let maxLength = 0;
                    column.eachCell({ includeEmpty: true }, (cell, rowIndex) => {
                        const length = cell.value ? cell.value.toString().length : 10;
                        if (length > maxLength) {
                            maxLength = length;
                        }
                    });
                    worksheet.getColumn(index + 1).width = Math.min(maxLength + 2, 50);
                });
                
                // Выделяем просроченные задачи в Excel
                worksheet.eachRow((row, rowIndex) => {
                    if (rowIndex > headerRow) {
                        const status = row.getCell(2).value;
                        const endDateStr = row.getCell(4).value;
                        
                        if (endDateStr && status !== 'Готово') {
                            try {
                                // Преобразуем дату в формате DD.MM.YYYY в объект Date
                                const [day, month, year] = endDateStr.toString().split('.');
                                if (day && month && year) {
                                    const endDate = new Date(`${year}-${month}-${day}`);
                                    const today = new Date();
                                    
                                    if (!isNaN(endDate.getTime()) && endDate < today) {
                                        // Выделяем просроченную строку красным
                                        row.eachCell((cell) => {
                                            cell.fill = {
                                                type: 'pattern',
                                                pattern: 'solid',
                                                fgColor: { argb: 'FFFFE5E5' }
                                            };
                                            cell.font = cell.font || {};
                                            cell.font.color = { argb: 'FFB71C1C' };
                                        });
                                        
                                        // Добавляем информацию о просрочке
                                        const daysOverdue = Math.ceil((today - endDate) / (1000*60*60*24));
                                        row.getCell(1).value = `${row.getCell(1).value} [Просрочено на ${daysOverdue} дн.]`;
                                    }
                                }
                            } catch (err) {
                                console.error('Ошибка при обработке даты:', err);
                            }
                        }
                    }
                });
                
                // Скачиваем файл с указанием диапазона дат в имени файла
                const fileName = dateFrom && dateTo
                    ? `План-график_${dateFrom}_${dateTo}_${new Date().toLocaleDateString().replace(/\./g, '-')}.xlsx` 
                    : `План-график_{{ $project->id }}_${new Date().toLocaleDateString().replace(/\./g, '-')}.xlsx`;
                
                // Скачиваем файл
                workbook.xlsx.writeBuffer().then(buffer => {
                    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    saveAs(blob, fileName);
                }).catch(err => {
                    console.error('Ошибка при создании Excel файла:', err);
                    alert('Произошла ошибка при создании файла для скачивания.');
                });
                
            } catch (err) {
                console.error('Ошибка при скачивании графика:', err);
                alert('Произошла ошибка при подготовке данных для скачивания.');
            }
        });
        
        // Форматирование даты в формат YYYY-MM-DD
        function formatDateToISO(date) {
            return date.toISOString().slice(0, 10);
        }
        
        // Быстрые фильтры по месяцам - оптимизированная версия
        document.getElementById('filter-this-month')?.addEventListener('click', function() {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            
            document.getElementById('date-from').value = formatDateToISO(firstDay);
            document.getElementById('date-to').value = formatDateToISO(lastDay);
            
            // Автоматически применяем фильтр
            document.getElementById('apply-filter')?.click();
        });
        
        document.getElementById('filter-next-month')?.addEventListener('click', function() {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth() + 1, 1);
            const lastDay = new Date(now.getFullYear(), now.getMonth() + 2, 0);
            
            document.getElementById('date-from').value = formatDateToISO(firstDay);
            document.getElementById('date-to').value = formatDateToISO(lastDay);
            
            // Автоматически применяем фильтр
            document.getElementById('apply-filter')?.click();
        });
        
        document.getElementById('filter-this-year')?.addEventListener('click', function() {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), 0, 1);
            const lastDay = new Date(now.getFullYear(), 11, 31);
            
            document.getElementById('date-from').value = formatDateToISO(firstDay);
            document.getElementById('date-to').value = formatDateToISO(lastDay);
            
            // Автоматически применяем фильтр
            document.getElementById('apply-filter')?.click();
        });
        
        // Фильтрация по датам - оптимизированная версия
        document.getElementById('apply-filter')?.addEventListener('click', function() {
            try {
                const dateFrom = document.getElementById('date-from').value;
                const dateTo = document.getElementById('date-to').value;
                
                // Используем плагин фильтров
                hot.getPlugin('filters').clearConditions();
                
                // Добавляем условия фильтрации для столбца начала (индекс 2)
                if (dateFrom) {
                    hot.getPlugin('filters').addCondition(2, 'date_after', [dateFrom], 'conjunction');
                }
                
                // Добавляем условия фильтрации для столбца конца (индекс 3)
                if (dateTo) {
                    hot.getPlugin('filters').addCondition(3, 'date_before', [dateTo], 'conjunction');
                }
                
                // Применяем фильтры
                hot.getPlugin('filters').filter();
            } catch (e) {
                console.error('Ошибка при фильтрации:', e);
                alert('Произошла ошибка при фильтрации данных. Пожалуйста, проверьте корректность дат.');
            }
        });
          // Выделение просроченных задач - оптимизированная версия
        // Кешированная дата для уменьшения вычислений
        const TODAY = new Date();
        
        // Кастомный рендерер для ячеек с мемоизацией просроченных задач
        // для минимизации повторных вычислений
        const overdueTasksCache = new Map();
        
        function overdueTaskRenderer(instance, td, row, col, prop, value, cellProperties) {
            // Применяем стандартный рендерер
            Handsontable.renderers.TextRenderer.apply(this, arguments);
            
            // Не применяем стили к заголовку
            if (row === 0) return;
            
            try {
                // Используем мемоизацию для уменьшения вычислений
                if (!overdueTasksCache.has(row)) {
                    // Если строки нет в кеше, вычисляем и запоминаем
                    let isOverdue = false;
                    let daysOverdue = 0;
                    
                    const rowData = instance.getDataAtRow(row);
                    if (rowData) {
                        const endDateStr = rowData[3];
                        const status = rowData[1];
                        
                        if (endDateStr && status !== 'Готово') {
                            const parts = endDateStr.toString().split('.');
                            if (parts.length === 3) {
                                const [d, m, y] = parts;
                                const endDate = new Date(`${y}-${m}-${d}`);
                                
                                if (!isNaN(endDate.getTime()) && endDate < TODAY) {
                                    isOverdue = true;
                                    daysOverdue = Math.ceil((TODAY - endDate) / (1000*60*60*24));
                                }
                            }
                        }
                    }
                    
                    // Сохраняем в кеш
                    overdueTasksCache.set(row, {isOverdue, daysOverdue});
                }
                
                // Получаем из кеша
                const {isOverdue, daysOverdue} = overdueTasksCache.get(row);
                
                // Применяем стили, если задача просрочена
                if (isOverdue) {
                    td.style.background = '#ffe5e5';
                    td.style.color = '#b71c1c';
                    
                    // Добавляем маркер просроченной задачи только в первую колонку
                    if (col === 0 && daysOverdue > 0) {
                        // Создаем элемент только для первой колонки
                        const overdueSpan = document.createElement('span');
                        overdueSpan.title = `Просрочено на ${daysOverdue} дн.`;
                        overdueSpan.style.color = '#b71c1c';
                        overdueSpan.style.fontWeight = 'bold';
                        overdueSpan.innerHTML = ` &#10060; (${daysOverdue})`;
                        td.appendChild(overdueSpan);
                    }
                }
            } catch (e) {
                console.error('Ошибка при отображении просроченной задачи:', e);
                // Не выводим ошибку в консоль слишком часто
                if (!window._overdueRendererErrorShown) {
                    window._overdueRendererErrorShown = true;
                    setTimeout(() => window._overdueRendererErrorShown = false, 5000);
                }
            }
        }
        
        // Устанавливаем кастомный рендерер для всех ячеек
        hot.updateSettings({
            cells: function(row, col) {
                return { renderer: overdueTaskRenderer };
            }
        });
        
        // Очищаем кеш при изменении данных
        hot.addHook('afterChange', function() {
            overdueTasksCache.clear();
        });
        
        // Обработчик для фильтрации по статусам
        function applyStatusFilters() {
            try {
                const showCompleted = document.getElementById('filter-completed')?.checked ?? true;
                const showInProgress = document.getElementById('filter-in-progress')?.checked ?? true;
                const showPending = document.getElementById('filter-pending')?.checked ?? true;
                
                // Очищаем фильтр для колонки статуса (индекс 1)
                hot.getPlugin('filters').clearConditions(1);
                
                // Собираем статусы для отображения
                const statusesToShow = [];
                if (showCompleted) statusesToShow.push('Готово');
                if (showInProgress) statusesToShow.push('В работе');
                if (showPending) statusesToShow.push('Ожидание');
                
                // Если выбран хотя бы один статус, но не все
                if (statusesToShow.length > 0 && statusesToShow.length < 4) {
                    hot.getPlugin('filters').addCondition(1, 'by_value', statusesToShow);
                }
                
                // Применяем фильтры
                hot.getPlugin('filters').filter();
            } catch (e) {
                console.error('Ошибка при фильтрации по статусам:', e);
            }
        }
        
        // Добавляем слушателей событий на чекбоксы статусов
        document.getElementById('filter-completed')?.addEventListener('change', applyStatusFilters);
        document.getElementById('filter-in-progress')?.addEventListener('change', applyStatusFilters);
        document.getElementById('filter-pending')?.addEventListener('change', applyStatusFilters);
    } catch (error) {
        console.error('Ошибка при инициализации таблицы:', error);
        if (loadingIndicator) {
            loadingIndicator.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Ошибка загрузки редактора таблиц: ${error.message}
                </div>
                <button class="btn btn-primary mt-2" onclick="window.location.reload()">
                    Обновить страницу
                </button>
            `;
        }
    }
}

// Обработчик для скачивания графика перенесен в DOMContentLoaded

// Обработчик клика по кнопке "Календарный вид" перенесен в DOMContentLoaded ниже

document.addEventListener('DOMContentLoaded', function() {
    // Обработчик для кнопки календарного вида
    const calendarLink = document.querySelector('a[href*="projects.calendar"]');
    if (calendarLink) {
        calendarLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Получаем текущие значения фильтров
            const startDate = document.getElementById('date-from').value;
            const endDate = document.getElementById('date-to').value;
            
            // Формируем URL с параметрами
            let url = this.getAttribute('href');
            url += `?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
            
            // Переходим по URL
            window.location.href = url;
        });
    }
    
    // Обработчик для скачивания графика
    const downloadBtn = document.getElementById('download-schedule');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Берем даты из фильтров основной страницы
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            
            // Создаем URL для скачивания с фильтрацией
            let downloadUrl = '{{ route('partner.projects.schedule-file', $project->id) }}';
            if (dateFrom && dateTo) {
                downloadUrl += `?start_date=${dateFrom}&end_date=${dateTo}`;
            }
              // Перенаправляем на скачивание файла
            window.location.href = downloadUrl;
        });
        
        // Обработчик кнопки "Обновить данные для клиента"
        document.getElementById('generate-client-data')?.addEventListener('click', function() {
            if (confirm('Обновить данные план-графика для клиентского интерфейса?')) {
                // Показываем индикатор загрузки
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Обновление...';
                
                // Отправляем запрос на генерацию данных
                fetch(`{{ route('partner.projects.schedule-generate-data', $project->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Данные для клиентского интерфейса успешно обновлены!');
                    } else {
                        alert('Ошибка при обновлении данных: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Ошибка при обновлении данных:', error);
                    alert('Ошибка при обновлении данных');
                })
                .finally(() => {
                    // Восстанавливаем кнопку
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-sync me-1"></i> Обновить данные для клиента';
                });
            }
        });
    }

    // Обработчик фильтрации дат с учетом даты окончания работ
    const applyFilterBtn = document.getElementById('apply-filter');
    const dateFromInput = document.getElementById('date-from');
    const dateToInput = document.getElementById('date-to');
    
    if (applyFilterBtn && dateFromInput && dateToInput) {
        // Максимальная дата для фильтрации (если указан work_end_date)
        @if($project->work_end_date)
            const workEndDate = '{{ date('Y-m-d', strtotime($project->work_end_date)) }}';
        @else
            const workEndDate = null;
        @endif

        // Обработчик нажатия кнопки "Применить"
        applyFilterBtn.addEventListener('click', function() {
            applyFilters();
        });

        // Быстрые фильтры с учетом ограничений
        document.getElementById('filter-this-month')?.addEventListener('click', function() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            setFilterDates(firstDay, lastDay);
            applyFilters();
        });

        document.getElementById('filter-next-month')?.addEventListener('click', function() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth() + 1, 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 2, 0);
            
            setFilterDates(firstDay, lastDay);
            applyFilters();
        });

        document.getElementById('filter-this-year')?.addEventListener('click', function() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), 0, 1);
            const lastDay = new Date(today.getFullYear(), 11, 31);
            
            setFilterDates(firstDay, lastDay);
            applyFilters();
        });

        // Функция установки дат фильтра с проверкой ограничений
        function setFilterDates(startDate, endDate) {
            dateFromInput.value = formatDate(startDate);
            
            // Проверяем ограничение по work_end_date
            if (workEndDate && new Date(workEndDate) < endDate) {
                dateToInput.value = workEndDate;
            } else {
                dateToInput.value = formatDate(endDate);
            }
        }

        // Функция форматирования даты в YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Функция применения фильтров с проверкой ограничений
        function applyFilters() {
            // Если указана work_end_date, проверяем, что dateToInput не превышает её
            if (workEndDate && dateToInput.value > workEndDate) {
                alert('Внимание: Дата окончания фильтра не может быть позже приблизительной даты окончания работ. Дата была скорректирована.');
                dateToInput.value = workEndDate;
            }
            
            // Здесь можно разместить логику для применения фильтра
            if (window.hot) {
                // Обновляем отображение таблицы с новыми датами
                window.hot.render();
            }
        }
    }
});
</script>

<style>
/* Стили для адаптивности таблицы */
.handsontable {
    font-size: 14px;
}

/* Явно задаем размеры контейнера таблицы */
#excel-editor {
    min-height: 600px;
    border: 1px solid #ddd;
    background-color: #fff;
}

/* Стили для индикатора загрузки */
#loading-indicator {
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

/* Стили для заголовка таблицы */
.handsontable .htCore th {
    background-color: #f8f9fa;
    font-weight: bold;
    text-align: center;
}

/* Стили для предпросмотра графика удалены, так как теперь используется календарный вид */

.table-danger {
    background-color: #ffe5e5 !important;
}

@media (max-width: 768px) {
    #excel-editor {
        height: 400px;
    }
    
    .handsontable {
        font-size: 12px;
    }
    
    .schedule-preview-table {
        font-size: 12px;
    }
    
    .schedule-preview-table th, 
    .schedule-preview-table td {
        padding: 4px;
    }
}

/* Устранение артефактов для не-премиум версии */
.hot-display-license-info {
    display: none !important;
}

.htDanger {
    background: #ffe5e5 !important;
    color: #b71c1c !important;
}
</style>
