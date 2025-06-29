@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4>{{ __('Изменение пароля') }}</h4>
                        <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('Назад к профилю') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update-password') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">{{ __('Текущий пароль') }}</label>
                            <input id="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" required>
                            @error('current_password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Новый пароль') }}</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <div class="form-text">Минимальная длина: 8 символов</div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ __('Подтвердите новый пароль') }}</label>
                            <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i>{{ __('Изменить пароль') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Мобильная адаптация для страницы смены пароля */
@media (max-width: 768px) {
    .col-md-8 {
        padding: 0 0.5rem;
    }
    
    .card-header .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 1rem;
    }
    
    .card-header h4 {
        text-align: center;
        margin-bottom: 0;
    }
    
    .btn-outline-secondary {
        width: 100%;
        text-align: center;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 0 0.5rem;
    }
    
    .card {
        margin: 0.5rem 0;
        border-radius: 1rem;
    }
    
    .card-header {
        padding: 1rem;
        text-align: center;
    }
    
    .card-header h4 {
        font-size: 1.3rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .form-label {
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .form-control {
        padding: 0.75rem;
        font-size: 1rem;
        border-radius: 0.5rem;
    }
    
    .btn {
        padding: 0.75rem 1rem;
        font-size: 1rem;
        border-radius: 0.5rem;
        width: 100%;
    }
    
    .form-text {
        font-size: 0.85rem;
        margin-top: 0.5rem;
    }
    
    .mb-3 {
        margin-bottom: 1.5rem !important;
    }
    
    .d-grid.gap-2.d-md-flex {
        display: grid !important;
        gap: 0.75rem !important;
    }
}

@media (max-width: 400px) {
    .card {
        margin: 0.25rem 0;
    }
    
    .card-header h4 {
        font-size: 1.2rem;
    }
    
    .card-body {
        padding: 0.75rem;
    }
    
    .form-control {
        padding: 0.65rem;
        font-size: 0.95rem;
    }
    
    .btn {
        padding: 0.65rem 0.85rem;
        font-size: 0.9rem;
    }
    
    .form-text {
        font-size: 0.8rem;
    }
}
</style>
@endsection
