@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Просмотр пользователя') }}</span>
                        <div>
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary me-2">
                                <i class="fas fa-edit"></i> Редактировать
                            </a>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Назад к списку
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" class="img-fluid rounded-circle mb-2" style="max-width: 150px; max-height: 150px;">
                        <h3>{{ $user->name }}</h3>
                        <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : ($user->role == 'partner' ? 'primary' : ($user->role == 'estimator' ? 'info' : 'secondary')) }} mb-3">
                            {{ ucfirst($user->role) }}
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th style="width: 30%">ID</th>
                                    <td>{{ $user->id }}</td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <th>Телефон</th>
                                    <td>{{ $user->phone ?? 'Не указан' }}</td>
                                </tr>
                                @if($user->isEstimator() && $user->partner_id)
                                    <tr>
                                        <th>Прикреплен к партнеру</th>
                                        <td>
                                            @if($partner = \App\Models\User::find($user->partner_id))
                                                <a href="{{ route('admin.users.show', $partner) }}">{{ $partner->name }}</a>
                                            @else
                                                Партнер не найден (ID: {{ $user->partner_id }})
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Дата регистрации</th>
                                    <td>{{ $user->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Последнее обновление</th>
                                    <td>{{ $user->updated_at->format('d.m.Y H:i') }}</td>
                                </tr>
                                @if(isset($user->email_verified_at))
                                <tr>
                                    <th>Email подтвержден</th>
                                    <td>
                                        @if($user->email_verified_at)
                                            <span class="text-success">Да, {{ $user->email_verified_at->format('d.m.Y H:i') }}</span>
                                        @else
                                            <span class="text-danger">Нет</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    @if(isset($relatedData) && count($relatedData) > 0)
                    <div class="mt-4">
                        <h5>Связанные данные</h5>
                        
                        @if($user->isPartner())
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong>Проекты ({{ $relatedData['projects_count'] ?? 0 }})</strong>
                                </div>
                                <div class="card-body">
                                    @if(isset($relatedData['recent_projects']) && count($relatedData['recent_projects']) > 0)
                                        <p>Последние проекты:</p>
                                        <div class="list-group">
                                            @foreach($relatedData['recent_projects'] as $project)
                                                <a href="/partner/projects/{{ $project->id }}" class="list-group-item list-group-item-action">
                                                    {{ $project->name }} ({{ $project->address }})
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <p>Нет проектов</p>
                                    @endif
                                </div>
                            </div>
                        @elseif($user->isClient())
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong>Проекты клиента</strong>
                                </div>
                                <div class="card-body">
                                    @if(isset($relatedData['projects']) && count($relatedData['projects']) > 0)
                                        <div class="list-group">
                                            @foreach($relatedData['projects'] as $project)
                                                <a href="/partner/projects/{{ $project->id }}" class="list-group-item list-group-item-action">
                                                    {{ $project->name }} ({{ $project->address }})
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <p>Нет проектов</p>
                                    @endif
                                </div>
                            </div>
                        @elseif($user->isEstimator())
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong>Проекты сметчика</strong>
                                </div>
                                <div class="card-body">
                                    @if(isset($relatedData['projects']) && count($relatedData['projects']) > 0)
                                        <div class="list-group">
                                            @foreach($relatedData['projects'] as $project)
                                                <a href="/partner/projects/{{ $project->id }}" class="list-group-item list-group-item-action">
                                                    {{ $project->name }} ({{ $project->address }})
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <p>Нет проектов</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    @endif

                    <div class="d-flex justify-content-center mt-3">
                        <div class="btn-group" role="group">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Редактировать
                            </a>
                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="d-inline" onsubmit="return confirm('Вы уверены, что хотите сбросить пароль этому пользователю?');">
                                @csrf
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Сбросить пароль
                                </button>
                            </form>
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash me-1"></i> {{ __('Удалить') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
