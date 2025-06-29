/**
 * Система пошагового обучения для платформы ремонта
 * Подробные туры для всех страниц с обязательным прохождением для клиентов
 */

// Импортируем Intro.js
import introJs from "intro.js";

// Функция для определения роли пользователя
function getUserRole() {
    // Получаем роль из data-атрибута на body или другом элементе
    const userRole = document.body.dataset.userRole || "";
    return userRole;
}

// Базовые настройки для всех туров
const tourDefaults = {
    nextLabel: "Далее",
    prevLabel: "Назад",
    doneLabel: "Готово",
    showStepNumbers: true,
    showBullets: true,
    showProgress: true,
    scrollToElement: true,
    disableInteraction: false,
    exitOnOverlayClick: false,
    exitOnEsc: false,
    hidePrev: true,
    hideNext: false
};

// Базовые настройки для обязательных туров (для клиентов)
const mandatoryTourDefaults = {
    ...tourDefaults,
    skipLabel: "", // Скрываем кнопку пропуска для клиентов
    exitOnOverlayClick: false,
    exitOnEsc: false,
    tooltipClass: "client-tour"
};

// Словарь туров по ключам страниц и ролей
const tours = {
    "partner": {},
    "client": {}
};

// Функция для запуска тура
function startTour(pageKey) {
    const userRole = getUserRole();
    
    // Если роль не партнер и не клиент, не запускаем тур
    if (userRole !== "partner" && userRole !== "client") {
        console.log("Пользователь не партнер и не клиент, тур не запущен");
        return;
    }
    
    // Создаем экземпляр IntroJS
    if (typeof introJs === "undefined") {
        console.error("Библиотека IntroJS не найдена");
        return;
    }
    
    const tour = introJs();
    
    // Определение текущего тура из глобального объекта
    let tourSteps = [];
    if (tours[userRole] && tours[userRole][pageKey]) {
        tourSteps = tours[userRole][pageKey];
        console.log(`Тур для роли ${userRole} и страницы ${pageKey} найден`);
    } else {
        console.log(`Тур для роли ${userRole} и страницы ${pageKey} не найден`);
        return;
    }
    
    // Выбираем настройки в зависимости от роли пользователя
    const tourOptions = userRole === "client" 
        ? { ...mandatoryTourDefaults, steps: tourSteps }
        : { ...tourDefaults, steps: tourSteps };
    
    // Применяем настройки
    tour.setOptions(tourOptions);
    
    // Для клиента блокируем возможность пропуска тура
    if (userRole === "client") {
        tour.onexit(function() {
            // Возвращаем пользователя к туру, если он пытается его закрыть
            setTimeout(() => {
                startTour(pageKey);
            }, 100);
        });
    }
    
    // Запускаем тур
    tour.start();
    
    // Сохраняем информацию о просмотре тура только при полном завершении
    tour.oncomplete(function() {
        saveTourCompletion(userRole, pageKey);
    });
    
    // Для партнера сохраняем прогресс при простом выходе, для клиента - только при завершении
    if (userRole !== "client") {
        tour.onexit(function() {
            saveTourCompletion(userRole, pageKey);
        });
    }
}

// Функция для сохранения информации о завершении тура
function saveTourCompletion(role, pageKey) {
    // Сохраняем в localStorage информацию о том, что пользователь прошел тур
    const tourKey = `tour_${role}_${pageKey}_completed`;
    localStorage.setItem(tourKey, "true");
    
    // Отправляем запрос на сервер для сохранения информации
    const csrfToken = document.querySelector("meta[name=\"csrf-token\"]")?.getAttribute("content");
    if (csrfToken) {
        fetch("/api/tour/completed", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body: JSON.stringify({
                role: role,
                page: pageKey
            })
        })
        .then(response => {
            if (!response.ok) {
                console.error("Ошибка при сохранении прогресса тура");
                return;
            }
            console.log(`Тур для роли ${role} и страницы ${pageKey} отмечен как пройденный`);
        })
        .catch(error => {
            console.error("Ошибка при сохранении прогресса тура:", error);
        });
    }
}

// Функция для проверки, нужно ли показывать тур
function shouldShowTour(pageKey) {
    const userRole = getUserRole();
    
    if (userRole !== "partner" && userRole !== "client") {
        return false;
    }
    
    // Проверяем, есть ли тур для данной страницы и роли
    const tourExists = tours[userRole] && tours[userRole][pageKey];
    
    if (!tourExists) {
        console.log(`Тур для роли ${userRole} и страницы ${pageKey} не найден`);
        return false;
    }
    
    // Проверяем, проходил ли пользователь этот тур ранее
    const tourKey = `tour_${userRole}_${pageKey}_completed`;
    const completed = localStorage.getItem(tourKey) === "true";
    
    // Тур показывается, если он существует и пользователь его еще не проходил
    return !completed;
}

// Функция для инициализации тура на странице
function initTour(pageKey) {
    console.log("Инициализация тура для страницы:", pageKey);
    if (shouldShowTour(pageKey)) {
        startTour(pageKey);
    } else {
        console.log("Тур уже был пройден или не существует");
    }
}

// Функция для запуска тура вручную
function manualStartTour(pageKey) {
    console.log("Запуск тура вручную для страницы:", pageKey);
    startTour(pageKey);
}

// Функция для сброса всех просмотренных туров
function resetAllTours() {
    // Находим все ключи в localStorage, относящиеся к турам
    const tourKeys = [];
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && key.includes("_tour_") && key.includes("_completed")) {
            tourKeys.push(key);
        }
    }
    
    // Удаляем все ключи туров
    tourKeys.forEach(key => localStorage.removeItem(key));
    console.log("Локальная информация о турах сброшена");
    
    // В профиле запрос на сброс отправляется отдельно, 
    // поэтому здесь проверяем, вызывается ли функция из профиля
    if (!window.location.pathname.includes("/profile")) {
        const csrfToken = document.querySelector("meta[name=\"csrf-token\"]")?.getAttribute("content");
        if (csrfToken) {
            fetch("/api/tour/reset", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log("Серверная информация о турах сброшена");
                } else {
                    console.error("Ошибка при сбросе информации о турах на сервере");
                }
            })
            .catch(error => {
                console.error("Ошибка при сбросе информации о турах:", error);
            });
        }
    }
}

// Экспорт туров для использования в других файлах
export default tours;

// Экспортируем функции и константы
export { 
    getUserRole, 
    tourDefaults, 
    mandatoryTourDefaults,
    initTour, 
    manualStartTour, 
    resetAllTours 
};
