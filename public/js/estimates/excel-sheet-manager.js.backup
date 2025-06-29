/**
 * Функция загрузки данных Excel с сервера
 * @param {string} url URL для загрузки файла Excel
 */
function loadExcelFile(url) {
    console.log('Загрузка файла Excel с:', url);
    showLoading(true);
    
    if (!url) {
        console.error('URL для загрузки данных не определен');
        showLoading(false);
        createNewExcelWorkbook();
        return;
    }
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                if (response.status === 500) {
                    // Получим текст ошибки для более точного диагностирования проблемы
                    return response.json().then(errorData => {
                        throw new Error(`Ошибка сервера: ${errorData.message || response.statusText}`);
                    }).catch(e => {
                        throw new Error(`Внутренняя ошибка сервера (500). Пожалуйста, обратитесь к администратору.`);
                    });
                } else {
                    throw new Error(`Ошибка загрузки: ${response.status} ${response.statusText}`);
                }
            }
            return response.json();
        })
        .then(data => {
            console.log('Данные Excel получены');
            if (data.success) {
                try {
                    const base64Data = data.data;
                    const binaryString = window.atob(base64Data);
                    const bytes = new Uint8Array(binaryString.length);
                    for (let i = 0; i < binaryString.length; i++) {
                        bytes[i] = binaryString.charCodeAt(i);
                    }
                    
                    // Разбор файла Excel
                    workbook = XLSX.read(bytes, {type: 'array'});
                    
                    // Обновляем структуру файла
                    fileStructure = data.structure || fileStructure;
                    
                    // Получаем список листов
                    sheets = [];
                    workbook.SheetNames.forEach(sheetName => {
                        const worksheet = workbook.Sheets[sheetName];
                        const sheetData = XLSX.utils.sheet_to_json(worksheet, {header: 1});
                        
                        // Определяем непустые столбцы
                        const nonEmptyColumns = detectNonEmptyColumns(sheetData, 5);
                        
                        // Сохраняем информацию о непустых столбцах для этого листа
                        sheets.push({
                            name: sheetName,
                            data: sheetData,
                            nonEmptyColumns: nonEmptyColumns
                        });
                    });
                    
                    // Обновляем вкладки листов
                    updateSheetTabs();
                    
                    // Загружаем данные первого листа
                    currentSheetIndex = 0;
                    loadSheetData(0);
                } catch (error) {
                    console.error('Ошибка при разборе Excel:', error);
                    alert('Ошибка при разборе файла Excel: ' + error.message);
                    createNewExcelWorkbook();
                }
            } else {
                console.warn('Ошибка загрузки данных Excel:', data.message);
                alert('Ошибка при загрузке данных: ' + data.message);
                // Если файл не найден или поврежден, создаем новый
                createNewExcelWorkbook();
            }
        })
        .catch(error => {
            console.error('Ошибка при загрузке файла Excel:', error);
            alert('Произошла ошибка при загрузке файла: ' + error.message);
            // В случае любой ошибки создаем новый пустой файл
            createNewExcelWorkbook();
        })
        .finally(() => {
            showLoading(false);
        });
}

/**
 * Создание нового рабочего документа Excel с учетом структуры
 */
function createNewExcelWorkbook() {
    try {
        console.log('Создание новой книги Excel с учетом структуры');
        
        // Получаем структуру файла (если она определена)
        const structure = fileStructure || {
            columnCount: 10,
            readOnlyColumns: [5, 8, 9],
            hasHeaders: true
        };
        
        // Создаем базовый шаблон для сметы
        workbook = XLSX.utils.book_new();
        
        // Добавляем первый лист "Смета"
        const sheetName = 'Смета';
        
        // Создаем структуру сметы с нужным количеством колонок
        const headerRow = [];
        for (let i = 0; i < structure.columnCount; i++) {
            headerRow.push('');  // Сначала создаем пустые ячейки
        }
        
        // Заполняем только заголовки таблицы, без дополнительной информации вначале
        const sheetData = [];
        
        // Добавляем пустые строки для сохранения структуры
        for (let i = 0; i < 5; i++) {
            sheetData.push([...headerRow]);
        }
        
        // Заголовки таблицы в зависимости от количества колонок
        const tableHeaders = [...headerRow];
        
        // Стандартные заголовки, которые всегда присутствуют
        tableHeaders[0] = '№';
        tableHeaders[1] = 'Позиция';
        tableHeaders[2] = 'Ед. изм.';
        tableHeaders[3] = 'Кол-во';
        tableHeaders[4] = 'Цена';
        
        // Остальные заголовки в зависимости от структуры
        if (structure.columnCount >= 6) tableHeaders[5] = 'Стоимость';
        if (structure.columnCount >= 7) tableHeaders[6] = 'Наценка, %';
        if (structure.columnCount >= 8) tableHeaders[7] = 'Скидка, %';
        if (structure.columnCount >= 9) tableHeaders[8] = 'Цена для заказчика';
        if (structure.columnCount >= 10) tableHeaders[9] = 'Стоимость для заказчика';
          // Добавляем заголовки таблицы
        sheetData.push(tableHeaders);
        
        // Добавляем несколько пустых строк для данных
        for (let i = 0; i < 5; i++) {
            sheetData.push([...headerRow]);
        }
        
        // Добавляем итоговую строку в самый конец
        const totalRow = [...Array(structure.columnCount)].map(() => '');
        totalRow[1] = 'ИТОГО:';
        
        // Устанавливаем формулы для итоговых колонок
        if (structure.readOnlyColumns && structure.columnCount > 5) {
            structure.readOnlyColumns.forEach(colIndex => {
                if (colIndex < structure.columnCount) {
                    totalRow[colIndex] = 0; // Начальное значение для суммы
                }
            });
        }
        
        sheetData.push(totalRow);
        
        // Создаем лист и добавляем в книгу
        const worksheet = XLSX.utils.aoa_to_sheet(sheetData);
        
        // Применяем стили сетки к новому листу
        applyTableBorders(worksheet, sheetData);
        
        XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
        
        // Заполняем данные для отображения
        sheets = [{
            name: sheetName,
            data: sheetData
        }];
        
        // Обновляем вкладки листов
        updateSheetTabs();
        
        // Загружаем данные первого листа в редактор
        loadSheetData(0);
        
        console.log('New workbook created with structure', structure);
        
        // Убедимся, что строка ИТОГО всегда в конце
        ensureTotalRowIsLast();
        
        // Сразу же применяем формулы и пересчитываем итоги
        recalculateAll();
    } catch (error) {
        console.error('Ошибка при создании книги Excel:', error);
        alert('Произошла ошибка при создании нового файла сметы: ' + error.message);
    }
}

