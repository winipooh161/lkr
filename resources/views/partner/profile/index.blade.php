@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fs-2">{{ __('Профиль партнера') }}</h1>
            <p class="text-muted">Управление данными вашего аккаунта</p>
        </div>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    @endif
    
    <div class="row">
        <div class="col-12 col-lg-4 mb-4">
            <!-- Карточка профиля -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-placeholder rounded-circle bg-primary text-white mx-auto mb-3" style="width: 120px; height: 120px; font-size: 2.5rem; display: flex; align-items: center; justify-content: center;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <h4 class="fw-bold">{{ $user->name }}</h4>
                        <p class="text-muted mb-0">{{ $user->email }}</p>
                        <p class="text-muted mb-0">Партнер</p>
                    </div>
                    
                    <hr>
                    
                    <div class="contact-info">
                        <h6 class="text-uppercase text-muted fw-bold mb-3">Контактная информация</h6>
                        
                        <div class="mb-3">
                            <p class="text-muted mb-1">Email</p>
                            <p class="fw-bold mb-0">{{ $user->email }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <p class="text-muted mb-1">Телефон</p>
                            <p class="fw-bold mb-0">{{ $user->phone ?? 'Не указан' }}</p>
                        </div>
                        
                        <div>
                            <p class="text-muted mb-1">Дата регистрации</p>
                            <p class="fw-bold mb-0">{{ $user->created_at->format('d.m.Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Основная информация</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">ФИО</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Телефон</label>
                                <input type="tel" class="form-control maskphone @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="company" class="form-label">Компания</label>
                                <input type="text" class="form-control @error('company') is-invalid @enderror" id="company" name="company" value="{{ old('company', $user->company) }}">
                                @error('company')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Смена пароля</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update-password') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Текущий пароль</label>
                                <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="password" class="form-label">Новый пароль</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="password_confirmation" class="form-label">Подтверждение пароля</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Изменить пароль</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Настройки уведомлений</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" {{ $user->email_notifications ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_notifications">Получать уведомления по email</label>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications" {{ $user->sms_notifications ? 'checked' : '' }}>
                            <label class="form-check-label" for="sms_notifications">Получать SMS-уведомления</label>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Сохранить настройки</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Адаптивные стили для страницы профиля */
@media (max-width: 768px) {
    .card {
        margin-bottom: 1rem;
    }
    
    .avatar-placeholder {
        width: 100px !important;
        height: 100px !important;
        font-size: 2rem !important;
    }
    
    .card-header {
        padding: 0.75rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    h1 {
        font-size: 1.5rem !important;
    }
    
    h4 {
        font-size: 1.25rem;
    }
    
    .contact-info h6 {
        font-size: 0.8rem;
    }
}
</style>
@endpush
