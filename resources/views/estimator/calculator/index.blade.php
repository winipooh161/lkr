@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2">Калькулятор строительных материалов</h1>
            <p class="text-muted">Рассчитайте необходимое количество материалов для ваших смет</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Расчет материалов</h5>
        </div>
        <div class="card-body">
            <form id="calculator-form">
                @csrf
                <div id="materials-container">
                    <div class="material-row mb-4" data-index="0">
                        <div class="row align-items-end">
                            <div class="col-md-4 col-12 mb-md-0 mb-2">
                                <label class="form-label">Материал</label>
                                <select name="calculations[0][material_id]" class="form-select material-select" required>
                                    <option value="">Выберите материал</option>
                                    @foreach($materials as $id => $material)
                                        <option value="{{ $id }}">{{ $material['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-6 mb-md-0 mb-2">
                                <label class="form-label">Объем, м²/м.п.</label>
                                <input type="number" step="0.01" min="0" name="calculations[0][volume]" 
                                       class="form-control volume-input" placeholder="0" required>
                            </div>
                            <div class="col-md-3 col-6 mb-md-0 mb-2">
                                <label class="form-label">Слой</label>
                                <input type="number" step="0.01" min="0" name="calculations[0][layer]" 
                                       class="form-control layer-input" placeholder="0" value="1">
                                <small class="form-text text-muted layer-hint"></small>
                            </div>
                            <div class="col-md-2 col-12 text-md-start text-end mt-md-0 mt-2">
                                <button type="button" class="btn btn-outline-danger remove-material w-md-auto w-100" title="Удалить" disabled>
                                    <i class="fas fa-trash"></i> <span class="d-inline d-md-none">Удалить</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Ширина комнаты (м)</label>
                                    <input type="number" class="form-control" name="materials[0][room_width]" step="0.01" placeholder="Ширина комнаты" value="0">
                                </div>
                            </div>
                        </div>

                        <h6 class="mt-3">Потолок</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Длина потолка (м)</label>
                                    <input type="number" class="form-control" name="materials[0][ceiling_length]" step="0.01" placeholder="Длина потолка" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Ширина потолка (м)</label>
                                    <input type="number" class="form-control" name="materials[0][ceiling_width]" step="0.01" placeholder="Ширина потолка" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <div>
                                <button type="button" class="btn btn-danger btn-sm remove-room" data-index="0">
                                    <i class="fas fa-trash me-1"></i>Удалить комнату
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <button type="button" id="add-room" class="btn btn-outline-primary">
                        <i class="fas fa-plus-circle me-1"></i>Добавить комнату
                    </button>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5>Материалы</h5>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Выберите материалы для расчета
                        </div>
                    </div>
                </div>

                <div id="materials-selection">
                    <div class="row mb-2 align-items-center">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_materials[]" value="putty" id="putty" checked>
                                <label class="form-check-label" for="putty">Шпаклевка</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Цена/кг</span>
                                <input type="number" class="form-control material-price" data-material="putty" value="120" step="0.01">
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2 align-items-center">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_materials[]" value="primer" id="primer" checked>
                                <label class="form-check-label" for="primer">Грунтовка</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Цена/л</span>
                                <input type="number" class="form-control material-price" data-material="primer" value="250" step="0.01">
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2 align-items-center">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_materials[]" value="paint" id="paint" checked>
                                <label class="form-check-label" for="paint">Краска</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Цена/л</span>
                                <input type="number" class="form-control material-price" data-material="paint" value="500" step="0.01">
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2 align-items-center">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_materials[]" value="wallpaper" id="wallpaper" checked>
                                <label class="form-check-label" for="wallpaper">Обои</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Цена/м²</span>
                                <input type="number" class="form-control material-price" data-material="wallpaper" value="800" step="0.01">
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2 align-items-center">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_materials[]" value="glue" id="glue" checked>
                                <label class="form-check-label" for="glue">Клей для обоев</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Цена/кг</span>
                                <input type="number" class="form-control material-price" data-material="glue" value="300" step="0.01">
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2 align-items-center">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_materials[]" value="laminate" id="laminate" checked>
                                <label class="form-check-label" for="laminate">Ламинат</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Цена/м²</span>
                                <input type="number" class="form-control material-price" data-material="laminate" value="1200" step="0.01">
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2 align-items-center">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_materials[]" value="putty_ceiling" id="putty_ceiling" checked>
                                <label class="form-check-label" for="putty_ceiling">Шпаклевка потолочная</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Цена/кг</span>
                                <input type="number" class="form-control material-price" data-material="putty_ceiling" value="150" step="0.01">
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2 align-items-center">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_materials[]" value="paint_ceiling" id="paint_ceiling" checked>
                                <label class="form-check-label" for="paint_ceiling">Краска потолочная</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Цена/л</span>
                                <input type="number" class="form-control material-price" data-material="paint_ceiling" value="450" step="0.01">
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="button" id="calculate-btn" class="btn btn-primary">
                        <i class="fas fa-calculator me-1"></i>Рассчитать
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4" id="results-card" style="display: none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Результаты расчета</h5>
            <button type="button" id="export-pdf-btn" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-file-pdf me-1"></i>Экспорт в PDF
            </button>
        </div>
        <div class="card-body" id="results-container">
            <!-- Здесь будут отображаться результаты расчета -->
        </div>
    </div>
</div>

<form id="exportForm" method="POST" action="" style="display: none;">
    @csrf
    <input type="hidden" name="calculationData" id="exportData">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Загрузка сохраненных цен
    fetchSavedPrices();
    
    // Инициализация калькулятора
    initializeCalculator();
});

// Функция для загрузки сохраненных цен
function fetchSavedPrices() {
    fetch('{{ route("estimator.calculator.get-prices") }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.prices) {
            // Устанавливаем сохраненные цены
            const priceInputs = document.querySelectorAll('.material-price');
            priceInputs.forEach(input => {
                const material = input.getAttribute('data-material');
                if (data.prices[material] !== undefined) {
                    input.value = data.prices[material];
                }
            });
        }
    })
    .catch(error => console.error('Error loading prices:', error));
}

