document.addEventListener('DOMContentLoaded', function() {
    // Выбор всех чекбоксов
    const selectAll = document.getElementById('selectAll');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const bulkActionButton = document.getElementById('applyBulkAction');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateBulkActionButton();
        });
    }
    
    // Обновление статуса кнопки массовых действий
    userCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionButton);
    });
    
    // Показать/скрыть селект роли при выборе действия
    const actionSelect = document.querySelector('select[name="action"]');
    const roleSelectContainer = document.getElementById('roleSelectContainer');
    
    if (actionSelect && roleSelectContainer) {
        actionSelect.addEventListener('change', function() {
            roleSelectContainer.style.display = actionSelect.value === 'change_role' ? 'block' : 'none';
            updateBulkActionButton();
        });
    }
    
    // Обновляет статус кнопки массовых действий
    function updateBulkActionButton() {
        if (!bulkActionButton) return;
        
        const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
        const actionSelected = actionSelect ? actionSelect.value : '';
        
        bulkActionButton.disabled = checkedCount === 0 || actionSelected === '';
    }
});
