/**
 * Принудительное добавление формул в строку
 * @param {number} row Индекс строки
 */
function enforceFormulasInRow(row) {
    if (!hot) return;
    
    // Получаем значения из ячеек
    const quantity = parseFloat(hot.getDataAtCell(row, 3)) || 0;
    const price = parseFloat(hot.getDataAtCell(row, 4)) || 0;
    const markup = parseFloat(hot.getDataAtCell(row, 6)) || 0;
    const discount = parseFloat(hot.getDataAtCell(row, 7)) || 0;
    
    // Всегда пересчитываем значения, даже если они уже есть
    // Стоимость = кол-во * цена
    const cost = quantity * price;
    
    // Расчет цены для заказчика = цена * (1 + наценка/100) * (1 - скидка/100)
    const priceForClient = price * (1 + markup/100) * (1 - discount/100);
    
    // Расчет стоимости для заказчика = кол-во * цена для заказчика
    const amountForClient = quantity * priceForClient;
    
    // Устанавливаем значения в ячейки
    hot.setDataAtCell(row, 5, cost, 'calculate');
    hot.setDataAtCell(row, 8, priceForClient, 'calculate');
    hot.setDataAtCell(row, 9, amountForClient, 'calculate');
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
        
        // Получаем значения из ячеек
        const quantity = parseFloat(hot.getDataAtCell(row, 3)) || 0;
        const price = parseFloat(hot.getDataAtCell(row, 4)) || 0;
        
        // Расчет стоимости = кол-во * цена
        const cost = quantity * price;
        hot.setDataAtCell(row, 5, cost, 'calculate');
        
        const markup = parseFloat(hot.getDataAtCell(row, 6)) || 0;
        const discount = parseFloat(hot.getDataAtCell(row, 7)) || 0;
        
        // Расчет цены для заказчика = цена * (1 + наценка/100) * (1 - скидка/100)
        const priceForClient = price * (1 + markup/100) * (1 - discount/100);
        
        // Расчет стоимости для заказчика = кол-во * цена для заказчика
        const amountForClient = quantity * priceForClient;
        
        // Устанавливаем значения в ячейки (включая стоимость)
        hot.setDataAtCell(row, 8, priceForClient, 'calculate');
        hot.setDataAtCell(row, 9, amountForClient, 'calculate');
        
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
        
        // Находим все строки с "ИТОГО" или "ВСЕГО"
        for (let row = 5; row < rowCount; row++) {
            const cellContent = hot.getDataAtCell(row, 1);
            if (cellContent && typeof cellContent === 'string' && 
                (cellContent.includes('ИТОГО') || cellContent.includes('Итого') || cellContent.includes('ВСЕГО'))) {
                totalRows.push(row);
            }
        }
        
        // Проверяем различные ситуации с итоговыми строками
        if (totalRows.length === 0) {
            // Если итоговых строк нет, создаем новую итоговую строку
            hot.setDataAtCell(totalRow, 1, 'ИТОГО:', 'formatting');
            totalRows = [totalRow];
            
            // Применяем форматирование для итоговой строки
            for (let col = 0; col < 10; col++) {
                hot.setCellMeta(totalRow, col, 'className', 'htBold');
                if (col >= 5 && col <= 9) {
                    hot.setCellMeta(totalRow, col, 'className', 'htBold htNumeric');
                }
            }
        } else if (totalRows.length > 1) {
            // Если обнаружено более одной строки ИТОГО, предупреждаем в консоли
            console.warn(`Обнаружено ${totalRows.length} строк ИТОГО при пересчете без перемещения. ` +
                         `Используется последняя строка (${totalRows[totalRows.length - 1]}) для расчетов.`);
        }
        
        // Для расчетов берем последнюю итоговую строку, если их несколько
        const actualTotalRow = totalRows.length > 0 ? totalRows[totalRows.length - 1] : totalRow;
        
        // Инициализируем суммы только для столбцов со стоимостью
        const totals = {
            5: 0, // Стоимость (сумма)
            9: 0  // Стоимость для заказчика (сумма)
        };
        
        // Считаем суммы только по нужным колонкам, исключая все итоговые строки
        for (let row = 5; row < rowCount; row++) {
            // Пропускаем все строки ИТОГО при подсчете сумм
            if (totalRows.includes(row)) continue;
            
            // Проверяем, есть ли что-то в строке (наличие значения в столбце "Позиция")
            const hasPosition = hot.getDataAtCell(row, 1) !== null && hot.getDataAtCell(row, 1) !== '';
            
            // Проверяем, не является ли строка заголовком раздела
            const isSectionHeader = hasPosition && hot.getCellMeta(row, 1).className && 
                                  hot.getCellMeta(row, 1).className.includes('htGroupHeader');
            
            // Если строка не пустая и не является заголовком раздела
            if (hasPosition && !isSectionHeader) {
                // Суммируем только значения колонок "Стоимость" и "Стоимость для заказчика"
                totals[5] += parseFloat(hot.getDataAtCell(row, 5)) || 0;
                totals[9] += parseFloat(hot.getDataAtCell(row, 9)) || 0;
            }
        }
        
        // Устанавливаем итоговые значения только для нужных столбцов в выбранной итоговой строке
        hot.setDataAtCell(actualTotalRow, 5, totals[5], 'calculate');
        hot.setDataAtCell(actualTotalRow, 9, totals[9], 'calculate');
        
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

// Экспортируем все функции в глобальную область видимости
window.recalculateRow = recalculateRow;
window.recalculateTotals = recalculateTotals;
window.recalculateAll = recalculateAll;
window.enforceFormulasInRow = enforceFormulasInRow;
window.formatRowByType = formatRowByType;
