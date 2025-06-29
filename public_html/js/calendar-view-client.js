// Скрипт для работы календарного вида графика для клиентского интерфейса
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
        
        console.log('Отправка запроса к API календаря:', calendarApiUrl);
        console.log('Параметры:', {startDate, endDate});
        
        fetch(calendarApiUrl + '?start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate), {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin' // Добавляем отправку куки для аутентификации
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
        
        // Получаем необходимые контейнеры
        const calendarContainer = document.getElementById('calendar-container');
        const monthsContainer = calendarContainer.querySelector('.calendar-months');
        const daysContainer = calendarContainer.querySelector('.calendar-days');
        const taskNamesContainer = calendarContainer.querySelector('.task-names');
        const tasksContainer = calendarContainer.querySelector('.calendar-tasks');
        
        // Очищаем контейнеры
        monthsContainer.innerHTML = '';
        daysContainer.innerHTML = '';
        taskNamesContainer.innerHTML = '';
        tasksContainer.innerHTML = '';
        
        // Отображаем месяцы
        data.months.forEach(month => {
            const monthElement = document.createElement('div');
            monthElement.className = 'calendar-month';
            monthElement.style.width = month.width + 'px';
            monthElement.innerHTML = month.name;
            monthsContainer.appendChild(monthElement);
        });
        
        // Отображаем дни
        data.days.forEach(day => {
            const dayElement = document.createElement('div');
            dayElement.className = `calendar-day ${day.isWeekend ? 'weekend' : ''}`;
            dayElement.innerHTML = day.day;
            daysContainer.appendChild(dayElement);
        });
        
        // Отображаем задачи
        data.tasks.forEach((task, index) => {
            // Создаем строку с названием задачи
            const taskNameElement = document.createElement('div');
            taskNameElement.className = 'task-name';
            taskNameElement.innerHTML = `<span class="task-number">${index + 1}.</span> ${task.name}`;
            taskNamesContainer.appendChild(taskNameElement);
            
            // Создаем строку с ячейками задач по дням
            const taskRowElement = document.createElement('div');
            taskRowElement.className = 'task-row';
            
            data.days.forEach((day, dayIndex) => {
                const taskCellElement = document.createElement('div');
                taskCellElement.className = 'task-cell';
                
                // Определяем, попадает ли день в период выполнения задачи
                if (task.days.includes(dayIndex)) {
                    taskCellElement.classList.add('active');
                    
                    // Определяем тип ячейки
                    if (task.startDay === dayIndex) {
                        taskCellElement.classList.add('start');
                    }
                    if (task.endDay === dayIndex) {
                        taskCellElement.classList.add('end');
                    }
                }
                
                taskRowElement.appendChild(taskCellElement);
            });
            
            tasksContainer.appendChild(taskRowElement);
        });
        
        // Показываем календарь
        calendarContainer.classList.remove('d-none');
    }
    
    // Создание таблицы для PDF версии
    function createPdfTable() {
        // Создаем оптимизированную для печати таблицу
        const startDate = document.getElementById('calendar-date-from').value;
        const endDate = document.getElementById('calendar-date-to').value;
        
        // Получаем элементы календаря
        const monthsContainer = document.querySelector('.calendar-months');
        const daysContainer = document.querySelector('.calendar-days');
        const taskNamesContainer = document.querySelector('.task-names');
        const tasksContainer = document.querySelector('.calendar-tasks');
        
        // Создаем таблицу
        let tableHTML = `<table border="1" cellpadding="1" cellspacing="0">
            <thead>
                <tr>
                    <th>№</th>
                    <th>Задача</th>`;
        
        // Добавляем дни в заголовок
        const days = daysContainer.querySelectorAll('.calendar-day');
        days.forEach(day => {
            const isWeekend = day.classList.contains('weekend');
            tableHTML += `<th class="task-cell${isWeekend ? ' weekend' : ''}">${day.innerHTML}</th>`;
        });
        
        tableHTML += `</tr>
            </thead>
            <tbody>`;
        
        // Добавляем строки задач
        const taskNames = taskNamesContainer.querySelectorAll('.task-name');
        const taskRows = tasksContainer.querySelectorAll('.task-row');
        
        taskNames.forEach((taskName, index) => {
            const taskRow = taskRows[index];
            const taskNumber = taskName.querySelector('.task-number')?.textContent || (index + 1) + '.';
            const taskNameText = taskName.textContent.replace(taskNumber, '').trim();
            
            tableHTML += `<tr>
                <td>${taskNumber}</td>
                <td>${taskNameText}</td>`;
            
            // Добавляем ячейки задач
            const taskCells = taskRow.querySelectorAll('.task-cell');
            taskCells.forEach(cell => {
                const classes = [];
                if (cell.classList.contains('active')) classes.push('active');
                if (cell.classList.contains('start')) classes.push('start');
                if (cell.classList.contains('end')) classes.push('end');
                
                tableHTML += `<td class="task-cell${classes.length > 0 ? ' ' + classes.join(' ') : ''}"></td>`;
            });
            
            tableHTML += `</tr>`;
        });
        
        tableHTML += `</tbody>
        </table>`;
        
        return tableHTML;
    }
    
    // Генерация и скачивание PDF
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
        
        // Получаем заголовок проекта (адаптация для клиентского интерфейса)
        const projectTitle = document.querySelector('h1') ? document.querySelector('h1').textContent : 'Календарный график';
        
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
                    
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        page-break-inside: avoid;
                    }
                    
                    thead {
                        display: table-header-group;
                    }
                    
                    tbody {
                        display: table-row-group;
                    }
                    
                    tr {
                        page-break-inside: avoid;
                        height: 18px;
                    }
                    
                    th, td {
                        border: 1px solid #000;
                        padding: 1px;
                        height: 16px;
                    }
                    
                    th:first-child, td:first-child {
                        width: 25px;
                        text-align: center;
                    }
                    
                    th:nth-child(2), td:nth-child(2) {
                        text-align: left;
                        width: 200px;
                        font-size: 6pt;
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
                    
                    .active {
                        background-color: #000;
                    }
                    
                    .weekend {
                        background-color: #f5f5f5;
                    }
                </style>
            </head>
            <body>
                <div class="pdf-header">
                    <h1 style="font-size: 14pt; margin: 0 0 5px 0;">${projectTitle}</h1>
                    <h2 style="font-size: 10pt; margin: 0 0 10px 0;">Календарный график (${startDate} - ${endDate})</h2>
                </div>
                <div class="calendar-container">
                    ${calendarTable}
                </div>
                <div class="pdf-footer">
                    Дата создания: ${new Date().toLocaleDateString('ru')}
                </div>
            </body>
            </html>
        `;
        
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
        console.log('Отправка запроса на генерацию PDF:', '/client/projects/generate-pdf');
        
        fetch('/client/projects/generate-pdf', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/pdf, application/json'
            },
            body: JSON.stringify({
                html: html,
                filename: uniqueFilename
            }),
            credentials: 'same-origin' // Добавляем отправку куки для аутентификации
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
            
            // Очистка ресурсов
            window.URL.revokeObjectURL(url);
            setTimeout(() => {
                document.body.removeChild(a);
                document.body.removeChild(loadingElement);
            }, 100);
        })
        .catch(error => {
            console.error('Ошибка генерации PDF:', error);
            loadingElement.querySelector('.spinner-border').remove();
            loadingElement.querySelector('p').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <strong>Ошибка:</strong> ${error.message || 'Не удалось создать PDF'}
                </div>
            `;
            
            const closeButton = document.createElement('button');
            closeButton.className = 'btn btn-primary mt-2';
            closeButton.innerText = 'Закрыть';
            closeButton.onclick = () => document.body.removeChild(loadingElement);
            loadingElement.querySelector('.text-center').appendChild(closeButton);
        });
    }
});
