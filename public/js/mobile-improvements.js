/**
 * Скрипты для улучшения мобильного отображения проекта
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Мобильные улучшения: скрипт загружен');
    
    // Инициализация для адаптивных таблиц 
    initResponsiveTables();
    
    // Инициализация для вкладок
    initTabsNavigation();
    
    // Инициализация для карточек файлов
    initFileCards();
    
    // Улучшения для мобильного просмотра фотографий
    initPhotoViewer();
    
    // Оптимизация для документов
    initDocumentFilters();
});

/**
 * Преобразует таблицы в адаптивный вид для мобильных устройств
 */
function initResponsiveTables() {
    // Добавляем data-label для ячеек таблиц для мобильного вида
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        // Пропускаем таблицы, которые уже имеют специальную адаптацию
        if (table.classList.contains('no-responsive-transform')) {
            return;
        }
        
        // На мобильных устройствах добавляем класс для карточного вида
        if (window.innerWidth <= 768) {
            table.classList.add('table-card-view');
        }
        
        // Добавляем data-label для всех ячеек таблицы
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (headers[index]) {
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });
    });
}

/**
 * Улучшает навигацию по вкладкам на мобильных устройствах
 */
function initTabsNavigation() {
    const tabsContainer = document.querySelector('.nav-tabs');
    if (!tabsContainer) return;
    
    // Прокрутка к активной вкладке
    function scrollToActiveTab() {
        const activeTab = tabsContainer.querySelector('.nav-link.active');
        if (activeTab) {
            // Рассчитываем позицию для центрирования активной вкладки
            const scrollLeft = activeTab.offsetLeft - (tabsContainer.clientWidth / 2) + (activeTab.offsetWidth / 2);
            tabsContainer.scrollLeft = Math.max(0, scrollLeft);
        }
    }
    
    // Проверка наличия горизонтальной прокрутки
    function checkScrollIndicator() {
        const hasScroll = tabsContainer.scrollWidth > tabsContainer.clientWidth;
        const tabsWrapper = tabsContainer.closest('.nav-tabs-wrapper');
        let indicator = document.querySelector('.nav-tabs-scroll-indicator');
        
        if (!indicator && hasScroll && tabsWrapper) {
            // Создаем индикатор, если его нет
            indicator = document.createElement('div');
            indicator.className = 'nav-tabs-scroll-indicator';
            tabsWrapper.appendChild(indicator);
        }
        
        if (indicator) {
            indicator.style.display = hasScroll ? 'block' : 'none';
        }
    }
    
    // Обработка событий для вкладок
    const tabLinks = tabsContainer.querySelectorAll('.nav-link');
    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Прокрутка к активной вкладке после активации
            setTimeout(scrollToActiveTab, 100);
        });
    });
    
    // Инициализация при загрузке страницы
    setTimeout(function() {
        scrollToActiveTab();
        checkScrollIndicator();
    }, 100);
    
    // Обновление при изменении размеров окна
    window.addEventListener('resize', function() {
        scrollToActiveTab();
        checkScrollIndicator();
    });
}

/**
 * Улучшает отображение карточек файлов
 */
function initFileCards() {
    // Добавляем обработку нажатия на карточки для превью
    const fileCards = document.querySelectorAll('.file-item .card');
    fileCards.forEach(card => {
        // Добавляем эффект нажатия для карточек
        card.addEventListener('mousedown', function() {
            this.classList.add('card-pressed');
        });
        
        card.addEventListener('mouseup', function() {
            this.classList.remove('card-pressed');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('card-pressed');
        });
    });
}

/**
 * Улучшает просмотр фотографий на мобильных устройствах
 */
function initPhotoViewer() {
    // Ищем контейнеры с фотографиями
    const photoItems = document.querySelectorAll('.photo-item');
    if (photoItems.length === 0) return;
    
    // Добавляем возможность открытия фото в полноэкранном режиме при клике
    photoItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Если клик был по кнопке удаления, не открываем превью
            if (e.target.closest('.delete-photo-btn')) {
                return;
            }
            
            const imgUrl = this.getAttribute('data-full-url') || 
                          this.style.backgroundImage.replace(/url\(['"](.+?)['"]\)/, '$1');
            
            if (imgUrl) {
                // Показываем фото в режиме предпросмотра
                const modal = document.createElement('div');
                modal.className = 'modal fade photo-preview-modal';
                modal.setAttribute('tabindex', '-1');
                modal.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-body p-0">
                                <button type="button" class="btn-close position-absolute top-0 end-0 m-2 bg-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                <img src="${imgUrl}" class="img-fluid w-100">
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Показываем модальное окно
                const modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();
                
                // Удаляем из DOM после закрытия
                modal.addEventListener('hidden.bs.modal', function() {
                    document.body.removeChild(modal);
                });
            }
        });
    });
}

/**
 * Улучшает работу с фильтрами документов
 */
function initDocumentFilters() {
    const filterButtons = document.querySelectorAll('.document-filter .btn');
    if (filterButtons.length === 0) return;
    
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Снимаем активное состояние со всех кнопок
            filterButtons.forEach(b => b.classList.remove('active'));
            
            // Активируем текущую кнопку
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            const documentItems = document.querySelectorAll('.document-item');
            
            documentItems.forEach(item => {
                if (filter === 'all' || item.getAttribute('data-type') === filter) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}
