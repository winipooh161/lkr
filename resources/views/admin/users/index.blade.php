@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>{{ __('Управление пользователями') }}</h2>
                <div>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-success mb-2">
                        <i class="fas fa-plus"></i> Добавить пользователя
                    </a>
                    <a href="{{ route('admin.notifications.form') }}" class="btn btn-warning mb-2 ms-2">
                        <i class="fas fa-envelope"></i> Отправить уведомления
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Карточки со статистикой -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Всего пользователей</h5>
                    <p class="card-text display-4">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Администраторы</h5>
                    <p class="card-text display-4">{{ $stats['admins'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Партнеры</h5>
                    <p class="card-text display-4">{{ $stats['partners'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Клиенты</h5>
                    <p class="card-text display-4">{{ $stats['clients'] }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <span class="h5 m-0">{{ __('Список пользователей') }}</span>
                        <div class="d-flex align-items-center">
                            <!-- Фильтры -->
                            <div class="dropdown me-2">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-filter"></i> Фильтры
                                </button>
                                <div class="dropdown-menu p-3" style="min-width: 250px;">
                                    <form method="GET" action="{{ route('admin.users.index') }}">
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Роль</label>
                                            <select class="form-select" name="role" id="role">
                                                <option value="all" {{ request('role') == 'all' ? 'selected' : '' }}>Все</option>
                                                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Администраторы</option>
                                                <option value="partner" {{ request('role') == 'partner' ? 'selected' : '' }}>Партнеры</option>
                                                <option value="client" {{ request('role') == 'client' ? 'selected' : '' }}>Клиенты</option>
                                                <option value="estimator" {{ request('role') == 'estimator' ? 'selected' : '' }}>Сметчики</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="sort" class="form-label">Сортировка</label>
                                            <select class="form-select" name="sort" id="sort">
                                                <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Дата регистрации</option>
                                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Имя</option>
                                                <option value="email" {{ request('sort') == 'email' ? 'selected' : '' }}>Email</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="direction" class="form-label">Направление</label>
                                            <select class="form-select" name="direction" id="direction">
                                                <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>По убыванию</option>
                                                <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>По возрастанию</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Применить</button>
                                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Сбросить</a>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Поиск -->
                            <form method="GET" action="{{ route('admin.users.search') }}" class="d-flex me-2">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="q" placeholder="Поиск..." value="{{ request('q') ?? '' }}">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Экспорт/Импорт -->
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                                    <i class="fas fa-download"></i> Экспорт/Импорт
                                </button>
                                <div class="dropdown-menu">
                                    <h6 class="dropdown-header">Экспорт</h6>
                                    <a class="dropdown-item" href="{{ route('admin.users.export', ['format' => 'xlsx']) }}">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </a>
                                    <a class="dropdown-item" href="{{ route('admin.users.export', ['format' => 'csv']) }}">
                                        <i class="fas fa-file-csv"></i> CSV
                                    </a>
                                    <a class="dropdown-item" href="{{ route('admin.users.export', ['format' => 'pdf']) }}">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <h6 class="dropdown-header">Импорт</h6>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#importModal">
                                        <i class="fas fa-file-import"></i> Импорт из файла
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    @if (session('new_password'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>Новый пароль:</strong> {{ session('new_password') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form id="bulkActionForm" action="{{ route('admin.users.bulk-action') }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="40">
                                            <div class="form-check">
                                                <input class="form-check-input select-all" type="checkbox" value="" id="selectAll">
                                            </div>
                                        </th>
                                        <th>ID</th>
                                        <th>Аватар</th>
                                        <th>Имя</th>
                                        <th>Email/Телефон</th>
                                        <th>Роль</th>
                                        <th>Дата регистрации</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input user-checkbox" type="checkbox" name="user_ids[]" value="{{ $user->id }}">
                                            </div>
                                        </td>
                                        <td>{{ $user->id }}</td>
                                        <td>
                                            <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" class="rounded-circle" width="40" height="40">
                                        </td>
                                        <td>{{ $user->name }}</td>
                                        <td>
                                            <div>{{ $user->email }}</div>
                                            <small class="text-muted">{{ $user->phone }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : ($user->role == 'partner' ? 'primary' : ($user->role == 'estimator' ? 'info' : 'secondary')) }}">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                        </td>
                                        <td>{{ $user->created_at->format('d.m.Y H:i') }}</td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Действия
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a href="{{ route('admin.users.show', $user) }}" class="dropdown-item">
                                                            <i class="fas fa-eye"></i> Просмотр
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('admin.users.edit', $user) }}" class="dropdown-item">
                                                            <i class="fas fa-edit"></i> Редактировать
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#resetPasswordModal{{ $user->id }}">
                                                            <i class="fas fa-key"></i> Сбросить пароль
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changeRoleModal{{ $user->id }}">
                                                            <i class="fas fa-user-tag"></i> Изменить роль
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#sendNotificationModal{{ $user->id }}">
                                                            <i class="fas fa-bell"></i> Отправить уведомление
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal{{ $user->id }}">
                                                            <i class="fas fa-trash"></i> Удалить
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Пользователи не найдены</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Групповые действия -->
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="d-flex align-items-center">
                                <span class="me-3">С выбранными:</span>
                                <select name="action" class="form-select me-2" style="width: auto;">
                                    <option value="" selected>Выберите действие</option>
                                    <option value="delete">Удалить</option>
                                    <option value="activate">Активировать</option>
                                    <option value="deactivate">Деактивировать</option>
                                    <option value="change_role">Изменить роль</option>
                                </select>
                                <div id="roleSelectContainer" style="display: none;" class="me-2">
                                    <select name="role" class="form-select">
                                        <option value="admin">Администратор</option>
                                        <option value="partner">Партнер</option>
                                        <option value="client">Клиент</option>
                                        <option value="estimator">Сметчик</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary" id="applyBulkAction" disabled>Применить</button>
                            </div>
                        </div>
                    </form>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальные окна пользователей -->
@foreach($users as $user)
    <!-- Модальное окно сброса пароля -->
    <div class="modal fade" id="resetPasswordModal{{ $user->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Сбросить пароль</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите сбросить пароль для пользователя <strong>{{ $user->name }}</strong>?</p>
                    <p>Новый случайный пароль будет сгенерирован автоматически.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <form action="{{ route('admin.users.reset-password', $user) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">Сбросить пароль</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно изменения роли -->
    <div class="modal fade" id="changeRoleModal{{ $user->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Изменить роль</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.users.change-role', $user) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="role{{ $user->id }}" class="form-label">Выберите новую роль</label>
                            <select class="form-select" id="role{{ $user->id }}" name="role" required>
                                <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Администратор</option>
                                <option value="partner" {{ $user->role === 'partner' ? 'selected' : '' }}>Партнер</option>
                                <option value="client" {{ $user->role === 'client' ? 'selected' : '' }}>Клиент</option>
                                <option value="estimator" {{ $user->role === 'estimator' ? 'selected' : '' }}>Сметчик</option>
                            </select>
                        </div>
                        
                        <div id="partnerSelectContainer{{ $user->id }}" class="mb-3" style="{{ $user->role === 'estimator' ? '' : 'display: none;' }}">
                            <label for="partner_id{{ $user->id }}" class="form-label">Выберите партнера для сметчика</label>
                            <select class="form-select" id="partner_id{{ $user->id }}" name="partner_id">
                                <option value="">-- Выберите партнера --</option>
                                @foreach(App\Models\User::where('role', 'partner')->orderBy('name')->get() as $partner)
                                    <option value="{{ $partner->id }}" {{ ($user->partner_id == $partner->id) ? 'selected' : '' }}>
                                        {{ $partner->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Изменить роль</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно отправки уведомления -->
    <div class="modal fade" id="sendNotificationModal{{ $user->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Отправить уведомление</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.users.send-notification', $user) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="subject{{ $user->id }}" class="form-label">Тема</label>
                            <input type="text" class="form-control" id="subject{{ $user->id }}" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message{{ $user->id }}" class="form-label">Сообщение</label>
                            <textarea class="form-control" id="message{{ $user->id }}" name="message" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="channel{{ $user->id }}" class="form-label">Способ отправки</label>
                            <select class="form-select" id="channel{{ $user->id }}" name="channel">
                                <option value="email">Email</option>
                                <option value="sms">SMS</option>
                                <option value="both">Email + SMS</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Отправить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно удаления пользователя -->
    <div class="modal fade" id="deleteUserModal{{ $user->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Удаление пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите удалить пользователя <strong>{{ $user->name }}</strong>?</p>
                    <p class="text-danger">Это действие невозможно отменить!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- Модальное окно импорта -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Импорт пользователей</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    @if(session('import_errors'))
                        <div class="alert alert-danger">
                            <h6>Ошибки при импорте:</h6>
                            <ul class="list-unstyled">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <div class="mb-3">
                        <label for="file" class="form-label">Выберите файл (CSV или XLSX)</label>
                        <input class="form-control" type="file" id="file" name="file" accept=".csv,.xlsx" required>
                        <div class="form-text">
                            Файл должен содержать следующие столбцы: name, email, phone, role
                        </div>
                    </div>
                    <div class="mb-3">
                        <a href="{{ route('admin.users.export', ['format' => 'csv', 'template' => true]) }}" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="fas fa-download"></i> Скачать шаблон CSV
                        </a>
                        <a href="{{ route('admin.users.export', ['format' => 'xlsx', 'template' => true]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download"></i> Скачать шаблон XLSX
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Импортировать</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/admin-users.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin-users-actions.js') }}?v={{ time() }}"></script>
@endpush
