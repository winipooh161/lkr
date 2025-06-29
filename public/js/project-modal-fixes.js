/**
 * Полное исправление для проблемы с модальными окнами на странице проектов
 * Предотвращает перемещение модальных окон в панель вкладок и устраняет мерцание
 */

document.addEventListener('DOMContentLoaded', function() {
    // Список всех известных модальных окон на странице
    const knownModalIds = [
        'uploadSchemeModal', 'uploadDesignModal', 'uploadDocumentModal', 
        'uploadContractModal', 'uploadOtherModal', 'editItemModal', 
        'addItemModal', 'deleteItemModal', 'deleteConfirmModal',
        'scheduleUrlModal', 'photoModal', 'uploadFileModal'
    ];
    
    /**
     * Функция для исправления положения всех модальных окон
     * Перемещает их в body, если они находятся в другом месте
     */
    function fixModalPositioning() {
        // Проверяем известные модальные окна по ID
        knownModalIds.forEach(function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal && modal.parentElement !== document.body) {
                document.body.appendChild(modal);
                applyModalStyles(modal);
            }
        });
        
        // Ищем и исправляем все модальные окна на странице
        document.querySelectorAll('.modal').forEach(function(modal) {
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
                applyModalStyles(modal);
            }
        });
        
        // Исправляем backdrop
        document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
            if (backdrop.parentElement !== document.body) {
                document.body.appendChild(backdrop);
            }
            backdrop.style.zIndex = '2055';
        });
    }
    
    /**
     * Применяет правильные стили к модальному окну
     */
    function applyModalStyles(modal) {
        modal.style.zIndex = '2060';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        
        // Также стилизуем внутренние элементы
        const modalDialog = modal.querySelector('.modal-dialog');
        if (modalDialog) modalDialog.style.zIndex = '2061';
        
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) modalContent.style.zIndex = '2061';
    }
    
    // Запускаем исправление сразу при загрузке страницы
    fixModalPositioning();
    
    // Настраиваем MutationObserver для отслеживания изменений в DOM
    // Это поможет обнаружить, когда модальные окна динамически добавляются или перемещаются
    const observer = new MutationObserver(function(mutations) {
        let needToFix = false;
        
        mutations.forEach(function(mutation) {
            // Проверяем, были ли добавлены новые узлы
            if (mutation.addedNodes.length > 0) {
                for (let i = 0; i < mutation.addedNodes.length; i++) {
                    const node = mutation.addedNodes[i];
                    // Если добавлен .modal или .modal-backdrop, нужно проверить их расположение
                    if (node.nodeType === 1 && 
                        (node.classList && (node.classList.contains('modal') || node.classList.contains('modal-backdrop')))) {
                        needToFix = true;
                        break;
                    }
                }
            }
            
            // Проверяем, были ли перемещены узлы
            if (mutation.type === 'childList' && mutation.target.classList && 
                (mutation.target.classList.contains('tab-pane') || 
                 mutation.target.classList.contains('card') || 
                 mutation.target.classList.contains('tab-content'))) {
                needToFix = true;
            }
        });
        
        if (needToFix) {
            fixModalPositioning();
        }
    });
    
    // Наблюдаем за изменениями во всём документе
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Переопределяем методы Bootstrap для модальных окон
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        // Сохраняем оригинальный метод показа
        const originalModalShow = bootstrap.Modal.prototype.show;
        
        // Переопределяем метод показа
        bootstrap.Modal.prototype.show = function() {
            // Гарантируем, что модальное окно находится в body до показа
            if (this._element.parentElement !== document.body) {
                document.body.appendChild(this._element);
                applyModalStyles(this._element);
            }
            
            // Вызываем оригинальный метод
            return originalModalShow.apply(this, arguments);
        };
        
        // Также переопределяем метод скрытия
        const originalModalHide = bootstrap.Modal.prototype.hide;
        
        bootstrap.Modal.prototype.hide = function() {
            // Запоминаем модальное окно
            const modalElement = this._element;
            
            // Вызываем оригинальный метод
            const result = originalModalHide.apply(this, arguments);
            
            // После скрытия проверяем, что модальное окно осталось в body
            setTimeout(function() {
                if (modalElement && modalElement.parentElement !== document.body) {
                    document.body.appendChild(modalElement);
                }
            }, 300);
            
            return result;
        };
    }
    
    // Добавляем обработчики событий для различных сценариев
    
    // Обработка события при клике на кнопки, открывающие модальные окна
    document.addEventListener('click', function(event) {
        if (event.target.hasAttribute('data-bs-toggle') && 
            event.target.getAttribute('data-bs-toggle') === 'modal') {
            // Применяем исправление после небольшой задержки
            setTimeout(fixModalPositioning, 10);
        }
    });

    // Исправление при показе модального окна
    document.addEventListener('show.bs.modal', function(event) {
        // Перемещаем модальное окно в body до показа
        if (event.target.parentElement !== document.body) {
            document.body.appendChild(event.target);
            applyModalStyles(event.target);
        }
    });

    // Исправление после показа модального окна
    document.addEventListener('shown.bs.modal', function(event) {
        // Дополнительная проверка после показа
        if (event.target.parentElement !== document.body) {
            document.body.appendChild(event.target);
            applyModalStyles(event.target);
        }
        
        // Исправляем z-index для открытого модального окна
        event.target.style.zIndex = '2060';
    });
    
    // Проверка и исправление при переключении вкладок
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(tabElement) {
        tabElement.addEventListener('shown.bs.tab', function() {
            setTimeout(fixModalPositioning, 10);
        });
    });
});
