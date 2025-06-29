@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
        <div>
            <h1 class="h3 mb-2 mb-md-0">Объекты</h1>
            <p class="text-muted mb-3" id="projects-counter">
                Показано: <span>{{ $projects->count() }}</span> из <span>{{ $projects->total() }}</span> объектов
            </p>
        </div>
        <a href="{{ route('partner.projects.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Создать объект
        </a>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <!-- Фильтры и поиск - улучшенная версия для мобильных -->
    <div class="card mb-4">
        <div class="card-header p-2 d-flex justify-content-between align-items-center">
            <h5 class="m-0">Фильтры</h5>
            <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="fas fa-sliders-h"></i>
            </button>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body p-2 p-md-3">
                <form action="{{ route('partner.projects.index') }}" method="GET" id="filterForm">
                    <input type="hidden" name="filter" value="true">
                    
                    <div class="row g-2">
                        <div class="col-12 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" placeholder="Поиск по имени, адресу..." value="{{ $filters['search'] ?? '' }}">
                                <button type="submit" class="btn btn-primary d-md-none">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-6 col-md-4 mb-2">
                            <label class="d-block d-md-none small mb-1">Статус</label>
                            <select class="form-select" name="status" id="status">
                                <option value="">Все статусы</option>
                                <option value="active" {{ isset($filters['status']) && $filters['status'] == 'active' ? 'selected' : '' }}>Активные</option>
                                <option value="completed" {{ isset($filters['status']) && $filters['status'] == 'completed' ? 'selected' : '' }}>Завершенные</option>
                                <option value="paused" {{ isset($filters['status']) && $filters['status'] == 'paused' ? 'selected' : '' }}>Приостановленные</option>
                                <option value="cancelled" {{ isset($filters['status']) && $filters['status'] == 'cancelled' ? 'selected' : '' }}>Отмененные</option>
                            </select>
                        </div>
                        
                        <div class="col-6 col-md-4 mb-2">
                            <label class="d-block d-md-none small mb-1">Тип работ</label>
                            <select class="form-select" name="work_type" id="work_type">
                                <option value="">Все типы работ</option>
                                <option value="repair" {{ isset($filters['work_type']) && $filters['work_type'] == 'repair' ? 'selected' : '' }}>Ремонт</option>
                                <option value="design" {{ isset($filters['work_type']) && $filters['work_type'] == 'design' ? 'selected' : '' }}>Дизайн</option>
                                <option value="construction" {{ isset($filters['work_type']) && $filters['work_type'] == 'construction' ? 'selected' : '' }}>Строительство</option>
                            </select>
                        </div>
                        
                        @if(Auth::user()->role === 'admin')
                        <div class="col-6 col-md-4 mb-2">
                            <label class="d-block d-md-none small mb-1">Партнер</label>
                            <select class="form-select" name="partner_id" id="partner_id">
                                <option value="">Все партнеры</option>
                                @foreach(\App\Models\User::where('role', 'partner')->orderBy('name')->get() as $partner)
                                <option value="{{ $partner->id }}" {{ isset($filters['partner_id']) && $filters['partner_id'] == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        
                        <div class="col-6 d-md-none">
                            <a href="{{ route('partner.projects.index', ['clear' => true]) }}" class="btn btn-outline-secondary w-100">
                                Сбросить
                            </a>
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-12 text-end d-none d-md-block">
                            <a href="{{ route('partner.projects.index', ['clear' => true]) }}" class="btn btn-outline-secondary">
                                Сбросить
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Информация о примененных фильтрах -->
    @if(!empty(array_filter($filters ?? [])))
        <div class="mb-3 overflow-auto hide-scroll">
            <div class="d-flex align-items-center flex-nowrap">
                <span class="me-2 text-nowrap">Фильтры:</span>
                @if(!empty($filters['search']))
                    <span class="badge bg-light text-dark me-2">Поиск: {{ Str::limit($filters['search'], 15) }}</span>
                @endif
                @if(!empty($filters['status']))
                    <span class="badge bg-light text-dark me-2">Статус: 
                        {{ $filters['status'] == 'active' ? 'Активные' : 
                          ($filters['status'] == 'completed' ? 'Завершенные' : 
                          ($filters['status'] == 'paused' ? 'Приостановленные' : 'Отмененные')) }}
                    </span>
                @endif
                @if(!empty($filters['work_type']))
                    <span class="badge bg-light text-dark me-2">Тип: 
                        {{ $filters['work_type'] == 'repair' ? 'Ремонт' : 
                          ($filters['work_type'] == 'design' ? 'Дизайн' : 'Строительство') }}
                    </span>
                @endif
                @if(Auth::user()->role === 'admin' && !empty($filters['partner_id']))
                    <span class="badge bg-light text-dark me-2">Партнер: 
                        {{ \App\Models\User::find($filters['partner_id'])->name ?? 'Не найден' }}
                    </span>
                @endif
            </div>
        </div>
    @endif
    
    @if($projects->isEmpty())
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Объекты не найдены</h5>
                @if(!empty(array_filter($filters ?? [])))
                    <p>Попробуйте изменить параметры фильтрации или <a href="{{ route('partner.projects.index', ['clear' => true]) }}">сбросить все фильтры</a>.</p>
                @else
                    <p>Создайте свой первый объект, нажав на кнопку "Создать объект" выше.</p>
                    <a href="{{ route('partner.projects.create') }}" class="btn btn-outline-primary mt-2">
                        <i class="fas fa-plus-circle me-1"></i>Создать объект
                    </a>
                @endif
            </div>
        </div>
    @else
        <!-- Пагинация и карточки проектов -->
        <div class="row" id="projects-container">
            @include('partner.projects.partials.projects-cards')
        </div>

        <div class="pagination-container mt-4 d-flex justify-content-center" id="pagination-container">
            {{ $projects->links() }}
        </div>
    @endif
</div>

<!-- Модальное окно для документов -->
<div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentModalLabel">Генерация документа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6>Параметры документа</h6>
                    <form id="documentForm">
                        <input type="hidden" id="document-project-id" name="project_id" value="">
                        <input type="hidden" id="document-type" name="document_type" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Формат документа</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="format" id="format-pdf" value="pdf" checked>
                                <label class="btn btn-outline-primary" for="format-pdf">PDF</label>
                                
                                <input type="radio" class="btn-check" name="format" id="format-docx" value="docx">
                                <label class="btn btn-outline-primary" for="format-docx">DOCX</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="include-signature" name="include_signature">
                                <label class="form-check-label" for="include-signature">Добавить подпись</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="include-stamp" name="include_stamp">
                                <label class="form-check-label" for="include-stamp">Добавить печать</label>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="mb-3">
                    <h6>Предпросмотр документа</h6>
                    <div id="document-preview-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                        <p class="mt-2">Загрузка предпросмотра...</p>
                    </div>
                    <div id="document-preview-container" class="border rounded p-3 bg-light" style="max-height: 400px; overflow-y: auto; display: none;">
                        <div id="document-preview-content"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-success" id="download-document">
                    <i class="fas fa-download me-2"></i>Скачать документ
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Фильтрация проектов
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        const selects = filterForm.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    }
    
    // Обработка выбора документа
    const documentLinks = document.querySelectorAll('.generate-document');
    documentLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const projectId = this.getAttribute('data-project-id');
            const documentType = this.getAttribute('data-document-type');
            
            // Устанавливаем значения в модальном окне
            document.getElementById('document-project-id').value = projectId;
            document.getElementById('document-type').value = documentType;
            
            // Устанавливаем заголовок модального окна
            let documentTitle = '';
            switch(documentType) {
                case 'completion_act_ip_ip': documentTitle = 'Акт завершения ремонта ИП-ИП'; break;
                case 'completion_act_fl_ip': documentTitle = 'Акт завершения ремонта ФЛ-ИП'; break;
                case 'act_ip_ip': documentTitle = 'Акт ИП-ИП'; break;
                case 'act_fl_ip': documentTitle = 'Акт ФЛ-ИП'; break;
                case 'bso': documentTitle = 'БСО'; break;
                case 'invoice_ip': documentTitle = 'Счет на ИП'; break;
                case 'invoice_fl': documentTitle = 'Счет на ФЛ'; break;
            }
            document.getElementById('documentModalLabel').textContent = documentTitle;
            
            // Загружаем предпросмотр
            loadDocumentPreview();
        });
    });
    
    // Обновление предпросмотра при изменении параметров
    document.getElementById('include-signature').addEventListener('change', loadDocumentPreview);
    document.getElementById('include-stamp').addEventListener('change', loadDocumentPreview);
    document.querySelectorAll('[name="format"]').forEach(radio => {
        radio.addEventListener('change', loadDocumentPreview);
    });
    
    // Функция загрузки предпросмотра документа
    function loadDocumentPreview() {
        const projectId = document.getElementById('document-project-id').value;
        const documentType = document.getElementById('document-type').value;
        const includeSignature = document.getElementById('include-signature').checked;
        const includeStamp = document.getElementById('include-stamp').checked;
        
        // Отладочная информация
        console.log('Debug loadDocumentPreview:', {
            projectId,
            documentType,
            includeSignature,
            includeStamp
        });
        
        // Показываем индикатор загрузки
        document.getElementById('document-preview-loading').style.display = 'block';
        document.getElementById('document-preview-container').style.display = 'none';
        
        // Отправляем запрос на предпросмотр документа
        const formData = new FormData();
        formData.append('include_signature', includeSignature ? 1 : 0);
        formData.append('include_stamp', includeStamp ? 1 : 0);
        
        // Выбираем правильный URL для предпросмотра в зависимости от типа документа
        let previewUrl;
        let useSpecializedRoute = true;
        
        switch (documentType) {
            case 'completion_act_ip_ip':
                previewUrl = `/partner/projects/${projectId}/documents/completion-act-ip-ip/preview`;
                break;
            case 'completion_act_fl_ip':
                previewUrl = `/partner/projects/${projectId}/documents/completion-act-fl-ip/preview`;
                break;
            case 'act_ip_ip':
                previewUrl = `/partner/projects/${projectId}/documents/act-ip-ip/preview`;
                break;
            case 'act_fl_ip':
                previewUrl = `/partner/projects/${projectId}/documents/act-fl-ip/preview`;
                break;
            case 'bso':
                previewUrl = `/partner/projects/${projectId}/documents/bso/preview`;
                break;
            case 'invoice_ip':
                previewUrl = `/partner/projects/${projectId}/documents/invoice-ip/preview`;
                break;
            case 'invoice_fl':
                previewUrl = `/partner/projects/${projectId}/documents/invoice-fl/preview`;
                break;
            default:
                useSpecializedRoute = false;
                previewUrl = `/partner/projects/${projectId}/documents/preview`;
                break;
        }
        
        // Добавляем document_type только если используется общий маршрут
        if (!useSpecializedRoute) {
            formData.append('document_type', documentType);
        }
        
        // Отладочная информация
        console.log('Preview URL selected:', {
            previewUrl,
            useSpecializedRoute,
            documentType
        });
        
        fetch(previewUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            // Скрываем индикатор загрузки
            document.getElementById('document-preview-loading').style.display = 'none';
            document.getElementById('document-preview-container').style.display = 'block';
            
            // Отображаем HTML документа
            document.getElementById('document-preview-content').innerHTML = data.html;
        })
        .catch(error => {
            console.error('Ошибка при загрузке предпросмотра:', error);
            document.getElementById('document-preview-loading').style.display = 'none';
            document.getElementById('document-preview-container').style.display = 'block';
            document.getElementById('document-preview-content').innerHTML = 
                '<div class="alert alert-danger">Ошибка при загрузке предпросмотра документа</div>';
        });
    }
    
    // Обработка скачивания документа
    document.getElementById('download-document').addEventListener('click', function() {
        const projectId = document.getElementById('document-project-id').value;
        const documentType = document.getElementById('document-type').value;
        const format = document.querySelector('input[name="format"]:checked').value;
        const includeSignature = document.getElementById('include-signature').checked;
        const includeStamp = document.getElementById('include-stamp').checked;
        
        // Добавляем индикатор загрузки
        const downloadBtn = document.getElementById('download-document');
        const originalBtnText = downloadBtn.innerHTML;
        downloadBtn.disabled = true;
        downloadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Генерация документа...';
        
        // Создаем форму для отправки
        const form = document.createElement('form');
        form.method = 'POST';
        
        // Выбираем правильный URL в зависимости от типа документа
        let useSpecializedRoute = true;
        
        switch (documentType) {
            case 'completion_act_ip_ip':
                form.action = `/partner/projects/${projectId}/documents/completion-act-ip-ip/generate`;
                break;
            case 'completion_act_fl_ip':
                form.action = `/partner/projects/${projectId}/documents/completion-act-fl-ip/generate`;
                break;
            case 'act_ip_ip':
                form.action = `/partner/projects/${projectId}/documents/act-ip-ip/generate`;
                break;
            case 'act_fl_ip':
                form.action = `/partner/projects/${projectId}/documents/act-fl-ip/generate`;
                break;
            case 'bso':
                form.action = `/partner/projects/${projectId}/documents/bso/generate`;
                break;
            case 'invoice_ip':
                form.action = `/partner/projects/${projectId}/documents/invoice-ip/generate`;
                break;
            case 'invoice_fl':
                form.action = `/partner/projects/${projectId}/documents/invoice-fl/generate`;
                break;
            default:
                useSpecializedRoute = false;
                form.action = `/partner/projects/${projectId}/documents/generate`;
                break;
        }
        
        form.target = '_blank'; // Открываем в новой вкладке
        form.style.display = 'none';
        
        // Добавляем CSRF токен
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfToken);
        
        // Добавляем параметры
        // Добавляем document_type только если используется общий маршрут
        if (!useSpecializedRoute) {
            const documentTypeInput = document.createElement('input');
            documentTypeInput.type = 'hidden';
            documentTypeInput.name = 'document_type';
            documentTypeInput.value = documentType;
            form.appendChild(documentTypeInput);
        }
        
        const formatInput = document.createElement('input');
        formatInput.type = 'hidden';
        formatInput.name = 'format';
        formatInput.value = format;
        form.appendChild(formatInput);
        
        const includeSignatureInput = document.createElement('input');
        includeSignatureInput.type = 'hidden';
        includeSignatureInput.name = 'include_signature';
        includeSignatureInput.value = includeSignature ? 1 : 0;
        form.appendChild(includeSignatureInput);
        
        const includeStampInput = document.createElement('input');
        includeStampInput.type = 'hidden';
        includeStampInput.name = 'include_stamp';
        includeStampInput.value = includeStamp ? 1 : 0;
        form.appendChild(includeStampInput);
        
        // Добавляем форму на страницу и отправляем
        document.body.appendChild(form);
        
        // Восстанавливаем кнопку через некоторое время
        setTimeout(function() {
            downloadBtn.disabled = false;
            downloadBtn.innerHTML = originalBtnText;
        }, 3000);
        
        form.submit();
    });
});
</script>

