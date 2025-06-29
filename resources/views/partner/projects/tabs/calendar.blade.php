@extends('layouts.app')

@section('title', 'Календарный график проекта')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <h3>Календарный график проекта #{{ $project->id }}</h3>
        <div>
            <a href="{{ route('partner.projects.show', ['project' => $project->id, 'tab' => 'schedule']) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Вернуться к графику
            </a>
        </div>
    </div>
    
    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex flex-wrap align-items-center mb-3">
                        <div class="input-group input-group-sm me-2 mb-2" style="max-width: 200px;">
                            <span class="input-group-text">С</span>
                            <input type="date" class="form-control" id="calendar-date-from" value="{{ $startDate ?? date('Y-m-01') }}">
                        </div>
                        <div class="input-group input-group-sm me-2 mb-2" style="max-width: 200px;">
                            <span class="input-group-text">По</span>
                            <input type="date" class="form-control" id="calendar-date-to" value="{{ $endDate ?? date('Y-m-t') }}">
                        </div>
                        <button class="btn btn-sm btn-primary mb-2" id="apply-calendar-filter">Применить</button>
                    </div>
                </div>                <div class="col-md-6">
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
                        <div class="me-2 mb-2">
                            <button class="btn btn-sm btn-success" id="download-calendar-pdf">
                                <i class="fas fa-file-pdf me-1"></i> Скачать PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Календарь -->
    <div class="card">
        <div class="card-body p-0">
            <div id="calendar-loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-2">Загрузка календарного графика...</p>
            </div>
            
            <div id="calendar-container" class="d-none">
                <div class="calendar-grid-container">
                    <div class="calendar-header">
                        <div class="task-column">Задача</div>
                        <div class="days-container">
                            <div class="calendar-months">
                                <!-- Месяцы будут добавлены через JavaScript -->
                            </div>
                            <div class="calendar-days">
                                <!-- Дни будут добавлены через JavaScript -->
                            </div>
                        </div>
                    </div>
                    <div class="calendar-body">
                        <div class="calendar-tasks">
                            <!-- Задачи будут добавлены через JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="calendar-error" class="alert alert-danger m-3 d-none"></div>
        </div>
    </div>
</div>
@endsection


<style>
    .calendar-grid-container {
        width: 100%;
        overflow-x: auto;
    }
      .calendar-header {
        display: flex;
        background-color: #f8f9fa;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .days-container {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        overflow-x: auto;
    }
    
    .task-column {
        min-width: 250px;
        max-width: 250px;
        padding: 10px;
        font-weight: bold;
        border-right: 1px solid #dee2e6;
        position: sticky;
        left: 0;
        background-color: #f8f9fa;
        z-index: 15;
    }
    
    .calendar-months {
        display: flex;
        border-bottom: 1px solid #dee2e6;
    }
    
    .month-cell {
        padding: 5px 0;
        text-align: center;
        font-weight: bold;
        border-right: 1px solid #dee2e6;
    }
    
    .calendar-body {
        display: flex;
        flex-direction: column;
    }
    
    .calendar-days {
        display: flex;
        border-bottom: 1px solid #dee2e6;
        height: 40px;
    }
    
    .day-cell {
        min-width: 30px;
        height: 40px;
        text-align: center;
        padding: 10px 0;
        border-right: 1px solid #dee2e6;
        font-size: 12px;
    }
    
    .weekend-day {
        background-color: #f8d7da;
    }
    
    .calendar-task {
        display: flex;
        border-bottom: 1px solid #dee2e6;
        height: 40px;
    }
    
    .task-name {
        min-width: 250px;
        max-width: 250px;
        padding: 10px;
        border-right: 1px solid #dee2e6;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        position: sticky;
        left: 0;
        background-color: #ffffff;
    }
    
    .task-days {
        display: flex;
        flex-grow: 1;
    }
    
    .task-day {
        min-width: 30px;
        height: 40px;
        border-right: 1px solid #dee2e6;
        position: relative;
    }
    
    .task-progress {
        position: absolute;
        top: 5px;
        bottom: 5px;
        left: 0;
        width: 100%;
    }
    
    .task-progress.status-in-progress {
        background-color: #007bff;
    }
    
    .task-progress.status-done {
        background-color: #28a745;
    }
    
    .task-progress.status-waiting {
        background-color: #ffc107;
    }
    
    .task-progress.status-canceled {
        background-color: #6c757d;
    }
    
    .task-progress.status-overdue {
        background-color: #dc3545;
    }

    /* Добавляем поддержку адаптивности */
    @media (max-width: 768px) {
        .task-column {
            min-width: 150px;
            max-width: 150px;
        }
        
        .task-name {
            min-width: 150px;
            max-width: 150px;
        }
    }
</style>



<script>
    // Определяем URL API для календаря
    const calendarApiUrl = '{{ route('partner.projects.calendar-view', ['project' => $project->id]) }}';
</script>
<script src="{{ asset('js/calendar-view.js') }}"></script>