/**
 * Загрузка данных листа в редактор
 * @param {number} sheetIndex Индекс листа для загрузки
 */
function loadSheetData(sheetIndex) {
    if (!sheets[sheetIndex]) {
        console.error('Лист с индексом', sheetIndex, 'не найден');
        return;
    }
    if (!hot) {
        console.error('Редактор Handsontable не инициализирован');
        return;
    }
    try {
        showLoading(true);
        const sheetData = sheets[sheetIndex].data;
        const sheetName = sheets[sheetIndex].name;
        
        // Получаем непустые столбцы для текущего листа
        const nonEmptyColumns = sheets[sheetIndex].nonEmptyColumns || detectNonEmptyColumns(sheetData, 5);
        
        // Создаем конфигурацию для скрытия пустых столбцов
        const hiddenColumnsConfig = createHiddenColumnsConfig(
            nonEmptyColumns,
            sheetData[0] ? sheetData[0].length : 10
        );
        
        // Обновляем конфигурацию Handsontable для скрытия пустых столбцов
        if (hot.getPlugin('hiddenColumns')) {
            hot.getPlugin('hiddenColumns').hideColumns(hiddenColumnsConfig.columns);
        }
        
        // Загружаем данные в редактор
        hot.loadData(sheetData);
        
        // Применяем форматирование асинхронно для лучшей производительности
        setTimeout(() => {
            try {
                // Сначала сбрасываем флаг обработки ИТОГО строк
                window._isProcessingTotalRow = false;
                
                // Предварительно проверяем на наличие дублирующихся строк ИТОГО
                let totalRowsCount = 0;
                let hasMultipleTotalRows = false;
                
                for (let row = 5; row < hot.countRows(); row++) {
                    const cellContent = hot.getDataAtCell(row, 1);
                    if (cellContent && typeof cellContent === 'string' && 
                        (cellContent.includes('ИТОГО') || cellContent.includes('Итого') || 
                        cellContent.includes('ВСЕГО'))) {
                        totalRowsCount++;
                        if (totalRowsCount > 1) {
                            hasMultipleTotalRows = true;
                            break;
                        }
                    }
                }
                
                // Если есть дублирующиеся строки ИТОГО, сначала обрабатываем их
                if (hasMultipleTotalRows && typeof window.ensureTotalRowIsLast === 'function') {
                    console.log('Обнаружены дублирующиеся строки ИТОГО при загрузке данных. Устраняем проблему...');
                    window.ensureTotalRowIsLast();
                }
                
                // Затем применяем форматирование
                applySheetFormatting(sheetData);
                updateSheetStatus(sheetIndex);
            } catch (error) {
                console.error('Ошибка при форматировании листа:', error);
            } finally {
                showLoading(false);
            }
        }, 100); // Увеличил таймаут для более стабильной работы
    } catch (error) {
        console.error('Ошибка при загрузке данных листа:', error);
        showLoading(false);
        alert('Произошла ошибка при загрузке листа: ' + error.message);
    }
}

