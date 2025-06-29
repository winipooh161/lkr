<script>
// Скрипт для принудительной инициализации выпадающих меню
document.addEventListener('DOMContentLoaded', function() {
    // Функция инициализации выпадающих меню
    function initDropdownsManually() {
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap не загружен! Повторная попытка через 300мс');
            setTimeout(initDropdownsManually, 300);
            return;
        }
        
        console.log('Запуск принудительной инициализации выпадающих меню');
        
        // Находим все кнопки выпадающих меню
        const dropdownToggles = document.querySelectorAll('[data-bs-toggle="dropdown"]');
        
        dropdownToggles.forEach(function(toggle, index) {
            try {
                // Добавляем уникальный идентификатор, если его еще нет
                if (!toggle.id) {
                    toggle.id = 'dropdown-toggle-' + index;
                }
                
                // Находим соответствующий список выпадающего меню
                const dropdownMenu = toggle.nextElementSibling;
                
                // Проверяем, является ли следующий элемент меню
                if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                    // Принудительно создаем экземпляр Dropdown
                    const dropdownInstance = new bootstrap.Dropdown(toggle);
                    
                    // Исправляем обработку кликов
                    toggle.addEventListener('click', function(e) {
                        e.stopPropagation();
                        dropdownInstance.toggle();
                    });
                    
                    console.log(`Выпадающее меню ${toggle.id} инициализировано`);
                }
            } catch (error) {
                console.error(`Ошибка инициализации выпадающего меню ${index}:`, error);
            }
        });
    }
    
    // Запускаем инициализацию после полной загрузки страницы
    if (document.readyState === 'complete') {
        initDropdownsManually();
    } else {
        window.addEventListener('load', function() {
            // Даем немного времени, чтобы другие скрипты выполнились
            setTimeout(initDropdownsManually, 500);
        });
    }
});
</script>
