/**
 * Обновление номеров строк с иерархической нумерацией
 */
function updateRowNumbers() {
    if (!hot) return;
    
    try {
        // Номер текущего раздела и подраздела
        let currentSectionNumber = 0;
        let currentItemNumber = 0;
        let lastRowWasSection = false;
        
        // Итоговая строка (исключаем из перенумерации)
        const totalRow = hot.countRows() - 1;
        
        // Перебираем все строки кроме заголовков (с 5-й) и итоговой
        for (let row = 5; row < totalRow; row++) {
            // Получаем значение ячейки в колонке названия
            const title = hot.getDataAtCell(row, 1);
            
            // Если нет названия, пропускаем строку
            if (!title) continue;
            
            // Проверяем, является ли строка заголовком раздела
            // Проверяем по метаданным ячейки либо по формату текста
            let isSection = false;
              // Проверяем, установлен ли класс htGroupHeader для этой ячейки
            const cellMeta = hot.getCellMeta(row, 1);
            if (cellMeta.className && cellMeta.className.includes('htGroupHeader')) {
                isSection = true;
            }
            
            // Проверяем по пустым значениям в колонках 2-9, что является характеристикой разделов
            // (Это более надежный способ, чем проверка на верхний регистр)
            if (!isSection) {
                let emptyCellCount = 0;
                for (let col = 2; col <= 9; col++) {
                    if (!hot.getDataAtCell(row, col) || hot.getDataAtCell(row, col) === '') {
                        emptyCellCount++;
                    }
                }
                // Если все ячейки справа пустые, это скорее всего раздел
                if (emptyCellCount >= 7) {
                    isSection = true;
                    // Устанавливаем стиль для раздела, если он еще не установлен
                    for (let col = 0; col <= 9; col++) {
                        hot.setCellMeta(row, col, 'className', 'htGroupHeader');
                        hot.setCellMeta(row, col, 'style', {
                            font: { bold: true },
                            fill: { type: 'solid', color: '#F0F0F0' }
                        });
                    }
                }
            }
            
            // Проверяем по значению в первой колонке (если оно имеет формат "N.")
            const firstCellValue = hot.getDataAtCell(row, 0);
            if (!isSection && 
                firstCellValue && 
                typeof firstCellValue === 'string' && 
                /^\d+\.$/.test(firstCellValue)) {
                isSection = true;
                // Устанавливаем стиль для раздела, если он еще не установлен
                for (let col = 0; col <= 9; col++) {
                    hot.setCellMeta(row, col, 'className', 'htGroupHeader');
                }
            }
            
            if (isSection) {
                // Это заголовок раздела
                currentSectionNumber++;
                currentItemNumber = 0; // Сбрасываем счетчик элементов
                hot.setDataAtCell(row, 0, currentSectionNumber + '.', 'numbering');
                lastRowWasSection = true;
            } else {
                // Это обычная строка элемента
                currentItemNumber++;
                hot.setDataAtCell(row, 0, currentSectionNumber + '.' + currentItemNumber, 'numbering');
                lastRowWasSection = false;
            }
        }        
        hot.render(); // Обновляем отображение
        window.isFileModified = true;
        if (typeof window.updateStatusIndicator === 'function') {
            window.updateStatusIndicator();
        }
    } catch (error) {
        console.error('Ошибка при обновлении номеров строк:', error);
        alert('Произошла ошибка при обновлении нумерации строк: ' + error.message);
    }
}

/**
 * Добавление новой строки
 * @param {string} [workName] - Название работы (опционально)
 * @param {string} [workUnit] - Единица измерения (опционально)
 * @param {string|number} [workQuantity] - Количество (опционально)
 */
function addNewRow(workName, workUnit, workQuantity) {    // Убедимся, что строка ИТОГО всегда в конце перед добавлением новой строки
    if (typeof window.ensureTotalRowIsLast === 'function') {
        window.ensureTotalRowIsLast();
    }
    
    // Получаем текущие выделенные ячейки
    const selected = hot.getSelected();
    
    // Определяем, куда вставлять строку
    let insertAt = hot.countRows() - 1; // По умолчанию перед итоговой строкой
    
    if (selected && selected.length > 0) {
        const selectedRow = selected[0][0];
        // Проверяем, не является ли выбранная строка итоговой
        const isLastRow = selectedRow === hot.countRows() - 1;
        const content = hot.getDataAtCell(selectedRow, 1);
        const isTotalRow = content && typeof content === 'string' && 
                         (content.includes('ИТОГО') || content.includes('Итого'));
        
        if (isLastRow || isTotalRow) {
            // Если выбрана итоговая строка, вставляем перед ней
            insertAt = selectedRow;
        } else {
            // Иначе вставляем после выделенной строки
            insertAt = selectedRow + 1;
        }
    }
    
    // Вставляем строку
    hot.alter('insert_row', insertAt, 1);
    
    // Находим последний номер строки
    let lastNumber = 0;
    for (let row = 5; row < hot.countRows() - 1; row++) {
        const num = parseInt(hot.getDataAtCell(row, 0));
        if (!isNaN(num) && num > lastNumber) {
            lastNumber = num;
        }
    }
    
    // Заполняем новую строку базовыми данными
    hot.setDataAtCell(insertAt, 0, lastNumber + 1); // №
    hot.setDataAtCell(insertAt, 1, workName || ''); // Название работы
    hot.setDataAtCell(insertAt, 2, workUnit || 'раб'); // Ед. изм.
    hot.setDataAtCell(insertAt, 3, workQuantity || 0); // Кол-во
    hot.setDataAtCell(insertAt, 4, 0); // Цена
    hot.setDataAtCell(insertAt, 5, 0); // Стоимость
    hot.setDataAtCell(insertAt, 6, 0); // Наценка, %
    hot.setDataAtCell(insertAt, 7, 0); // Скидка, %
    hot.setDataAtCell(insertAt, 8, 0); // Цена для заказчика
    hot.setDataAtCell(insertAt, 9, 0); // Стоимость для заказчика
    
    // Прокручиваем к добавленной строке и выделяем ячейку для позиции
    hot.scrollViewportTo(insertAt);
    hot.selectCell(insertAt, 1);
      // Пересчитываем формулы для новой строки
    if (typeof window.recalculateRow === 'function') {
        window.recalculateRow(insertAt);
    }
    
    // Устанавливаем флаг изменения
    window.isFileModified = true;
    if (typeof window.updateStatusIndicator === 'function') {
        window.updateStatusIndicator();
    }
}

