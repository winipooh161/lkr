@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
            <div>
                <h1 class="h3 mb-2 mb-md-0">
                    {{ $project->client_name }}: {{ Str::limit($project->address, 30) }}{{ $project->apartment_number ? ', кв. ' . $project->apartment_number : '' }}
                </h1>
                <div class="d-flex align-items-center flex-wrap mt-2">
                    <span class="badge {{ $project->status == 'active' ? 'bg-success' : ($project->status == 'paused' ? 'bg-warning text-dark' : ($project->status == 'completed' ? 'bg-info' : 'bg-secondary')) }} me-2">
                        {{ $project->status == 'active' ? 'Активен' : ($project->status == 'paused' ? 'Приостановлен' : ($project->status == 'completed' ? 'Завершен' : 'Отменен')) }}
                    </span>
                    <span class="text-muted small">{{ $project->work_type_text }}</span>
                </div>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="{{ route('client.projects.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>К списку объектов
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Панель вкладок с горизонтальной прокруткой -->
    <div class="card mb-4 project-tabs-card">
        <div class="card-header p-0 position-relative">
            <div class="nav-tabs-wrapper">
                <div class="nav-tabs-scroll-indicator d-none d-md-block"></div>
                <ul class="nav nav-tabs" id="projectTabs" data-project-id="{{ $project->id }}" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request('tab') == null ? 'active' : ''}}" id="main-tab" data-bs-toggle="tab" data-bs-target="#main" type="button" role="tab" aria-controls="main" aria-selected="{{ request('tab') == null ? 'true' : 'false'}}">
                            <i class="fas fa-info-circle me-1"></i>Основная информация
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request('tab') == 'finance' ? 'active' : ''}}" id="finance-tab" data-bs-toggle="tab" data-bs-target="#finance" type="button" role="tab" aria-controls="finance" aria-selected="{{ request('tab') == 'finance' ? 'true' : 'false'}}">
                            <i class="fas fa-money-bill-wave me-1"></i>Финансы
                        </button>
                    </li>                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request('tab') == 'schedule' ? 'active' : ''}}" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab" aria-controls="schedule" aria-selected="{{ request('tab') == 'schedule' ? 'true' : 'false'}}">
                            <i class="fas fa-tasks me-1"></i>План-график
                        </button>
                    </li>
    
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request('tab') == 'camera' ? 'active' : ''}}" id="camera-tab" data-bs-toggle="tab" data-bs-target="#camera" type="button" role="tab" aria-controls="camera" aria-selected="{{ request('tab') == 'camera' ? 'true' : 'false'}}">
                            <i class="fas fa-video me-1"></i>Камера
                        </button>
                    </li>                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request('tab') == 'photos' ? 'active' : ''}}" id="photos-tab" data-bs-toggle="tab" data-bs-target="#photos" type="button" role="tab" aria-controls="photos" aria-selected="{{ request('tab') == 'photos' ? 'true' : 'false'}}">
                            <i class="fas fa-images me-1"></i>Фото
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request('tab') == 'check' ? 'active' : ''}}" id="check-tab" data-bs-toggle="tab" data-bs-target="#check" type="button" role="tab" aria-controls="check" aria-selected="{{ request('tab') == 'check' ? 'true' : 'false'}}">
                            <i class="fas fa-clipboard-check me-1"></i>Проверка объекта
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request('tab') == 'design' ? 'active' : ''}}" id="design-tab" data-bs-toggle="tab" data-bs-target="#design" type="button" role="tab" aria-controls="design" aria-selected="{{ request('tab') == 'design' ? 'true' : 'false'}}">
                            <i class="fas fa-paint-brush me-1"></i>Дизайн
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request('tab') == 'documentation' ? 'active' : ''}}" id="documentation-tab" data-bs-toggle="tab" data-bs-target="#documentation" type="button" role="tab" aria-controls="documentation" aria-selected="{{ request('tab') == 'documentation' ? 'true' : 'false'}}">
                            <i class="fas fa-file-alt me-1"></i>Документация
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request('tab') == 'other' ? 'active' : ''}}" id="other-tab" data-bs-toggle="tab" data-bs-target="#other" type="button" role="tab" aria-controls="other" aria-selected="{{ request('tab') == 'other' ? 'true' : 'false'}}">
                            <i class="fas fa-folder-open me-1"></i>Прочие файлы
                        </button>
                    </li>
                </ul>
                <div class="nav-tabs-scroll-indicator d-none d-md-block"></div>
            </div>
        </div>
        <div class="card-body">
            <div class="tab-content" id="projectTabsContent">
                <div class="tab-pane fade {{ request('tab') == null ? 'show active' : ''}}" id="main" role="tabpanel" aria-labelledby="main-tab">
                    @include('client.projects.tabs.main')
                </div>
                <div class="tab-pane fade {{ request('tab') == 'finance' ? 'show active' : ''}}" id="finance" role="tabpanel" aria-labelledby="finance-tab">
                    @include('client.projects.tabs.finance')
                </div>                <div class="tab-pane fade {{ request('tab') == 'schedule' ? 'show active' : ''}}" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                    @include('client.projects.tabs.schedule')
                </div>

                <div class="tab-pane fade {{ request('tab') == 'camera' ? 'show active' : ''}}" id="camera" role="tabpanel" aria-labelledby="camera-tab">
                    @include('client.projects.tabs.camera')
                </div>                <div class="tab-pane fade{{ request('tab') == 'photos' ? 'show active' : ''}}" id="photos" role="tabpanel" aria-labelledby="photos-tab">
                    @include('client.projects.tabs.photos')
                </div>
                <div class="tab-pane fade {{ request('tab') == 'check' ? 'show active' : ''}}" id="check" role="tabpanel" aria-labelledby="check-tab">
                    @include('client.projects.tabs.check')
                </div>
                <div class="tab-pane fade {{ request('tab') == 'design' ? 'show active' : ''}}" id="design" role="tabpanel" aria-labelledby="design-tab">
                    @include('client.projects.tabs.design')
                </div>
                <div class="tab-pane fade {{ request('tab') == 'documentation' ? 'show active' : ''}}" id="documentation" role="tabpanel" aria-labelledby="documentation-tab">
                    @include('client.projects.tabs.documentation')
                </div>
                <div class="tab-pane fade {{ request('tab') == 'other' ? 'show active' : ''}}" id="other" role="tabpanel" aria-labelledby="other-tab">
                    @include('client.projects.tabs.other')
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Базовая инициализация для страницы проекта
document.addEventListener('DOMContentLoaded', function() {
    console.log('Инициализация страницы проекта клиента');
});
</script>

