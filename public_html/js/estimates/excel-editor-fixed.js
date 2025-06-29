// Глобальные переменные для работы с Excel
let hot;
let currentSheetIndex = 0;
let workbook = null;
let sheets = [];
let isApplyingStyles = false;
let isFileModified = false;
let fileStructure = null;

/**
 * Инициализация редактора Excel
 * @param {string} dataUrl Опциональный URL для загрузки данных
 */
function initExcelEditor(dataUrl = null) {
    console.log('Initializing Excel editor');
    
    const container = document.getElementById('excelEditor');
    
    // Проверяем наличие контейнера
    if (!container) {
        console.error('Excel editor container not found!');
        return;
    }
    
    console.log('Container found, initializing Handsontable');
    
    // Устанавливаем фиксированную высоту
    container.style.height = '600px';
    
    // Если есть URL данных, сначала загружаем структуру файла
    if (dataUrl) {
        console.log('Loading existing file structure');
        loadExcelFileStructure(dataUrl, function(structure) {
            initHandsontableWithStructure(structure, dataUrl); // Передаем URL данных вторым параметром
        });
    } else {
        // Если это новый файл, используем стандартную структуру
        initHandsontableWithStructure({
            columnCount: 10,
            readOnlyColumns: [5, 8, 9], // Индексы колонок, которые нельзя редактировать (формулы)
            hasHeaders: true
        }, null); // Передаем null вместо URL
    }
}

/**
 * Загружает структуру Excel-файла с сервера
 * @param {string} url URL для загрузки данных
 * @param {function} callback Функция обратного вызова с результатом
 */
function loadExcelFileStructure(url, callback) {
    if (!url) {
        // Если URL не предоставлен, используем стандартную структуру
        callback({
            columnCount: 10,
            readOnlyColumns: [5, 8, 9],
            hasHeaders: true
        });
        return;
    }

    fetch(url + '?structure=true')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Ошибка загрузки: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Структура Excel получена');
                
                // Определяем структуру файла
                const structure = {
                    columnCount: data.structure.columnCount || 10,
                    readOnlyColumns: data.structure.readOnlyColumns || [5, 8, 9],
                    readOnlyCells: data.structure.readOnlyCells || [],
                    hasHeaders: data.structure.hasHeaders !== false,
                    columnWidths: data.structure.columnWidths || []
                };
                
                fileStructure = structure; // Сохраняем структуру глобально
                callback(structure);
            } else {
                console.error('Ошибка получения структуры:', data.message);
                // Используем стандартную структуру в случае ошибки
                callback({
                    columnCount: 10,
                    readOnlyColumns: [5, 8, 9],
                    hasHeaders: true
                });
            }
        })
        .catch(error => {
            console.error('Ошибка при определении структуры файла:', error);
            // Используем стандартную структуру в случае ошибки
            callback({
                columnCount: 10,
                readOnlyColumns: [5, 8, 9],
                hasHeaders: true
            });
        });
}

/**
 * Инициализирует редактор Handsontable с учетом структуры файла
 * @param {Object} structure Структура файла
 * @param {string|null} dataUrl URL для загрузки данных или null для нового файла
 */