<style>
/* Дополнительные стили для мобильных устройств */
@media (max-width: 576px) {
    .project-card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 8px;
    }
    
    .project-card .card-header {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
    
    .project-card .card-footer {
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.35em 0.65em;
    }
    
    /* Улучшения для фильтров */
    #filterCollapse label {
        font-weight: 500;
        color: #495057;
    }
    
    .form-select, .form-control, .btn {
        font-size: 16px; /* Оптимальный размер для предотвращения масштабирования на iOS */
    }
    
    /* Пагинация */
    .pagination {
        flex-wrap: nowrap;
        overflow-x: auto;
    }
    
    .page-item {
        white-space: nowrap;
    }
    
    /* Растянутые ссылки */
    .stretched-link::after {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 1;
        content: "";
    }
}

/* Улучшения для сетки карточек */
@media (max-width: 768px) {
    .row {
        margin-left: -8px;
        margin-right: -8px;
    }
    
    .row > [class*="col-"] {
        padding-left: 8px;
        padding-right: 8px;
    }
    
    /* Улучшенный стиль заголовков */
    h5.card-title {
        font-size: 1rem;
        font-weight: 600;
    }
}

/* Улучшенный стиль контейнера пагинации */
.pagination-container {
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 5px;
}

/* Стили для скелетон-загрузчика */
.skeleton-card {
    position: relative;
    overflow: hidden;
}

.skeleton-text, .skeleton-badge, .skeleton-icon, .skeleton-button {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    height: 16px;
    border-radius: 4px;
}

.skeleton-badge {
    width: 60px;
    height: 20px;
    border-radius: 12px;
}

.skeleton-icon {
    width: 16px;
    height: 16px;
    border-radius: 50%;
}

.skeleton-button {
    height: 36px;
    border-radius: 4px;
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

/* Анимация для индикатора загрузки */
.loading-pulse .spinner-grow:nth-child(1) {
    animation-delay: 0s;
}
.loading-pulse .spinner-grow:nth-child(2) {
    animation-delay: 0.3s;
}
.loading-pulse .spinner-grow:nth-child(3) {
    animation-delay: 0.6s;
}
</style>
@endsection
