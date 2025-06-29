/**
 * Обеспечение совместимости между системой туров и выпадающими меню Bootstrap
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Обработка взаимодействия между IntroJS и Bootstrap Dropdowns
        const fixTourDropdownConflict = function() {
            // Наблюдаем за появлением тура на странице
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    // Проверяем, появились ли элементы introjs на странице
                    if (document.querySelector('.introjs-overlay') || 
                        document.querySelector('.introjs-helperLayer')) {

                        console.log('Обнаружен активный тур, применяем исправления для выпадающих меню');
                        
                        // Закрываем все открытые выпадающие меню
                        if (typeof bootstrap !== 'undefined') {
                            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                                const button = menu.previousElementSibling;
                                if (button && button.classList.contains('dropdown-toggle')) {
                                    const dropdownInstance = bootstrap.Dropdown.getInstance(button);
                                    if (dropdownInstance) {
                                        dropdownInstance.hide();
                                    }
                                }
                            });
                        }

                        // Добавляем обработчик клика по элементам тура
                        document.querySelectorAll('.introjs-tooltip, .introjs-tooltipReferenceLayer').forEach(function(element) {
                            element.addEventListener('click', function(e) {
                                // Предотвращаем всплытие события, чтобы не закрывать тур при клике на элементы
                                e.stopPropagation();
                            });
                        });
                    }
                });
            });

            // Начинаем наблюдение за body для отслеживания появления элементов тура
            observer.observe(document.body, { 
                childList: true, 
                subtree: true 
            });
        };

        // Проверка и повторная инициализация выпадающих меню после завершения тура
        if (typeof window.introJs !== 'undefined') {
            // Добавляем обработчики для событий тура
            const intro = introJs();
            
            intro.oncomplete(function() {
                console.log('Тур завершен, проверяем выпадающие меню');
                setTimeout(reinitializeDropdowns, 300);
            });

            intro.onexit(function() {
                console.log('Тур закрыт, проверяем выпадающие меню');
                setTimeout(reinitializeDropdowns, 300);
            });
        }

        // Функция для переинициализации всех выпадающих меню на странице
        function reinitializeDropdowns() {
            if (typeof bootstrap !== 'undefined') {
                // Переинициализируем все выпадающие меню
                document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(element) {
                    try {
                        // Пытаемся получить существующий экземпляр
                        let dropdownInstance = bootstrap.Dropdown.getInstance(element);
                        
                        // Если экземпляр существует, пересоздаем его
                        if (dropdownInstance) {
                            dropdownInstance.dispose();
                        }
                        
                        // Создаем новый экземпляр
                        new bootstrap.Dropdown(element);
                    } catch (error) {
                        console.warn('Ошибка при переинициализации выпадающего меню:', error);
                    }
                });

                // Особое внимание для элементов со сметами
                document.querySelectorAll('.estimate-action-btn, .btn-outline-secondary.dropdown-toggle').forEach(function(button) {
                    try {
                        let dropdownInstance = bootstrap.Dropdown.getInstance(button);
                        if (dropdownInstance) {
                            dropdownInstance.dispose();
                        }
                        new bootstrap.Dropdown(button);
                    } catch (error) {
                        // Игнорируем ошибки
                    }
                });
            }
        }

        // Запускаем исправление конфликтов
        fixTourDropdownConflict();
        
        // Запускаем проверку выпадающих меню через некоторое время после загрузки страницы
        setTimeout(reinitializeDropdowns, 1000);
    });
    
})();