function initHandsontableWithStructure(structure, dataUrl) {
    const container = document.getElementById('excelEditor');
    
    try {
        // Создаем экземпляр Handsontable с учетом структуры файла
        hot = new Handsontable(container, {
            licenseKey: 'non-commercial-and-evaluation',
            rowHeaders: true,
            colHeaders: true,
            contextMenu: {
                items: {
                    'row_above': {name: 'Вставить строку выше'},
                    'row_below': {name: 'Вставить строку ниже'},
                    'col_left': {name: 'Вставить столбец слева'},
                    'col_right': {name: 'Вставить столбец справа'},
                    'remove_row': {name: 'Удалить строку'},
                    'remove_col': {name: 'Удалить столбец'},
                    'separator1': Handsontable.plugins.ContextMenu.SEPARATOR,
                    'copy': {name: 'Копировать'},
                    'cut': {name: 'Вырезать'},
                    'separator2': Handsontable.plugins.ContextMenu.SEPARATOR,
                    'alignment': {
                        name: 'Выравнивание',
                        submenu: {
                            items: [
                                {key: 'alignment:left', name: 'По левому краю'},
                                {key: 'alignment:center', name: 'По центру'},
                                {key: 'alignment:right', name: 'По правому краю'},
                                {key: 'alignment:justify', name: 'По ширине'},
                                {key: 'alignment:top', name: 'По верхнему краю'},
                                {key: 'alignment:middle', name: 'По середине'},
                                {key: 'alignment:bottom', name: 'По нижнему краю'}
                            ]
                        }
                    },
                    'merge_cells': {name: 'Объединить ячейки'},
                    'unmerge_cells': {name: 'Разъединить ячейки'},
                    'borders': {name: 'Границы'}
                }
            },            height: '100%',
            width: '100%',
            rowHeights: 30,
            rowHeaderWidth: 50,
            
            // Отключаем фиксацию строк при прокрутке
            fixedRowsTop: 0,
            
            // Определяем количество столбцов для отображения
            columns: Array.from({length: structure.columnCount || 10}, (_, index) => ({
                data: index,
                type: [3, 4, 5, 6, 7, 8, 9].includes(index) ? 'numeric' : 'text',
                numericFormat: [3, 4, 5, 8, 9].includes(index) ? {
                    pattern: '0,0.00',
                    culture: 'ru-RU'
                } : undefined
            })),
            
            colWidths: function(index) {
                // Используем пользовательские ширины колонок, если они доступны
                if (structure.columnWidths && structure.columnWidths[index] !== undefined) {
                    return structure.columnWidths[index];
                }
                
                // Иначе используем стандартные ширины
                if (index === 0) return 80;       // №
                if (index === 1) return 250;      // Наименование/Позиция
                if (index === 2) return 70;       // Ед.изм.
                if (index === 3) return 80;       // Кол-во
                if (index === 4) return 100;      // Цена
                if (index === 5) return 120;      // Стоимость
                if (index === 6) return 90;       // Наценка, %
                if (index === 7) return 90;       // Скидка, %
                if (index === 8) return 120;      // Цена для заказчика
                if (index === 9) return 140;      // Стоимость для заказчика
                
                return 100; // Стандартная ширина для остальных колонок
            },            manualColumnResize: true,
            manualRowResize: true,
            // НЕ используем 'all' для избежания проблем с отображением
            stretchH: 'none',            
            // Включаем автоматический расчет ширины колонок
            autoColumnSize: false, // Отключаем автоматический размер
            autoRowSize: { syncLimit: 200 },
            mergeCells: true,
            
            // НЕ используем плагин скрытия столбцов для смет
            // hiddenColumns: {
            //     columns: [],
            //     indicators: true
            // },
            
            // Настройки для форматирования ячеек
            cells: function(row, col) {
                const cellProperties = {};
                
                // Форматирование только для строки заголовков столбцов (5-я строка, индекс 4)
                if (row === 4) {
                    // Строка с заголовками колонок
                    cellProperties.renderer = headerRenderer;
                    cellProperties.className = 'htBold htCenter';
                }
                
                // Базовое форматирование по типу данных для разных колонок
                if (col === 3 || (structure.columnCount > 5 && [3, 4, 5, structure.columnCount-2, structure.columnCount-1].includes(col))) {
                    cellProperties.numericFormat = {
                        pattern: '0,0.00',
                        culture: 'ru-RU'
                    };
                    cellProperties.type = 'numeric';
                }
                
                // Процентные колонки (если они есть)
                const percentColumns = structure.readOnlyColumns ? 
                    Array.from(Array(structure.columnCount).keys())
                        .filter(i => !structure.readOnlyColumns.includes(i) && i > 5 && i < structure.columnCount - 2) : 
                    [];
                        
                if (percentColumns.includes(col)) {
                    cellProperties.numericFormat = {
                        pattern: '0.00',
                        culture: 'ru-RU'
                    };
                    cellProperties.type = 'numeric';
                }
                
                // Выделяем жирным шрифтом ячейки в колонках Стоимость и Стоимость для заказчика
                if (col === 5 || col === 9) {
                    cellProperties.className = (cellProperties.className || '') + ' htBold';
                }
                
                // Защита ячеек с формулами от редактирования
                if (structure.readOnlyColumns && structure.readOnlyColumns.includes(col)) {
                    cellProperties.readOnly = true;
                    cellProperties.className = (cellProperties.className || '') + ' htCalculatedCell';
                }
                
                // Дополнительная защита отдельных ячеек
                if (structure.readOnlyCells) {
                    const cellKey = `${row},${col}`;
                    if (structure.readOnlyCells.includes(cellKey)) {
                        cellProperties.readOnly = true;
                        cellProperties.className = (cellProperties.className || '') + ' htReadOnlyCell';
                    }
                }
                
                // Выделение итоговой строки
                const lastRow = hot ? hot.countRows() - 1 : 0;
                if (row === lastRow) {
                    cellProperties.className = (cellProperties.className || '') + ' htTotalRow htBold';
                    cellProperties.readOnly = col !== 1; // Разрешаем редактировать только текст "ИТОГО"
                }
                
                return cellProperties;
            },            // Обработчик изменений данных в ячейках
            afterChange: function(changes, source) {
                if (changes && source !== 'loadData' && source !== 'numbering' && source !== 'calculate') {
                    changes.forEach(([row, col, oldValue, newValue]) => {
                        if (oldValue !== newValue) {
                            // Пересчитываем строку при изменениях в колонках D, E, G, H (количество, цена, наценка, скидка)
                            if (col === 3 || col === 4 || col === 6 || col === 7) {
                                if (typeof window.recalculateRow === 'function') {
                                    window.recalculateRow(row);
                                }
                            }
                            isFileModified = true;
                            if (typeof window.updateStatusIndicator === 'function') {
                                window.updateStatusIndicator();
                            }
                        }
                    });
                }
            },            // Другие обработчики
            afterRemoveRow: function() {
                if (typeof window.recalculateTotals === 'function') {
                    window.recalculateTotals();
                }
                isFileModified = true;
                if (typeof window.updateStatusIndicator === 'function') {
                    window.updateStatusIndicator();
                }
            },
            afterCreateRow: function() {
                isFileModified = true;
                if (typeof window.updateStatusIndicator === 'function') {
                    window.updateStatusIndicator();
                }
            },
            // Устанавливаем наблюдатель за изменением размера окна
            afterInit: function() {
                window.addEventListener('resize', function() {
                    if (hot) {
                        hot.render();
                        setTimeout(() => hot.render(), 100); // Дополнительный ре-рендер для надежности
                    }
                });
            }
        });
        
        console.log('Handsontable initialized with structure:', structure);
        
        // Загрузка данных или создание нового файла
        if (dataUrl) {
            console.log('Loading existing file data');
            loadExcelFile(dataUrl);
        } else {
            console.log('Creating new Excel workbook');
            createNewExcelWorkbook();
        }
        
        // Инициализируем обработчики событий для кнопок
        initEventHandlers();
          // Выполняем принудительный рендер для обеспечения правильного отображения
        setTimeout(() => {
            if (hot) {
                // Устанавливаем явные настройки ширины колонок и гарантируем, что все столбцы видны
                const columnWidths = [];
                for (let i = 0; i < structure.columnCount; i++) {
                    if (i === 0) columnWidths.push(80);       // №
                    else if (i === 1) columnWidths.push(250); // Наименование/Позиция
                    else if (i === 2) columnWidths.push(70);  // Ед.изм.
                    else if (i === 3) columnWidths.push(80);  // Кол-во
                    else if (i === 4) columnWidths.push(100); // Цена
                    else if (i === 5) columnWidths.push(120); // Стоимость
                    else if (i === 6) columnWidths.push(90);  // Наценка, %
                    else if (i === 7) columnWidths.push(90);  // Скидка, %
                    else if (i === 8) columnWidths.push(120); // Цена для заказчика
                    else if (i === 9) columnWidths.push(140); // Стоимость для заказчика
                    else columnWidths.push(100);              // Стандартная ширина
                }
                  hot.updateSettings({
                    stretchH: 'all', // Растягиваем все столбцы
                    colWidths: columnWidths, // Устанавливаем базовые размеры столбцов
                    width: '100%',
                    wordWrap: true, // Включаем перенос текста в ячейках
                    manualColumnResize: true, // Разрешаем ручное изменение ширины
                    outsideClickDeselects: false, // Предотвращаем случайное снятие выделения
                    viewportColumnRenderingOffset: 10 // Увеличиваем количество предзагружаемых столбцов
                });
                
                hot.render();
                  // Повторно применяем настройки через секунду для надежности
                setTimeout(() => {
                    // Убеждаемся, что все столбцы видны
                    hot.updateSettings({
                        width: '100%',
                        stretchH: 'all',
                        viewportColumnRenderingOffset: 10
                    });
                    hot.render();
                    
                    // Принудительно показываем все столбцы
                    if (typeof window.forceShowAllColumns === 'function') {
                        window.forceShowAllColumns();
                    }
                }, 1000);
            }
        }, 500);
        
    } catch (error) {
        console.error('Error initializing Excel editor:', error);
        alert('Произошла ошибка при инициализации редактора смет. Попробуйте перезагрузить страницу.');
    }
}

