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
        if (typeof window.ensureTotalRowIsLast === 'function') {
            window.ensureTotalRowIsLast();
        }
        
        // Сразу же применяем формулы и пересчитываем итоги
        if (typeof window.recalculateAll === 'function') {
            window.recalculateAll();
        }
        
        // Принудительно показываем все столбцы при создании новой книги
        setTimeout(() => {
            if (typeof window.forceShowAllColumns === 'function') {
                window.forceShowAllColumns();
            }
        }, 500);
          // Убедимся, что строка ИТОГО всегда в конце
        if (typeof window.ensureTotalRowIsLast === 'function') {
            window.ensureTotalRowIsLast();
        }
        
        // Сразу же применяем формулы и пересчитываем итоги
        if (typeof window.recalculateAll === 'function') {
            window.recalculateAll();
        }
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
        
        // Определяем количество столбцов из данных
        const columnCount = Math.max(
            sheetData[0] ? sheetData[0].length : 10,
            fileStructure ? fileStructure.columnCount : 10,
            10 // минимум 10 столбцов
        );
          console.log('Загружаем данные листа с', columnCount, 'столбцами');
        
        // Безопасно обновляем настройки для отображения всех столбцов
        hot.updateSettings({
            stretchH: 'all',
            viewportColumnRenderingOffset: columnCount,
            columns: Array.from({length: columnCount}, (_, index) => ({
                data: index,
                type: index === 3 || index === 4 || index === 5 || index === 8 || index === 9 ? 'numeric' : 'text'
            }))
        });
        
        // Загружаем данные в редактор
        hot.loadData(sheetData);
          // Применяем форматирование асинхронно для лучшей производительности
        setTimeout(() => {
            try {
                // Сначала сбрасываем флаг обработки ИТОГО строк
                window._isProcessingTotalRow = false;
                
                // Принудительно показываем все столбцы
                if (typeof window.forceShowAllColumns === 'function') {
                    window.forceShowAllColumns();
                }
                
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
                  // Автоматически пересчитываем все формулы после загрузки
                if (typeof window.recalculateAll === 'function') {
                    console.log('Автоматический пересчет формул после загрузки данных');
                    window.recalculateAll();
                } else {
                    console.warn('Функция recalculateAll не найдена');
                }
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
                if (typeof window.formatRowByType === 'function') {
                    window.formatRowByType(row, name);
                }
                if (typeof window.enforceFormulasInRow === 'function') {
                    window.enforceFormulasInRow(row);
                }
            }
        }    }
    // Используем прямой вызов без рекурсии через ensureTotalRowIsLast
    if (typeof window.recalculateTotalsWithoutReordering === 'function') {
        window.recalculateTotalsWithoutReordering();
    }
    hot.render();
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
            if (typeof showSheetContextMenu === 'function') {
                showSheetContextMenu(e, index);
            }
        });
        
        // Обработчик клика по вкладке
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            switchToSheet(index);
        });
        
        // Добавляем индикатор изменений
        if (typeof isSheetModified === 'function' && isSheetModified(index)) {
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
    if (typeof scrollToActiveTab === 'function') {
        scrollToActiveTab();
    }
}

/**
 * Прокрутка к активной вкладке
 */
