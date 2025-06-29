<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Календарный график</h5>
    </div>
    
    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex flex-wrap align-items-center mb-3">
                        <div class="input-group input-group-sm me-2 mb-2" style="max-width: 200px;">
                            <span class="input-group-text">С</span>
                            <input type="date" class="form-control" id="calendar-date-from" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="input-group input-group-sm me-2 mb-2" style="max-width: 200px;">
                            <span class="input-group-text">По</span>
                            <input type="date" class="form-control" id="calendar-date-to" value="{{ date('Y-m-t') }}">
                        </div>
                        <button class="btn btn-sm btn-primary mb-2" id="apply-calendar-filter">Применить</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex flex-wrap justify-content-md-end mb-3">
                        <div class="me-2 mb-2">
                            <select class="form-select form-select-sm" id="month-quick-select">
                                <option value="">Быстрый выбор месяца</option>
                                @for ($i = 0; $i < 12; $i++)
                                    @php
                                        $date = \Carbon\Carbon::now()->startOfYear()->addMonths($i);
                                        $monthYear = $date->format('m.Y');
                                        $russianMonths = [
                                            'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                                            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
                                        ];
                                        $monthName = $russianMonths[$date->month - 1] . ' ' . $date->year;
                                    @endphp
                                    <option value="{{ $monthYear }}">{{ $monthName }}</option>
                                @endfor
                            </select>
                        </div>
                        <button class="btn btn-sm btn-outline-primary mb-2" id="download-calendar-pdf">
                            <i class="fas fa-file-pdf me-1"></i>Экспорт в PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Контейнер для календаря -->
    <div class="card">
        <div class="card-body p-0">
            <!-- Спиннер загрузки -->
            <div id="calendar-loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-2 text-muted">Загрузка данных календаря...</p>
            </div>
            
            <!-- Сообщение об ошибке -->
            <div id="calendar-error" class="alert alert-danger m-4 d-none"></div>
            
            <!-- Контейнер с календарем -->
            <div id="calendar-container" class="d-none">
                <div class="calendar-view">
                    <div class="calendar-header">
                        <div class="task-name-header">Задача</div>
                        <div class="calendar-content-header">
                            <div class="calendar-months"></div>
                            <div class="calendar-days"></div>
                        </div>
                    </div>
                    <div class="calendar-body">
                        <div class="task-names"></div>
                        <div class="calendar-tasks"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Подключаем скрипт для работы с календарем -->
<script src="/public_html/js/calendar-view-client-fixed.js"></script>

<!-- Устанавливаем URL для API календаря -->
<script>
    // Явно указываем URL с префиксом
    const calendarApiUrl = '/client/projects/{{ $project->id }}/calendar-view';
</script>

<!-- Стили для календаря -->
<style>
    .calendar-view {
        display: flex;
        flex-direction: column;
        width: 100%;
        overflow: hidden;
    }
    
    .calendar-header {
        display: flex;
        font-weight: bold;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    .task-name-header {
        width: 220px;
        min-width: 220px;
        padding: 10px;
        border-right: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .calendar-content-header {
        display: flex;
        flex-direction: column;
        overflow-x: auto;
        flex: 1;
    }
    
    .calendar-months {
        display: flex;
        border-bottom: 1px solid #dee2e6;
    }
    
    .calendar-days {
        display: flex;
    }
    
    .month-cell {
        text-align: center;
        padding: 5px 0;
        background-color: #e9ecef;
    }
    
    .day-cell {
        text-align: center;
        border-right: 1px solid #dee2e6;
        padding: 5px 0;
        width: 30px;
        flex-shrink: 0;
    }
    
    .weekend-day {
        background-color: #ffe0e0;
    }
    
    .calendar-body {
        display: flex;
        overflow: auto;
    }
    
    .task-names {
        width: 220px;
        min-width: 220px;
        flex-shrink: 0;
    }
    
    .calendar-tasks {
        flex: 1;
        width: 100%;
    }
    
    .calendar-task {
        display: flex;
        border-bottom: 1px solid #dee2e6;
    }
    
    .task-name {
        width: 220px;
        min-width: 220px;
        padding: 5px 10px;
        border-right: 1px solid #dee2e6;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        background-color: #f8f9fa;
    }
    
    .task-days {
        display: flex;
        flex: 1;
    }
    
    .task-day {
        border-right: 1px solid #dee2e6;
        width: 30px;
        height: 30px;
        position: relative;
        flex-shrink: 0;
    }
    
    .task-progress {
        height: 100%;
        width: 100%;
    }
    
    .status-done {
        background-color: #28a745;
    }
    
    .status-in-progress {
        background-color: #007bff;
    }
    
    .status-waiting {
        background-color: #ffc107;
    }
    
    .status-overdue {
        background-color: #dc3545;
    }
    
    .status-canceled {
        background-color: #6c757d;
    }
    
    /* Адаптивность */
    @media (max-width: 768px) {
        .task-name-header,
        .task-name {
            width: 150px;
            min-width: 150px;
        }
    }
</style>
