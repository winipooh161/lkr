/**
 * Гарантируем, что строка ИТОГО всегда в конце таблицы
 */
function ensureTotalRowIsLast() {
    if (!hot) return false;
    
    try {
        let totalRowIndex = -1;
        let lastDataRowIndex = -1;
        const totalRows = hot.countRows();
        
        // Ищем строку с ИТОГО и последнюю строку с данными
        for (let row = 5; row < totalRows; row++) {
            const value = hot.getDataAtCell(row, 1); // колонка Б
            
            // Проверяем, если это строка ИТОГО
            if (value && typeof value === 'string' && value.toUpperCase().indexOf('ИТОГО') >= 0) {
                totalRowIndex = row;
            } 
            // Проверяем, есть ли данные в этой строке
            else if (hot.getDataAtCell(row, 2) || hot.getDataAtCell(row, 3)) {
                lastDataRowIndex = Math.max(lastDataRowIndex, row);
            }
        }
        
        // Если нашли строку ИТОГО и она не последняя после данных
        if (totalRowIndex >= 0 && totalRowIndex !== lastDataRowIndex + 1) {
            // Сохраняем данные из строки ИТОГО
            const totalRowData = hot.getDataAtRow(totalRowIndex);
            
            // Удаляем строку ИТОГО с текущей позиции
            hot.alter('remove_row', totalRowIndex);
            
            // Добавляем строку ИТОГО после последней строки с данными
            const newTotalRowIndex = lastDataRowIndex + 1;
            hot.alter('insert_row', newTotalRowIndex);
            
            // Вставляем данные ИТОГО в новую строку
            hot.populateFromArray(newTotalRowIndex, 0, [totalRowData]);
            
            return true;
        }
        
        return false;
    } catch (error) {
        console.error('Ошибка при перемещении строки ИТОГО:', error);
        return false;
    }
}

/**
 * Принудительное добавление формул в строку
 * @param {number} row Индекс строки
 */
function enforceFormulasInRow(row) {
    if (!hot) return;
    
    try {
        // Проверяем, не является ли эта строка строкой ИТОГО
        const cellValue = hot.getDataAtCell(row, 1);
        if (cellValue && typeof cellValue === 'string' && cellValue.toUpperCase().indexOf('ИТОГО') >= 0) {
            // Для строки ИТОГО устанавливаем формулы суммирования
            return;  // Пропускаем, т.к. формулы для ИТОГО устанавливаются в recalculateTotals
        }
        
        // Получаем значения из ячеек и конвертируем их в числа
        let quantity = parseFloat(hot.getDataAtCell(row, 3));
        quantity = isNaN(quantity) ? 0 : quantity;
        
        let price = parseFloat(hot.getDataAtCell(row, 4));
        price = isNaN(price) ? 0 : price;
        
        let markup = parseFloat(hot.getDataAtCell(row, 6));
        markup = isNaN(markup) ? 0 : markup;
        
        let discount = parseFloat(hot.getDataAtCell(row, 7));
        discount = isNaN(discount) ? 0 : discount;
        
        // Всегда пересчитываем значения, даже если они уже есть
        // Стоимость = кол-во * цена
        const cost = quantity * price;
        
        // Расчет цены для заказчика = цена * (1 + наценка/100) * (1 - скидка/100)
        let priceForClient = price;
        if (markup > 0) priceForClient *= (1 + markup/100);
        if (discount > 0) priceForClient *= (1 - discount/100);
        
        // Расчет стоимости для заказчика = кол-во * цена для заказчика
        const amountForClient = quantity * priceForClient;
        
        // Устанавливаем значения в ячейки с принудительным форматированием как числа
        hot.setDataAtCell(row, 5, isNaN(cost) ? 0 : cost, 'calculate');
        hot.setDataAtCell(row, 8, isNaN(priceForClient) ? price : priceForClient, 'calculate');
        hot.setDataAtCell(row, 9, isNaN(amountForClient) ? 0 : amountForClient, 'calculate');
    } catch (error) {
        console.error('Ошибка при расчете формул в строке', row, error);
    }
}

