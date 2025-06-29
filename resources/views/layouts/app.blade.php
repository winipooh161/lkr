<!doctype html>
@php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if(auth()->check())
    <meta name="user-role" content="{{ auth()->user()->role }}" data-user-role="{{ auth()->user()->role }}">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @if(isset($pageTitle))
            {{ $pageTitle }} - {{ config('app.name', 'Laravel') }}
        @else
            {{ config('app.name', 'Laravel') }} [Не установлен заголовок страницы]
        @endif
    </title>

    
    <!-- jQuery должен быть подключен до скриптов, которые его используют -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" 
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" 
            crossorigin="anonymous"></script>
            
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    
    <!-- Дополнительные стили и скрипты из дочерних представлений -->
    @yield('head')
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Days+One&family=Onest:wght@100..900&display=swap" rel="stylesheet">
            

    <!-- Дополнительные стили для мобильной адаптации -->
    <link href="{{ asset('css/mobile-fixes.css') }}?v={{ time() }}" rel="stylesheet">
    
    <!-- Стили для административной панели -->
    <link href="{{ asset('css/admin-dashboard.css') }}?v={{ time() }}" rel="stylesheet">
    
    <!-- Исправления для выпадающих меню (используем версионирование для кеширования) -->
    <link href="{{ asset('css/dropdown-fixes.css') }}?v={{ time() }}" rel="stylesheet">
    

    <!-- Новая тема сайта -->
    <link href="{{ asset('css/theme.css') }}?v={{ time() }}" rel="stylesheet">
    
    <!-- Анимации и эффекты -->
    <link href="{{ asset('css/animations.css') }}?v={{ time() }}" rel="stylesheet">
    
    <!-- Дополнительные оптимизации для разных устройств -->
    <link href="{{ asset('css/optimizations.css') }}?v={{ time() }}" rel="stylesheet">
    
    <!-- Стили для документов -->
    <link href="{{ asset('css/documents.css') }}?v={{ time() }}" rel="stylesheet">
    
    <!-- Скрипт для улучшения мобильного отображения -->
    <script src="{{ asset('js/mobile-improvements.js') }}?v={{ time() }}"></script>
    
    <!-- Скрипт для интерактивных эффектов темы -->
    <script src="{{ asset('js/theme-effects.js') }}?v={{ time() }}"></script>

    <!-- Стили для интерактивных туров -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/introjs.min.css">
    <link href="{{ asset('css/intro-tour.css') }}?v={{ time() }}" rel="stylesheet">
    
    <!-- Библиотека для интерактивных туров -->
    <script src="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/intro.min.js"></script>
    
    <!-- Данные туров и логика работы системы обучения -->
    <script src="{{ asset('js/tours-data.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/tours-bundle.js') }}?v={{ time() }}"></script>
    
    <!-- Исправления для выпадающих меню в сметах -->
    <script src="{{ asset('js/estimates-dropdowns.js') }}?v={{ time() }}"></script>

    <!-- Совместимость туров и выпадающих меню -->
    <script src="{{ asset('js/dropdown-tour-compatibility.js') }}?v={{ time() }}"></script>
        <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz"
            crossorigin="anonymous"
            onload="console.log('Bootstrap JS loaded successfully')" 
            onerror="console.error('Failed to load Bootstrap JS')"></script>
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Select2 CSS и JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Scripts -->
    @vite(['resources/css/app.css',  'resources/sass/app.scss','resources/js/app.js'])
</head>
<body data-user-role="{{ auth()->check() ? auth()->user()->role : '' }}">
    @include('components.help-tour-button')
    <div class="wrapper">
        <!-- Боковая навигационная панель -->
        @include('layouts.partials.sidebar')

        <!-- Основной контент -->
        <div id="content">
            <!-- Верхняя навигационная панель -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapseShow" class="btn btn-light d-inline-block d-md-none me-2" onclick="console.log('Клик по кнопке меню')">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a class="navbar-brand d-none d-md-inline" href="{{ url('/') }}">
                        {{ config('app.name', 'Laravel') }}
                    </a>
                    
                    <div class="ms-2 d-none d-lg-inline">
                        @if(isset($pageTitle))
                            <span class="text-secondary fw-light">/ {{ $pageTitle }}</span>
                        @endif
                        @if(isset($pageSubtitle))
                            <span class="text-muted small"> - {{ $pageSubtitle }}</span>
                        @endif
                    </div>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link" href="#" role="button">
                                    <i class="fas fa-bell"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <main class="">
             
                
                @yield('content')
            </main>
        </div>
    </div>


    <!-- Исправление для выпадающих меню (добавляем версионирование для обновления кеша) -->
    <script src="{{ asset('js/dropdown-fix.js') }}?v={{ time() }}"></script>
    
    <!-- Новый универсальный скрипт для инициализации всех выпадающих меню -->
    <script src="{{ asset('js/dropdown-init.js') }}?v={{ time() }}"></script>
    
    <!-- Обязательно разместить yield для скриптов в конце body -->
    @yield('scripts')
    
    @stack('scripts')
</body>
</html>