function applySheetFormatting(sheetData) {
    if (!hot || !sheetData) return;
    const rowCount = hot.countRows();
    const colCount = sheetData[0] ? sheetData[0].length : 10;
    
    // Применяем стилизацию к заголовкам таблицы
    for (let col = 0; col < colCount; col++) {
        hot.setCellMeta(4, col, 'className', 'htBold htCenter');
    }
      // Итоговая строка должна быть последней
    const totalRow = rowCount - 1;
    
    // Проверяем, есть ли сохраненные метаданные стилизации
    const currentSheet = sheets[currentSheetIndex];
    const hasSavedMetadata = currentSheet && currentSheet.cellMetadata && currentSheet.cellMetadata.length > 0;
    
    // Проверяем наличие дублирующихся строк ИТОГО
    let totalRows = [];
    for (let row = 5; row < rowCount; row++) {
        const cellContent = hot.getDataAtCell(row, 1);
        if (cellContent && typeof cellContent === 'string' && 
            (cellContent.includes('ИТОГО') || cellContent.includes('Итого') || cellContent.includes('ВСЕГО'))) {
            totalRows.push(row);
        }
    }
    
    // Если найдено несколько строк ИТОГО
    if (totalRows.length > 1) {
        console.log(`Найдено ${totalRows.length} строк ИТОГО при форматировании. Вызываем обработку через ensureTotalRowIsLast.`);
        
        // Вызовем обработку для устранения дублей
        if (typeof window.ensureTotalRowIsLast === 'function' && !window._isProcessingTotalRow) {
            window._isProcessingTotalRow = true;
            window.ensureTotalRowIsLast();
            window._isProcessingTotalRow = false;
            return;  // Прерываем текущее выполнение, так как будет новый вызов
        }
    }
    
    // Проверяем, что итоговая строка действительно последняя
    const totalRowIsLast = totalRows.length === 1 && totalRows[0] === totalRow;
    
    // Форматируем строку с итогами
    const totalRowName = hot.getDataAtCell(totalRow, 1);
    if (!totalRowName || typeof totalRowName !== 'string' || 
        (!totalRowName.includes('ИТОГО') && !totalRowName.includes('Итого') && !totalRowName.includes('ВСЕГО'))) {
        // Если в последней строке нет "ИТОГО", это значит что она не итоговая
        // Проверяем, есть ли итоговая строка в другом месте
        if (totalRows.length > 0) {
            // Уже есть итоговая строка в другом месте, нужно её переместить
            if (typeof window.ensureTotalRowIsLast === 'function' && !window._isProcessingTotalRow) {
                window._isProcessingTotalRow = true;
                window.ensureTotalRowIsLast();
                window._isProcessingTotalRow = false;
                return;  // Прерываем текущее выполнение, так как будет новый вызов
            }
        } else {
            // Добавляем итоговую строку или исправляем последнюю
            hot.setDataAtCell(totalRow, 1, 'ИТОГО:', 'formatting');
        }
        
        // Устанавливаем форматирование для итоговой строки
        for (let col = 0; col < colCount; col++) {
            hot.setCellMeta(totalRow, col, 'className', 'htBold');
            if (col >= 5 && col <= 9) {
                hot.setCellMeta(totalRow, col, 'className', 'htBold htNumeric');
            }
        }
    }
    
    // Форматируем все остальные строки
    for (let row = 5; row < totalRow; row++) {
        const name = hot.getDataAtCell(row, 1);
        if (name) {
            // Проверяем признаки раздела
            let isSection = false;
            const emptyCellCount = [2, 3, 4, 5, 6, 7, 8, 9].filter(col => 
                !hot.getDataAtCell(row, col) || hot.getDataAtCell(row, col) === ''
            ).length;
            
            if (emptyCellCount >= 7) {
                isSection = true;
            }
            
            // Если это сохраненный метаданные секции, применяем их
            if (hasSavedMetadata) {
                const sectionMeta = currentSheet.cellMetadata.find(meta => meta.row === row && meta.isSection);
                if (sectionMeta) {
                    isSection = true;
                }
            }
            
            if (isSection) {
                // Применяем форматирование заголовка раздела
                for (let col = 0; col <= 9; col++) {
                    hot.setCellMeta(row, col, 'className', 'htGroupHeader');
                    hot.setCellMeta(row, col, 'style', {
                        font: { bold: true },
                        fill: { type: 'solid', color: '#F0F0F0' }
                    });
                }            } else {
                // Обычная строка, проверяем и применяем формулы
                formatRowByType(row, name);
                enforceFormulasInRow(row);
            }
        }
    }
    // Используем прямой вызов без рекурсии через ensureTotalRowIsLast
    recalculateTotalsWithoutReordering();
    hot.render();
}

function formatRowByType(row, name) {
    if (!hot) return;
    const colCount = 10;
    if (isHeaderRow(name)) {
        for (let col = 0; col < colCount; col++) {
            hot.setCellMeta(row, col, 'className', 'htGroupHeader');
        }
    } else {
        hot.setCellMeta(row, 5, 'className', 'htBold htNumeric');
        hot.setCellMeta(row, 9, 'className', 'htBold htNumeric');
        hot.setCellMeta(row, 6, 'className', 'htNumeric');
        hot.setCellMeta(row, 7, 'className', 'htNumeric');
        hot.setCellMeta(row, 3, 'className', 'htNumeric');
        hot.setCellMeta(row, 4, 'className', 'htNumeric');
        hot.setCellMeta(row, 8, 'className', 'htNumeric');
        for (let col of [5, 8, 9]) {
            hot.setCellMeta(row, col, 'readOnly', true);
        }
    }
}

function isHeaderRow(name) {
    if (!name || typeof name !== 'string') return false;
    return name === name.toUpperCase() && name.length > 3 && !name.includes('ИТОГО') && !name.includes('ВСЕГО');
}

function updateSheetStatus(sheetIndex) {
    if (sheetIndex < 0 || sheetIndex >= sheets.length) return;
    const sheet = sheets[sheetIndex];
    const sheetData = sheet.data;
    sheet.nonEmptyColumns = detectNonEmptyColumns(sheetData, 5);
    if (hot) {
        const stats = calculateSheetStats(sheetData);
        console.log(`Статистика листа "${sheet.name}":`, stats);
    }
}

function calculateSheetStats(sheetData) {
    if (!sheetData || sheetData.length === 0) {
        return { totalRows: 0, dataRows: 0, emptyRows: 0 };
    }
    const totalRows = sheetData.length;
    let dataRows = 0;
    let emptyRows = 0;
    for (let i = 5; i < totalRows - 1; i++) {
        const row = sheetData[i];
        const hasData = row && row.some(cell => cell !== null && cell !== '');
        if (hasData) {
            dataRows++;
        } else {
            emptyRows++;
        }
    }
    return {
        totalRows: totalRows,
        dataRows: dataRows,
        emptyRows: emptyRows
    };
}