/**
 * Пересчет значений одной строки
 * @param {number} row Индекс строки
 */
function recalculateRow(row) {
    if (!hot) return;
    
    try {
        const totalRow = hot.countRows() - 1;
        
        // Пропускаем итоговую строку
        if (row === totalRow) return;
        
        // Получаем значения из ячеек и убеждаемся что они числа
        const quantity = parseFloat(hot.getDataAtCell(row, 3)) || 0;
        const price = parseFloat(hot.getDataAtCell(row, 4)) || 0;
        
        // Расчет стоимости = кол-во * цена
        const cost = quantity * price;
        hot.setDataAtCell(row, 5, isNaN(cost) ? 0 : cost, 'calculate');
        
        const markup = parseFloat(hot.getDataAtCell(row, 6)) || 0;
        const discount = parseFloat(hot.getDataAtCell(row, 7)) || 0;
        
        // Расчет цены для заказчика = цена * (1 + наценка/100) * (1 - скидка/100)
        const priceForClient = price * (1 + markup/100) * (1 - discount/100);
        
        // Расчет стоимости для заказчика = кол-во * цена для заказчика
        const amountForClient = quantity * priceForClient;
        
        // Устанавливаем значения в ячейки (включая стоимость)
        hot.setDataAtCell(row, 8, isNaN(priceForClient) ? price : priceForClient, 'calculate');
        hot.setDataAtCell(row, 9, isNaN(amountForClient) ? 0 : amountForClient, 'calculate');
        
        // После изменения одной строки пересчитываем итоги
        recalculateTotals();
    } catch (error) {
        console.error('Error recalculating row:', error);
    }
}

/**
 * Пересчет всех строк
 */
function recalculateAll() {
    if (hot) {
        showLoading(true);
        setTimeout(() => {
            try {
                // Убедимся, что строка ИТОГО всегда в конце
                if (typeof window.ensureTotalRowIsLast === 'function') {
                    window.ensureTotalRowIsLast();
                }
                
                // Пересчитываем формулы для всех строк
                const totalRow = hot.countRows() - 1;
                for (let row = 5; row < totalRow; row++) {
                    const name = hot.getDataAtCell(row, 1);
                    if (name) {
                        enforceFormulasInRow(row);
                    }
                }
                recalculateTotals();
                hot.render();
                alert('Все формулы успешно пересчитаны');
                isFileModified = true;
                updateStatusIndicator();
            } catch (error) {
                console.error('Error during recalculation:', error);
                alert('Ошибка при пересчете: ' + error.message);
            } finally {
                showLoading(false);
            }
        }, 200);
    }
}

/**
 * Пересчет итогов без перемещения строки ИТОГО
 * Используется для избежания рекурсивных вызовов
 */