/**
 * Специальный рендерер для заголовков таблицы
 */
function headerRenderer(instance, td, row, col, prop, value, cellProperties) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    
    // Добавляем стили для заголовков
    td.style.fontWeight = 'bold';
    td.style.backgroundColor = '#f8f9fa';
    td.style.textAlign = 'center';
    
    return td;
}

/**
 * Обновление индикатора статуса изменений
 */
function updateStatusIndicator() {
    const saveBtn = document.getElementById('submitBtn'); // Исправлено название кнопки
    
    if (!saveBtn) return;
    
    if (isFileModified) {
        saveBtn.classList.remove('btn-outline-primary');
        saveBtn.classList.add('btn-warning');
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Сохранить изменения*';
    } else {
        saveBtn.classList.remove('btn-warning');
        saveBtn.classList.add('btn-outline-primary');
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Сохранить изменения';
    }
}

// Экспортируем функцию в глобальную область видимости
window.updateStatusIndicator = updateStatusIndicator;

// Экспортируем глобальные переменные для управления состоянием
window.isFileModified = false;

/**
 * Инициализация обработчиков событий для кнопок управления таблицей
 */
function initEventHandlers() {
    // Добавляем функцию для принудительного исправления ширин столбцов
    function fixColumnWidths() {
        if (!hot) return;
        
        const columnCount = hot.countCols();
        const columnWidths = [];
        
        // Устанавливаем фиксированные ширины для колонок
        for (let i = 0; i < columnCount; i++) {
            if (i === 0) columnWidths.push(80);       // №
            else if (i === 1) columnWidths.push(250); // Наименование/Позиция
            else if (i === 2) columnWidths.push(70);  // Ед.изм.
            else if (i === 3) columnWidths.push(80);  // Кол-во
            else if (i === 4) columnWidths.push(100); // Цена
            else if (i === 5) columnWidths.push(120); // Стоимость
            else if (i === 6) columnWidths.push(90);  // Наценка, %
            else if (i === 7) columnWidths.push(90);  // Скидка, %
            else if (i === 8) columnWidths.push(120); // Цена для заказчика
            else if (i === 9) columnWidths.push(140); // Стоимость для заказчика
            else columnWidths.push(100);              // Стандартная ширина
        }
          // Применяем настройки для правильного отображения всех столбцов
        hot.updateSettings({
            stretchH: 'all', // Используем 'all', чтобы все столбцы были видны
            colWidths: columnWidths,
            wordWrap: true,
            width: '100%',
            viewportColumnRenderingOffset: 10,
            manualColumnResize: true
        });
        
        // Принудительно показать все столбцы
        hot.updateSettings({
            stretchH: 'all', // Растягиваем столбцы по ширине контейнера
            wordWrap: true,
            viewportColumnRenderingOffset: structure.columnCount // Рендерим все столбцы
        });
        
        // Перерисовываем таблицу
        hot.render();
        
        console.log('Column widths fixed:', columnWidths);
    }

    // Добавляем обработчик на двойной клик по кнопке recalcAll для исправления видимости столбцов
    const recalcAllBtn = document.getElementById('recalcAllBtn');
    if (recalcAllBtn) {
        recalcAllBtn.addEventListener('dblclick', function() {
            console.log('Fixing column widths on double click');
            fixColumnWidths();
        });
    }
      // Создаем новую кнопку для исправления столбцов
    const excelEditorHeader = document.querySelector('.card-header');
    if (excelEditorHeader) {
        const fixColumnsBtn = document.createElement('button');
        fixColumnsBtn.type = 'button';
        fixColumnsBtn.className = 'btn btn-danger btn-sm ms-2';
        fixColumnsBtn.innerHTML = '<i class="fas fa-columns"></i> Показать все столбцы';
        fixColumnsBtn.title = 'Показать все столбцы таблицы';
        
        fixColumnsBtn.addEventListener('click', function() {
            // Вызываем функцию из excel-sheet-manager-fixed.js
            if (typeof window.forceShowAllColumns === 'function') {
                window.forceShowAllColumns();
            } else {
                // Альтернативный способ, если функция недоступна
                fixColumnWidths();
            }
        });
        
        // Проверяем наличие группы кнопок
        const btnGroup = excelEditorHeader.querySelector('.btn-group');
        if (btnGroup) {
            btnGroup.appendChild(fixColumnsBtn);
        } else {
            // Если группы нет, добавляем кнопку напрямую
            excelEditorHeader.appendChild(fixColumnsBtn);
        }
    }
    
    // Вызываем исправление столбцов после загрузки данных
    window.addEventListener('load', function() {
        setTimeout(fixColumnWidths, 1500);
    });

    // Обработчик кнопки "Сохранить изменения"
    const saveBtn = document.getElementById('submitBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            saveExcelToServer();
        });
    }

    // Обработчик для кнопки добавления листа
    const addSheetBtn = document.getElementById('addSheetBtn');
    if (addSheetBtn) {
        addSheetBtn.addEventListener('click', function() {
            addNewSheet();
        });
    }
    
    // Дополнительные обработчики кнопок можно добавить здесь
}

