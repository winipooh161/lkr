<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ошибка') | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            max-width: 800px;
            padding: 40px;
            text-align: center;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #e74c3c;
            margin: 0;
        }
        .error-title {
            font-size: 24px;
            margin-top: 20px;
        }
        .error-description {
            margin: 20px 0;
            font-size: 16px;
            color: #555;
        }
        .home-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3498db;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: background-color 0.3s;
            margin-top: 20px;
        }
        .home-button:hover {
            background-color: #2980b9;
            color: #fff;
        }
        .additional-info {
            margin-top: 30px;
            font-size: 14px;
            color: #777;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon">@yield('icon', '⚠️')</div>
        <h1 class="error-code">@yield('code', '500')</h1>
        <h2 class="error-title">@yield('title', 'Ошибка сервера')</h2>
        <div class="error-description">
            @yield('message', 'Что-то пошло не так. Наши специалисты уже работают над решением проблемы.')
        </div>
        <a href="{{ url('/') }}" class="home-button">На главную</a>
        @yield('additional')
        <div class="additional-info">
            ID: {{ Str::uuid() }} | Время: {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>
    @yield('scripts')
</body>
</html>
