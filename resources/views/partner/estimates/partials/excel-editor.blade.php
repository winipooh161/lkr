<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>Редактирование сметы</div>
        <div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" id="toggleColumnsBtn" title="Показать/скрыть пустые столбцы">
                    <i class="fas fa-columns"></i> Все столбцы
                </button>
                <button type="button" class="btn btn-outline-secondary" id="recalcAllBtn" title="Пересчитать все формулы">
                    <i class="fas fa-calculator"></i> Пересчитать
                </button>
                <button type="button" class="btn btn-outline-secondary" id="addRowBtn" title="Добавить строку">
                    <i class="fas fa-plus"></i> Строка
                </button>
                <button type="button" class="btn btn-outline-secondary" id="addSectionBtn" title="Добавить заголовок раздела">
                    <i class="fas fa-heading"></i> Раздел
                </button>
                <button type="button" class="btn btn-outline-secondary" id="updateNumberingBtn" title="Обновить нумерацию">
                    <i class="fas fa-sort-numeric-down"></i> Номерация
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Контейнер для выбора листов Excel -->
        <div class="bg-light border-bottom p-2 d-flex justify-content-between align-items-center" id="sheetTabsContainer">
            <div id="sheetTabs" class="overflow-auto flex-grow-1"></div>
            <div class="ms-2">
                <button type="button" class="btn btn-sm btn-outline-success" id="addSheetBtn" title="Добавить лист">
                    <i class="fas fa-plus"></i> Лист
                </button>
            </div>
        </div>

        <!-- Индикатор загрузки -->
        <div id="loadingIndicator" class="position-absolute top-50 start-50 translate-middle bg-white p-3 rounded shadow d-flex flex-column align-items-center justify-content-center" style="display: none; z-index: 1000; min-width: 150px; min-height: 150px;">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2">Загрузка...</div>
            <!-- Кнопка принудительного скрытия (появляется через 10 секунд) -->
            <button type="button" id="forceHideBtn" class="btn btn-sm btn-outline-danger mt-2" style="display: none;" onclick="forceHideAllLoaders()">
                <i class="fas fa-times"></i> Скрыть
            </button>
        </div>

        <!-- Контейнер для редактора Excel -->
        <div id="excelEditor" style=" height: 100vh; width: 100%; overflow: hidden;"></div>
    </div>
    <div class="card-footer d-flex justify-content-end">
        <button type="button" id="submitBtn" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>{{ isset($estimate) ? 'Сохранить смету' : 'Создать смету' }}
        </button>
    </div>
</div>
<script>
// Функция для отображения/скрытия индикатора загрузки
function showLoading(show) {
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        if (show) {
            loadingIndicator.style.display = 'flex';
            loadingIndicator.innerHTML = '<div class="spinner-border text-primary" role="status"></div><div class="mt-2">Сохранение...</div><button type="button" id="forceHideBtn" class="btn btn-sm btn-outline-danger mt-2" style="display: none;" onclick="forceHideAllLoaders()"><i class="fas fa-times"></i> Скрыть</button>';
            
            // Показываем кнопку принудительного скрытия через 10 секунд
            setTimeout(() => {
                const btn = document.getElementById('forceHideBtn');
                if (btn && loadingIndicator.style.display !== 'none') {
                    btn.style.display = 'block';
                }
            }, 10000);
        } else {
            loadingIndicator.style.setProperty('display', 'none', 'important');
        }
    }
    
    // Также проверяем наличие динамически созданного загрузчика
    const excelLoader = document.getElementById('excelLoader');
    if (excelLoader && !show) {
        excelLoader.remove();
    }
    
    // Управляем прозрачностью контейнера редактора
    const container = document.getElementById('excelEditor');
    if (container) {
        container.style.opacity = show ? '0.5' : '1';
    }
}

// Функция принудительной очистки всех индикаторов загрузки
function forceHideAllLoaders() {
    console.log('Принудительная очистка всех загрузчиков');
    
    // Скрываем основной индикатор
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.style.setProperty('display', 'none', 'important');
    }
    
    // Удаляем динамический загрузчик
    const excelLoader = document.getElementById('excelLoader');
    if (excelLoader) {
        excelLoader.remove();
    }
    
    // Восстанавливаем прозрачность
    const container = document.getElementById('excelEditor');
    if (container) {
        container.style.opacity = '1';
    }
    
    // Ищем и удаляем все элементы со спиннерами
    document.querySelectorAll('.spinner-border').forEach(spinner => {
        const parent = spinner.closest('[id*="loading"], [id*="loader"]');
        if (parent) {
            parent.style.setProperty('display', 'none', 'important');
        }
    });
    
    // Дополнительно скрываем все элементы с loading в ID или классе
    document.querySelectorAll('[id*="loading"], [class*="loading"], [id*="loader"], [class*="loader"]').forEach(element => {
        if (element.style) {
            element.style.setProperty('display', 'none', 'important');
        }
    });
}

// Делаем функцию доступной глобально
window.forceHideAllLoaders = forceHideAllLoaders;

