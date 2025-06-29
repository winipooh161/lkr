/**
 * Скомпилированный файл системы туров для корректной работы в браузере
 */

(function() {
    'use strict';
    
    // Функция для определения роли пользователя
    function getUserRole() {
        const userRole = document.body.dataset.userRole || '';
        return userRole;
    }
    
    // Базовые настройки для всех туров
    const tourDefaults = {
        nextLabel: 'Далее',
        prevLabel: 'Назад',
        skipLabel: '',
        doneLabel: 'Готово',
        showStepNumbers: true,
        showBullets: true,
        showProgress: true,
        scrollToElement: true,
        disableInteraction: false,
        exitOnOverlayClick: false,
        exitOnEsc: false,
        hidePrev: true,
        hideNext: false
    };
    
    // Настройки для обязательных туров (для клиентов)
    const mandatoryTourDefaults = {
        ...tourDefaults,
        skipLabel: '', // Убираем кнопку пропуска
        exitOnOverlayClick: false,
        exitOnEsc: false,
        showButtons: true,
        showBullets: true,
        showProgress: true,
        disableInteraction: true,
        hidePrev: false
    };
    
    // Импорт всех определенных туров из основного файла tours.js
    
    // Функция для запуска тура
    function startTour(pageKey) {
        const userRole = getUserRole();
        
        // Если роль не партнер и не клиент, не запускаем тур
        if (userRole !== 'partner' && userRole !== 'client') {
            console.log('Пользователь не партнер и не клиент, тур не запущен');
            return;
        }
        
        // Создаем экземпляр IntroJS
        if (typeof introJs === 'undefined') {
            console.error('Ошибка: библиотека introJs не найдена');
            return;
        }
        
        const tour = introJs();
        
        // Определение текущего тура из глобального объекта
        let tourSteps = [];
        if (window.appTours && window.appTours[userRole] && window.appTours[userRole][pageKey]) {
            tourSteps = window.appTours[userRole][pageKey];
        } else {
            console.log(`Тур для страницы ${pageKey} и роли ${userRole} не найден`);
            return;
        }
        
        // Выбираем настройки в зависимости от роли пользователя
        const tourOptions = userRole === 'client' 
            ? { ...mandatoryTourDefaults, steps: tourSteps }
            : { ...tourDefaults, steps: tourSteps };
        
        // Применяем настройки
        tour.setOptions(tourOptions);
        
        // Для клиента блокируем возможность пропуска тура
        if (userRole === 'client') {
            // Перехватываем событие перед выходом
            tour.onbeforeexit(function() {
                // Проверяем, был ли тур завершён
                const tourKey = `tour_${userRole}_${pageKey}_completed`;
                const completed = localStorage.getItem(tourKey) === 'true';
                
                // Если тур не был завершен, не разрешаем выход
                return completed;
            });
        }
        
        // Запускаем тур
        tour.start();
        
        // Сохраняем информацию о просмотре тура только при полном завершении
        tour.oncomplete(function() {
            saveTourCompletion(userRole, pageKey);
        });
        
        // Для партнера сохраняем прогресс при простом выходе, для клиента - только при завершении
        if (userRole !== 'client') {
            tour.onexit(function() {
                saveTourCompletion(userRole, pageKey);
            });
        }
    }
    
    // Функция для сохранения информации о завершении тура
    function saveTourCompletion(role, pageKey) {
        // Сохраняем в localStorage информацию о том, что пользователь прошел тур
        const tourKey = `tour_${role}_${pageKey}_completed`;
        localStorage.setItem(tourKey, 'true');
        
        // Отправляем запрос на сервер для сохранения информации
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            fetch('/api/tours/complete', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    role: role,
                    page: pageKey
                })
            }).catch(error => console.error('Ошибка при сохранении информации о туре:', error));
        }
    }
    
    // Функция для проверки, нужно ли показывать тур
    function shouldShowTour(pageKey) {
        const userRole = getUserRole();
        
        if (userRole !== 'partner' && userRole !== 'client') {
            return false;
        }
        
        // Проверяем, есть ли тур для данной страницы и роли
        const tourExists = window.appTours && window.appTours[userRole] && window.appTours[userRole][pageKey];
        
        if (!tourExists) {
            return false;
        }
        
        // Проверяем, проходил ли пользователь этот тур ранее
        const tourKey = `tour_${userRole}_${pageKey}_completed`;
        const completed = localStorage.getItem(tourKey) === 'true';
        
        // Тур показывается, если он существует и пользователь его еще не проходил
        return !completed;
    }
    
    // Функция для инициализации тура на странице
    function initTour(pageKey) {
        console.log('Инициализация тура для страницы:', pageKey);
        if (shouldShowTour(pageKey)) {
            // Добавляем небольшую задержку для уверенности, что DOM загружен полностью
            setTimeout(() => {
                startTour(pageKey);
            }, 1500);
        } else {
            console.log('Тур не будет показан (уже просмотрен или не существует)');
        }
    }
    
    // Функция для запуска тура вручную
    function manualStartTour(pageKey) {
        console.log('Запуск тура вручную для страницы:', pageKey);
        startTour(pageKey);
    }
    
    // Функция для сброса всех просмотренных туров
    function resetAllTours() {
        // Находим все ключи в localStorage, относящиеся к турам
        const tourKeys = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key.startsWith('tour_') && key.endsWith('_completed')) {
                tourKeys.push(key);
            }
        }
        
        // Удаляем все ключи туров
        tourKeys.forEach(key => localStorage.removeItem(key));
        console.log('Локальная информация о турах сброшена');
        
        // В профиле запрос на сброс отправляется отдельно, 
        // поэтому здесь проверяем, вызывается ли функция из профиля
        if (!window.location.pathname.includes('/profile')) {
            // Отправляем запрос на сервер для сброса информации о турах
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                fetch('/api/tours/reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin'
                }).catch(error => console.error('Ошибка при сбросе информации о турах:', error));
            }
        }
    }
    
    // Сделаем функции доступными глобально
    window.initTour = initTour;
    window.manualStartTour = manualStartTour;
    window.resetAllTours = resetAllTours;
    
    // Инициализация после загрузки страницы
    document.addEventListener('DOMContentLoaded', function() {
        // Определение текущей страницы и запуск соответствующего тура
        const path = window.location.pathname;
        
        // Определение ключа страницы на основе URL
        let pageKey = 'dashboard'; // По умолчанию
        
        if (path.includes('/partner')) {
            if (path === '/partner/dashboard' || path === '/partner') {
                pageKey = 'dashboard';
            } else if (path === '/partner/projects') {
                pageKey = 'projects-list';
            } else if (path === '/partner/estimates') {
                pageKey = 'estimates-list';
            } else if (path === '/partner/employees') {
                pageKey = 'employees';
            } else if (path === '/partner/calculator') {
                pageKey = 'calculator';
            } else if (path === '/partner/estimates/create') {
                pageKey = 'estimate-create';
            } else if (path.match(/\/partner\/estimates\/\d+\/edit$/)) {
                pageKey = 'estimate-edit';
            } else if (path.match(/\/partner\/projects\/\d+$/)) {
                pageKey = 'project';
            } else if (path.match(/\/partner\/projects\/\d+\/files$/)) {
                pageKey = 'project-files';
            } else if (path.match(/\/partner\/projects\/\d+\/photos$/)) {
                pageKey = 'project-photos';
            } else if (path.match(/\/partner\/projects\/\d+\/estimates$/)) {
                pageKey = 'project-estimates';
            } else if (path.match(/\/partner\/projects\/\d+\/schedule$/)) {
                pageKey = 'project-schedule';
            } else if (path.match(/\/partner\/projects\/\d+\/finance$/)) {
                pageKey = 'project-finance';
            } else if (path.match(/\/partner\/projects\/\d+\/checks$/)) {
                pageKey = 'project-checks';
            }
        } else if (path.includes('/client')) {
            if (path === '/client/dashboard' || path === '/client') {
                pageKey = 'client-dashboard';
            } else if (path === '/client/projects') {
                pageKey = 'client-projects-list';
            } else if (path.match(/\/client\/projects\/\d+$/)) {
                pageKey = 'client-project';
            } else if (path.match(/\/client\/projects\/\d+\/files$/)) {
                pageKey = 'client-project-files';
            } else if (path.match(/\/client\/projects\/\d+\/photos$/)) {
                pageKey = 'client-project-photos';
            } else if (path.match(/\/client\/projects\/\d+\/estimates$/)) {
                pageKey = 'client-project-estimates';
            } else if (path.match(/\/client\/projects\/\d+\/schedule$/)) {
                pageKey = 'client-project-schedule';
            }
        }
        
        // Запуск тура если необходимо
        console.log('Текущий путь:', path, 'Ключ страницы:', pageKey);
        initTour(pageKey);
    });
})();
