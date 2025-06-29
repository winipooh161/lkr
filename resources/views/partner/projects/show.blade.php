@extends('layouts.app')

@push('styles')
<!-- Исправления для модальных окон в проектах -->
<link href="{{ asset('css/modal-fixes-project.css') }}?v={{ time() }}" rel="stylesheet">
@endpush

@push('scripts')
<!-- JavaScript для исправления модальных окон в проектах -->
<script src="{{ asset('js/project-modal-fixes.js') }}?v={{ time() }}"></script>
<script>
    // Дополнительное исправление для модальных окон после загрузки страницы
    document.addEventListener('DOMContentLoaded', function() {
        // При каждом переключении вкладки проверяем модальные окна
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(element) {
            element.addEventListener('shown.bs.tab', function() {
                // Перемещаем все модальные окна в body после переключения вкладки
                document.querySelectorAll('.modal').forEach(function(modal) {
                    if (modal.parentElement !== document.body) {
                        document.body.appendChild(modal);
                    }
                });
            });
        });
        
        // Автоматическое открытие вкладки, если есть параметр session('open_tab')
        @if(session('open_tab'))
            // Находим нужную вкладку и активируем её
            const tabId = '{{ session('open_tab') }}';
            const tabElement = document.getElementById(tabId + '-tab');
            if (tabElement) {
                const tab = new bootstrap.Tab(tabElement);
                tab.show();
            }
        @endif
    });
</script>
@endpush

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-2">
            <div>
                <h4>{{ $project->client_name }}</h4>
                <p class="text-muted mb-2">
                    <span class="badge {{ $project->status == 'active' ? 'bg-success' : ($project->status == 'paused' ? 'bg-warning' : ($project->status == 'completed' ? 'bg-info' : 'bg-secondary')) }}">
                        {{ $project->status == 'active' ? 'Активен' : ($project->status == 'paused' ? 'Приостановлен' : ($project->status == 'completed' ? 'Завершен' : 'Отменен')) }}
                    </span>
                    <span class="d-block d-md-inline mt-1 mt-md-0 ms-md-2">{{ $project->address }}{{ $project->apartment_number ? ', кв. ' . $project->apartment_number : '' }}</span>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <div class="action-buttons-mobile d-flex flex-column flex-md-row">
                    <a href="{{ route('partner.projects.edit', $project) }}" class="btn btn-outline-primary mb-2 mb-md-0 me-md-2">
                        <i class="fas fa-edit me-1"></i> Редактировать
                    </a>
                    <button type="button" class="btn btn-outline-danger mb-2 mb-md-0 me-md-2" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
                        <i class="fas fa-trash-alt me-1"></i> Удалить
                    </button>
                    <a href="{{ route('partner.projects.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> К списку
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Панель вкладок -->
    <div class="card">
        <div class="card-header p-1 position-relative nav-tabs-wrapper">
            <ul class="nav nav-tabs card-header-tabs scrollable-x hide-scroll" id="projectTabs" role="tablist" data-project-id="{{ $project->id }}">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="main-tab" data-bs-toggle="tab" data-bs-target="#main" type="button" role="tab" aria-controls="main" aria-selected="true">
                        <i class="fas fa-info-circle d-block d-md-none"></i>
                        <span class="d-none d-md-block">Основная</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="finance-tab" data-bs-toggle="tab" data-bs-target="#finance" type="button" role="tab" aria-controls="finance" aria-selected="false">
                        <i class="fas fa-money-bill d-block d-md-none"></i>
                        <span class="d-none d-md-block">Финансы</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab" aria-controls="schedule" aria-selected="false">
                        <i class="fas fa-calendar d-block d-md-none"></i>
                        <span class="d-none d-md-block">График</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="camera-tab" data-bs-toggle="tab" data-bs-target="#camera" type="button" role="tab" aria-controls="camera" aria-selected="false">
                        <i class="fas fa-video d-block d-md-none"></i>
                        <span class="d-none d-md-block">Камера</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="photos-tab" data-bs-toggle="tab" data-bs-target="#photos" type="button" role="tab" aria-controls="photos" aria-selected="false">
                        <i class="fas fa-camera d-block d-md-none"></i>
                        <span class="d-none d-md-block">Фото</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="design-tab" data-bs-toggle="tab" data-bs-target="#design" type="button" role="tab" aria-controls="design" aria-selected="false">
                        <i class="fas fa-paint-brush d-block d-md-none"></i>
                        <span class="d-none d-md-block">Дизайн</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="schemes-tab" data-bs-toggle="tab" data-bs-target="#schemes" type="button" role="tab" aria-controls="schemes" aria-selected="false">
                        <i class="fas fa-sitemap d-block d-md-none"></i>
                        <span class="d-none d-md-block">Схемы</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="false">
                        <i class="fas fa-file d-block d-md-none"></i>
                        <span class="d-none d-md-block">Документы</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="contract-tab" data-bs-toggle="tab" data-bs-target="#contract" type="button" role="tab" aria-controls="contract" aria-selected="false">
                        <i class="fas fa-file-contract d-block d-md-none"></i>
                        <span class="d-none d-md-block">Договор</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="check-tab" data-bs-toggle="tab" data-bs-target="#check" type="button" role="tab" aria-controls="check" aria-selected="false">
                        <i class="fas fa-check-square d-block d-md-none"></i>
                        <span class="d-none d-md-block">Проверка</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="other-tab" data-bs-toggle="tab" data-bs-target="#other" type="button" role="tab" aria-controls="other" aria-selected="false">
                        <i class="fas fa-ellipsis-h d-block d-md-none"></i>
                        <span class="d-none d-md-block">Прочее</span>
                    </button>
                </li>
            </ul>
            <div class="nav-tabs-scroll-indicator d-md-none"></div>
        </div>
        
        <div class="card-body">
            <div class="tab-content" id="projectTabsContent">
                <!-- Подключение содержимого вкладок из отдельных файлов -->
                <div class="tab-pane fade show active" id="main" role="tabpanel" aria-labelledby="main-tab">
                    @include('partner.projects.tabs.main')
                </div>
                
                <div class="tab-pane fade" id="finance" role="tabpanel" aria-labelledby="finance-tab">
                    @include('partner.projects.tabs.finance')
                </div>
                
                <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                    @include('partner.projects.tabs.schedule')
                </div>
                
                <div class="tab-pane fade" id="camera" role="tabpanel" aria-labelledby="camera-tab">
                    @include('partner.projects.tabs.camera')
                </div>
                
                <div class="tab-pane fade" id="photos" role="tabpanel" aria-labelledby="photos-tab">
                    @include('partner.projects.tabs.photos')
                </div>
                
                <div class="tab-pane fade" id="design" role="tabpanel" aria-labelledby="design-tab">
                    @include('partner.projects.tabs.design')
                </div>
                
                <div class="tab-pane fade" id="schemes" role="tabpanel" aria-labelledby="schemes-tab">
                    @include('partner.projects.tabs.schemes')
                </div>
                
                <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                    @include('partner.projects.tabs.documents')
                </div>
                
                <div class="tab-pane fade" id="contract" role="tabpanel" aria-labelledby="contract-tab">
                    @include('partner.projects.tabs.contract')
                </div>
                
                <div class="tab-pane fade" id="check" role="tabpanel" aria-labelledby="check-tab">
                    @include('partner.projects.tabs.check')
                </div>
                
                <div class="tab-pane fade" id="other" role="tabpanel" aria-labelledby="other-tab">
                    @include('partner.projects.tabs.other')
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы действительно хотите удалить объект "{{ $project->client_name }}"?</p>
                <p class="text-danger">Это действие невозможно отменить.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <form action="{{ route('partner.projects.destroy', $project) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для генерации документов -->
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
// Обработчики для генерации документов
document.addEventListener('DOMContentLoaded', function() {
    // Обработка клика на элементы генерации документов
    document.querySelectorAll('.generate-document').forEach(element => {
        element.addEventListener('click', function() {
            const projectId = this.getAttribute('data-project-id');
            const documentType = this.getAttribute('data-document-type');
            
            // Устанавливаем значения в модальном окне
            document.getElementById('document-project-id').value = projectId;
            document.getElementById('document-type').value = documentType;
            
            // Загружаем предпросмотр при открытии модального окна
            loadDocumentPreview();
        });
    });
    
    // Обработчики изменения параметров документа
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
        
        // Добавляем поля в форму
        const fields = [
            { name: '_token', value: document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
            { name: 'format', value: format },
            { name: 'include_signature', value: includeSignature ? '1' : '0' },
            { name: 'include_stamp', value: includeStamp ? '1' : '0' }
        ];
        
        // Добавляем document_type только если используется общий маршрут
        if (!useSpecializedRoute) {
            fields.push({ name: 'document_type', value: documentType });
        }
        
        fields.forEach(field => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = field.name;
            input.value = field.value;
            form.appendChild(input);
        });
        
        // Отправляем форму
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        // Восстанавливаем кнопку через некоторое время
        setTimeout(() => {
            downloadBtn.disabled = false;
            downloadBtn.innerHTML = originalBtnText;
        }, 3000);
    });
});
</script>

