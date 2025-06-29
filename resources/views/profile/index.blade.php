@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>{{ __('Мой профиль') }}</h4>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-4 mb-4 mb-md-0">
                            <div class="text-center">
                                <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" class="img-fluid rounded-circle img-thumbnail profile-avatar">
                                <h4 class="mt-3">{{ $user->name }}</h4>
                                <div class="badge bg-secondary mb-3">{{ ucfirst($user->role) }}</div>
                                <div class="d-grid gap-2 mt-3">
                                    <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                                        <i class="fas fa-edit me-2"></i>{{ __('Редактировать профиль') }}
                                    </a>
                                    <a href="{{ route('profile.change-password') }}" class="btn btn-outline-primary">
                                        <i class="fas fa-key me-2"></i>{{ __('Изменить пароль') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>{{ __('Контактная информация') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="text-muted">{{ __('Имя') }}:</label>
                                        <p class="lead">{{ $user->name }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted">{{ __('Номер телефона') }}:</label>
                                        <p class="lead">{{ $user->phone }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted">{{ __('Email') }}:</label>
                                        <p class="lead">{{ $user->email ?? 'Не указан' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted">{{ __('Дата регистрации') }}:</label>
                                        <p class="lead">{{ $user->created_at->format('d.m.Y') }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            @if(auth()->user()->role === 'partner' || auth()->user()->role === 'client')
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ __('Настройки обучения') }}</h5>
                                </div>
                                <div class="card-body">
                                    <p>Вы можете сбросить прогресс обучения, чтобы снова увидеть все подсказки.</p>
                                    
                                    @php
                                        $completedToursCount = $user->completedTours()->count();
                                    @endphp
                                    
                                    <div class="mb-3">
                                        <div class="alert alert-secondary">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <span>
                                                @if($completedToursCount > 0)
                                                    Вы просмотрели {{ $completedToursCount }} {{ trans_choice('тур|тура|туров', $completedToursCount) }} обучения.
                                                @else
                                                    Вы еще не просмотрели ни одного тура обучения.
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <button id="resetTours" class="btn btn-info">
                                        <i class="fas fa-redo me-2"></i>Сбросить прогресс обучения
                                    </button>
                                </div>
                            </div>
                            @endif

                            @if(auth()->user()->role === 'partner' || auth()->user()->role === 'admin')
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>{{ __('Банковские реквизиты') }}</h5>
                                </div>
                                <div class="card-body">
                                    @if($user->bank || $user->bik || $user->checking_account || $user->correspondent_account || $user->recipient_bank || $user->inn || $user->kpp)
                                        <div class="row">
                                            <div class="col-md-6">
                                                @if($user->bank)
                                                <div class="mb-3">
                                                    <label class="text-muted">{{ __('Банк') }}:</label>
                                                    <p class="lead">{{ $user->bank }}</p>
                                                </div>
                                                @endif
                                                
                                                @if($user->bik)
                                                <div class="mb-3">
                                                    <label class="text-muted">{{ __('БИК') }}:</label>
                                                    <p class="lead">{{ $user->bik }}</p>
                                                </div>
                                                @endif
                                                
                                                @if($user->checking_account)
                                                <div class="mb-3">
                                                    <label class="text-muted">{{ __('Р/с') }}:</label>
                                                    <p class="lead">{{ $user->checking_account }}</p>
                                                </div>
                                                @endif
                                                
                                                @if($user->correspondent_account)
                                                <div class="mb-3">
                                                    <label class="text-muted">{{ __('К/с') }}:</label>
                                                    <p class="lead">{{ $user->correspondent_account }}</p>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="col-md-6">
                                                @if($user->recipient_bank)
                                                <div class="mb-3">
                                                    <label class="text-muted">{{ __('Банк получателя') }}:</label>
                                                    <p class="lead">{{ $user->recipient_bank }}</p>
                                                </div>
                                                @endif
                                                
                                                @if($user->inn)
                                                <div class="mb-3">
                                                    <label class="text-muted">{{ __('ИНН') }}:</label>
                                                    <p class="lead">{{ $user->inn }}</p>
                                                </div>
                                                @endif
                                                
                                                @if($user->kpp)
                                                <div class="mb-3">
                                                    <label class="text-muted">{{ __('КПП') }}:</label>
                                                    <p class="lead">{{ $user->kpp }}</p>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <span>Банковские реквизиты не заполнены. Вы можете добавить их в разделе "Редактировать профиль".</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>{{ __('Подпись и печать') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="text-muted">{{ __('Подпись') }}:</label>
                                            <div class="mt-2">
                                                @if($user->signature_file)
                                                    <img src="{{ $user->getSignatureUrl() }}" alt="Подпись" class="img-thumbnail" style="max-height: 100px;">
                                                @else
                                                    <div class="alert alert-secondary">
                                                        <i class="fas fa-file-image me-2"></i>
                                                        <span>Файл подписи не загружен</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="text-muted">{{ __('Печать') }}:</label>
                                            <div class="mt-2">
                                                @if($user->stamp_file)
                                                    <img src="{{ $user->getStampUrl() }}" alt="Печать" class="img-thumbnail" style="max-height: 100px;">
                                                @else
                                                    <div class="alert alert-secondary">
                                                        <i class="fas fa-certificate me-2"></i>
                                                        <span>Файл печати не загружен</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Кнопка сброса туров обучения
    const resetButton = document.getElementById('resetTours');
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            // Показываем индикатор загрузки
            resetButton.disabled = true;
            resetButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Сбрасываем...';
            
            // Очищаем локальное хранилище
            if (typeof window.resetAllTours === 'function') {
                try {
                    // Если функция resetAllTours доступна, она сделает запрос к API
                    window.resetAllTours();
                    
                    // Проверяем статус через 1 секунду (даем время на выполнение запроса)
                    setTimeout(() => {
                        resetButton.innerHTML = '<i class="fas fa-check me-2"></i>Сброшено успешно';
                        setTimeout(() => {
                            resetButton.innerHTML = '<i class="fas fa-redo me-2"></i>Сбросить прогресс обучения';
                            resetButton.disabled = false;
                        }, 2000);
                    }, 1000);
                } catch (error) {
                    handleResetError(error);
                }
            } else {
                // Если функция недоступна, очищаем хранилище напрямую и делаем запрос
                resetToursManually();
            }
            
            function resetToursManually() {
                // Очищаем локальное хранилище
                const tourKeys = [];
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (key.startsWith('tour_') && key.endsWith('_completed')) {
                        tourKeys.push(key);
                    }
                }
                tourKeys.forEach(key => localStorage.removeItem(key));
                
                // Отправляем запрос на сервер для сброса данных в БД
                fetch('/api/tours/reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin' // Важно для работы с аутентификацией
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Ошибка ответа сервера: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Показываем сообщение об успехе
                        alert('Прогресс обучения успешно сброшен. При следующем посещении страниц вы снова увидите подсказки.');
                        resetButton.innerHTML = '<i class="fas fa-check me-2"></i>Сброшено успешно';
                        setTimeout(() => {
                            resetButton.innerHTML = '<i class="fas fa-redo me-2"></i>Сбросить прогресс обучения';
                            resetButton.disabled = false;
                        }, 2000);
                    }
                })
                .catch(handleResetError);
            }
            
            function handleResetError(error) {
                console.error('Ошибка при сбросе туров:', error);
                alert('Произошла ошибка при сбросе прогресса обучения. Попробуйте обновить страницу и повторить попытку.');
                resetButton.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Ошибка';
                resetButton.disabled = false;
            }
        });
    }
});
</script>
@endpush

<style>
.profile-avatar {
    width: 200px;
    height: 200px;
    object-fit: cover;
}

/* Мобильная адаптация для профиля */
@media (max-width: 768px) {
    .profile-avatar {
        width: 150px;
        height: 150px;
    }
    
    .card-header h4 {
        font-size: 1.5rem;
        text-align: center;
        margin-bottom: 0;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .lead {
        font-size: 1rem;
        margin-bottom: 0.5rem;
    }
    
    .text-muted {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .profile-avatar {
        width: 120px;
        height: 120px;
    }
    
    .card {
        margin: 0.5rem;
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
        padding: 0.75rem;
    }
    
    .btn {
        padding: 0.75rem;
        font-size: 0.9rem;
        border-radius: 0.5rem;
    }
    
    .mb-3 {
        margin-bottom: 1rem !important;
    }
    
    .badge {
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
    }
    
    h4.mt-3 {
        font-size: 1.2rem;
        margin-top: 1rem !important;
    }
    
    .lead {
        font-size: 0.95rem;
    }
    
    .text-muted {
        font-size: 0.85rem;
    }
}

@media (max-width: 400px) {
    .profile-avatar {
        width: 100px;
        height: 100px;
    }
    
    .card {
        margin: 0.25rem;
    }
    
    .card-header h4 {
        font-size: 1.2rem;
    }
    
    h4.mt-3 {
        font-size: 1.1rem;
    }
    
    .btn {
        padding: 0.65rem;
        font-size: 0.85rem;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.6rem;
    }
}
</style>
@endsection
