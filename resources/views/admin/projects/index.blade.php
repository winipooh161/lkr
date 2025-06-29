@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Управление проектами</h1>
        <div>
            <a href="#" class="btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#filterModal">
                <i class="fas fa-filter fa-sm text-white-50"></i> Фильтры
            </a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Вернуться на панель
            </a>
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
                                Всего проектов</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
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
                                Новые проекты</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['by_status']['new'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
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
                                В работе</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['by_status']['in_progress'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cogs fa-2x text-gray-300"></i>
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
                                Завершенные</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['by_status']['completed'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Таблица проектов -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Список проектов</h6>
            <div>
                <form action="{{ route('admin.projects.index') }}" method="GET" class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control bg-light border-0 small" placeholder="Поиск..." 
                            value="{{ request('search') }}" aria-label="Search" aria-describedby="basic-addon2">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Клиент</th>
                            <th>Партнер</th>
                            <th>Статус</th>
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $project)
                            <tr>
                                <td>{{ $project->id }}</td>
                                <td><a href="{{ route('admin.projects.show', $project) }}">{{ $project->name }}</a></td>
                                <td>
                                    @if($project->client)
                                        <a href="{{ route('admin.users.show', $project->client) }}">
                                            {{ $project->client->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">Не указан</span>
                                    @endif
                                </td>
                                <td>
                                    @if($project->partner)
                                        <a href="{{ route('admin.users.show', $project->partner) }}">
                                            {{ $project->partner->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">Не указан</span>
                                    @endif
                                </td>
                                <td>
                                    @switch($project->status)
                                        @case('new')
                                            <span class="badge badge-primary">Новый</span>
                                            @break
                                        @case('in_progress')
                                            <span class="badge badge-info">В работе</span>
                                            @break
                                        @case('completed')
                                            <span class="badge badge-success">Завершен</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ $project->status }}</span>
                                    @endswitch
                                </td>
                                <td>{{ $project->created_at->format('d.m.Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Проекты не найдены</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            <div class="mt-3">
                {{ $projects->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно фильтрации -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.projects.index') }}" method="GET">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Фильтр проектов</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="status">Статус проекта</label>
                        <select name="status" id="status" class="form-control">
                            <option value="all">Все статусы</option>
                            <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>Новые</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>В работе</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Завершенные</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="partner_id">Партнер</label>
                        <select name="partner_id" id="partner_id" class="form-control">
                            <option value="all">Все партнеры</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ request('partner_id') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="client_id">Клиент</label>
                        <select name="client_id" id="client_id" class="form-control">
                            <option value="all">Все клиенты</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Применить</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
