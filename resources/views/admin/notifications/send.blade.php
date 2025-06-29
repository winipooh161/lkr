@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>{{ __('Массовая отправка уведомлений') }}</h2>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Вернуться к списку пользователей
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Получатели</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Всего пользователей
                            <span class="badge bg-primary rounded-pill">{{ $stats['total'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Администраторы
                            <span class="badge bg-danger rounded-pill">{{ $stats['by_role']['admins'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Партнеры
                            <span class="badge bg-success rounded-pill">{{ $stats['by_role']['partners'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Клиенты
                            <span class="badge bg-info rounded-pill">{{ $stats['by_role']['clients'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Сметчики
                            <span class="badge bg-secondary rounded-pill">{{ $stats['by_role']['estimators'] }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Отправка уведомления</h5>
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
                    
                    <div class="alert alert-info mb-4">
                        <h5><i class="fas fa-info-circle"></i> Информация о способах отправки</h5>
                        <ul class="mb-0">
                            <li><strong>Email:</strong> Отправка на адреса электронной почты пользователей</li>
                            <li><strong>SMS:</strong> Отправка SMS через сервис SMS.ru (на российские номера)</li>
                            <li><strong>Email + SMS:</strong> Отправка через оба канала одновременно</li>
                        </ul>
                    </div>

                    <form action="{{ route('admin.notifications.send') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="target_type" class="form-label">Кому отправить</label>
                            <select class="form-select @error('target_type') is-invalid @enderror" id="target_type" name="target_type">
                                <option value="all" {{ old('target_type') == 'all' ? 'selected' : '' }}>Всем пользователям</option>
                                <option value="role" {{ old('target_type') == 'role' ? 'selected' : '' }}>По роли</option>
                            </select>
                            @error('target_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3" id="roleSelectContainer" style="display: none;">
                            <label for="role" class="form-label">Выберите роль</label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role">
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Администраторы</option>
                                <option value="partner" {{ old('role') == 'partner' ? 'selected' : '' }}>Партнеры</option>
                                <option value="client" {{ old('role') == 'client' ? 'selected' : '' }}>Клиенты</option>
                                <option value="estimator" {{ old('role') == 'estimator' ? 'selected' : '' }}>Сметчики</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="channel" class="form-label">Способ отправки</label>
                            <select class="form-select @error('channel') is-invalid @enderror" id="channel" name="channel">
                                <option value="email" {{ old('channel') == 'email' ? 'selected' : '' }}>Email</option>
                                <option value="sms" {{ old('channel') == 'sms' ? 'selected' : '' }}>SMS</option>
                                <option value="both" {{ old('channel') == 'both' ? 'selected' : '' }}>Email + SMS</option>
                            </select>
                            @error('channel')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Тема</label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Сообщение</label>
                            <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Отправить уведомление</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const targetTypeSelect = document.getElementById('target_type');
        const roleSelectContainer = document.getElementById('roleSelectContainer');
        
        // Показываем/скрываем выбор роли при загрузке страницы
        if (targetTypeSelect.value === 'role') {
            roleSelectContainer.style.display = 'block';
        } else {
            roleSelectContainer.style.display = 'none';
        }
        
        // Показываем/скрываем выбор роли при изменении типа цели
        targetTypeSelect.addEventListener('change', function() {
            if (targetTypeSelect.value === 'role') {
                roleSelectContainer.style.display = 'block';
            } else {
                roleSelectContainer.style.display = 'none';
            }
        });
    });
</script>
@endpush
