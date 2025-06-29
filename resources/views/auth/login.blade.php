@extends('layouts.auth')

@section('content')
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Левая колонка с формой -->
        <div class="col-md-6 bg-white">
            <div class="login-form-container d-flex align-items-center py-5">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-10 col-xl-8 mx-auto">
                            <h3 class="display-6 mb-4">{{ __('Вход') }}</h3>
                            <div class="card border-0 shadow rounded-3">
                                <div class="card-body p-4 p-sm-5">
                                    @if ($errors->has('db_error'))
                                    <div class="alert alert-danger mb-3">
                                        {{ $errors->first('db_error') }}
                                    </div>
                                    @endif
                                    
                                    <div id="loginAlert" class="alert d-none" role="alert"></div>
                                    
                                    <form method="POST" action="{{ route('login') }}" id="loginForm">
                                        @csrf

                                        <div class="mb-3">
                                            <label for="phone" class="form-label">{{ __('Номер телефона') }}</label>
                                            <input id="phone" type="tel" class="form-control maskphone @error('phone') is-invalid @enderror" 
                                                name="phone" value="{{ old('phone') }}" required autocomplete="phone" autofocus
                                                placeholder="+7 (___) ___-__-__">
                                            @error('phone')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                                          <div class="mb-4 code-input-group d-none">
                            <label for="code" class="form-label">{{ __('Код из SMS') }}</label>
                            <div class="code-container">
                                <div class="code-inputs d-flex gap-2 justify-content-center mb-3">
                                    <input type="text" class="form-control code-digit" maxlength="1" pattern="\d" inputmode="numeric" autocomplete="off" data-index="0">
                                    <input type="text" class="form-control code-digit" maxlength="1" pattern="\d" inputmode="numeric" autocomplete="off" data-index="1">
                                    <input type="text" class="form-control code-digit" maxlength="1" pattern="\d" inputmode="numeric" autocomplete="off" data-index="2">
                                    <input type="text" class="form-control code-digit" maxlength="1" pattern="\d" inputmode="numeric" autocomplete="off" data-index="3">
                                </div>
                                <div class="resend-container text-center">
                                    <button type="button" class="btn btn-outline-secondary" id="resendCode" disabled>
                                        {{ __('Отправить повторно') }} <span id="timer" class="ms-1">(60)</span>
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" id="code" name="code" value="">
                            
                            @error('code')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                                        <div class="mb-3 form-check">
                                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="remember">
                                                {{ __('Запомнить меня') }}
                                            </label>
                                        </div>

                                        <div class="d-grid gap-2 mb-3">
                                            <button type="button" id="requestCodeBtn" class="btn btn-primary btn-block">
                                                {{ __('Получить код') }}
                                            </button>
                                            <button type="submit" id="loginBtn" class="btn btn-success btn-block d-none">
                                                {{ __('Войти') }}
                                            </button>
                                        </div>

                                        <div class="text-center">
                                            @if (Route::has('password.request'))
                                                <a class="text-decoration-none" href="{{ route('password.request') }}">
                                                    {{ __('Забыли пароль?') }}
                                                </a>
                                            @endif
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Правая колонка с фоновым изображением -->
        <div class="col-md-6 d-none d-md-block p-0">
            <div class="bg-image h-100" style="background-image: url('https://images.unsplash.com/photo-1556156653-e5a7c69cc263?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=987&q=80'); background-size: cover; background-position: center;"></div>
        </div>
    </div>
</div>
<style>
.login-form-container {
    min-height: calc(100vh - 72px);
}
.bg-image {
    min-height: calc(100vh - 72px);
}
.code-input-group input {
    letter-spacing: 4px;
    font-size: 1.5rem;
    text-align: center;
    font-weight: bold;
}
.code-inputs {
    flex: 1;
}

/* Базовые стили для контейнера кода */
.code-container {
    width: 100%;
}

.resend-container {
    margin-top: 0.5rem;
}

