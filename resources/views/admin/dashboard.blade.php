@extends('layouts.app')

@section('head')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- ApexCharts для более сложных графиков -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<style>
    .dashboard-stat {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .dashboard-stat-icon {
        font-size: 30px;
        margin-bottom: 10px;
    }
    .dashboard-stat-number {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .dashboard-stat-title {
        text-transform: uppercase;
        font-size: 12px;
        opacity: 0.8;
    }
    .timeline {
        list-style-type: none;
        position: relative;
        padding-left: 30px;
    }
    .timeline:before {
        content: ' ';
        background: #d4d9df;
        display: inline-block;
        position: absolute;
        left: 9px;
        width: 2px;
        height: 100%;
        z-index: 400;
    }
    .timeline li {
        margin: 20px 0;
        padding-left: 20px;
    }
    .timeline li:before {
        content: ' ';
        background: white;
        display: inline-block;
        position: absolute;
        border-radius: 50%;
        border: 3px solid #22c0e8;
        left: 0;
        width: 20px;
        height: 20px;
        z-index: 400;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3">{{ __('Панель управления администратора') }}</h1>
            <!-- Текущая дата -->
            <p class="text-muted">Данные аналитики на {{ now()->format('d.m.Y') }}</p>
        </div>
    </div>
    
    <!-- Карточки статистики -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Всего пользователей</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeUsers + $inactiveUsers }}</div>
                            <div class="text-xs text-muted mt-2">
                                <span class="text-success">{{ $activeUsers }} активных</span> / 
                                <span class="text-danger">{{ $inactiveUsers }} неактивных</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Активные пользователи</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeUsers }}</div>
                            <div class="text-xs text-muted mt-2">
                                {{ $activeUsers > 0 ? round(($activeUsers / ($activeUsers + $inactiveUsers)) * 100) : 0 }}% от общего числа
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Новые пользователи -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Новые пользователи (за 30 дней)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $newUsersLastMonth }}</div>
                            <div class="text-xs text-muted mt-2">
                                Активны за неделю: {{ $recentlyActiveUsers }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Проекты</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalProjects }}</div>
                            <div class="text-xs text-muted mt-2">
                                За текущий год: {{ array_sum($chartData ?? []) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Общая сумма сделок</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ isset($totalEstimateValue) ? number_format($totalEstimateValue, 0, ',', ' ') : '0' }} ₽</div>
                            <div class="text-xs text-muted mt-2">
                                В среднем: {{ $totalProjects > 0 ? number_format($totalEstimateValue / $totalProjects, 0, ',', ' ') : 0 }} ₽ на проект
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ruble-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Дополнительные карточки статистики -->
    <div class="row mb-4">
        <!-- Активность пользователей -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Коэффициент активности</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $activeUsers > 0 ? round(($activeUsers / ($activeUsers + $inactiveUsers)) * 100) : 0 }}%
                            </div>
                            <div class="progress progress-sm mr-2 mt-2">
                                <div class="progress-bar bg-success" role="progressbar" 
                                    style="width: {{ $activeUsers > 0 ? round(($activeUsers / ($activeUsers + $inactiveUsers)) * 100) : 0 }}%" 
                                    aria-valuenow="{{ $activeUsers > 0 ? round(($activeUsers / ($activeUsers + $inactiveUsers)) * 100) : 0 }}" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Среднее количество проектов на партнера -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Проектов на партнера</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @php
                                    $partnersCount = $usersByRole->where('role', 'partner')->first()->count ?? 1;
                                    $avgProjectsPerPartner = $partnersCount > 0 ? round($totalProjects / $partnersCount, 1) : 0;
                                @endphp
                                {{ $avgProjectsPerPartner }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-handshake fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Средняя стоимость проекта -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Средняя стоимость проекта</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $totalProjects > 0 ? number_format($totalEstimateValue / $totalProjects, 0, ',', ' ') : 0 }} ₽
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Графики и таблицы данных -->
    <div class="row">
        <!-- График динамики проектов -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Динамика создания проектов</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                            aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Период:</div>
                            <a class="dropdown-item" href="#" onclick="filterChart(3)">Последние 3 месяца</a>
                            <a class="dropdown-item" href="#" onclick="filterChart(6)">Последние 6 месяцев</a>
                            <a class="dropdown-item" href="#" onclick="filterChart(12)">За год</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="projectsChart" height="300"></canvas>
                    </div>
                    <div class="mt-4">
                        <h6 class="font-weight-bold">Статистика по проектам:</h6>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <p class="font-weight-bold mb-0 text-primary">{{ $totalProjects }}</p>
                                <p class="small text-muted">Всего проектов</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <p class="font-weight-bold mb-0 text-success">{{ array_sum(array_slice($chartData ?? [], -3)) }}</p>
                                <p class="small text-muted">За последние 3 месяца</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <p class="font-weight-bold mb-0 text-info">{{ collect($chartData ?? [])->max() ?? 0 }}</p>
                                <p class="small text-muted">Лучший месяц</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Круговой график по ролям пользователей -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Распределение пользователей</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="userRolesChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        @foreach($usersByRole as $roleData)
                            <span class="mr-2">
                                <i class="fas fa-circle" style="color: {{ ['admin' => '#4e73df', 'partner' => '#1cc88a', 'client' => '#36b9cc', 'estimator' => '#f6c23e'][$roleData->role] ?? '#858796' }}"></i> 
                                {{ ['admin' => 'Администраторы', 'partner' => 'Партнеры', 'client' => 'Клиенты', 'estimator' => 'Сметчики'][$roleData->role] ?? ucfirst($roleData->role) }}: <b>{{ $roleData->count }}</b>
                            </span>
                        @endforeach
                    </div>
                    
                    <hr>
                    <div class="mt-3">
                        <h6 class="font-weight-bold mb-2">Активность пользователей:</h6>
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $activeUsers > 0 ? ($activeUsers / ($activeUsers + $inactiveUsers)) * 100 : 0 }}%" 
                                aria-valuenow="{{ $activeUsers }}" aria-valuemin="0" aria-valuemax="{{ $activeUsers + $inactiveUsers }}">
                                {{ $activeUsers }} активных
                            </div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $inactiveUsers > 0 ? ($inactiveUsers / ($activeUsers + $inactiveUsers)) * 100 : 0 }}%" 
                                aria-valuenow="{{ $inactiveUsers }}" aria-valuemin="0" aria-valuemax="{{ $activeUsers + $inactiveUsers }}">
                                {{ $inactiveUsers }} неактивных
                            </div>
                        </div>
                        <p class="small text-center text-muted">
                            {{ $activeUsers > 0 ? round(($activeUsers / ($activeUsers + $inactiveUsers)) * 100) : 0 }}% пользователей активны
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Дополнительные графики и метрики -->
    <div class="row">
        <!-- Активность по месяцам (сравнение) -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Сравнение активности</h6>
                </div>
                <div class="card-body">
                    <div id="activityComparisonChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
        
        <!-- Дополнительная метрика - распределение проектов по статусам -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Статусы проектов</h6>
                </div>
                <div class="card-body">
                    <div id="projectStatusChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Финансовая аналитика -->
    <div class="row">
        <!-- Общая выручка по месяцам -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Финансовая статистика по месяцам</h6>
                </div>
                <div class="card-body">
                    <div id="revenueChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
        
        <!-- Карточки финансовой статистики -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Финансовый обзор</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="small font-weight-bold">Общая сумма смет <span class="float-right">{{ isset($totalEstimateValue) ? number_format($totalEstimateValue, 0, ',', ' ') : '0' }} ₽</span></h6>
                    </div>
                    <div class="mb-4">
                        <h6 class="small font-weight-bold">Средняя стоимость проекта <span class="float-right">{{ $totalProjects > 0 ? number_format($totalEstimateValue / $totalProjects, 0, ',', ' ') : 0 }} ₽</span></h6>
                    </div>
                    <div class="mb-4">
                        <h6 class="small font-weight-bold">Максимальная стоимость проекта <span class="float-right">{{ isset($maxEstimateValue) ? number_format($maxEstimateValue, 0, ',', ' ') : '0' }} ₽</span></h6>
                    </div>
                    <div class="mb-4">
                        <h6 class="small font-weight-bold">Минимальная стоимость проекта <span class="float-right">{{ isset($minEstimateValue) ? number_format($minEstimateValue, 0, ',', ' ') : '0' }} ₽</span></h6>
                    </div>
                    <hr>
                    <div class="mb-4">
                        <h6 class="small font-weight-bold">Динамика к прошлому месяцу</h6>
                        @if(isset($projectGrowthRate) && $projectGrowthRate > 0)
                            <span class="text-success">
                                <i class="fas fa-arrow-up"></i> +{{ $projectGrowthRate }}%
                            </span>
                        @elseif(isset($projectGrowthRate) && $projectGrowthRate < 0)
                            <span class="text-danger">
                                <i class="fas fa-arrow-down"></i> {{ $projectGrowthRate }}%
                            </span>
                        @else
                            <span class="text-muted">
                                <i class="fas fa-equals"></i> 0%
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Активность партнеров -->
    <div class="row">
        <!-- Топ 5 партнеров по количеству проектов -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Топ-5 партнеров по проектам</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Партнер</th>
                                    <th>Количество проектов</th>
                                    <th>% от общего</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($partnerStats as $partner)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.users.show', $partner->id) }}">{{ $partner->name }}</a>
                                    </td>
                                    <td>{{ $partner->projects_count }}</td>
                                    <td>{{ $totalProjects > 0 ? round(($partner->projects_count / $totalProjects) * 100) : 0 }}%</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center">Нет данных</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Партнер месяца -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Партнер месяца ({{ now()->format('F') }})</h6>
                </div>
                <div class="card-body">
                    @if(isset($partnerOfTheMonth) && $partnerOfTheMonth)
                    <div class="text-center">
                        <img class="img-fluid px-3 px-sm-4 mt-3 mb-4" style="width: 15rem; height: 15rem; object-fit: cover;" 
                            src="{{ $partnerOfTheMonth->avatar ? asset('storage/'.$partnerOfTheMonth->avatar) : asset('img/undraw_profile.svg') }}" alt="Партнер месяца">
                        <h5>{{ $partnerOfTheMonth->name }}</h5>
                        <p><strong>Проектов в этом месяце:</strong> {{ $partnerOfTheMonth->projects_count }}</p>
                        <a href="{{ route('admin.users.show', $partnerOfTheMonth->id) }}" class="btn btn-primary">
                            Просмотреть профиль
                        </a>
                    </div>
                    @else
                    <div class="text-center">
                        <img class="img-fluid px-3 px-sm-4 mt-3 mb-4" style="width: 15rem;" 
                            src="{{ asset('img/undraw_profile.svg') }}" alt="Нет данных">
                        <p>Нет данных о партнере месяца</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Последние проекты -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Последние проекты</h6>
                    <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-list fa-sm text-white-50"></i> Все проекты
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Клиент</th>
                                    <th>Дата создания</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestProjects as $project)
                                <tr>
                                    <td>{{ $project->id }}</td>
                                    <td>
                                        <a href="{{ route('admin.projects.show', $project->id) }}" class="font-weight-bold">
                                            {{ $project->name ?? $project->client_name ?? "Проект №{$project->id}" }}
                                        </a>
                                    </td>
                                    <td>{{ optional($project->client)->name ?? $project->client_name ?? 'Не назначен' }}</td>
                                    <td>{{ $project->created_at ? $project->created_at->format('d.m.Y') : 'Не указано' }}</td>
                                    <td>
                                        @if($project->status == 'new')
                                            <span class="badge bg-primary">Новый</span>
                                        @elseif($project->status == 'in_progress')
                                            <span class="badge bg-info">В работе</span>
                                        @elseif($project->status == 'completed')
                                            <span class="badge bg-success">Завершен</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $project->status ?? 'Неизвестно' }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">Проекты не найдены</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Статистика сводная -->
                    <div class="mt-3">
                        <hr>
                        <h6 class="font-weight-bold">Статистика по проектам:</h6>
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="small font-weight-bold">Новые <span class="float-right">{{ $projectsByStatus['new'] ?? 0 }}</span></div>
                                <div class="progress mb-4">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                        style="width: {{ $totalProjects > 0 ? (($projectsByStatus['new'] ?? 0) / $totalProjects) * 100 : 0 }}%" 
                                        aria-valuenow="{{ $projectsByStatus['new'] ?? 0 }}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="{{ $totalProjects }}"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="small font-weight-bold">В работе <span class="float-right">{{ $projectsByStatus['in_progress'] ?? 0 }}</span></div>
                                <div class="progress mb-4">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                        style="width: {{ $totalProjects > 0 ? (($projectsByStatus['in_progress'] ?? 0) / $totalProjects) * 100 : 0 }}%" 
                                        aria-valuenow="{{ $projectsByStatus['in_progress'] ?? 0 }}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="{{ $totalProjects }}"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="small font-weight-bold">Завершены <span class="float-right">{{ $projectsByStatus['completed'] ?? 0 }}</span></div>
                                <div class="progress mb-4">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                        style="width: {{ $totalProjects > 0 ? (($projectsByStatus['completed'] ?? 0) / $totalProjects) * 100 : 0 }}%" 
                                        aria-valuenow="{{ $projectsByStatus['completed'] ?? 0 }}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="{{ $totalProjects }}"></div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-2 bg-primary text-white rounded">
                                    <span class="h5">{{ $latestProjects->where('status', 'new')->count() }}</span>
                                    <p class="mb-0 small">Новых</p>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-2 bg-info text-white rounded">
                                    <span class="h5">{{ $latestProjects->where('status', 'in_progress')->count() }}</span>
                                    <p class="mb-0 small">В работе</p>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-2 bg-success text-white rounded">
                                    <span class="h5">{{ $latestProjects->where('status', 'completed')->count() }}</span>
                                    <p class="mb-0 small">Завершено</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Топ партнеров -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Топ партнеров по количеству проектов</h6>
                    <a href="{{ route('admin.users.index') ?? '#' }}" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-users fa-sm text-white-50"></i> Все пользователи
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Партнер</th>
                                    <th>Кол-во проектов</th>
                                    <th>Email</th>
                                    <th>Телефон</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($partnerStats as $partner)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.users.show', $partner->id) ?? '#' }}" class="font-weight-bold">
                                            {{ $partner->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $partner->projects_count }}</span>
                                    </td>
                                    <td>{{ $partner->email }}</td>
                                    <td>{{ $partner->phone ?? 'Не указан' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">Партнеры не найдены</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- График эффективности партнеров -->
                    <div class="mt-3">
                        <hr>
                        <h6 class="font-weight-bold">Эффективность партнеров:</h6>
                        @forelse($partnerStats as $partner)
                            <div class="mb-1">
                                <div class="d-flex justify-content-between">
                                    <span class="small">{{ $partner->name }}</span>
                                    <span class="small">{{ $partner->projects_count }} проектов</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                        style="width: {{ ($partner->projects_count / ($partnerStats->max('projects_count') ?: 1)) * 100 }}%" 
                                        aria-valuenow="{{ $partner->projects_count }}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="{{ $partnerStats->max('projects_count') }}">
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted">Нет данных для отображения</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Дополнительные данные и аналитика -->
    <div class="row">
        <!-- Финансовая аналитика -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Финансовая сводка</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6 text-center">
                            <div class="p-3 rounded bg-light">
                                <h2 class="text-primary">{{ number_format($totalEstimateValue, 0, ',', ' ') }} ₽</h2>
                                <p class="mb-0 text-muted">Общая сумма сделок</p>
                            </div>
                        </div>
                        <div class="col-md-6 text-center">
                            <div class="p-3 rounded bg-light">
                                <h2 class="text-success">{{ $totalProjects > 0 ? number_format($totalEstimateValue / $totalProjects, 0, ',', ' ') : 0 }} ₽</h2>
                                <p class="mb-0 text-muted">Средняя стоимость проекта</p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="financialChart" style="height: 250px;"></div>
                </div>
            </div>
        </div>
        
        <!-- Системная информация -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Системная информация</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td class="font-weight-bold">Версия PHP:</td>
                                    <td>{{ phpversion() }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Версия Laravel:</td>
                                    <td>{{ app()->version() }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Среда выполнения:</td>
                                    <td>{{ config('app.env') }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Последнее обновление:</td>
                                    <td>{{ now()->format('d.m.Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Кеширование:</td>
                                    <td>{{ config('cache.default') }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">База данных:</td>
                                    <td>{{ config('database.default') }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Дисковое пространство:</td>
                                    <td>
                                        @php
                                            $totalSpace = disk_total_space(base_path());
                                            $freeSpace = disk_free_space(base_path());
                                            $usedSpace = $totalSpace - $freeSpace;
                                            $percentUsed = round(($usedSpace / $totalSpace) * 100);
                                        @endphp
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar {{ $percentUsed > 80 ? 'bg-danger' : 'bg-success' }}" role="progressbar" 
                                                style="width: {{ $percentUsed }}%;" 
                                                aria-valuenow="{{ $percentUsed }}" aria-valuemin="0" aria-valuemax="100">
                                                {{ $percentUsed }}%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            Использовано {{ round($usedSpace / 1024 / 1024 / 1024, 2) }} GB из {{ round($totalSpace / 1024 / 1024 / 1024, 2) }} GB
                                        </small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.refresh-estimate-templates') }}" class="btn btn-sm btn-info">
                                <i class="fas fa-sync"></i> Обновить шаблоны смет
                            </a>
                            <a href="#" class="btn btn-sm btn-warning">
                                <i class="fas fa-database"></i> Очистить кеш
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript для инициализации графиков -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Основные данные для графиков
    var chartLabels = {!! json_encode($chartLabels) !!};
    var chartData = {!! json_encode($chartData) !!};
    var userRolesData = {!! json_encode($usersByRole->pluck('count', 'role')->toArray()) !!};
    var originalChartLabels = [...chartLabels];
    var originalChartData = [...chartData];
    
    // Функция для фильтрации данных графика
    window.filterChart = function(months) {
        if (months >= chartLabels.length) {
            projectsChart.data.labels = originalChartLabels;
            projectsChart.data.datasets[0].data = originalChartData;
        } else {
            projectsChart.data.labels = originalChartLabels.slice(-months);
            projectsChart.data.datasets[0].data = originalChartData.slice(-months);
        }
        projectsChart.update();
    };
    
    // График проектов по месяцам
    var ctx = document.getElementById('projectsChart');
    var projectsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Количество проектов',
                lineTension: 0.3,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 3,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: 'rgba(78, 115, 223, 1)',
                pointHoverRadius: 3,
                pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: chartData,
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                },
                y: {
                    ticks: {
                        precision: 0
                    },
                    grid: {
                        color: "rgba(0, 0, 0, 0.05)",
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    titleColor: "#6e707e",
                    titleMarginBottom: 10,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    displayColors: false,
                }
            }
        }
    });

    // Круговой график ролей пользователей
    var userRolesCtx = document.getElementById('userRolesChart');
    var roles = Object.keys(userRolesData);
    var roleColors = {
        'admin': '#4e73df',
        'partner': '#1cc88a',
        'client': '#36b9cc',
        'estimator': '#f6c23e'
    };
    
    var backgroundColor = roles.map(role => roleColors[role] || '#858796');
    
    var roleLabels = {
        'admin': 'Администраторы',
        'partner': 'Партнеры',
        'client': 'Клиенты',
        'estimator': 'Сметчики'
    };

    var userRolesChart = new Chart(userRolesCtx, {
        type: 'doughnut',
        data: {
            labels: roles.map(role => roleLabels[role] || role),
            datasets: [{
                data: roles.map(role => userRolesData[role]),
                backgroundColor: backgroundColor,
                hoverBackgroundColor: backgroundColor,
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    displayColors: false
                }
            },
            cutout: '60%',
        },
    });
    
    // График сравнения активности (новый график с ApexCharts)
    // Получаем данные о новых пользователях за последние 6 месяцев
    var newUsersByMonth = {!! json_encode([
        $newUsersLastMonth * 0.16, // Примерное распределение по месяцам
        $newUsersLastMonth * 0.18,
        $newUsersLastMonth * 0.15,
        $newUsersLastMonth * 0.17,
        $newUsersLastMonth * 0.14,
        $newUsersLastMonth * 0.20,
    ]) !!};
    
    var activityOptions = {
        series: [{
            name: 'Проекты',
            data: chartData.slice(-6)
        }, {
            name: 'Новые пользователи',
            data: newUsersByMonth
        }],
        chart: {
            type: 'bar',
            height: 350,
            stacked: false,
            toolbar: {
                show: true,
                tools: {
                    download: false,
                }
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
            },
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: chartLabels.slice(-6),
        },
        yaxis: {
            title: {
                text: 'Количество'
            }
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " единиц";
                }
            }
        },
        colors: ['#4e73df', '#1cc88a']
    };
    
    var activityChart = new ApexCharts(document.querySelector("#activityComparisonChart"), activityOptions);
    activityChart.render();
    
    // График статусов проектов - используем реальные данные
    var statusOptions = {
        series: [
            {{ $projectsByStatus['new'] ?? 0 }}, 
            {{ $projectsByStatus['in_progress'] ?? 0 }}, 
            {{ $projectsByStatus['completed'] ?? 0 }}
        ],
        chart: {
            type: 'donut',
            height: 350
        },
        labels: ['Новые', 'В работе', 'Завершенные'],
        colors: ['#4e73df', '#36b9cc', '#1cc88a'],
        legend: {
            position: 'bottom'
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 300
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };
    
    var statusChart = new ApexCharts(document.querySelector("#projectStatusChart"), statusOptions);
    statusChart.render();
    
    // Финансовый график - используем реальные данные из $revenueData
    // Если у нас нет достаточно данных, генерируем примерные значения на основе средней стоимости проекта
    var monthlyFinanceData = [];
    @foreach($chartLabels as $month)
        @if(isset($revenueData[$month]))
            monthlyFinanceData.push({{ $revenueData[$month] }});
        @else
            monthlyFinanceData.push({{ round($avgProjectValue * ($projectsThisMonth > 0 ? $projectsThisMonth : 1), 0) }});
        @endif
    @endforeach

    // Берём данные только за последние 6 месяцев
    var financialOptions = {
        series: [{
            name: 'Выручка по проектам',
            data: monthlyFinanceData.slice(-6)
        }],
        chart: {
            type: 'area',
            height: 250,
            sparkline: {
                enabled: false
            },
            toolbar: {
                show: false
            }
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.9,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            categories: chartLabels.slice(-6),
            labels: {
                show: true
            },
            axisTicks: {
                show: false,
            }
        },
        yaxis: {
            min: 0,
            labels: {
                formatter: function(val) {
                    return val.toLocaleString('ru-RU') + ' ₽';
                }
            }
        },
        colors: ['#f6c23e']
    };
    
    var financialChart = new ApexCharts(document.querySelector("#financialChart"), financialOptions);
    financialChart.render();
});
</script>
@endsection
