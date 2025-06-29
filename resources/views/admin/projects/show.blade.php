@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Информация о проекте</h1>
        <div>
            <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Вернуться к списку
            </a>
        </div>
    </div>
    
    <!-- Информация о проекте -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Основная информация</h6>
                    <span class="badge badge-{{ $project->status == 'new' ? 'primary' : ($project->status == 'in_progress' ? 'info' : 'success') }}">
                        {{ $project->status == 'new' ? 'Новый' : ($project->status == 'in_progress' ? 'В работе' : 'Завершен') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5>{{ $project->name }}</h5>
                        <p class="text-muted">ID: {{ $project->id }}</p>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Дата создания:</strong> {{ $project->created_at->format('d.m.Y') }}</p>
                            <p><strong>Последнее обновление:</strong> {{ $project->updated_at->format('d.m.Y') }}</p>
                            <p><strong>Адрес:</strong> {{ $project->address ?? 'Не указан' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Клиент:</strong> 
                                @if($project->client)
                                    <a href="{{ route('admin.users.show', $project->client) }}">{{ $project->client->name }}</a>
                                @else
                                    Не указан
                                @endif
                            </p>
                            <p><strong>Партнер:</strong> 
                                @if($project->partner)
                                    <a href="{{ route('admin.users.show', $project->partner) }}">{{ $project->partner->name }}</a>
                                @else
                                    Не указан
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    @if($project->description)
                    <div class="mb-4">
                        <h6 class="font-weight-bold">Описание проекта:</h6>
                        <p>{{ $project->description }}</p>
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">История статусов:</h6>
                        <ul class="timeline">
                            <li>
                                <span class="badge badge-primary">Создан</span>
                                <span class="small text-gray-600">{{ $project->created_at->format('d.m.Y H:i') }}</span>
                            </li>
                            @if($project->status != 'new')
                            <li>
                                <span class="badge badge-info">В работе</span>
                                <span class="small text-gray-600">{{ $project->updated_at->format('d.m.Y H:i') }}</span>
                            </li>
                            @endif
                            @if($project->status == 'completed')
                            <li>
                                <span class="badge badge-success">Завершен</span>
                                <span class="small text-gray-600">{{ $project->updated_at->format('d.m.Y H:i') }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Файлы проекта -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Файлы проекта</h6>
                </div>
                <div class="card-body">
                    @if($project->files && $project->files->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Тип</th>
                                        <th>Размер</th>
                                        <th>Дата загрузки</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($project->files as $file)
                                        <tr>
                                            <td>{{ $file->original_name }}</td>
                                            <td>{{ $file->mime_type }}</td>
                                            <td>{{ number_format($file->size / 1024, 2) }} KB</td>
                                            <td>{{ $file->created_at->format('d.m.Y') }}</td>
                                            <td>
                                                <a href="{{ route('client.project-files.download', $file) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center">Файлы отсутствуют</p>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <!-- Сметы проекта -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Сметы</h6>
                </div>
                <div class="card-body">
                    @if($project->estimates && $project->estimates->count() > 0)
                        <div class="list-group">
                            @foreach($project->estimates as $estimate)
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $estimate->name ?? 'Смета #' . $estimate->id }}</h6>
                                        <p class="mb-1">{{ number_format($estimate->total_amount, 2) }} ₽</p>
                                        <small class="text-muted">Создана: {{ $estimate->created_at->format('d.m.Y') }}</small>
                                    </div>
                                    <a href="{{ route('client.estimates.download', $estimate) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center">Сметы отсутствуют</p>
                    @endif
                </div>
            </div>
            
            <!-- Контакты -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Контактная информация</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Клиент:</h6>
                        @if($project->client)
                            <p><i class="fas fa-user mr-2"></i> {{ $project->client->name }}</p>
                            <p><i class="fas fa-phone mr-2"></i> {{ $project->client->phone }}</p>
                            @if($project->client->email)
                                <p><i class="fas fa-envelope mr-2"></i> {{ $project->client->email }}</p>
                            @endif
                        @else
                            <p>Информация о клиенте отсутствует</p>
                        @endif
                    </div>
                    
                    <div>
                        <h6 class="font-weight-bold">Партнер:</h6>
                        @if($project->partner)
                            <p><i class="fas fa-user mr-2"></i> {{ $project->partner->name }}</p>
                            <p><i class="fas fa-phone mr-2"></i> {{ $project->partner->phone }}</p>
                            @if($project->partner->email)
                                <p><i class="fas fa-envelope mr-2"></i> {{ $project->partner->email }}</p>
                            @endif
                        @else
                            <p>Информация о партнере отсутствует</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