/* Touch-friendly стили */
.code-digit {
    -webkit-appearance: none;
    -moz-appearance: textfield;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

.code-digit::-webkit-outer-spin-button,
.code-digit::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Улучшение для Safari на iOS */
.form-control {
    -webkit-appearance: none;
    border-radius: 0.375rem;
}

.btn {
    -webkit-appearance: none;
    touch-action: manipulation;
}
.code-digit {
    width: 60px;
    height: 60px;
    text-align: center;
    font-size: 1.8rem;
    font-weight: bold;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    transition: all 0.3s ease;
}
.code-digit:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.code-digit.filled {
    background-color: #e7f3ff;
    border-color: #0d6efd;
}
.code-digit.error {
    border-color: #dc3545;
    animation: shake 0.5s;
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-3px); }
    20%, 40%, 60%, 80% { transform: translateX(3px); }
}
#timer {
    font-size: 0.85rem;
    opacity: 0.8;
}
.alert {
    transition: all 0.3s ease;
}
.form-control.is-invalid:focus {
    box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}
@keyframes btn-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
    }
}
.btn-pulse {
    animation: btn-pulse 1.5s infinite;
}

/* Адаптивные стили для мобильных устройств */
@media (max-width: 991px) {
    .bg-image {
        display: none !important;
    }
    
    .col-md-6:first-child {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

@media (max-width: 768px) {
    .login-form-container {
        min-height: 100vh;
        padding: 1rem 0;
    }
    
    .container {
        padding: 0 1rem;
    }
    
    .card {
        margin: 0.5rem;
        border-radius: 1rem !important;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
    
    .display-6 {
        font-size: 1.75rem;
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 576px) {
    .login-form-container {
        padding: 0.5rem 0;
    }
    
    .container {
        padding: 0 0.5rem;
    }
    
    .card {
        margin: 0.25rem;
        box-shadow: none !important;
        border: 1px solid #dee2e6;
    }
    
    .card-body {
        padding: 1rem !important;
    }
    
    .display-6 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
        text-align: center;
    }
    
    /* Адаптация полей ввода кода для мобильных */
    .code-digit {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .code-inputs {
        gap: 0.5rem !important;
        justify-content: center;
        margin-bottom: 1rem;
    }
    
    /* Адаптация кнопки повторной отправки */
    .resend-container {
        width: 100%;
    }
    
    #resendCode {
        width: 100%;
        max-width: 280px;
    }
    
    /* Улучшение отображения кнопок */
    .btn {
        padding: 0.75rem 1rem;
        font-size: 1rem;
        border-radius: 0.5rem;
    }
    
    /* Улучшение полей ввода */
    .form-control {
        padding: 0.75rem;
        font-size: 1rem;
        border-radius: 0.5rem;
    }
    
    /* Адаптация алертов */
    .alert {
        margin-bottom: 1rem;
        padding: 0.75rem;
        font-size: 0.9rem;
        border-radius: 0.5rem;
    }
    
    /* Улучшение отображения чекбокса */
    .form-check {
        margin-bottom: 1.5rem;
    }
    
    .form-check-label {
        font-size: 0.9rem;
    }
}

@media (max-width: 400px) {
    .code-digit {
        width: 45px;
        height: 45px;
        font-size: 1.3rem;
    }
    
    .code-inputs {
        gap: 0.25rem !important;
    }
    
    .display-6 {
        font-size: 1.3rem;
    }
    
    .card-body {
        padding: 0.75rem !important;
    }
    
    .btn {
        padding: 0.65rem 0.85rem;
        font-size: 0.95rem;
    }
    
    .form-control {
        padding: 0.65rem;
        font-size: 0.95rem;
    }
    
    #resendCode {
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
    }
}

/* Дополнительные стили для очень маленьких экранов */
@media (max-width: 350px) {
    .container {
        padding: 0 0.25rem;
    }
    
    .card {
        margin: 0.1rem;
    }
    
    .code-digit {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
    
    .display-6 {
        font-size: 1.2rem;
        margin-bottom: 0.75rem;
    }
}

/* Улучшение для горизонтальной ориентации на мобильных */
@media (max-height: 600px) and (orientation: landscape) {
    .login-form-container {
        min-height: auto;
        padding: 1rem 0;
    }
    
    .display-6 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .card-body {
        padding: 1rem !important;
    }
    
    .mb-3, .mb-4 {
        margin-bottom: 0.75rem !important;
    }
}
</style>
<script>
 
document.addEventListener("DOMContentLoaded", function () {
    // Инициализация маски для телефона
    var inputs = document.querySelectorAll("input.maskphone");
    for (var i = 0; i < inputs.length; i++) {
        var input = inputs[i];
        input.addEventListener("input", mask);
        input.addEventListener("focus", mask);
        input.addEventListener("blur", mask);
        
        // Вызываем маску при загрузке страницы для полей с существующим значением
        if (input.value && input.value.length > 0) {
            mask.call(input);
        }
    }
    
    function mask(event) {
        var blank = "+_ (___) ___-__-__";
        var i = 0;
        var val = this.value.replace(/\D/g, "").replace(/^8/, "7").replace(/^9/, "79");
        this.value = blank.replace(/./g, function (char) {
            if (/[_\d]/.test(char) && i < val.length) return val.charAt(i++);
            return i >= val.length ? "" : char;
        });
        if (event.type == "blur") {
            if (this.value.length == 2) this.value = "";
        } else {
            setCursorPosition(this, this.value.length);
        }
    }
    
    function setCursorPosition(elem, pos) {
        elem.focus();
        if (elem.setSelectionRange) {
            elem.setSelectionRange(pos, pos);
            return;
        }
        if (elem.createTextRange) {
            var range = elem.createTextRange();
            range.collapse(true);
            range.moveEnd("character", pos);
            range.moveStart("character", pos);
            range.select();
            return;
        }
    }
    
    // Элементы формы для SMS-аутентификации
    const phoneInput = document.getElementById('phone');
    const codeInput = document.getElementById('code');
    const codeDigits = document.querySelectorAll('.code-digit');
    const codeGroup = document.querySelector('.code-input-group');
    const requestCodeBtn = document.getElementById('requestCodeBtn');
    const loginBtn = document.getElementById('loginBtn');
    const resendCodeBtn = document.getElementById('resendCode');
    const timerSpan = document.getElementById('timer');
    const loginForm = document.getElementById('loginForm');
    const loginAlert = document.getElementById('loginAlert');
    
    // Инициализация полей для ввода кода
    function initializeCodeInputs() {
        codeDigits.forEach((input, index) => {
            // Обработка ввода
            input.addEventListener('input', function(e) {
                // Разрешаем только цифры
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value) {
                    this.classList.add('filled');
                    // Переход к следующему полю
                    if (index < codeDigits.length - 1) {
                        codeDigits[index + 1].focus();
                    }
                } else {
                    this.classList.remove('filled');
                }
                
                // Обновляем скрытое поле
                updateHiddenCodeField();
                
                // Проверяем, заполнены ли все поля
                if (isCodeComplete()) {
                    submitForm();
                }
            });
            
            // Обработка клавиш
            input.addEventListener('keydown', function(e) {
                // Backspace - переход к предыдущему полю
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    codeDigits[index - 1].focus();
                    codeDigits[index - 1].value = '';
                    codeDigits[index - 1].classList.remove('filled');
                    updateHiddenCodeField();
                }
                
                // Стрелки для навигации
                if (e.key === 'ArrowLeft' && index > 0) {
                    codeDigits[index - 1].focus();
                }
                if (e.key === 'ArrowRight' && index < codeDigits.length - 1) {
                    codeDigits[index + 1].focus();
                }
            });
            
            // Обработка вставки из буфера обмена
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const digits = paste.replace(/[^0-9]/g, '').slice(0, 4);
                
                digits.split('').forEach((digit, i) => {
                    if (codeDigits[i]) {
                        codeDigits[i].value = digit;
                        codeDigits[i].classList.add('filled');
                    }
                });
                
                updateHiddenCodeField();
                
                if (isCodeComplete()) {
                    submitForm();
                }
            });
            
            // Выделение текста при фокусе
            input.addEventListener('focus', function() {
                this.select();
            });
        });
    }
    
    // Обновление скрытого поля с кодом
    function updateHiddenCodeField() {
        const code = Array.from(codeDigits).map(input => input.value).join('');
        codeInput.value = code;
    }
    
    // Проверка, заполнены ли все поля
    function isCodeComplete() {
        return Array.from(codeDigits).every(input => input.value.length === 1);
    }
    
    // Очистка полей кода
    function clearCodeFields() {
        codeDigits.forEach(input => {
            input.value = '';
            input.classList.remove('filled', 'error');
        });
        codeInput.value = '';
    }
    
    // Показать ошибку в полях кода
    function showCodeError() {
        codeDigits.forEach(input => {
            input.classList.add('error');
            setTimeout(() => {
                input.classList.remove('error');
            }, 500);
        });
    }
    
    // Отправка формы
    function submitForm() {
        loginBtn.classList.add('btn-pulse');
        setTimeout(() => {
            loginForm.submit();
        }, 300);
    }
    
    let timer;
    let countdown = 60;
    
    // Функция для показа уведомления
    function showAlert(type, message) {
        loginAlert.className = `alert alert-${type}`;
        loginAlert.textContent = message;
        loginAlert.classList.remove('d-none');
    }
    
    // Функция для скрытия уведомления
    function hideAlert() {
        loginAlert.classList.add('d-none');
    }
    
    // Функция для запуска таймера обратного отсчета
    function startTimer() {
        countdown = 60;
        resendCodeBtn.disabled = true;
        timerSpan.textContent = `(${countdown})`;
        
        clearInterval(timer);
        timer = setInterval(() => {
            countdown--;
            timerSpan.textContent = `(${countdown})`;
            
            if (countdown <= 0) {
                clearInterval(timer);
                resendCodeBtn.disabled = false;
                timerSpan.textContent = '';
            }
        }, 1000);
    }
    
    // Функция для запроса кода
    async function requestCode() {
        const phone = phoneInput.value;
        
        if (!phone) {
            showAlert('danger', 'Пожалуйста, введите номер телефона');
            return;
        }
        
        // Блокируем кнопку на время запроса
        requestCodeBtn.disabled = true;
        requestCodeBtn.textContent = 'Отправляем...';
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }
            
            const response = await fetch("{{ route('login.send-code') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ phone })
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.status === 'success') {
                showAlert('success', data.message);
                
                // Показываем поле для ввода кода и кнопку входа
                codeGroup.classList.remove('d-none');
                requestCodeBtn.classList.add('d-none');
                loginBtn.classList.remove('d-none');
                
                // Очищаем поля кода
                clearCodeFields();
                
                // Запускаем таймер для повторной отправки кода
                startTimer();
                
                // Устанавливаем фокус на первое поле ввода кода
                codeDigits[0].focus();
            } else {
                showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('danger', 'Произошла ошибка при отправке запроса: ' + error.message);
        } finally {
            // Восстанавливаем кнопку
            requestCodeBtn.disabled = false;
            requestCodeBtn.textContent = '{{ __('Получить код') }}';
        }
    }
    
    // Инициализируем поля для ввода кода
    initializeCodeInputs();
    
    // Обработчик нажатия кнопки запроса кода
    if (requestCodeBtn) {
        requestCodeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            requestCode();
        });
    }
    
    // Обработчик нажатия кнопки повторной отправки кода
    if (resendCodeBtn) {
        resendCodeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            requestCode();
        });
    }
    
    // Обработчик отправки формы
    loginForm.addEventListener('submit', function(e) {
        if (!isCodeComplete()) {
            e.preventDefault();
            showAlert('danger', 'Введите 4-значный код из SMS');
            showCodeError();
            codeDigits[0].focus();
        }
    });
});
</script>



@endsection