// Функция диагностики для проверки загрузки всех функций
console.log('Excel editor загружен');

window.checkExcelFunctions = function() {
    const functions = [
        'recalculateAll',
        'recalculateRow', 
        'recalculateTotals',
        'formatRowByType',
        'enforceFormulasInRow',
        'addNewRow',
        'addNewSection',
        'saveExcelToServer',
        'forceShowAllColumns',
        'updateStatusIndicator'
    ];
    
    console.log('Проверка доступности функций Excel редактора:');
    functions.forEach(func => {
        const available = typeof window[func] === 'function';
        console.log(`${func}: ${available ? '✓' : '✗'}`);
    });
};

/**
 * Пересчитывает все формулы в таблице
 */
function recalculateAll() {
    if (hot) {
        // Показываем индикатор загрузки
        const loadingIndicator = document.getElementById('loadingIndicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = 'block';
            loadingIndicator.innerHTML = '<div class="spinner-border text-primary" role="status"></div><div class="mt-2">Пересчет формул...</div>';
        }
        
        setTimeout(() => {
            try {
                const rowCount = hot.countRows();
                
                // Пересчитываем все строки кроме итоговой
                for (let row = 5; row < rowCount - 1; row++) {
                    if (typeof window.recalculateRow === 'function') {
                        window.recalculateRow(row);
                    }
                }
                
                // Отдельно пересчитываем итоговую строку
                if (typeof window.recalculateTotals === 'function') {
                    window.recalculateTotals();
                }
                
                // Обновляем отображение таблицы
                hot.render();
                
                // После завершения расчетов показываем все столбцы
                if (typeof window.forceShowAllColumns === 'function') {
                    window.forceShowAllColumns();
                }
            } catch (error) {
                console.error('Ошибка при пересчете формул:', error);
                alert('Произошла ошибка при пересчете формул: ' + error.message);
            } finally {
                // Скрываем индикатор загрузки
                if (loadingIndicator) {
                    loadingIndicator.style.setProperty('display', 'none', 'important');
                }
            }
        }, 200);
    }
}
