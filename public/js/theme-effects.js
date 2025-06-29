/**
 * theme-effects.js - интерактивные эффекты для темы с цветом #01ACFF
 */

document.addEventListener('DOMContentLoaded', function() {
    // Добавляем класс для плавного появления элементов
    document.querySelectorAll('.card, .alert, .modal').forEach(element => {
        element.classList.add('fade-in');
    });

    // Эффект для карточек при наведении
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 20px rgba(1, 172, 255, 0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });

    // Эффект пульсации для элементов с классом .notification-badge
    document.querySelectorAll('.notification-badge, .alert-important, .highlight-element').forEach(element => {
        element.classList.add('notification-badge');
    });

    // Эффект волны для кнопок
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            // Создаем элемент для эффекта волны
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            
            // Позиционируем в месте клика
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            // Добавляем эффект и удаляем после завершения анимации
            this.appendChild(ripple);
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Добавляем класс active для текущей страницы в навигации
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        const linkPath = link.getAttribute('href');
        if (linkPath && currentPath.includes(linkPath) && linkPath !== '/') {
            link.classList.add('active');
        } else if (linkPath === '/' && currentPath === '/') {
            link.classList.add('active');
        }
    });

    // Инициализация всплывающих подсказок Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Инициализация всплывающих уведомлений Bootstrap
    const toastElList = [].slice.call(document.querySelectorAll('.toast'))
    toastElList.forEach(function (toastEl) {
        new bootstrap.Toast(toastEl)
    });

    // Анимированное появление элементов при прокрутке
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150; // расстояние от нижней части экрана, когда элемент должен стать видимым
            
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('fade-in');
            }
        });
    }
    
    // Запускаем функцию при загрузке и прокрутке
    animateOnScroll();
    window.addEventListener('scroll', animateOnScroll);
    
    // Эффекты для вкладок
    document.querySelectorAll('.nav-tabs .nav-link').forEach(tab => {
        tab.addEventListener('click', function() {
            // Удаляем активный класс у всех вкладок
            document.querySelectorAll('.nav-tabs .nav-link').forEach(t => {
                t.classList.remove('active');
            });
            
            // Добавляем активный класс текущей вкладке
            this.classList.add('active');
        });
    });
    
    // Показываем содержимое вкладок с анимацией
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tabTrigger => {
        tabTrigger.addEventListener('shown.bs.tab', function(e) {
            const targetTab = document.querySelector(e.target.getAttribute('href'));
            if (targetTab) {
                targetTab.classList.add('fade-in');
            }
        });
    });
    
    // Анимация для прогресс-бара
    document.querySelectorAll('.progress-bar').forEach(bar => {
        const targetWidth = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.width = targetWidth;
        }, 300);
    });
    
    // Обработка загрузки изображений
    document.querySelectorAll('img').forEach(img => {
        if (!img.complete) {
            img.classList.add('loading-shimmer');
            
            img.addEventListener('load', function() {
                this.classList.remove('loading-shimmer');
                this.classList.add('fade-in');
            });
        }
    });
    
    // Добавляем класс hover-effect ко всем элементам, которые должны реагировать на наведение
    document.querySelectorAll('.list-group-item, .dropdown-item, .sidebar ul li a').forEach(element => {
        element.classList.add('hover-effect');
    });
});