function scrollToActiveTab() {
    const tabsContainer = document.getElementById('sheetTabs');
    if (!tabsContainer) return;
    
    const activeTab = tabsContainer.querySelector('.btn-primary');
    if (activeTab) {
        activeTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    }
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

/**
 * Проверка, есть ли несохраненные изменения в листе
 * @param {number} sheetIndex Индекс листа
 * @returns {boolean} true если лист содержит изменения
 */
function isSheetModified(sheetIndex) {
    // Простая проверка - можно расширить более сложной логикой
    return window.isFileModified && sheetIndex === currentSheetIndex;
}

/**
 * Показать контекстное меню для вкладки листа
 * @param {Event} event Событие
 * @param {number} sheetIndex Индекс листа
 */
function showSheetContextMenu(event, sheetIndex) {
    // Заглушка функции для предотвращения ошибок
    console.log('Контекстное меню для вкладки листа', sheetIndex);
    // Реализацию можно добавить позже по необходимости
}

/**
 * Обновление статуса листа
 * @param {number} sheetIndex Индекс листа для обновления
 */
function updateSheetStatus(sheetIndex) {
    if (sheetIndex < 0 || sheetIndex >= sheets.length) return;
    
    const sheet = sheets[sheetIndex];
    const sheetData = sheet.data;
    
    // Обновляем информацию о непустых столбцах
    sheet.nonEmptyColumns = detectNonEmptyColumns(sheetData, 5);
      // Обеспечиваем видимость всех столбцов
    if (hot && sheetIndex === currentSheetIndex) {
        // Получаем количество столбцов
        const columnCount = sheetData[0] ? sheetData[0].length : 10;        
        // Убеждаемся, что все столбцы видны (плагин hiddenColumns отключен)
        // Больше не используем hiddenColumns плагин для избежания ошибок
        
        // Принудительно устанавливаем параметры для корректного отображения
        hot.updateSettings({
            stretchH: 'all',
            viewportColumnRenderingOffset: 10
        });
        
        hot.render();
    }
}

/**
 * Обнаружение непустых столбцов в данных
 * @param {Array} data Данные листа (массив строк)
 * @param {number} minNonEmptyRows Минимальное количество непустых строк для определения столбца как непустого
 * @returns {Array} Массив индексов непустых столбцов
 */
function detectNonEmptyColumns(data, minNonEmptyRows = 1) {
    if (!data || data.length === 0) return [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]; // По умолчанию отображаем все основные столбцы
    
    const result = [];
    const colCount = Math.max(data[0] ? data[0].length : 10, 10); // минимум 10 столбцов
    
    // Для смет ВСЕГДА показываем все основные столбцы (до 10)
    for (let i = 0; i < Math.min(colCount, 10); i++) {
        result.push(i);
    }
    
    // Для дополнительных столбцов (больше 10) проверяем наличие данных
    for (let col = 10; col < colCount; col++) {
        let nonEmptyCount = 0;
        const rowCount = data.length;
        
        // Проверяем ячейки в столбце (начиная с заголовков таблицы)
        for (let row = 0; row < rowCount; row++) {
            if (data[row] && data[row][col] !== undefined && data[row][col] !== null && data[row][col] !== '') {
                nonEmptyCount++;
                if (nonEmptyCount >= minNonEmptyRows) {
                    result.push(col);
                    break;
                }
            }
        }
    }
    
    return result;
}

/**
 * Создает конфигурацию для скрытия пустых столбцов
 * @param {Array} nonEmptyColumns Массив индексов непустых столбцов
 * @param {number} totalColumns Общее количество столбцов
 * @returns {Object} Конфигурация для плагина hiddenColumns
 */
function createHiddenColumnsConfig(nonEmptyColumns, totalColumns) {
    // Больше не скрываем столбцы - показываем все
    return {
        columns: [], // Пустой массив означает, что все столбцы видимы
        indicators: true
    };
}

/**
 * Принудительно отображает все столбцы таблицы
 * Эта функция решает проблему, когда в редакторе видим только один столбец
 */
function forceShowAllColumns() {
    if (!hot) return;
    
    console.log('Принудительное отображение всех столбцов');
    
    try {
        // Определяем количество столбцов
        const columnCount = hot.countCols();
        
        // Устанавливаем оптимальные ширины для всех столбцов
        const columnWidths = [];
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
        
        // Показываем все столбцы        // Плагин hiddenColumns отключен для избежания ошибок
        // Все столбцы должны быть видимы по умолчанию
        
        // Обновляем настройки таблицы
        hot.updateSettings({
            stretchH: 'all',          // Растягиваем все столбцы
            colWidths: columnWidths,  // Устанавливаем оптимальные ширины
            width: '100%',           
            viewportColumnRenderingOffset: columnCount, // Предзагружаем все столбцы
            wordWrap: true,          // Перенос текста
            manualColumnResize: true  // Разрешаем изменение ширины вручную
        });
        
        // Перерисовываем таблицу
        hot.render();
        
        // Повторно применяем настройки через небольшую задержку для надежности
        setTimeout(() => {
            hot.updateSettings({
                stretchH: 'all',
                viewportColumnRenderingOffset: columnCount
            });
            hot.render();
        }, 500);
        
        console.log('Отображение всех столбцов применено успешно');
        return true;
    } catch (error) {
        console.error('Ошибка при отображении всех столбцов:', error);
        return false;
    }
}

// Добавляем функцию в глобальную область видимости
window.forceShowAllColumns = forceShowAllColumns;

/**
 * Принудительно показывает все столбцы в редакторе
 * Глобальная функция, доступная из других JS-файлов
 */
window.forceShowAllColumns = function forceShowAllColumns() {
    if (!hot) {
        console.log('Редактор Handsontable не инициализирован, пропускаем принудительный показ столбцов');
        return;
    }
    
    try {
        console.log('Принудительно показываем все столбцы');
        
        // Определяем количество столбцов
        const columnCount = Math.max(
            fileStructure ? fileStructure.columnCount : 10,
            hot.countCols ? hot.countCols() : 10,
            10 // минимум 10 столбцов
        );
        
        console.log('Будем показывать', columnCount, 'столбцов');
        
        // Безопасно обновляем настройки таблицы для показа всех столбцов
        hot.updateSettings({
            columns: Array.from({length: columnCount}, (_, index) => ({
                data: index,
                type: [3, 4, 5, 6, 7, 8, 9].includes(index) ? 'numeric' : 'text'
            })),
            viewportColumnRenderingOffset: columnCount,
            stretchH: 'all'
        });
        
        // Принудительно перерисовываем таблицу
        hot.render();
        
        console.log('Столбцы принудительно показаны и таблица перерисована');
    } catch (error) {
        console.error('Ошибка при принудительном показе столбцов:', error);
    }
};

// Автоматически вызываем функцию после загрузки страницы
document.addEventListener('DOMContentLoaded', function() {
    // Запускаем с задержкой, чтобы все компоненты успели инициализироваться
    setTimeout(() => {
        if (typeof forceShowAllColumns === 'function') {
            forceShowAllColumns();
        }
    }, 1000);
});

/**
 * Сохранение данных Excel на сервер
 */
function saveExcelToServer() {
    if (!hot || !workbook) {
        console.error('Редактор или данные не инициализированы');
        alert('Ошибка: данные не готовы для сохранения');
        return;
    }
    
    try {
        console.log('Начинаем сохранение данных на сервер');
        showLoading(true);
        
        // Сохраняем текущие данные в sheets
        saveCurrentSheetData();
        
        // Обновляем данные в workbook
        sheets.forEach((sheet, index) => {
            const worksheet = XLSX.utils.aoa_to_sheet(sheet.data);
            workbook.Sheets[sheet.name] = worksheet;
        });
        
        // Преобразуем в бинарные данные
        const wbout = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
        const base64Data = btoa(String.fromCharCode(...new Uint8Array(wbout)));        // Получаем URL для сохранения
        let saveUrl;
        const currentPath = window.location.pathname;
        
        console.log('Текущий путь для анализа:', currentPath);
        
        if (currentPath.includes('/estimates/') && currentPath.includes('/edit')) {
            // Режим редактирования существующей сметы: /partner/estimates/139/edit
            const estimateId = currentPath.match(/\/estimates\/(\d+)/)[1];
            saveUrl = `/partner/estimates/${estimateId}/saveExcel`;
            console.log('Определен режим редактирования, ID сметы:', estimateId);
        } else if (currentPath.includes('/create')) {
            // Режим создания новой сметы
            saveUrl = '/partner/estimates/saveExcel';
            console.log('Определен режим создания новой сметы');
        } else {
            console.error('Не удалось определить URL для сохранения. Текущий путь:', currentPath);
            alert('Ошибка: не удалось определить режим сохранения');
            showLoading(false);
            return;
        }
        
        console.log('Используем URL для сохранения:', saveUrl);
        
        // Отправляем данные на сервер
        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                excel_data: base64Data,
                sheets_info: sheets.map(sheet => ({
                    name: sheet.name,
                    row_count: sheet.data.length,
                    col_count: sheet.data[0] ? sheet.data[0].length : 10
                }))
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Ошибка сервера: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Данные успешно сохранены на сервере');
                
                // Немедленно отключаем индикатор загрузки при успешном сохранении
                showLoading(false);
                if (typeof window.forceHideAllLoaders === 'function') {
                    window.forceHideAllLoaders();
                }
                
                window.isFileModified = false;
                if (typeof window.updateStatusIndicator === 'function') {
                    window.updateStatusIndicator();
                }
                
                // Показываем уведомление об успешном сохранении
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
                alertDiv.style.top = '20px';
                alertDiv.style.right = '20px';
                alertDiv.style.zIndex = '9999';
                alertDiv.innerHTML = `
                    <strong>Успешно!</strong> Смета сохранена.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(alertDiv);
                
                // Автоматически скрываем уведомление через 3 секунды
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 3000);
                
            } else {
                console.error('Ошибка при сохранении:', data.message);
                alert('Ошибка при сохранении: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Ошибка при отправке данных:', error);
            alert('Произошла ошибка при сохранении: ' + error.message);
            
            // Немедленно отключаем индикатор загрузки при ошибке
            showLoading(false);
            if (typeof window.forceHideAllLoaders === 'function') {
                window.forceHideAllLoaders();
            }
        })
        .finally(() => {
            // Гарантированно отключаем индикатор загрузки
            console.log('Принудительное отключение индикатора загрузки');
            showLoading(false);
            
            // Дополнительная проверка через небольшую задержку
            setTimeout(() => {
                console.log('Дополнительная проверка отключения загрузчика');
                showLoading(false);
                // Принудительная очистка всех возможных загрузчиков
                if (typeof window.forceHideAllLoaders === 'function') {
                    window.forceHideAllLoaders();
                }
                
                // Дополнительная проверка элементов DOM
                const loadingIndicator = document.getElementById('loadingIndicator');
                if (loadingIndicator) {
                    console.log('Принудительное скрытие loadingIndicator');
                    loadingIndicator.style.display = 'none';
                }
                
                const excelLoader = document.getElementById('excelLoader');
                if (excelLoader) {
                    console.log('Удаление excelLoader');
                    excelLoader.remove();
                }
                
                // Восстанавливаем прозрачность контейнера
                const container = document.getElementById('excelEditor');
                if (container) {
                    console.log('Восстановление прозрачности контейнера');
                    container.style.opacity = '1';
                }
            }, 200);
        });
        
    } catch (error) {
        console.error('Ошибка при подготовке данных для сохранения:', error);
        alert('Произошла ошибка при подготовке данных: ' + error.message);
        showLoading(false);
        
        // Немедленно принудительно отключаем все загрузчики
        if (typeof window.forceHideAllLoaders === 'function') {
            window.forceHideAllLoaders();
        }
        
        // Дополнительная проверка через небольшую задержку
        setTimeout(() => {
            showLoading(false);
            
            // Дополнительная проверка элементов DOM
            const loadingIndicator = document.getElementById('loadingIndicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            
            const excelLoader = document.getElementById('excelLoader');
            if (excelLoader) {
                excelLoader.remove();
            }
            
            // Восстанавливаем прозрачность контейнера
            const container = document.getElementById('excelEditor');
            if (container) {
                container.style.opacity = '1';
            }
        }, 200);
    }
}

/**
 * Сохранение данных текущего листа
 */
function saveCurrentSheetData() {
    if (!hot || !sheets[currentSheetIndex]) return;
    
    try {
        const currentData = hot.getData();
        sheets[currentSheetIndex].data = currentData;
        console.log('Данные текущего листа сохранены в память');
    } catch (error) {
        console.error('Ошибка при сохранении данных текущего листа:', error);
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
    if (typeof updateStatusIndicator === 'function') {
        updateStatusIndicator();
    }
    
    console.log(`Создан новый лист: "${trimmedName}"`);
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
    if (typeof window.ensureTotalRowIsLast === 'function') {
        window.ensureTotalRowIsLast();
    }
    
    console.log(`Переключились на лист: ${sheets[sheetIndex].name}`);
}

// Экспортируем функции в глобальную область видимости
window.formatRowByType = formatRowByType;
window.isHeaderRow = isHeaderRow;
window.addNewSheet = addNewSheet;
window.createNewSheetStructure = createNewSheetStructure;
window.switchToSheet = switchToSheet;
