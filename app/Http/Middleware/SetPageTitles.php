<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

class SetPageTitles
{
    /**
     * Карта соответствия маршрутов и заголовков страниц
     *
     * @var array
     */
    protected $routeTitles = [
        // Админ
        'admin.dashboard' => 'Панель управления',
        'admin.users.index' => 'Управление пользователями',
        'admin.users.create' => 'Добавление пользователя',
        'admin.users.edit' => 'Редактирование пользователя',
        'admin.users.show' => 'Просмотр пользователя',
        'admin.notifications.form' => 'Отправка уведомлений',
        
        // Проекты в админке
        'admin.projects.index' => 'Все проекты',
        'admin.projects.show' => 'Просмотр проекта',
        'admin.projects.edit' => 'Редактирование проекта',
        
        // Профиль
        'profile.index' => 'Мой профиль',
        'profile.edit' => 'Редактирование профиля',
        
        // Клиент
        'client.index' => 'Личный кабинет',
        'client.projects' => 'Мои проекты',
        'client.projects.show' => 'Просмотр проекта',
        
        // Партнер
        'partner.index' => 'Панель управления партнера',
        'partner.profile' => 'Профиль компании',
        'partner.projects.index' => 'Проекты',
        'partner.projects.create' => 'Создание проекта',
        'partner.projects.edit' => 'Редактирование проекта',
        'partner.projects.show' => 'Информация о проекте',
        'partner.employees.index' => 'Сотрудники',
        'partner.employees.create' => 'Добавление сотрудника',
        'partner.employees.edit' => 'Редактирование сотрудника',
        'partner.employees.show' => 'Информация о сотруднике',
        
        // Сметы
        'partner.estimates.index' => 'Сметы',
        'partner.estimates.create' => 'Создание сметы',
        'partner.estimates.edit' => 'Редактирование сметы',
        'partner.estimates.show' => 'Просмотр сметы',
        
        // Финансы
        'partner.finances.index' => 'Финансы',
        
        // Сметчик
        'estimator.index' => 'Панель сметчика',
        'estimator.projects' => 'Мои проекты',
        'estimator.estimates.index' => 'Сметы',
        'estimator.estimates.create' => 'Создание сметы',
        'estimator.estimates.edit' => 'Редактирование сметы',
        
        // Авторизация
        'login' => 'Вход в систему',
        'register' => 'Регистрация',
        'password.request' => 'Восстановление пароля',
        'password.reset' => 'Сброс пароля',
        
        // Общие страницы
        'home' => 'Главная страница',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Устанавливаем значение по умолчанию для тех страниц, где не будет явно указан заголовок
        if (!isset($GLOBALS['page_title_already_set'])) {
            // Устанавливаем значение по умолчанию
            view()->share('pageTitle', 'Страница');
            $GLOBALS['page_title_already_set'] = true;
        }
        
        // Получаем текущий маршрут перед обработкой запроса
        $routeName = Route::currentRouteName();
        Log::debug('SetPageTitles middleware - текущий маршрут: ' . ($routeName ?: 'не определен'));
        
        // Если у нас есть имя маршрута, устанавливаем заголовок
        if ($routeName) {
            Log::debug('Устанавливаем заголовок для маршрута: ' . $routeName);
            $this->setTitleForRoute($request, $routeName);
        }
        
        // Обрабатываем запрос
        $response = $next($request);
        
        return $response;
    }
    
    /**
     * Устанавливает заголовок страницы на основе маршрута
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $routeName
     * @return void
     */
    protected function setTitleForRoute(Request $request, $routeName)
    {
        // Если маршрут найден в карте, устанавливаем заголовок страницы
        if (array_key_exists($routeName, $this->routeTitles)) {
            $pageTitle = $this->routeTitles[$routeName];
            Log::debug('Установлен заголовок из карты: ' . $pageTitle);
            
            // Проверяем есть ли у нас подзаголовок (например, для проектов)
            $pageSubtitle = $this->getPageSubtitle($request, $routeName);
            
            // Добавляем переменные к представлению
            view()->share('pageTitle', $pageTitle);
            
            if ($pageSubtitle) {
                Log::debug('Установлен подзаголовок: ' . $pageSubtitle);
                view()->share('pageSubtitle', $pageSubtitle);
            }
        } else {
            // Для маршрутов, которых нет в карте, пробуем сгенерировать заголовок автоматически
            $this->generateTitleFromRoute($routeName);
        }
    }
    
    /**
     * Получает подзаголовок страницы на основе маршрута и параметров запроса
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $routeName
     * @return string|null
     */
    protected function getPageSubtitle($request, $routeName)
    {
        // Проверяем наличие моделей в маршруте и устанавливаем соответствующие подзаголовки
        
        // Для проектов
        if (preg_match('/(projects\.show|projects\.edit)$/', $routeName) && $request->route('project')) {
            $project = $request->route('project');
            return $project->name ?? ('Проект #' . $project->id);
        }
        
        // Для пользователей
        if (preg_match('/(users\.show|users\.edit)$/', $routeName) && $request->route('user')) {
            $user = $request->route('user');
            return $user->name ?? ('Пользователь #' . $user->id);
        }
        
        // Для смет
        if (preg_match('/(estimates\.show|estimates\.edit)$/', $routeName) && $request->route('estimate')) {
            $estimate = $request->route('estimate');
            return 'Смета #' . $estimate->id;
        }
        
        return null;
    }
    
    /**
     * Генерирует заголовок страницы из имени маршрута, если его нет в карте
     * 
     * @param  string  $routeName
     * @return void
     */
    protected function generateTitleFromRoute($routeName)
    {
        // Разбиваем имя маршрута на части
        $parts = explode('.', $routeName);
        
        if (count($parts) > 0) {
            $lastPart = end($parts);
            
            // Преобразуем имя действия в заголовок
            switch ($lastPart) {
                case 'index':
                    $action = 'Список';
                    break;
                case 'create':
                    $action = 'Создание';
                    break;
                case 'edit':
                    $action = 'Редактирование';
                    break;
                case 'show':
                    $action = 'Просмотр';
                    break;
                default:
                    $action = ucfirst($lastPart);
            }
            
            // Получаем тип ресурса из предыдущей части
            $resource = '';
            if (count($parts) > 1) {
                $resourceKey = count($parts) - 2;
                $resourceMap = [
                    'projects' => 'проекта',
                    'users' => 'пользователя',
                    'estimates' => 'сметы',
                    'finances' => 'финансов',
                    'employees' => 'сотрудника',
                    'profile' => 'профиля',
                    'notifications' => 'уведомления'
                ];
                
                $resourceName = $parts[$resourceKey];
                $resource = isset($resourceMap[$resourceName]) ? $resourceMap[$resourceName] : $resourceName;
            }
            
            // Формируем заголовок
            $pageTitle = trim($action . ' ' . $resource);
            
            // Устанавливаем заголовок
            view()->share('pageTitle', $pageTitle);
        } else {
            // Если невозможно сгенерировать заголовок, используем имя приложения
            view()->share('pageTitle', 'Страница');
        }
    }
}