/**
 * Обновление всех номеров строк
 */
function updateAllRowNumbers() {
    if (hot) {
        showLoading(true);
        setTimeout(() => {
            try {
                // Вызываем функцию обновления номеров строк                updateRowNumbers();
                hot.render();
                alert('Нумерация успешно обновлена');
                window.isFileModified = true;
                if (typeof window.updateStatusIndicator === 'function') {
                    window.updateStatusIndicator();
                }
            } catch (error) {
                console.error('Error updating row numbers:', error);
                alert('Ошибка при обновлении нумерации: ' + error.message);
            } finally {
                showLoading(false);
            }
        }, 100);
    }
}

/**
 * Добавление нового раздела
 * @param {string} [sectionName] - Название раздела (опционально)
 */
function addNewSection(sectionName) {
    // Запрашиваем название раздела, если не указано
    const finalSectionName = sectionName || prompt('Введите название раздела:', 'Новый раздел');
    
    if (finalSectionName === null) {
        return; // Пользователь нажал "Отмена"
    }
      // Убедимся, что строка ИТОГО всегда в конце перед добавлением нового раздела
    if (typeof window.ensureTotalRowIsLast === 'function') {
        window.ensureTotalRowIsLast();
    }
      
    if (hot) {
        // Получаем текущие выделенные ячейки
        const selected = hot.getSelected();
        
        // Определяем, куда вставлять строку
        let insertAt = hot.countRows() - 1; // По умолчанию перед итоговой строкой
        
        if (selected && selected.length > 0) {
            const selectedRow = selected[0][0];
            // Проверяем, не является ли выбранная строка итоговой
            const isLastRow = selectedRow === hot.countRows() - 1;
            const content = hot.getDataAtCell(selectedRow, 1);
            const isTotalRow = content && typeof content === 'string' && 
                             (content.includes('ИТОГО') || content.includes('Итого'));
            
            if (isLastRow || isTotalRow) {
                // Если выбрана итоговая строка, вставляем перед ней
                insertAt = selectedRow;
            } else {
                // Иначе вставляем после выделенной строки
                insertAt = selectedRow + 1;
            }
        }
        
        // Вставляем строку
        hot.alter('insert_row', insertAt, 1);
        
        // Форматируем строку как заголовок раздела
        hot.setDataAtCell(insertAt, 0, '', 'numbering'); // Номер раздела будет установлен позже
        
        // Используем полное название раздела без преобразования в верхний регистр
        // (как в EstimateTemplateService.php)
        hot.setDataAtCell(insertAt, 1, finalSectionName, 'numbering');
        
        // Очищаем ячейки, которые не должны иметь значений
        for (let col = 2; col <= 9; col++) {
            hot.setDataAtCell(insertAt, col, '', 'numbering');
        }
        
        // Применяем стилизацию точно как в EstimateTemplateService.php
        for (let col = 0; col <= 9; col++) {
            hot.setCellMeta(insertAt, col, 'className', 'htGroupHeader');
        }
          // Применяем форматирование заголовка раздела точно как в EstimateTemplateService.php
        for (let col = 0; col <= 9; col++) {
            hot.setCellMeta(insertAt, col, 'style', {
                font: { bold: true },
                fill: { type: 'solid', color: '#F0F0F0' }
            });
        }
        
        // Прокручиваем к добавленной строке и выделяем её
        hot.scrollViewportTo(insertAt);
        hot.selectCell(insertAt, 1);
        
        // После добавления заголовка обновляем нумерацию автоматически
        updateRowNumbers();
        
        // Устанавливаем флаг изменений
        isFileModified = true;
        updateStatusIndicator();
        
        // Применяем рендеринг, чтобы стили вступили в силу немедленно
        hot.render();
        
        // Прокручиваем к добавленной строке и выделяем её
        hot.scrollViewportTo(insertAt);
        hot.selectCell(insertAt, 1);
        
        // После добавления заголовка обновляем нумерацию автоматически
        updateRowNumbers();
        
        // Устанавливаем флаг изменений        isFileModified = true;
        updateStatusIndicator();
        
        // Применяем рендеринг, чтобы стили вступили в силу немедленно
        hot.render();
    }
}

// Экспортируем функции в глобальную область видимости для использования в обработчиках кнопок
window.addNewRow = addNewRow;
window.addNewSection = addNewSection;
window.updateRowNumbers = updateRowNumbers;
window.updateAllRowNumbers = updateAllRowNumbers;
