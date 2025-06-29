// import './bootstrap';
import $ from 'jquery';



// Глобальный доступ к jQuery
window.$ = window.jQuery = $;

// Глобальный доступ к функциям туров
window.initTour = initTour;
window.manualStartTour = manualStartTour;
window.resetAllTours = resetAllTours;



// Функция для инициализации всех компонентов Bootstrap
function initializeBootstrapComponents() {
    if (typeof bootstrap !== 'undefined') {
        // Для dropdown НЕ создаем новые экземпляры, позволяем Bootstrap сделать это автоматически
        // Bootstrap сам инициализирует все dropdown по атрибуту data-bs-toggle="dropdown"
        
        // Для поповеров и тултипов продолжаем делать явную инициализацию
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        if (popoverTriggerList.length > 0) {
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        }
        
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        if (tooltipTriggerList.length > 0) {
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
        console.log('Bootstrap компоненты успешно инициализированы');
    }
}

// Функция для инициализации боковой панели
function initializeSidebar() {
    const sidebarCollapseShow = document.getElementById('sidebarCollapseShow');
    if (sidebarCollapseShow) {
        sidebarCollapseShow.addEventListener('click', function(e) {
            console.log('Кнопка меню нажата');
            // Добавляем класс для показа боковой панели на мобильных устройствах
            document.getElementById('sidebar').classList.add('active');
            
            // Добавляем затемняющий фон
            addSidebarOverlay();
        });
    }
    
    // Функция для добавления затемняющего фона
    function addSidebarOverlay() {
        // Убедимся, что старый overlay удален
        removeSidebarOverlay();
        
        // Создаем новый overlay
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.id = 'sidebarOverlay';
        
        // Добавляем обработчик клика по overlay
        overlay.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.remove('active');
            }
            removeSidebarOverlay();
        });
        
        document.body.appendChild(overlay);
        console.log('Overlay добавлен');
    }
    
    // Функция для удаления затемняющего фона
    function removeSidebarOverlay() {
        const overlay = document.getElementById('sidebarOverlay');
        if (overlay && overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
            console.log('Overlay удален');
        }
    }
    
    // Добавляем обработчик клика по документу для закрытия сайдбара при клике вне его
    document.addEventListener('mousedown', function(event) {
        const sidebar = document.getElementById('sidebar');
        const sidebarCollapseShow = document.getElementById('sidebarCollapseShow');
        
        // Проверяем, что сайдбар активен, клик был вне его и не по кнопке открытия
        if (
            sidebar && 
            sidebar.classList.contains('active') && 
            !sidebar.contains(event.target) && 
            (!sidebarCollapseShow || !sidebarCollapseShow.contains(event.target))
        ) {
            sidebar.classList.remove('active');
            removeSidebarOverlay();
            console.log('Сайдбар закрыт по клику вне области');
        }
    });
}

// Инициализируем компоненты при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    initializeBootstrapComponents();
    initializeSidebar();
    
    // Определение текущей страницы и запуск тура если нужно
    detectCurrentPageAndInitTour();
});

// Импортируем наш новый скрипт автозаполнения адресов
import './address-autocomplete.js';

// Импортируем остальные скрипты
import './project-filters.js';
import './project-file-upload.js';
import './project-tabs.js';
import './mask.js';

// Убеждаемся, что jQuery доступен глобально после загрузки всех модулей
window.addEventListener('DOMContentLoaded', function() {
    if (!window.$ && window.jQuery) {
        window.$ = window.jQuery;
    }
    console.log('jQuery статус:', window.$ ? 'доступен' : 'недоступен');
});

// Функция для определения текущей страницы и запуска соответствующего тура
function detectCurrentPageAndInitTour() {
    // Получение текущего пути из URL
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
    
    // Запускаем тур для определенной страницы
    console.log('Запуск тура для страницы:', pageKey);
    initTour(pageKey);
}