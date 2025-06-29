document.addEventListener('DOMContentLoaded', function() {
    // Обработка модальных окон изменения роли
    const users = document.querySelectorAll('[id^="changeRoleModal"]');
    
    users.forEach(modal => {
        // Извлекаем ID пользователя из ID модального окна
        const userId = modal.id.replace('changeRoleModal', '');
        
        // Находим селект роли и контейнер партнеров для этого модального окна
        const roleSelect = document.getElementById('role' + userId);
        const partnerContainer = document.getElementById('partnerSelectContainer' + userId);
        
        if (roleSelect && partnerContainer) {
            // Добавляем обработчик изменения роли
            roleSelect.addEventListener('change', function() {
                partnerContainer.style.display = roleSelect.value === 'estimator' ? 'block' : 'none';
            });
        }
    });
});
