@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">Сметы</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('partner.estimates.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i>Создать смету
            </a>
        </div>
    </div>

    <!-- Панель фильтров -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i> Фильтры
        </div>
        <div class="card-body">
            <form method="GET" id="filter-form" class="row">
                <!-- Фильтр по объекту -->
                <div class="col-md-3 mb-3">
                    <label for="project_id" class="form-label">Объект</label>
                    <select class="project-search-select" id="project_id" name="project_id" style="width: 100%;" data-placeholder="Выберите объект">
                        <option value=""></option>
                        <option value="">Все объекты</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->client_name }} ({{ $project->address }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Фильтр по статусу -->
                <div class="col-md-2 mb-3">
                    <label for="status" class="form-label">Статус</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Все статусы</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Черновик</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>На рассмотрении</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Утверждена</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Отклонена</option>
                    </select>
                </div>
                
                <!-- Поиск -->
                <div class="col-md-4 mb-3">
                    <label for="search" class="form-label">Поиск</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Название или описание" value="{{ request('search') }}">
                </div>
                
                <!-- Кнопки фильтра -->
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Применить
                    </button>
                    <a href="{{ route('partner.estimates.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Сбросить
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    @endif

    <!-- Блок со списком смет -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Список смет</h5>
            <div>
                <a href="{{ route('partner.estimates.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i>Создать смету
                </a>
            </div>
        </div>
        <div class="card-body">
            @include('partner.estimates.partials.estimates-list')
            @include('partner.estimates.partials.dropdown-init-script')
        </div>
    </div>

    <!-- Пагинация -->
    <div class="d-flex justify-content-center mt-4">
        {{ $estimates->links() }}
    </div>
</div>

@endsection

<style>
/* Кастомные стили для Select2 */
.select2-container--bootstrap-5 .select2-selection {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
    height: auto;
}

.select2-container--bootstrap-5 .select2-selection--single {
    height: 38px;
}

.select2-container--bootstrap-5 .select2-selection__rendered {
    line-height: 1.5;
    padding-left: 0;
    color: #212529;
}

.select2-container--bootstrap-5 .select2-selection__arrow {
    height: 36px;
}

.select2-container--bootstrap-5.select2-container--focus .select2-selection,
.select2-container--bootstrap-5.select2-container--open .select2-selection {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Убираем конфликты с Bootstrap */
.select2-container--bootstrap-5 .select2-dropdown {
    border-color: #86b7fe;
    border-radius: 0.25rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}

.select2-container--bootstrap-5 .select2-dropdown .select2-search .select2-search__field {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
}

.select2-container--bootstrap-5 .select2-dropdown .select2-results__option--highlighted[aria-selected] {
    background-color: #0d6efd;
    color: #fff;
}
</style>

@push('scripts')
<script>
$(document).ready(function() {
    // Проверяем, загружен ли Select2
    if (typeof $.fn.select2 === 'undefined') {
        console.error('Select2 не загружен. Пробую загрузить динамически...');
        
        // Динамическая загрузка Select2 если он не загружен
        var cssLink = document.createElement('link');
        cssLink.rel = 'stylesheet';
        cssLink.href = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';
        document.head.appendChild(cssLink);
        
        var cssThemeLink = document.createElement('link');
        cssThemeLink.rel = 'stylesheet';
        cssThemeLink.href = 'https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css';
        document.head.appendChild(cssThemeLink);
        
        var scriptTag = document.createElement('script');
        scriptTag.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        document.body.appendChild(scriptTag);
        
        // Пытаемся инициализировать через 500мс
        setTimeout(function() {
            if (typeof $.fn.select2 !== 'undefined') {
                console.log('Select2 успешно загружен динамически');
                initializeSelect2();
            } else {
                console.error('Не удалось динамически загрузить Select2');
            }
        }, 500);
        return;
    }
    
    // Вызов функции инициализации
    initializeSelect2();
});

// Функция для инициализации Select2
function initializeSelect2() {
    $('.project-search-select').select2({
        theme: 'bootstrap-5',
        placeholder: 'Выберите или найдите объект...',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "Ничего не найдено";
            },
            searching: function() {
                return "Поиск...";
            }
        },
        // Добавим возможность поиска локально по уже загруженным опциям
        matcher: function(params, data) {
            // Если нет поискового запроса, вернуть все данные
            if ($.trim(params.term) === '') {
                return data;
            }

            // Если нет значения, вернуть false
            if (typeof data.text === 'undefined') {
                return null;
            }

            // Поиск по тексту опции без учета регистра
            if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                return data;
            }

            // Если ничего не нашлось
            return null;
        }
    });
}
</script>

<!-- Специальный скрипт для исправления выпадающих меню на странице оценок -->
<script src="{{ asset('js/estimates-dropdowns.js') }}"></script>
@endpush