<style>
/* Дополнительные стили для улучшения мобильного отображения */
@media (max-width: 768px) {
    .card-header {
        padding: 0.5rem;
    }
    
    .card-body {
        padding: 0.75rem;
    }
    
    .tab-content {
        padding: 0.5rem;
    }
    
    /* Улучшенные вкладки для мобильных устройств */
    .nav-tabs {
        padding: 0;
        margin: 0;
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
    }
    
    .nav-tabs::-webkit-scrollbar {
        height: 3px;
    }
    
    .nav-tabs::-webkit-scrollbar-thumb {
        background-color: rgba(0,0,0,0.2);
        border-radius: 4px;
    }
    
    .nav-tabs .nav-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        white-space: nowrap;
    }
    
    /* Мобильные версии таблиц */
    .table-card-view td {
        white-space: normal;
    }
    
    /* Стили для камеры */
    .camera-container {
        padding: 1rem 0;
    }
    
    .camera-view-container {
        background: #f5f5f5;
        padding: 10px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    }
    
    @media (max-width: 768px) {
        .camera-view-container iframe {
            height: 240px;
        }
        
        .iv-embed {
            width: 100% !important;
        }
        
        .camera-info {
            font-size: 0.9rem;
        }
    }
}

/* Специфичные стили для страницы проекта */
.project-tabs-card {
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.nav-tabs-wrapper {
    position: relative;
    width: 100%;
    overflow: hidden;
}

/* Улучшаем вкладки проекта */
#projectTabs {
    background-color: #f8f9fa;
    padding-top: 0.25rem;
    border-bottom: none;
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
}

#projectTabs::-webkit-scrollbar {
    display: none;
}

#projectTabs .nav-link {
    color: #555;
    font-weight: 500;
    border: none;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
    white-space: nowrap;
}

#projectTabs .nav-link:hover {
    background-color: rgba(0, 0, 0, 0.04);
}

#projectTabs .nav-link.active {
    color: #007bff;
    background-color: transparent;
    border-bottom: 2px solid #007bff;
}

/* Улучшенная навигация на мобильных устройствах */
@media (max-width: 576px) {
    /* Общие улучшения для всей страницы */
    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    /* Кнопки в мобильном виде */
    .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* Уменьшаем отступы в контейнерах */
    .tab-pane .mb-3, .tab-pane .mb-4 {
        margin-bottom: 0.75rem !important;
    }
    
    /* Уменьшаем заголовки */
    .tab-pane h5 {
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
    }
    
    /* Улучшения для мобильной версии страницы проекта */
    #projectTabs .nav-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    
    #projectTabs .nav-link i {
        margin-right: 0.2rem;
    }
    
    /* Инидикатор прокрутки для вкладок */
    .nav-tabs-scroll-indicator {
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        width: 24px;
        background: linear-gradient(to right, transparent, rgba(248, 249, 250, 0.9));
        pointer-events: none;
        z-index: 1;
    }
}
</style>
@endsection