<script>
// Добавляем скрипт для индикации горизонтальной прокрутки на мобильных устройствах
document.addEventListener('DOMContentLoaded', function() {
    const tabsContainer = document.querySelector('.nav-tabs');
    const scrollIndicator = document.querySelector('.nav-tabs-scroll-indicator');
    
    if (tabsContainer && scrollIndicator) {
        tabsContainer.addEventListener('scroll', function() {
            const isAtEnd = tabsContainer.scrollLeft + tabsContainer.clientWidth >= tabsContainer.scrollWidth - 5;
            scrollIndicator.style.opacity = isAtEnd ? '0' : '1';
        });
        
        // Проверка при загрузке
        const isAtEnd = tabsContainer.scrollLeft + tabsContainer.clientWidth >= tabsContainer.scrollWidth - 5;
        scrollIndicator.style.opacity = isAtEnd ? '0' : '1';
    }
});
</script>

<style>
/* Дополнительные стили для улучшения мобильного отображения */
@media (max-width: 768px) {
    .action-buttons-mobile {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .action-buttons-mobile .btn {
        margin-bottom: 0.5rem;
    }
    
    /* Улучшение навигации по вкладкам */
    .nav-tabs .nav-link {
        padding: 0.5rem;
        min-width: 40px;
        text-align: center;
    }
    
    .nav-tabs .nav-link i {
        font-size: 1.2rem;
    }
    
    .card-header {
        padding: 0.5rem 0.25rem !important;
    }
    
    .card-body {
        padding: 1rem 0.75rem;
    }
    
    /* Улучшенный заголовок */
    h4 {
        font-size: 1.4rem;
    }
}
</style>
@endsection