function createHiddenColumnsConfig(nonEmptyColumns, totalColumns) {
    const hiddenColumns = [];
    const alwaysVisible = [0, 1, 2, 3, 4, 5];
    for (let i = 0; i < totalColumns; i++) {
        if (!alwaysVisible.includes(i) && !nonEmptyColumns.includes(i)) {
            hiddenColumns.push(i);
        }
    }
    return {
        columns: hiddenColumns,
        indicators: true
    };
}

/**
 * Обновление вкладок листов
 */
function updateSheetTabs() {
    const tabsContainer = document.getElementById('sheetTabs');
    if (!tabsContainer) {
        console.warn('Контейнер вкладок листов не найден');
        return;
    }
    
    // Очищаем контейнер
    tabsContainer.innerHTML = '';
    
    // Создаем обертку для вкладок с прокруткой
    const tabsWrapper = document.createElement('div');
    tabsWrapper.className = 'd-flex gap-1 overflow-auto';
    tabsWrapper.style.maxWidth = '100%';
    tabsWrapper.style.whiteSpace = 'nowrap';
    
    if (!sheets || sheets.length === 0) {
        console.warn('Нет листов для отображения');
        tabsContainer.appendChild(tabsWrapper);
        return;
    }
    
    sheets.forEach((sheet, index) => {
        const tab = document.createElement('button');
        tab.className = `btn btn-sm flex-shrink-0 ${index === currentSheetIndex ? 'btn-primary' : 'btn-outline-secondary'}`;
        tab.textContent = sheet.name || `Лист ${index + 1}`;
        tab.title = sheet.name || `Лист ${index + 1}`;
        tab.style.minWidth = '80px';
        tab.style.maxWidth = '150px';
        tab.style.overflow = 'hidden';
        tab.style.textOverflow = 'ellipsis';
        
        // Добавляем контекстное меню для вкладки
        tab.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            showSheetContextMenu(e, index);
        });
        
        // Обработчик клика по вкладке
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            switchToSheet(index);
        });
        
        // Добавляем индикатор изменений
        if (isSheetModified(index)) {
            const indicator = document.createElement('span');
            indicator.className = 'ms-1';
            indicator.innerHTML = '•';
            indicator.style.color = '#ffc107';
            indicator.title = 'Лист содержит несохраненные изменения';
            tab.appendChild(indicator);
        }
        
        tabsWrapper.appendChild(tab);
    });
    
    tabsContainer.appendChild(tabsWrapper);
    
    // Прокручиваем к активной вкладке если она вне видимости
    scrollToActiveTab();
}

/**
 * Переключение на указанный лист
 * @param {number} sheetIndex Индекс листа для переключения
 */
function switchToSheet(sheetIndex) {
    if (sheetIndex < 0 || sheetIndex >= sheets.length) {
        console.warn('Некорректный индекс листа:', sheetIndex);
        return;
    }
    
    // Сохраняем изменения текущего листа перед переключением
    if (hot && currentSheetIndex !== sheetIndex) {
        saveCurrentSheetData();
    }
    
    currentSheetIndex = sheetIndex;
    loadSheetData(sheetIndex);
    updateSheetTabs();
    
    // Убедимся, что строка ИТОГО всегда в конце
    ensureTotalRowIsLast();
    
    console.log(`Переключились на лист: ${sheets[sheetIndex].name}`);
}

/**
 * Сохранение данных текущего листа
 */
function saveCurrentSheetData() {
    if (!hot || currentSheetIndex < 0 || currentSheetIndex >= sheets.length) {
        return;
    }
    
    try {
        const currentData = hot.getData();
        sheets[currentSheetIndex].data = currentData;
        
        // Сохраняем метаданные стилизации для разделов
        if (!sheets[currentSheetIndex].cellMetadata) {
            sheets[currentSheetIndex].cellMetadata = [];
        }
        
        // Сохраняем метаданные для ячеек разделов
        for (let row = 5; row < hot.countRows() - 1; row++) {
            const cellMeta = hot.getCellMeta(row, 1);
            if (cellMeta.className && cellMeta.className.includes('htGroupHeader')) {
                // Сохраняем информацию о форматировании разделов
                sheets[currentSheetIndex].cellMetadata.push({
                    row: row,
                    isSection: true,
                    styles: cellMeta.style || {}
                });
            }
        }
        
        // Обновляем данные в workbook если он существует
        if (workbook && sheets[currentSheetIndex].name) {
            // Убедимся, что строка ИТОГО всегда в конце
            ensureTotalRowIsLast();
            
            const worksheet = XLSX.utils.aoa_to_sheet(currentData);
            workbook.Sheets[sheets[currentSheetIndex].name] = worksheet;
        }
        
        console.log(`Данные листа "${sheets[currentSheetIndex].name}" сохранены с метаданными форматирования`);
    } catch (error) {
        console.error('Ошибка при сохранении данных листа:', error);
    }
}

/**
 * Добавление нового листа
 */
