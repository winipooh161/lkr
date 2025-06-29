@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Создание пользователя') }}</span>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Назад к списку
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3 row">
                            <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('Имя') }}</label>
                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email') }}</label>
                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3 row">
                            <label for="phone" class="col-md-4 col-form-label text-md-end">{{ __('Телефон') }}</label>
                            <div class="col-md-6">
                                <input id="phone" type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" autocomplete="phone">
                                @error('phone')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="role" class="col-md-4 col-form-label text-md-end">{{ __('Роль') }}</label>
                            <div class="col-md-6">
                                <select id="role" class="form-select @error('role') is-invalid @enderror" name="role" required onchange="togglePartnerSelect()">
                                    <option value="client" {{ old('role') == 'client' ? 'selected' : '' }}>Клиент</option>
                                    <option value="partner" {{ old('role') == 'partner' ? 'selected' : '' }}>Партнер</option>
                                    <option value="estimator" {{ old('role') == 'estimator' ? 'selected' : '' }}>Сметчик</option>
                                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Администратор</option>
                                </select>
                                @error('role')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3 row" id="partner-select-container" style="display: none;">
                            <label for="partner_id" class="col-md-4 col-form-label text-md-end">{{ __('Выберите партнера') }}</label>
                            <div class="col-md-6">
                                <select id="partner_id" class="form-select @error('partner_id') is-invalid @enderror" name="partner_id">
                                    <option value="">Выберите партнера</option>
                                    @foreach($partners as $partner)
                                        <option value="{{ $partner->id }}" {{ old('partner_id') == $partner->id ? 'selected' : '' }}>
                                            {{ $partner->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('partner_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Пароль') }}</label>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                    <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Подтверждение пароля') }}</label>
                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>
                        
                        <div class="mb-3 row">
                            <label for="avatar" class="col-md-4 col-form-label text-md-end">{{ __('Аватар') }}</label>
                            <div class="col-md-6">
                                <input id="avatar" type="file" class="form-control @error('avatar') is-invalid @enderror" name="avatar" accept="image/*">
                                <small class="form-text text-muted">Допустимые форматы: JPG, PNG, GIF. Максимальный размер: 2МБ.</small>
                                @error('avatar')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Создать пользователя') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePartnerSelect() {
        var roleSelect = document.getElementById('role');
        var partnerSelectContainer = document.getElementById('partner-select-container');
        
        if (roleSelect.value === 'estimator') {
            partnerSelectContainer.style.display = 'flex';
        } else {
            partnerSelectContainer.style.display = 'none';
        }
    }
    
    function generatePassword() {
        // Генерируем случайный пароль длиной 10 символов
        var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        var password = '';
        for (var i = 0; i < 10; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        // Устанавливаем пароль в оба поля
        document.getElementById('password').value = password;
        document.getElementById('password-confirm').value = password;
        
        // Опционально: показываем пароль пользователю
        alert('Сгенерированный пароль: ' + password);
    }
    
    // Вызываем функцию при загрузке страницы для корректного отображения
    document.addEventListener('DOMContentLoaded', function() {
        togglePartnerSelect();
    });
</script>
@endsection
