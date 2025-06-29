/**
 * Дополнительные исправления для выпадающих меню на страницах с оценками
 */
document.addEventListener('DOMContentLoaded', function() {
    // Функция для инициализации выпадающих меню на странице оценок
    function initEstimateDropdowns() {
        console.log('Инициализация выпадающих меню на странице оценок');
        
        // Поиск всех кнопок действий на странице оценок
        // Находим элементы и по старому селектору, и по новому
        const actionButtons = document.querySelectorAll('.btn-outline-secondary.dropdown-toggle, .estimate-action-btn');
        
        if (actionButtons.length > 0) {
            console.log(`Найдено ${actionButtons.length} кнопок действий`);
            
            actionButtons.forEach(function(button, index) {
                // Проверяем, что Bootstrap доступен
                if (typeof bootstrap !== 'undefined') {
                    try {
                        // Принудительно создаем новый экземпляр Dropdown
                        const dropdownInstance = new bootstrap.Dropdown(button, {
                            autoClose: true,
                            display: 'dynamic' 
                        });
                        
                        // Добавляем обработчик клика с предотвращением всплытия
                        button.addEventListener('click', function(event) {
                            // Остановка всплытия для предотвращения двойной обработки
                            event.stopPropagation();
                            
                            // Принудительно показываем выпадающее меню
                            dropdownInstance.show();
                        });
                        
                        console.log(`Dropdown ${index + 1} успешно инициализирован`);
                    } catch (error) {
                        console.error(`Ошибка инициализации Dropdown ${index + 1}:`, error);
                    }
                } else {
                    console.error('Bootstrap не доступен, невозможно инициализировать выпадающие меню');
                }
            });
        } else {
            console.log('Кнопки действий не найдены');
        }
        
        // Обработка элементов выпадающего меню
        document.querySelectorAll('.dropdown-item').forEach(function(item) {
            item.addEventListener('click', function(event) {
                // Для всех элементов, кроме формы удаления
                if (!item.closest('form.delete-form')) {
                    console.log('Клик по элементу выпадающего меню:', item.textContent.trim());
                }
            });
        });
    }
    
    // Запускаем инициализацию с увеличенной задержкой, чтобы гарантировать полную загрузку Bootstrap
    setTimeout(initEstimateDropdowns, 500);
    
    // Повторная попытка инициализации через еще больший интервал, если выпадающие меню все еще не работают
    setTimeout(initEstimateDropdowns, 1500);
    
    // Обработчик события для ручной инициализации при клике на документ
    document.addEventListener('click', function(event) {
        // Если клик был по кнопке выпадающего меню, но меню не открывается
        if (event.target.matches('.estimate-action-btn, .btn-outline-secondary.dropdown-toggle')) {
            // Проверяем наличие Bootstrap
            if (typeof bootstrap !== 'undefined') {
                try {
                    const dropdownInstance = new bootstrap.Dropdown(event.target);
                    dropdownInstance.show();
                } catch (error) {
                    console.error('Ошибка при попытке инициализации dropdown после клика:', error);
                }
            }
        }
    });
    
    // Инициализация для туров
    // Обработчик события для пересоздания выпадающих меню после запуска и завершения туров
    if (window.introJs) {
        const intro = introJs();
        intro.oncomplete(function() {
            console.log('Тур завершен, переинициализируем выпадающие меню');
            setTimeout(initEstimateDropdowns, 200);
        });
        
        intro.onexit(function() {
            console.log('Тур завершен, переинициализируем выпадающие меню');
            setTimeout(initEstimateDropdowns, 200);
        });
    }
});

// Повторная инициализация выпадающих меню при изменении DOM
// С небольшой дебаунс-функцией, чтобы не вызывать обработчик слишком часто
(function() {
    let timeout = null;
    
    // Создаем MutationObserver для отслеживания изменений в DOM
    const observer = new MutationObserver(function(mutations) {
        if (timeout) clearTimeout(timeout);
        
        timeout = setTimeout(function() {
            // Проверяем, есть ли на странице элементы с классом estimate-action-dropdown
            const hasDropdowns = document.querySelector('.estimate-action-dropdown') !== null;
            
            if (hasDropdowns) {
                console.log('Обнаружены изменения DOM, переинициализируем выпадающие меню');
                
                // Повторная инициализация выпадающих меню
                if (typeof bootstrap !== 'undefined') {
                    document.querySelectorAll('.estimate-action-btn, .btn-outline-secondary.dropdown-toggle').forEach(function(button) {
                        try {
                            const dropdownInstance = new bootstrap.Dropdown(button);
                        } catch (error) {
                            // Скорее всего, экземпляр уже существует, игнорируем ошибку
                        }
                    });
                }
            }
        }, 500);
    });
    
    // Начинаем наблюдение за изменениями в DOM только после полной загрузки страницы
    document.addEventListener('DOMContentLoaded', function() {
        observer.observe(document.body, { 
            childList: true, 
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style']
        });
    });
})();