function addNewSheet() {
    // Генерируем уникальное имя листа
    let newSheetName = 'Новый лист';
    let counter = 1;
    
    while (sheets.some(sheet => sheet.name === newSheetName)) {
        counter++;
        newSheetName = `Новый лист ${counter}`;
    }
    
    const sheetName = prompt('Введите название нового листа:', newSheetName);
    if (!sheetName || sheetName.trim() === '') {
        return;
    }
    
    const trimmedName = sheetName.trim();
    
    // Проверяем уникальность имени
    if (sheets.some(sheet => sheet.name === trimmedName)) {
        alert('Лист с таким названием уже существует!');
        return;
    }

    // Сохраняем данные текущего листа перед созданием нового
    if (hot) {
        saveCurrentSheetData();
    }

    // Создаем новый лист с улучшенной структурой
    const newSheetData = createNewSheetStructure(trimmedName);

    // Добавляем лист в рабочую книгу
    if (!workbook) {
        workbook = XLSX.utils.book_new();
    }
      const worksheet = XLSX.utils.aoa_to_sheet(newSheetData);
    
    // Применяем стили сетки к новому листу
    applyTableBorders(worksheet, newSheetData);
    
    XLSX.utils.book_append_sheet(workbook, worksheet, trimmedName);

    // Добавляем в массив листов
    const newSheet = {
        name: trimmedName,
        data: newSheetData,
        nonEmptyColumns: detectNonEmptyColumns(newSheetData, 5)
    };
    
    sheets.push(newSheet);

    // Переключаемся на новый лист
    switchToSheet(sheets.length - 1);

    // Флаг изменения
    isFileModified = true;
    updateStatusIndicator();
    
    console.log(`Создан новый лист: "${trimmedName}"`);
}

/**
 * Создание структуры данных для нового листа
 * @param {string} sheetName Название листа
 * @returns {Array} Массив данных для нового листа
 */
function createNewSheetStructure(sheetName) {
    // Получаем структуру из глобальной переменной или используем стандартную
    const structure = fileStructure || {
        columnCount: 10,
        readOnlyColumns: [5, 8, 9],
        hasHeaders: true
    };
    
    const columnCount = structure.columnCount;
    const emptyRow = new Array(columnCount).fill('');
    
    const sheetData = [];
    
    // Заголовок сметы
    const titleRow = [...emptyRow];
    titleRow[0] = 'СМЕТА';
    titleRow[1] = sheetName;
    sheetData.push(titleRow);
    
    // Информационные строки
    const dateRow = [...emptyRow];
    dateRow[0] = 'Дата:';
    dateRow[1] = new Date().toLocaleDateString('ru-RU');
    sheetData.push(dateRow);
    
    const objectRow = [...emptyRow];
    objectRow[0] = 'Объект:';
    sheetData.push(objectRow);
    
    // Пустая строка
    sheetData.push([...emptyRow]);
    
    // Заголовки таблицы
    const headerRow = [...emptyRow];
    headerRow[0] = '№';
    headerRow[1] = 'Наименование работ';
    headerRow[2] = 'Ед. изм.';
    headerRow[3] = 'Кол-во';
    headerRow[4] = 'Цена за ед.';
    
    if (columnCount >= 6) headerRow[5] = 'Стоимость';
    if (columnCount >= 7) headerRow[6] = 'Наценка, %';
    if (columnCount >= 8) headerRow[7] = 'Скидка, %';
    if (columnCount >= 9) headerRow[8] = 'Цена для заказчика';
    if (columnCount >= 10) headerRow[9] = 'Стоимость для заказчика';
    
    sheetData.push(headerRow);
    
    // Добавляем несколько пустых строк для данных
    for (let i = 0; i < 5; i++) {
        sheetData.push([...emptyRow]);
    }
    
    // Итоговая строка в самом конце
    const totalRow = [...emptyRow];
    totalRow[1] = 'ИТОГО:';
    
    // Инициализируем итоговые колонки нулями
    if (structure.readOnlyColumns) {
        structure.readOnlyColumns.forEach(colIndex => {
            if (colIndex < columnCount) {
                totalRow[colIndex] = 0;
            }
        });
    }
    
    // Важно: добавляем итоговую строку последней
    sheetData.push(totalRow);
    
    return sheetData;
}

/**
 * Проверка, есть ли несохраненные изменения в листе
 * @param {number} sheetIndex Индекс листа
 * @returns {boolean} true если лист содержит изменения
 */
function isSheetModified(sheetIndex) {
    // Простая проверка - можно расширить более сложной логикой
    return isFileModified && sheetIndex === currentSheetIndex;
}

/**
 * Прокрутка к активной вкладке
 */
function scrollToActiveTab() {
    const tabsContainer = document.getElementById('sheetTabs');
    if (!tabsContainer) return;
    
    const activeTab = tabsContainer.querySelector('.btn-primary');
    if (activeTab) {
        activeTab.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center'
        });
    }
}

/**
 * Сохранение Excel файла на сервер
 */