// Обработчик кнопки сохранения
document.getElementById('submitBtn').addEventListener('click', function() {
    if (typeof saveExcelToServer === 'function') {
        saveExcelToServer();
        
        // Устанавливаем таймер принудительного скрытия индикатора через 30 секунд
        setTimeout(() => {
            console.log('Принудительное скрытие индикатора загрузки через таймаут');
            showLoading(false);
            if (typeof window.forceHideAllLoaders === 'function') {
                window.forceHideAllLoaders();
            }
        }, 30000);
    } else {
        console.error('Function saveExcelToServer is not defined');
    }
});

// Обработчик кнопки добавления строки
document.getElementById('addRowBtn').addEventListener('click', function() {
    if (typeof addNewRow === 'function') {
        addNewRow();
    } else {
        console.error('Function addNewRow is not defined');
    }
});

// Обработчик кнопки добавления раздела
document.getElementById('addSectionBtn').addEventListener('click', function() {
    if (typeof addNewSection === 'function') {
        addNewSection();
    } else {
        console.error('Function addNewSection is not defined');
    }
});

// Обработчик кнопки обновления нумерации
document.getElementById('updateNumberingBtn').addEventListener('click', function() {
    if (typeof updateAllRowNumbers === 'function') {
        updateAllRowNumbers();
    } else {
        console.error('Function updateAllRowNumbers is not defined');
    }
});

// Обработчик кнопки пересчета формул
document.getElementById('recalcAllBtn').addEventListener('click', function() {
    if (typeof window.recalculateAll === 'function') {
        console.log('Запуск полного пересчета формул');
        window.recalculateAll();
    } else {
        console.error('Function recalculateAll is not defined');
    }
});

// Обработчик кнопки добавления листа
document.getElementById('addSheetBtn').addEventListener('click', function() {
    if (typeof addNewSheet === 'function') {
        addNewSheet();
    } else {
        console.error('Function addNewSheet is not defined');
    }
});

// Обработчик кнопки показа всех столбцов
document.getElementById('toggleColumnsBtn').addEventListener('click', function() {
    if (typeof window.forceShowAllColumns === 'function') {
        window.forceShowAllColumns();
        // Показываем уведомление пользователю
        this.innerHTML = '<i class="fas fa-check"></i> Показаны';
        this.classList.add('btn-success');
        this.classList.remove('btn-outline-secondary');
        
        // Возвращаем обычный вид кнопки через 2 секунды
        setTimeout(() => {
            this.innerHTML = '<i class="fas fa-columns"></i> Все столбцы';
            this.classList.remove('btn-success');
            this.classList.add('btn-outline-secondary');
        }, 2000);
    } else {
        console.error('Function forceShowAllColumns is not defined');
    }
});

// Кнопка сохранения в боковой панели (если существует)
const saveExcelBtn = document.getElementById('saveExcelBtn');
if (saveExcelBtn) {
    saveExcelBtn.addEventListener('click', function() {
        if (typeof saveExcelToServer === 'function') {
            saveExcelToServer();
        } else {
            console.error('Function saveExcelToServer is not defined');
        }
    });
}

// Функция обновления индикатора статуса изменений
function updateStatusIndicator() {
    const statusIndicator = document.getElementById('statusIndicator');
    if (statusIndicator) {
        statusIndicator.style.display = isFileModified ? 'block' : 'none';
    }
    
    // Обновляем надписи на кнопке сохранения
    const saveBtn = document.getElementById('saveExcelBtn');
    if (saveBtn) {
        if (isFileModified) {
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Сохранить изменения*';
            saveBtn.classList.add('btn-primary');
            saveBtn.classList.remove('btn-outline-primary');
        } else {
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Сохранить изменения';
            saveBtn.classList.add('btn-outline-primary');
            saveBtn.classList.remove('btn-primary');
        }
    }
}

// Дополнительная защита от зависшего индикатора загрузки
// Автоматически скрываем загрузчик через 30 секунд максимум
let loadingTimeoutId;

// Переопределяем showLoading для добавления таймаута
const originalShowLoading = window.showLoading || showLoading;
window.showLoading = function(show) {
    // Вызываем оригинальную функцию
    originalShowLoading(show);
    
    if (show) {
        // Устанавливаем таймаут на 30 секунд
        if (loadingTimeoutId) {
            clearTimeout(loadingTimeoutId);
        }
        loadingTimeoutId = setTimeout(() => {
            console.warn('Принудительное скрытие загрузчика по таймауту (30 секунд)');
            forceHideAllLoaders();
        }, 30000);
    } else {
        // Отменяем таймаут если загрузка завершена
        if (loadingTimeoutId) {
            clearTimeout(loadingTimeoutId);
            loadingTimeoutId = null;
        }
    }
};

// Также добавляем дополнительную проверку при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Скрываем все возможные загрузчики при загрузке страницы
    setTimeout(() => {
        if (typeof forceHideAllLoaders === 'function') {
            forceHideAllLoaders();
        }
    }, 1000);
});
</script>
