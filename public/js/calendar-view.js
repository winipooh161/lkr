// Скрипт для работы календарного вида графика
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация календаря
    loadCalendarData();
    
    // Обработчики фильтрации
    document.getElementById('apply-calendar-filter').addEventListener('click', function() {
        loadCalendarData();
    });
    
    // Обработчик кнопки скачивания PDF
    document.getElementById('download-calendar-pdf').addEventListener('click', function() {
        generatePDF();
    });
    
    // Быстрый выбор месяца
    document.getElementById('month-quick-select').addEventListener('change', function() {
        const value = this.value;
        if (!value) return;
        
        const [month, year] = value.split('.');
        const startDate = new Date(year, month - 1, 1);
        const endDate = new Date(year, month, 0);
        
        document.getElementById('calendar-date-from').value = formatDate(startDate);
        document.getElementById('calendar-date-to').value = formatDate(endDate);
        
        loadCalendarData();
    });
    
    // Форматирование даты
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Загрузка данных календаря
    function loadCalendarData() {
        const startDate = document.getElementById('calendar-date-from').value;
        const endDate = document.getElementById('calendar-date-to').value;
        
        if (!startDate || !endDate) {
            showError('Укажите диапазон дат');
            return;
        }
        
        document.getElementById('calendar-loading').classList.remove('d-none');
        document.getElementById('calendar-container').classList.add('d-none');
        document.getElementById('calendar-error').classList.add('d-none');
        
        // Получаем CSRF-токен из meta-тега
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch(calendarApiUrl + '?start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate), {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                renderCalendar(response.data);
            } else {
                showError(response.message || 'Ошибка при загрузке данных');
            }
        })
        .catch(error => {
            console.error('Ошибка загрузки данных:', error);
            showError('Произошла ошибка при загрузке данных');
        })
        .finally(() => {
            document.getElementById('calendar-loading').classList.add('d-none');
        });
    }
    
    // Отображение ошибки
    function showError(message) {
        const errorElement = document.getElementById('calendar-error');
        errorElement.innerHTML = message;
        errorElement.classList.remove('d-none');
        document.getElementById('calendar-container').classList.add('d-none');
    }
      // Отрисовка календаря
    function renderCalendar(data) {
        if (!data.days || !data.months || !data.tasks) {
            showError('Некорректные данные для отображения календаря');
            return;
        }
        
        // Проверка на пустые данные
        if (data.days.length === 0) {
            showError('Нет данных для отображения. Выберите другой диапазон дат.');
            return;
        }
        
        // Показываем календарь
        document.getElementById('calendar-container').classList.remove('d-none');
        
        // Очищаем контейнеры
        document.querySelector('.calendar-months').innerHTML = '';
        document.querySelector('.calendar-days').innerHTML = '';
        document.querySelector('.calendar-tasks').innerHTML = '';
          // Отрисовка месяцев и добавление к ним правильной ширины
        data.months.forEach(function(month) {
            const monthElement = document.createElement('div');
            monthElement.className = 'month-cell';
            monthElement.style.width = `${month.days * 30}px`;
            monthElement.textContent = month.name;
            document.querySelector('.calendar-months').appendChild(monthElement);
        });
        
        // Отрисовка дней с точно такой же шириной как у месяцев для правильного выравнивания
        data.days.forEach(function(day) {
            const dayElement = document.createElement('div');
            dayElement.className = `day-cell ${day.isWeekend ? 'weekend-day' : ''}`;
            dayElement.dataset.date = day.date;
            dayElement.textContent = day.day;
            dayElement.style.width = '30px'; // Фиксированная ширина ячейки дня
            document.querySelector('.calendar-days').appendChild(dayElement);
        });
        
        // Отрисовка задач
        data.tasks.forEach(function(task) {
            const taskRow = document.createElement('div');
            taskRow.className = 'calendar-task';
            taskRow.dataset.taskId = task.id;
            
            // Название задачи
            const taskName = document.createElement('div');
            taskName.className = 'task-name';
            taskName.title = task.name;
            
            if (task.isOverdue) {
                const icon = document.createElement('i');
                icon.className = 'fas fa-exclamation-triangle text-danger me-1';
                taskName.appendChild(icon);
            }
            
            const nameText = document.createTextNode(task.name);
            taskName.appendChild(nameText);
            
            const badge = document.createElement('span');
            badge.className = `badge ${task.statusClass} ms-1`;
            badge.textContent = task.displayStatus;
            taskName.appendChild(badge);
            
            taskRow.appendChild(taskName);
            
            // Дни задачи
            const taskDays = document.createElement('div');
            taskDays.className = 'task-days';
            
            data.days.forEach(function(day) {
                const dayDate = day.date;
                const taskStartDate = task.start_date;
                const taskEndDate = task.end_date;
                
                const dayCell = document.createElement('div');
                dayCell.className = 'task-day';
                
                // Если день входит в период выполнения задачи
                if (dayDate >= taskStartDate && dayDate <= taskEndDate) {
                    const taskProgress = document.createElement('div');
                    let statusClass = '';
                    
                    if (task.isOverdue) {
                        statusClass = 'status-overdue';
                    } else {
                        switch(task.status.toLowerCase()) {
                            case 'готово':
                                statusClass = 'status-done';
                                break;
                            case 'в работе':
                                statusClass = 'status-in-progress';
                                break;
                            case 'ожидание':
                                statusClass = 'status-waiting';
                                break;
                            case 'отменено':
                                statusClass = 'status-canceled';
                                break;
                            default:
                                statusClass = 'status-in-progress';
                        }
                    }
                    
                    taskProgress.className = `task-progress ${statusClass}`;
                    taskProgress.title = `${task.name}: ${task.displayStatus}`;
                    dayCell.appendChild(taskProgress);
                }
                
                taskDays.appendChild(dayCell);
            });
            
            taskRow.appendChild(taskDays);
            document.querySelector('.calendar-tasks').appendChild(taskRow);
        });
        
        document.getElementById('calendar-container').classList.remove('d-none');
    }
    
    // Создает HTML-таблицу для PDF на основе текущих данных календаря
    function createPdfTable() {
        // Получаем текущие данные из DOM
        const data = {
            months: [],
            days: [],
            tasks: []
        };
        
        // Получаем месяцы
        document.querySelectorAll('.month-cell').forEach(monthElem => {
            data.months.push({
                name: monthElem.textContent,
                width: monthElem.style.width
            });
        });
        
        // Получаем дни
        document.querySelectorAll('.day-cell').forEach(dayElem => {
            data.days.push({
                day: dayElem.textContent,
                date: dayElem.dataset.date,
                isWeekend: dayElem.classList.contains('weekend-day')
            });
        });
        
        // Получаем задачи
        document.querySelectorAll('.calendar-task').forEach(taskElem => {
            const taskId = taskElem.dataset.taskId;
            const taskNameElem = taskElem.querySelector('.task-name');
            const taskName = taskNameElem.title || taskNameElem.textContent;
            const badgeElem = taskElem.querySelector('.badge');
            const displayStatus = badgeElem ? badgeElem.textContent : '';
            const statusClass = badgeElem ? badgeElem.className.split(' ').find(c => c.startsWith('bg-')) : '';
            const isOverdue = taskElem.querySelector('.fa-exclamation-triangle') !== null;
            
            // Создаем массив дней
            const taskDays = [];
            const dayElements = taskElem.querySelectorAll('.task-day');
            
            dayElements.forEach(dayCell => {
                const progressElem = dayCell.querySelector('.task-progress');
                const hasProgress = progressElem !== null;
                const statusClass = hasProgress ? progressElem.className.split(' ').find(c => c.startsWith('status-')) : '';
                
                taskDays.push({
                    hasProgress: hasProgress,
                    statusClass: statusClass
                });
            });
            
            data.tasks.push({
                id: taskId,
                name: taskName,
                displayStatus: displayStatus,
                statusClass: statusClass,
                isOverdue: isOverdue,
                days: taskDays
            });
        });
          // Создаем таблицу HTML с оптимизацией для одной страницы
        let tableHtml = '<table>';
        
        // Создаем thead для предотвращения разрывов
        tableHtml += '<thead>';
        
        // Строка с месяцами
        tableHtml += '<tr>';
        tableHtml += '<th>Задача</th>';
        
        let currentMonth = '';
        let colspanCount = 0;
        
        data.days.forEach((day, index) => {
            // Определяем месяц для каждого дня
            const date = new Date(day.date);
            const month = date.toLocaleDateString('ru', { month: 'long' });
            
            // Если месяц изменился или это первый день
            if (month !== currentMonth || index === 0) {
                // Если не первый месяц, закрываем предыдущий
                if (index > 0) {
                    tableHtml += `<th colspan="${colspanCount}">${currentMonth}</th>`;
                }
                currentMonth = month;
                colspanCount = 1;
            } else {
                colspanCount++;
            }
            
            // Для последнего дня
            if (index === data.days.length - 1) {
                tableHtml += `<th colspan="${colspanCount}">${currentMonth}</th>`;
            }
        });
        
        tableHtml += '</tr>';
        
        // Строка с днями
        tableHtml += '<tr>';
        tableHtml += '<th></th>';
        
        data.days.forEach(day => {
            const weekendStyle = day.isWeekend ? ' style="background-color:#ffe0e0;"' : '';
            tableHtml += `<th class="task-cell"${weekendStyle}>${day.day}</th>`;
        });
        
        tableHtml += '</tr>';
        tableHtml += '</thead>';
        
        // Создаем tbody
        tableHtml += '<tbody>';
        
        // Строки задач
        data.tasks.forEach(task => {
            tableHtml += '<tr>';
            
            // Ячейка с названием задачи - сокращенная версия
            let taskNameCell = '<td>';
            
            if (task.isOverdue) {
                taskNameCell += '⚠ ';
            }
            
            // Обрезаем длинные названия задач
            let taskNameShort = task.name.length > 25 ? task.name.substring(0, 22) + '...' : task.name;
            taskNameCell += taskNameShort;
            
            // Добавляем статус более компактно
            let statusColor = '#007bff'; // По умолчанию синий
            if (task.isOverdue) {
                statusColor = '#dc3545';
            } else {
                switch (task.displayStatus.toLowerCase()) {
                    case 'готово':
                        statusColor = '#28a745';
                        break;
                    case 'в работе':
                        statusColor = '#007bff';
                        break;
                    case 'ожидание':
                        statusColor = '#ffc107';
                        break;
                    case 'отменено':
                        statusColor = '#6c757d';
                        break;
                }
            }
            
            taskNameCell += ` <span style="display:inline-block; width:8px; height:8px; background-color:${statusColor}; border-radius:50%; margin-left:3px;"></span>`;
            taskNameCell += '</td>';
            
            tableHtml += taskNameCell;
            
            // Ячейки дней задачи
            task.days.forEach(day => {
                if (day.hasProgress) {
                    let bgColor = '#007bff'; // По умолчанию синий
                    
                    if (day.statusClass === 'status-overdue') {
                        bgColor = '#dc3545';
                    } else if (day.statusClass === 'status-done') {
                        bgColor = '#28a745';
                    } else if (day.statusClass === 'status-in-progress') {
                        bgColor = '#007bff';
                    } else if (day.statusClass === 'status-waiting') {
                        bgColor = '#ffc107';
                    } else if (day.statusClass === 'status-canceled') {
                        bgColor = '#6c757d';
                    }
                    
                    tableHtml += `<td class="task-cell" style="background-color:${bgColor};"></td>`;
                } else {
                    tableHtml += '<td class="task-cell"></td>';
                }
            });
            
            tableHtml += '</tr>';
        });
        
        tableHtml += '</tbody>';
        tableHtml += '</table>';
        
        return tableHtml;
    }
    
    // Генерация PDF-документа
    function generatePDF() {
        // Проверяем, загружен ли календарь
        if (document.querySelector('.calendar-tasks').children.length === 0) {
            alert('Сначала загрузите данные календаря');
            return;
        }
        
        // Показываем индикатор загрузки
        const loadingElement = document.createElement('div');
        loadingElement.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-50';
        loadingElement.style.zIndex = '9999';
        loadingElement.innerHTML = `
            <div class="text-center bg-white p-4 rounded">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-2">Создание PDF...</p>
            </div>
        `;
        document.body.appendChild(loadingElement);
        
        // Получаем диапазон дат для имени файла
        const startDate = document.getElementById('calendar-date-from').value;
        const endDate = document.getElementById('calendar-date-to').value;
        const dateRange = startDate === endDate ? startDate : `${startDate}_${endDate}`;
        
        // Получаем заголовок проекта
        const projectTitle = document.querySelector('h3').textContent;
        
        // Создаем оптимизированную для печати таблицу
        const calendarTable = createPdfTable();
          // Создаем полный HTML для PDF с использованием простой таблицы вместо flexbox
        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <title>Календарный график ${dateRange}</title>
                <style>
                    @page {
                        size: A3 landscape;
                        margin: 5mm;
                    }
                    
                    body {
                        font-family: "DejaVu Sans", Arial, sans-serif;
                        margin: 0;
                        padding: 5px;
                        font-size: 8pt;
                        line-height: 1.2;
                    }
                    
                    .pdf-header {
                        text-align: center;
                        margin-bottom: 10px;
                        page-break-inside: avoid;
                    }
                    
                    .pdf-header h1 {
                        font-size: 14pt;
                        margin: 0 0 5px 0;
                        padding: 0;
                    }
                    
                    .pdf-header p {
                        font-size: 10pt;
                        margin: 0;
                        padding: 0;
                    }
                    
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        table-layout: fixed;
                        page-break-inside: avoid;
                        page-break-after: avoid;
                        font-size: 7pt;
                    }
                    
                    thead tr {
                        page-break-inside: avoid;
                        page-break-after: avoid;
                    }
                    
                    tbody tr {
                        page-break-inside: avoid;
                        height: 20px;
                    }
                    
                    td, th {
                        border: 1px solid #000;
                        padding: 2px;
                        vertical-align: middle;
                        text-align: center;
                        height: 18px;
                        max-height: 18px;
                        overflow: hidden;
                    }
                      td:first-child, th:first-child {
                        text-align: left;
                        width: 200px;
                        min-width: 200px;
                        max-width: 200px;
                        font-size: 6pt;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    
                    .task-cell {
                        width: 15px;
                        min-width: 15px;
                        max-width: 15px;
                        padding: 0;
                        height: 18px;
                    }
                    
                    .pdf-footer {
                        text-align: right;
                        margin-top: 5px;
                        font-size: 7pt;
                        page-break-inside: avoid;
                    }
                    
                    /* Принудительно запрещаем разрывы страниц */
                    * {
                        page-break-inside: avoid !important;
                    }
                    
                    .calendar-container {
                        page-break-inside: avoid !important;
                    }
                </style>
            </head>
            <body>
                <div class="pdf-header">
                    <h1>${projectTitle}</h1>
                    <p>Период: ${startDate} - ${endDate}</p>
                </div>
                
                <div class="calendar-container">
                    ${calendarTable}
                </div>
                
                <div class="pdf-footer">
                    <p>Дата создания: ${new Date().toLocaleDateString('ru-RU')}</p>
                </div>
            </body>
            </html>
        `;
        
        // Получаем CSRF-токен из meta-тега
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
          // Добавляем информацию о генерации в консоль для отладки
        console.log(`Генерация PDF: ${dateRange}, HTML длина: ${html.length}`);
        
        // Показываем сообщение пользователю
        loadingElement.querySelector('p').innerText = 'Создание PDF... Это может занять до минуты';
        
        // Уникальное имя файла с текущей датой и временем для избежания проблем с кешированием
        const timestamp = new Date().getTime();
        const uniqueFilename = `Календарный_график_${dateRange}_${timestamp}.pdf`;
        
        // Устанавливаем таймаут для запроса
        const timeout = setTimeout(() => {
            loadingElement.querySelector('p').innerText = 'Генерация PDF занимает больше времени, чем обычно... Пожалуйста, подождите';
        }, 10000); // показываем сообщение, если процесс занимает больше 10 секунд
        
        // Отправляем запрос на сервер для создания PDF с улучшенной обработкой ошибок
        fetch('/partner/projects/generate-pdf', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/pdf, application/json'
            },
            body: JSON.stringify({
                html: html,
                filename: uniqueFilename
            })
        })
        .then(response => {
            clearTimeout(timeout);
            
            // Проверяем тип контента ответа
            const contentType = response.headers.get('content-type');
            
            if (!response.ok) {
                if (contentType && contentType.includes('application/json')) {
                    // Если сервер вернул JSON с ошибкой, анализируем его
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Ошибка при создании PDF');
                    });
                }
                throw new Error(`Ошибка сервера: ${response.status} ${response.statusText}`);
            }
            
            // Если контент - не PDF, это может быть ошибка
            if (contentType && !contentType.includes('application/pdf')) {
                console.warn('Сервер вернул не PDF контент:', contentType);
            }
            
            return response.blob();
        })
        .then(blob => {
            if (blob.size < 1000) {
                // Если файл слишком маленький, это, вероятно, ошибка
                console.error('Подозрительно маленький PDF:', blob.size, 'байт');
                throw new Error('Сгенерированный PDF некорректен. Попробуйте уменьшить период дат.');
            }
            
            // Создаем URL для скачивания
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = uniqueFilename;
            document.body.appendChild(a);
            a.click();
            
            // Очищаем ресурсы после задержки
            setTimeout(() => {
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            }, 100);
            
            // Показываем сообщение об успехе
            loadingElement.querySelector('.spinner-border').classList.add('d-none');
            loadingElement.querySelector('p').innerHTML = 'PDF успешно создан!<br>Если скачивание не началось автоматически, проверьте настройки браузера.';
            
            // Добавляем кнопку закрытия
            const closeButton = document.createElement('button');
            closeButton.className = 'btn btn-primary mt-2';
            closeButton.innerText = 'Закрыть';
            closeButton.onclick = () => document.body.removeChild(loadingElement);
            loadingElement.querySelector('.text-center').appendChild(closeButton);
            
            // Автоматически закрываем через 3 секунды
            setTimeout(() => {
                if (document.body.contains(loadingElement)) {
                    document.body.removeChild(loadingElement);
                }
            }, 3000);
        })
        .catch(error => {
            clearTimeout(timeout);
            console.error('Ошибка при создании PDF:', error);
            
            // Показываем детальное сообщение об ошибке
            loadingElement.querySelector('.spinner-border').classList.add('d-none');
            loadingElement.querySelector('p').innerHTML = `
                <div class="alert alert-danger">
                    <strong>Ошибка при создании PDF:</strong><br>
                    ${error.message || 'Неизвестная ошибка'}
                    <br><small>Попробуйте выбрать меньший диапазон дат или обновить страницу.</small>
                </div>
            `;
            
            // Добавляем кнопку закрытия
            const closeButton = document.createElement('button');
            closeButton.className = 'btn btn-primary mt-2';
            closeButton.innerText = 'Закрыть';            closeButton.onclick = () => document.body.removeChild(loadingElement);
            loadingElement.querySelector('.text-center').appendChild(closeButton);
        });
    }
});