function saveExcelToServer() {
    if (!workbook) {
        alert('Нет данных для сохранения');
        return;
    }
    
    showLoading(true);
    
    try {        
        // Обновляем данные текущего листа
        const currentSheetData = hot.getData();
        sheets[currentSheetIndex].data = currentSheetData;
        
        // Убедимся, что строка ИТОГО всегда в конце
        ensureTotalRowIsLast();
        
        // Обновляем данные в рабочей книге с применением стилей
        const worksheet = XLSX.utils.aoa_to_sheet(currentSheetData);
        
        // Применяем стили сетки к таблице
        applyTableBorders(worksheet, currentSheetData);
        
        workbook.Sheets[sheets[currentSheetIndex].name] = worksheet;
          // Преобразуем книгу в двоичные данные с использованием промежуточной переменной
        let wbout;
        try {
            wbout = XLSX.write(workbook, { 
                bookType: 'xlsx', 
                type: 'binary',
                cellStyles: true,
                compression: true,
                bookSST: false,
                cellDates: false
            });
        } catch (writeError) {
            console.error('Error writing Excel workbook:', writeError);
            alert('Ошибка при создании файла Excel: ' + writeError.message);
            showLoading(false);
            return;
        }
        
        // Вспомогательная функция для корректного преобразования строки в массив байтов
        function s2ab(s) {
            const buf = new ArrayBuffer(s.length);
            const view = new Uint8Array(buf);
            for (let i = 0; i < s.length; i++) {
                view[i] = s.charCodeAt(i) & 0xFF;
            }
            return buf;
        }
        
        // Создаем Blob из данных для надежного преобразования в Base64
        const blob = new Blob([s2ab(wbout)], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
        
        // Используем FileReader для преобразования Blob в Base64
        const fileReader = new FileReader();
        fileReader.onload = function(e) {
            // Получаем Base64 данные, удаляя префикс Data URL
            const base64Data = e.target.result.split(',')[1];
            
            // Проверяем, что данные не пустые
            if (!base64Data) {
                console.error('Failed to convert Excel data to Base64');
                alert('Ошибка при подготовке файла к сохранению: данные не могут быть преобразованы');
                showLoading(false);
                return;
            }
            
            // Получаем корректный URL для сохранения - ИСПРАВЛЯЕМ ЗДЕСЬ
            const form = document.querySelector('form#estimateForm');
            let saveUrl;
            
            if (form) {
                // Извлекаем ID сметы из текущего URL страницы
                const urlParts = window.location.pathname.split('/');
                const estimateId = urlParts[urlParts.indexOf('estimates') + 1];
                
                // Формируем правильный URL для сохранения Excel
                saveUrl = `/partner/estimates/${estimateId}/saveExcel`;
                
                console.log('Сформирован URL для сохранения:', saveUrl);
            } else {
                console.error('Форма сметы не найдена');
                alert('Ошибка: форма сметы не найдена на странице');
                showLoading(false);
                return;
            }
            
            // Отладочный вывод
            console.log('Sending Excel data to server, data size:', base64Data.length);
            console.log('Save URL:', saveUrl);
            
            // Отправляем на сервер
            fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    excel_data: base64Data
                })
            })
            .then(response => {
                // Проверяем код ответа перед разбором JSON
                if (!response.ok) {
                    if (response.status === 422) {
                        return response.json().then(data => {
                            throw new Error(data.message || 'Данные Excel не могут быть обработаны сервером');
                        });
                    }
                    throw new Error(`Ошибка сервера: ${response.status} ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Обновляем информацию о файле
                    const fileInfoElement = document.getElementById('fileInfo');
                    if (fileInfoElement) {
                        fileInfoElement.innerHTML = `
                            <p class="mb-1"><strong>Файл сметы:</strong> ${data.fileName || 'Смета.xlsx'}</p>
                            <p class="mb-1"><small>Последнее обновление: ${data.updated_at}</small></p>
                            <p class="mb-1"><small>Размер: ${formatFileSize(data.filesize)}</small></p>
                        `;
                    }
                    
                    // Сбрасываем флаг модификации
                    isFileModified = false;
                    updateStatusIndicator();
                    
                    // Уведомляем пользователя
                    alert('Файл успешно сохранен');
                } else {
                    throw new Error(data.message || 'Неизвестная ошибка при сохранении файла');
                }
            })
            .catch(error => {
                console.error('Ошибка при сохранении файла:', error);
                alert('Произошла ошибка при сохранении файла: ' + error.message);
            })
            .finally(() => {
                showLoading(false);
            });
        };
        
        fileReader.onerror = function() {
            console.error('Ошибка при чтении файла как base64');
            alert('Не удалось преобразовать Excel файл для отправки');
            showLoading(false);
        };
        
        // Запускаем преобразование в base64
        fileReader.readAsDataURL(blob);
        
    } catch (error) {
        console.error('Error preparing file for saving:', error);
        alert('Ошибка при подготовке файла к сохранению: ' + error.message);
        showLoading(false);
    }
}

/**
 * Форматирует размер файла для читаемого отображения
 * @param {number} size Размер в байтах
 * @return {string} Отформатированный размер
 */
function formatFileSize(size) {
    if (!size) return '0 B';
    
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;
    
    while (size >= 1024 && i < units.length - 1) {
        size /= 1024;
        i++;
    }
    
    return Math.round(size * 100) / 100 + ' ' + units[i];
}

/**
 * Обнаружение непустых столбцов в данных
 * @param {Array} data Данные листа (массив строк)
 * @param {number} minNonEmptyRows Минимальное количество непустых строк для определения столбца как непустого
 * @returns {Array} Массив индексов непустых столбцов
 */
function detectNonEmptyColumns(data, minNonEmptyRows = 1) {
    const nonEmptyColumns = [];
    const rowCount = data.length;
    
    // Предполагаем, что первая строка - это заголовки
    const columnCount = data[0].length;
    
    for (let col = 0; col < columnCount; col++) {
        let nonEmptyCount = 0;
        
        for (let row = 0; row < rowCount; row++) {
            if (data[row][col] !== null && data[row][col] !== '') {
                nonEmptyCount++;
            }
            
            // Если нашли достаточно непустых ячеек, добавляем столбец в результат
            if (nonEmptyCount >= minNonEmptyRows) {
                nonEmptyColumns.push(col);
                break;
            }
        }
    }
    
    return nonEmptyColumns;
}

/**
 * Применяет стили границ к таблице Excel
 * @param {Object} worksheet Рабочий лист Excel
 * @param {Array} data Данные таблицы
 */
function applyTableBorders(worksheet, data) {
    if (!worksheet || !data || data.length === 0) return;
    
    const rowCount = data.length;
    const colCount = data[0] ? data[0].length : 10;
    
    // Определяем диапазон таблицы (начиная с заголовков)
    const tableStartRow = 5; // Строка с заголовками (нумерация с 0)
    const tableEndRow = rowCount - 1; // Последняя строка
    
    // Стиль границ для обычных ячеек
    const borderStyle = {
        style: 'thin',
        color: { rgb: '000000' }
    };
    
    // Стиль границ для заголовков (более толстая граница)
    const headerBorderStyle = {
        style: 'medium',
        color: { rgb: '000000' }
    };
    
    // Инициализируем объект стилей если его нет
    if (!worksheet['!cols']) worksheet['!cols'] = [];
    if (!worksheet['!rows']) worksheet['!rows'] = [];
    
    // Применяем границы к каждой ячейке в диапазоне таблицы
    for (let row = tableStartRow; row <= tableEndRow; row++) {
        for (let col = 0; col < colCount; col++) {
            // Формируем адрес ячейки (A1, B1, etc.)
            const cellAddress = XLSX.utils.encode_cell({ r: row, c: col });
            
            // Получаем или создаем ячейку
            if (!worksheet[cellAddress]) {
                worksheet[cellAddress] = { t: 's', v: '' };
            }
            
            // Применяем стили
            if (!worksheet[cellAddress].s) {
                worksheet[cellAddress].s = {};
            }
            
            // Выбираем стиль границ в зависимости от типа строки
            const currentBorderStyle = (row === tableStartRow) ? headerBorderStyle : borderStyle;
            
            // Применяем границы
            worksheet[cellAddress].s.border = {
                top: currentBorderStyle,
                bottom: currentBorderStyle,
                left: currentBorderStyle,
                right: currentBorderStyle
            };
            
            // Дополнительное форматирование для заголовков
            if (row === tableStartRow) {
                worksheet[cellAddress].s.font = {
                    bold: true,
                    color: { rgb: '000000' }
                };
                worksheet[cellAddress].s.fill = {
                    patternType: 'solid',
                    fgColor: { rgb: 'E6E6FA' } // Светло-сиреневый фон для заголовков
                };
                worksheet[cellAddress].s.alignment = {
                    horizontal: 'center',
                    vertical: 'center'
                };
            }
            
            // Форматирование для итоговой строки
            if (row === tableEndRow && data[row] && data[row][1] && 
                (data[row][1].toString().includes('ИТОГО') || data[row][1].toString().includes('ВСЕГО'))) {
                worksheet[cellAddress].s.font = {
                    bold: true,
                    color: { rgb: '000000' }
                };
                worksheet[cellAddress].s.fill = {
                    patternType: 'solid',
                    fgColor: { rgb: 'F0F8FF' } // Светло-голубой фон для итогов
                };
            }
            
            // Выравнивание для числовых колонок
            if (col >= 3 && col <= 9) {
                if (!worksheet[cellAddress].s.alignment) {
                    worksheet[cellAddress].s.alignment = {};
                }
                worksheet[cellAddress].s.alignment.horizontal = 'right';
            }
        }
    }
    
    // Устанавливаем ширину колонок для лучшего отображения
    const colWidths = [
        { wch: 5 },   // № 
        { wch: 40 },  // Наименование работ
        { wch: 10 },  // Ед. изм.
        { wch: 10 },  // Кол-во
        { wch: 12 },  // Цена за ед.
        { wch: 15 },  // Стоимость
        { wch: 12 },  // Наценка, %
        { wch: 12 },  // Скидка, %
        { wch: 15 },  // Цена для заказчика
        { wch: 18 }   // Стоимость для заказчика
    ];
    
    worksheet['!cols'] = colWidths.slice(0, colCount);
    
    // Устанавливаем высоту строк
    for (let row = tableStartRow; row <= tableEndRow; row++) {
        if (!worksheet['!rows'][row]) {
            worksheet['!rows'][row] = {};
        }
        worksheet['!rows'][row].hpx = row === tableStartRow ? 25 : 20; // Заголовки чуть выше
    }
    
    console.log('Применены стили границ к таблице Excel');
}

/**
 * Проверяет и перемещает итоговую строку в конец таблицы, если она не там
 * Вызывается после структурных изменений таблицы
 * Глобальная функция, доступная из других JS-файлов
 */
// Флаг для предотвращения бесконечной рекурсии
window._isProcessingTotalRow = false;

window.ensureTotalRowIsLast = function ensureTotalRowIsLast() {
    if (!hot) return;
    
    // Предотвращаем рекурсивные вызовы
    if (window._isProcessingTotalRow) return;
    
    // Устанавливаем флаг, что мы обрабатываем строку ИТОГО
    window._isProcessingTotalRow = true;
    
    try {
        const rowCount = hot.countRows();
        let totalRows = []; // Массив для хранения всех строк ИТОГО
        
        // Находим все строки с "ИТОГО" в таблице
        for (let row = 5; row < rowCount; row++) {
            const cellContent = hot.getDataAtCell(row, 1);
            if (cellContent && typeof cellContent === 'string' && 
                (cellContent.includes('ИТОГО') || cellContent.includes('Итого') || cellContent.includes('ВСЕГО'))) {
                totalRows.push(row);
            }
        }
        
        // Если найдено несколько строк ИТОГО, оставляем только одну с максимальными значениями
        if (totalRows.length > 1) {
            console.log(`Найдено ${totalRows.length} строк с ИТОГО. Удаляем дубликаты.`);
            
            // Находим строку с максимальными значениями в колонках сумм
            let maxTotalCost = 0;
            let maxClientCost = 0;
            let bestTotalRowIndex = totalRows[0]; // По умолчанию берем первую строку
            
            for (let i = 0; i < totalRows.length; i++) {
                const rowIndex = totalRows[i];
                const totalCost = parseFloat(hot.getDataAtCell(rowIndex, 5)) || 0;
                const clientCost = parseFloat(hot.getDataAtCell(rowIndex, 9)) || 0;
                
                if (totalCost > maxTotalCost || clientCost > maxClientCost) {
                    maxTotalCost = totalCost;
                    maxClientCost = clientCost;
                    bestTotalRowIndex = rowIndex;
                }
            }
            
            // Сохраняем данные лучшей строки ИТОГО
            const totalRowData = [];
            for (let col = 0; col < 10; col++) {
                totalRowData[col] = hot.getDataAtCell(bestTotalRowIndex, col);
            }
            
            // Удаляем все строки ИТОГО (начинаем с конца, чтобы индексы не сбивались)
            for (let i = totalRows.length - 1; i >= 0; i--) {
                hot.alter('remove_row', totalRows[i], 1);
            }
            
            // Добавляем одну строку ИТОГО в самый конец
            const newRowCount = hot.countRows();
            hot.alter('insert_row', newRowCount, 1);
            
            // Заполняем данными из лучшей строки ИТОГО
            for (let col = 0; col < 10; col++) {
                hot.setDataAtCell(newRowCount, col, totalRowData[col], 'move');
            }
            
            // Обновляем переменные для дальнейшей обработки
            totalRows = [newRowCount];
            
            // Убедимся, что в итоговой строке правильный текст
            hot.setDataAtCell(newRowCount, 1, 'ИТОГО:', 'move');
        }
        
        // Проверяем, найдена ли хотя бы одна строка ИТОГО
        let totalRowFound = totalRows.length > 0;
        let totalRowIndex = totalRows.length > 0 ? totalRows[0] : -1;
        
        // Если строка найдена и она не последняя - перемещаем её
        if (totalRowFound && totalRowIndex !== rowCount - 1) {
            console.log('Перемещаем строку с ИТОГО с позиции ' + totalRowIndex + ' в конец таблицы');
            
            // Получаем данные итоговой строки
            const totalRowData = [];
            for (let col = 0; col < 10; col++) {
                totalRowData[col] = hot.getDataAtCell(totalRowIndex, col);
            }
            
            // Удаляем строку из её текущей позиции
            hot.alter('remove_row', totalRowIndex, 1);
            
            // Добавляем строку в конец
            const updatedRowCount = hot.countRows();
            hot.alter('insert_row', updatedRowCount, 1);
            
            // Заполняем данными
            for (let col = 0; col < 10; col++) {
                hot.setDataAtCell(updatedRowCount, col, totalRowData[col], 'move');
            }
            
            // Убедимся, что в итоговой строке правильный текст
            hot.setDataAtCell(updatedRowCount, 1, 'ИТОГО:', 'move');
            
            // Применяем форматирование
            for (let col = 0; col < 10; col++) {
                hot.setCellMeta(updatedRowCount, col, 'className', 'htBold');
                if (col >= 5 && col <= 9) {
                    hot.setCellMeta(updatedRowCount, col, 'className', 'htBold htNumeric');
                }
            }
            
            // Обновляем представление
            hot.render();
        } 
        // Если итоговой строки нет вообще - создаём её
        else if (!totalRowFound) {
            console.log('Создаем итоговую строку в конце таблицы');
            
            const lastRowIndex = rowCount - 1;
            const emptyRow = Array(10).fill('');
            emptyRow[1] = 'ИТОГО:';
            
            // Если последняя строка не пуста, добавляем новую
            const lastRowContent = hot.getDataAtCell(lastRowIndex, 1);
            if (lastRowContent && lastRowContent.trim() !== '') {
                hot.alter('insert_row', rowCount, 1);
                for (let col = 0; col < 10; col++) {
                    hot.setDataAtCell(rowCount, col, emptyRow[col], 'create');
                }
            } else {
                // Используем существующую пустую строку
                for (let col = 0; col < 10; col++) {
                    hot.setDataAtCell(lastRowIndex, col, emptyRow[col], 'create');
                }
            }
            
            // Применяем форматирование
            const newTotalRowIndex = hot.countRows() - 1;
            for (let col = 0; col < 10; col++) {
                hot.setCellMeta(newTotalRowIndex, col, 'className', 'htBold');
                if (col >= 5 && col <= 9) {
                    hot.setCellMeta(newTotalRowIndex, col, 'className', 'htBold htNumeric');
                }
            }
            
            // Обновляем представление
            hot.render();
        }
          // После изменений пересчитываем итоги без вызова ensureTotalRowIsLast
        if (typeof recalculateTotalsWithoutReordering === 'function') {
            recalculateTotalsWithoutReordering();
        }
    } catch (error) {
        console.error('Ошибка при проверке итоговой строки:', error);
    } finally {
        // Сбрасываем флаг обработки строки ИТОГО
        window._isProcessingTotalRow = false;
    }
}