function recalculateTotalsWithoutReordering() {
    if (!hot) return;
    
    try {
        // Убедимся, что в таблице только одна строка с ИТОГО и она в конце
        const rowCount = hot.countRows();
        const totalRow = rowCount - 1;
        let totalRows = []; // Массив для хранения всех строк ИТОГО
        let lastDataRow = -1; // Последняя строка с данными
        let dataRows = []; // Массив строк с данными
        
        // Находим все строки с "ИТОГО" или "ВСЕГО" и строки с данными
        for (let row = 5; row < rowCount; row++) {
            const cellContent = hot.getDataAtCell(row, 1);
            const colBHasContent = cellContent !== null && cellContent !== '';
            const colCHasContent = hot.getDataAtCell(row, 2) !== null && hot.getDataAtCell(row, 2) !== '';
            const colDHasContent = hot.getDataAtCell(row, 3) !== null && hot.getDataAtCell(row, 3) !== '';
            
            // Проверка на строку ИТОГО
            if (colBHasContent && typeof cellContent === 'string' && 
                (cellContent.toUpperCase().includes('ИТОГО') || cellContent.toUpperCase().includes('ВСЕГО'))) {
                totalRows.push(row);
            } 
            // Проверка на строку с данными (не заголовок раздела)
            else if ((colBHasContent && (colCHasContent || colDHasContent)) &&
                    !(cellContent && typeof cellContent === 'string' && 
                      (cellContent.includes('Раздел') || cellContent.match(/^\d+\./)))) {
                
                lastDataRow = row;
                dataRows.push(row);
            }
        }
        
        // Проверяем различные ситуации с итоговыми строками
        if (totalRows.length === 0) {
            // Если итоговых строк нет, создаем новую итоговую строку
            const newTotalRow = lastDataRow >= 0 ? lastDataRow + 1 : totalRow;
            
            if (newTotalRow !== totalRow) {
                // Если у нас есть строки с данными, но последняя не совпадает с последней строкой таблицы
                hot.alter('insert_row', newTotalRow);
            }
            
            hot.setDataAtCell(newTotalRow, 1, 'ИТОГО:', 'formatting');
            totalRows = [newTotalRow];
            
            // Применяем форматирование для итоговой строки
            for (let col = 0; col < 10; col++) {
                hot.setCellMeta(newTotalRow, col, 'className', 'htBold');
                if (col >= 5 && col <= 9) {
                    hot.setCellMeta(newTotalRow, col, 'className', 'htBold htNumeric');
                }
            }
        } else if (totalRows.length > 1) {
            // Если обнаружено более одной строки ИТОГО, выполним действия по их объединению позже
            console.warn(`Обнаружено ${totalRows.length} строк ИТОГО при пересчете без перемещения. ` +
                         `Используется последняя строка (${totalRows[totalRows.length - 1]}) для расчетов.`);
        }
        
        // Для расчетов берем последнюю итоговую строку, если их несколько
        const actualTotalRow = totalRows.length > 0 ? totalRows[totalRows.length - 1] : (lastDataRow >= 0 ? lastDataRow + 1 : totalRow);
          // Инициализируем суммы только для столбцов со стоимостью
        const totals = {
            5: 0, // Стоимость (сумма)
            9: 0  // Стоимость для заказчика (сумма)
        };
        
        // Если у нас имеются строки для суммирования
        if (dataRows.length > 0) {
            // Считаем суммы только по строкам с данными (dataRows)
            for (let i = 0; i < dataRows.length; i++) {
                const row = dataRows[i];
                
                // Суммируем только значения колонок "Стоимость" и "Стоимость для заказчика"
                // с проверкой типа данных и конвертацией
                let costValue = hot.getDataAtCell(row, 5);
                if (costValue === null || costValue === undefined || costValue === '') {
                    // Если ячейка пустая, пробуем рассчитать значение
                    const qty = parseFloat(hot.getDataAtCell(row, 3)) || 0;
                    const price = parseFloat(hot.getDataAtCell(row, 4)) || 0;
                    costValue = qty * price;
                } else {
                    costValue = parseFloat(costValue);
                }
                
                let clientCostValue = hot.getDataAtCell(row, 9);
                if (clientCostValue === null || clientCostValue === undefined || clientCostValue === '') {
                    // Если ячейка пустая, пробуем рассчитать значение
                    const qty = parseFloat(hot.getDataAtCell(row, 3)) || 0;
                    const clientPrice = parseFloat(hot.getDataAtCell(row, 8)) || 0;
                    clientCostValue = qty * clientPrice;
                } else {
                    clientCostValue = parseFloat(clientCostValue);
                }
                
                // Добавляем только числовые значения к итогам
                totals[5] += isNaN(costValue) ? 0 : costValue;
                totals[9] += isNaN(clientCostValue) ? 0 : clientCostValue;
            }
        }
        
        // Устанавливаем итоговые значения, округляя до двух знаков после запятой
        hot.setDataAtCell(actualTotalRow, 5, Math.round((totals[5] + Number.EPSILON) * 100) / 100, 'calculate');
        hot.setDataAtCell(actualTotalRow, 9, Math.round((totals[9] + Number.EPSILON) * 100) / 100, 'calculate');
        
        // Убедимся, что ячейка с названием ИТОГО содержит правильный текст
        hot.setDataAtCell(actualTotalRow, 1, 'ИТОГО:', 'formatting');
        
        // Очищаем ячейки в столбцах, где итоги не нужны
        hot.setDataAtCell(actualTotalRow, 3, '', 'calculate'); // Кол-во
        hot.setDataAtCell(actualTotalRow, 4, '', 'calculate'); // Цена
        hot.setDataAtCell(actualTotalRow, 6, '', 'calculate'); // Наценка, %
        hot.setDataAtCell(actualTotalRow, 7, '', 'calculate'); // Скидка, %
        hot.setDataAtCell(actualTotalRow, 8, '', 'calculate'); // Цена для заказчика
    } catch (error) {
        console.error('Error recalculating totals without reordering:', error);
    }
}