function initializeCalculator() {
    const STORAGE_KEY = 'calculator_materials_data';
    const PRICES_STORAGE_KEY = 'calculator_material_prices';
    
    // Обработчик добавления комнаты
    document.getElementById('add-room').addEventListener('click', function() {
        const materialsContainer = document.getElementById('materials-container');
        const index = materialsContainer.querySelectorAll('.material-row').length;
        
        const roomHtml = `
            <div class="material-row mb-4" data-index="${index}">
                <hr class="my-4">
                <h6>Стены</h6>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Длина стен (м)</label>
                            <input type="number" class="form-control" name="materials[${index}][walls_length]" step="0.01" placeholder="Длина стен" value="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Высота стен (м)</label>
                            <input type="number" class="form-control" name="materials[${index}][walls_height]" step="0.01" placeholder="Высота стен" value="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Площадь проемов (м²)</label>
                            <input type="number" class="form-control" name="materials[${index}][openings_area]" step="0.01" placeholder="Площадь проемов" value="0">
                        </div>
                    </div>
                </div>

                <h6 class="mt-3">Пол</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Длина комнаты (м)</label>
                            <input type="number" class="form-control" name="materials[${index}][room_length]" step="0.01" placeholder="Длина комнаты" value="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Ширина комнаты (м)</label>
                            <input type="number" class="form-control" name="materials[${index}][room_width]" step="0.01" placeholder="Ширина комнаты" value="0">
                        </div>
                    </div>
                </div>

                <h6 class="mt-3">Потолок</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Длина потолка (м)</label>
                            <input type="number" class="form-control" name="materials[${index}][ceiling_length]" step="0.01" placeholder="Длина потолка" value="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Ширина потолка (м)</label>
                            <input type="number" class="form-control" name="materials[${index}][ceiling_width]" step="0.01" placeholder="Ширина потолка" value="0">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <div>
                        <button type="button" class="btn btn-danger btn-sm remove-room" data-index="${index}">
                            <i class="fas fa-trash me-1"></i>Удалить комнату
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        materialsContainer.insertAdjacentHTML('beforeend', roomHtml);
        
        // Добавить обработчики событий для новой комнаты
        setupRoomEvents();
    });
    
    // Настройка обработчиков событий для комнат
    function setupRoomEvents() {
        // Обработчики для удаления комнаты
        document.querySelectorAll('.remove-room').forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-index');
                const materialsContainer = document.getElementById('materials-container');
                const rooms = materialsContainer.querySelectorAll('.material-row');
                
                if (rooms.length > 1) {
                    const room = document.querySelector(`.material-row[data-index="${index}"]`);
                    if (room) {
                        room.remove();
                    }
                } else {
                    alert('Необходимо иметь хотя бы одну комнату для расчета.');
                }
            });
        });
    }
    
    // Инициализация обработчиков для существующих комнат
    setupRoomEvents();
    
    // Сохранение цен материалов
    document.querySelectorAll('.material-price').forEach(input => {
        input.addEventListener('change', function() {
            const materialPrices = {};
            document.querySelectorAll('.material-price').forEach(priceInput => {
                const material = priceInput.getAttribute('data-material');
                materialPrices[material] = parseFloat(priceInput.value) || 0;
            });
            
            // Сохраняем цены в локальное хранилище
            localStorage.setItem(PRICES_STORAGE_KEY, JSON.stringify(materialPrices));
            
            // Отправляем на сервер для сохранения
            saveServerPrices(materialPrices);
        });
    });
    
    // Функция для сохранения цен на сервере
    function saveServerPrices(prices) {
        fetch('{{ route("estimator.calculator.save-prices") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ prices: prices })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error saving prices:', data.message);
            }
        })
        .catch(error => console.error('Error saving prices:', error));
    }
    
    // Загрузка сохраненных цен из локального хранилища
    const savedPrices = localStorage.getItem(PRICES_STORAGE_KEY);
    if (savedPrices) {
        try {
            const prices = JSON.parse(savedPrices);
            document.querySelectorAll('.material-price').forEach(input => {
                const material = input.getAttribute('data-material');
                if (prices[material] !== undefined) {
                    input.value = prices[material];
                }
            });
        } catch (e) {
            console.error('Error loading saved prices:', e);
        }
    }
    
    // Обработчик для кнопки расчета
    document.getElementById('calculate-btn').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Сбор данных формы
        const formData = new FormData(document.getElementById('calculator-form'));
        const materialsData = [];
        const selectedMaterials = [];
        const materialPrices = {};
        
        // Собираем данные о комнатах
        document.querySelectorAll('.material-row').forEach(room => {
            const index = room.getAttribute('data-index');
            const roomData = {
                walls_length: parseFloat(document.querySelector(`input[name="materials[${index}][walls_length]"]`).value) || 0,
                walls_height: parseFloat(document.querySelector(`input[name="materials[${index}][walls_height]"]`).value) || 0,
                openings_area: parseFloat(document.querySelector(`input[name="materials[${index}][openings_area]"]`).value) || 0,
                room_length: parseFloat(document.querySelector(`input[name="materials[${index}][room_length]"]`).value) || 0,
                room_width: parseFloat(document.querySelector(`input[name="materials[${index}][room_width]"]`).value) || 0,
                ceiling_length: parseFloat(document.querySelector(`input[name="materials[${index}][ceiling_length]"]`).value) || 0,
                ceiling_width: parseFloat(document.querySelector(`input[name="materials[${index}][ceiling_width]"]`).value) || 0
            };
            materialsData.push(roomData);
        });
        
        // Собираем выбранные материалы
        document.querySelectorAll('input[name="selected_materials[]"]:checked').forEach(checkbox => {
            selectedMaterials.push(checkbox.value);
        });
        
        // Собираем цены материалов
        document.querySelectorAll('.material-price').forEach(input => {
            const material = input.getAttribute('data-material');
            materialPrices[material] = parseFloat(input.value) || 0;
        });
        
        // Подготавливаем данные для отправки
        const requestData = {
            materials: materialsData,
            selectedMaterials: selectedMaterials,
            materialPrices: materialPrices
        };
        
        // Сохраняем данные формы в локальное хранилище
        localStorage.setItem(STORAGE_KEY, JSON.stringify(requestData));
        
        // Отправляем запрос на сервер
        fetch('{{ route("estimator.calculator.calculate") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResults(data.results, data.totals);
            } else {
                alert('Произошла ошибка при расчете. Проверьте введенные данные и попробуйте снова.');
            }
        })
        .catch(error => {
            console.error('Error calculating materials:', error);
            alert('Произошла ошибка при расчете. Пожалуйста, попробуйте позже.');
        });
    });
    
    // Функция отображения результатов
    function displayResults(results, totals) {
        const resultsContainer = document.getElementById('results-container');
        const resultsCard = document.getElementById('results-card');
        
        let html = '<div class="row">';
        
        // Добавляем результаты по каждой комнате
        results.forEach((room, index) => {
            html += `
                <div class="col-md-12 mb-4">
                    <h5>Комната ${index + 1}</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Материал</th>
                                    <th>Количество</th>
                                    <th>Единица измерения</th>
                                    <th>Цена за единицу</th>
                                    <th>Общая стоимость</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            Object.entries(room).forEach(([material, data]) => {
                html += `
                    <tr>
                        <td>${data.name}</td>
                        <td>${data.quantity.toFixed(2)}</td>
                        <td>${data.unit}</td>
                        <td>${data.price.toFixed(2)} ₽</td>
                        <td>${data.total.toFixed(2)} ₽</td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        });
        
        // Добавляем общую информацию
        html += `
            <div class="col-md-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="mb-3">Итоговая информация</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Общая площадь стен:</strong>
                            <span>${totals.wallsArea.toFixed(2)} м²</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Общая площадь пола:</strong>
                            <span>${totals.floorArea.toFixed(2)} м²</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Общая площадь потолка:</strong>
                            <span>${totals.ceilingArea.toFixed(2)} м²</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Общая стоимость:</strong>
                            <span class="fs-5 fw-bold">${totals.totalCost.toFixed(2)} ₽</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        html += '</div>';
        
        resultsContainer.innerHTML = html;
        resultsCard.style.display = 'block';
        
        // Сохраняем данные для экспорта
        document.getElementById('exportData').value = JSON.stringify({
            results: results,
            totals: totals
        });
    }
    
    // Инициализация экспорта в PDF
    document.getElementById('export-pdf-btn').addEventListener('click', function() {
        const exportForm = document.getElementById('exportForm');
        exportForm.action = '{{ route("estimator.calculator.export-pdf") }}';
        exportForm.submit();
    });
    
    // Загружаем предыдущие данные формы, если они есть
    const savedData = localStorage.getItem(STORAGE_KEY);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            
            // Загружаем данные о комнатах, если они есть
            if (data.materials && data.materials.length > 0) {
                // Удаляем все комнаты кроме первой
                const materialsContainer = document.getElementById('materials-container');
                const rooms = materialsContainer.querySelectorAll('.material-row');
                for (let i = 1; i < rooms.length; i++) {
                    rooms[i].remove();
                }
                
                // Заполняем первую комнату данными
                const firstRoom = data.materials[0];
                if (firstRoom) {
                    document.querySelector('input[name="materials[0][walls_length]"]').value = firstRoom.walls_length || 0;
                    document.querySelector('input[name="materials[0][walls_height]"]').value = firstRoom.walls_height || 0;
                    document.querySelector('input[name="materials[0][openings_area]"]').value = firstRoom.openings_area || 0;
                    document.querySelector('input[name="materials[0][room_length]"]').value = firstRoom.room_length || 0;
                    document.querySelector('input[name="materials[0][room_width]"]').value = firstRoom.room_width || 0;
                    document.querySelector('input[name="materials[0][ceiling_length]"]').value = firstRoom.ceiling_length || 0;
                    document.querySelector('input[name="materials[0][ceiling_width]"]').value = firstRoom.ceiling_width || 0;
                }
                
                // Добавляем дополнительные комнаты, если они есть
                for (let i = 1; i < data.materials.length; i++) {
                    const room = data.materials[i];
                    document.getElementById('add-room').click();
                    
                    document.querySelector(`input[name="materials[${i}][walls_length]"]`).value = room.walls_length || 0;
                    document.querySelector(`input[name="materials[${i}][walls_height]"]`).value = room.walls_height || 0;
                    document.querySelector(`input[name="materials[${i}][openings_area]"]`).value = room.openings_area || 0;
                    document.querySelector(`input[name="materials[${i}][room_length]"]`).value = room.room_length || 0;
                    document.querySelector(`input[name="materials[${i}][room_width]"]`).value = room.room_width || 0;
                    document.querySelector(`input[name="materials[${i}][ceiling_length]"]`).value = room.ceiling_length || 0;
                    document.querySelector(`input[name="materials[${i}][ceiling_width]"]`).value = room.ceiling_width || 0;
                }
            }
            
            // Загружаем выбранные материалы
            if (data.selectedMaterials && data.selectedMaterials.length > 0) {
                document.querySelectorAll('input[name="selected_materials[]"]').forEach(checkbox => {
                    checkbox.checked = data.selectedMaterials.includes(checkbox.value);
                });
            }
            
            // Загружаем цены материалов, если они есть
            if (data.materialPrices) {
                document.querySelectorAll('.material-price').forEach(input => {
                    const material = input.getAttribute('data-material');
                    if (data.materialPrices[material] !== undefined) {
                        input.value = data.materialPrices[material];
                    }
                });
            }
        } catch (e) {
            console.error('Error loading saved form data:', e);
        }
    }
}
</script>

<style>
.material-row {
    position: relative;
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
}

#materials-selection .row {
    padding: 8px;
    border-radius: 5px;
}

#materials-selection .row:nth-child(odd) {
    background-color: #f8f9fa;
}

.feature-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Адаптивные стили для мобильных устройств */
@media (max-width: 767px) {
    .form-group {
        margin-bottom: 15px;
    }
    
    .material-row {
        padding: 10px;
    }
    
    h5, h6 {
        font-size: 1rem;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
}
</style>
@endsection
