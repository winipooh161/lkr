// Дополнительные функции для улучшения работы с создаеми смет
// Этот файл можно подключить к create.blade.php для расширенной функциональности

/**
 * Функция для валидации данных Excel перед отправкой
 */
function validateExcelData() {
    if (typeof hot === 'undefined' || !hot) {
        console.warn('Excel editor not initialized');
        return false;
    }
    
    const data = hot.getData();
    if (!data || data.length === 0) {
        console.warn('No data in Excel editor');
        return false;
    }
    
    // Проверяем, есть ли хотя бы одна строка с данными (кроме заголовков)
    let hasData = false;
    for (let i = 5; i < data.length; i++) { // Начинаем с 5-й строки (после заголовков)
        const row = data[i];
        if (row && row.some(cell => cell && cell.toString().trim() !== '')) {
            hasData = true;
            break;
        }
    }
    
    if (!hasData) {
        console.warn('No meaningful data found in Excel editor');
        return false;
    }
    
    return true;
}

/**
 * Функция для автоматического сохранения черновика
 */
function autoSaveDraft() {
    if (typeof saveExcelToForm === 'function') {
        try {
            saveExcelToForm();
            console.log('Draft auto-saved');
            
            // Показываем уведомление о сохранении
            showNotification('Черновик автоматически сохранен', 'success');
        } catch (error) {
            console.error('Error auto-saving draft:', error);
        }
    }
}

/**
 * Функция для показа уведомлений
 */
function showNotification(message, type = 'info') {
    // Создаем элемент уведомления
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 1060; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Добавляем к body
    document.body.appendChild(notification);
    
    // Автоматически удаляем через 3 секунды
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

/**
 * Функция для улучшенного управления горячими клавишами
 */
function setupAdvancedHotkeys() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+S для сохранения
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.click();
            }
        }
        
        // Ctrl+Shift+S для автосохранения черновика
        if (e.ctrlKey && e.shiftKey && e.key === 'S') {
            e.preventDefault();
            autoSaveDraft();
        }
        
        // Ctrl+N для добавления новой строки
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            const addRowBtn = document.getElementById('addRowBtn');
            if (addRowBtn) {
                addRowBtn.click();
            }
        }
        
        // Ctrl+Shift+N для добавления нового раздела
        if (e.ctrlKey && e.shiftKey && e.key === 'N') {
            e.preventDefault();
            const addSectionBtn = document.getElementById('addSectionBtn');
            if (addSectionBtn) {
                addSectionBtn.click();
            }
        }
        
        // F9 для пересчета всех формул
        if (e.key === 'F9') {
            e.preventDefault();
            const recalcBtn = document.getElementById('recalcAllBtn');
            if (recalcBtn) {
                recalcBtn.click();
            }
        }
    });
}

/**
 * Функция для отслеживания изменений в данных
 */
function setupChangeTracking() {
    let lastSavedData = null;
    let changeTimeout = null;
    
    // Функция для проверки изменений
    function checkChanges() {
        if (typeof hot !== 'undefined' && hot) {
            const currentData = JSON.stringify(hot.getData());
            
            if (lastSavedData && lastSavedData !== currentData) {
                // Данные изменились
                updateUnsavedIndicator(true);
                
                // Очищаем предыдущий таймер
                if (changeTimeout) {
                    clearTimeout(changeTimeout);
                }
                
                // Устанавливаем новый таймер для автосохранения
                changeTimeout = setTimeout(() => {
                    autoSaveDraft();
                }, 30000); // Автосохранение через 30 секунд
            }
            
            lastSavedData = currentData;
        }
    }
    
    // Отслеживаем изменения каждые 2 секунды
    setInterval(checkChanges, 2000);
}

/**
 * Функция для обновления индикатора несохраненных изменений
 */
function updateUnsavedIndicator(hasUnsavedChanges) {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        if (hasUnsavedChanges) {
            submitBtn.classList.add('btn-warning');
            submitBtn.classList.remove('btn-primary');
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Создать смету *';
        } else {
            submitBtn.classList.add('btn-primary');
            submitBtn.classList.remove('btn-warning');
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Создать смету';
        }
    }
}

/**
 * Функция для улучшенной предварительной загрузки шаблонов
 */
function preloadTemplateData() {
    // Предварительно загружаем данные шаблонов в фоновом режиме
    if (typeof populateSectionSelects === 'function') {
        populateSectionSelects();
    }
    
    // Подготавливаем модальные окна
    const sectionModal = document.getElementById('sectionSelectorModal');
    const workModal = document.getElementById('workTypeSelectorModal');
    
    if (sectionModal && workModal) {
        // Инициализируем модальные окна Bootstrap
        new bootstrap.Modal(sectionModal);
        new bootstrap.Modal(workModal);
    }
}

/**
 * Функция инициализации всех улучшений
 */
function initializeEnhancements() {
    console.log('Initializing create page enhancements...');
    
    // Настраиваем горячие клавиши
    setupAdvancedHotkeys();
    
    // Настраиваем отслеживание изменений
    setupChangeTracking();
    
    // Предварительно загружаем данные шаблонов
    preloadTemplateData();
    
    // Показываем справку по горячим клавишам при первом посещении
    if (!localStorage.getItem('create_page_help_shown')) {
        setTimeout(() => {
            showNotification('Используйте Ctrl+S для сохранения, Ctrl+N для добавления строки', 'info');
            localStorage.setItem('create_page_help_shown', 'true');
        }, 2000);
    }
    
    console.log('Create page enhancements initialized');
}

// Экспортируем функции для использования
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateExcelData,
        autoSaveDraft,
        showNotification,
        setupAdvancedHotkeys,
        setupChangeTracking,
        updateUnsavedIndicator,
        preloadTemplateData,
        initializeEnhancements
    };
}