/**
 * Пересчет итогов
 * Сначала убеждается, что итоговая строка в конце, затем пересчитывает итоги
 */
function recalculateTotals() {
    if (!hot) return;
    
    try {
        // Убедимся, что мы не в рекурсивном вызове
        if (window._isProcessingTotalRow) {
            // Если уже идет обработка ИТОГО строки, используем безопасную версию без рекурсии
            recalculateTotalsWithoutReordering();
            return;
        }
        
        // Проверяем наличие дублирующихся строк ИТОГО
        let totalRows = [];
        for (let row = 5; row < hot.countRows(); row++) {
            const cellContent = hot.getDataAtCell(row, 1);
            if (cellContent && typeof cellContent === 'string' && 
                (cellContent.includes('ИТОГО') || cellContent.includes('Итого') || cellContent.includes('ВСЕГО'))) {
                totalRows.push(row);
            }
        }
        
        // Если найдено более одной строки ИТОГО или итоговая строка не последняя
        if (totalRows.length > 1 || (totalRows.length === 1 && totalRows[0] !== hot.countRows() - 1)) {
            // Убедимся, что строка ИТОГО всегда в конце перед расчетом итогов
            if (typeof window.ensureTotalRowIsLast === 'function') {
                window._isProcessingTotalRow = true;
                window.ensureTotalRowIsLast();
                window._isProcessingTotalRow = false;
                // После вызова ensureTotalRowIsLast итоги будут пересчитаны автоматически
                return;
            }
        }
        
        // Если не было необходимости в перемещении строк или функция ensureTotalRowIsLast недоступна,
        // просто выполняем расчет итогов
        recalculateTotalsWithoutReordering();
    } catch (error) {
        console.error('Error recalculating totals:', error);
    }
}

/**
 * Принудительный пересчет всех формул
 */
function recalculateAllFormulas() {
    if (hot) {
        showLoading(true);
        setTimeout(() => {
            try {
                // Убедимся, что строка ИТОГО всегда в конце
                if (typeof window.ensureTotalRowIsLast === 'function') {
                    window.ensureTotalRowIsLast();
                }
                
                // Принудительно пересчитываем все строки
                const totalRow = hot.countRows() - 1;
                for (let row = 5; row < totalRow; row++) {
                    const name = hot.getDataAtCell(row, 1);
                    if (name) {
                        enforceFormulasInRow(row);
                    }
                }
                recalculateTotals();
                hot.render();
                alert('Все формулы успешно пересчитаны');
                isFileModified = true;
                updateStatusIndicator();
            } catch (error) {
                console.error('Error during recalculation:', error);
                alert('Ошибка при пересчете: ' + error.message);
            } finally {
                showLoading(false);
            }
        }, 200);
    }
}

/**
 * Пересчет итогов без перемещения строки ИТОГО
 * Используется для избежания рекурсивных вызовов
 */
window.recalculateTotalsWithoutReordering = recalculateTotalsWithoutReordering;
window.recalculateTotals = recalculateTotals;
window.ensureTotalRowIsLast = ensureTotalRowIsLast;
