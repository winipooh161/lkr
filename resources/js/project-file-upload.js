/**
 * Обработка загрузки файлов для проектов
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Project file upload script loaded');
    
    // Обработчик для всех кнопок загрузки файлов
    document.querySelectorAll('.upload-file-btn').forEach(button => {
        console.log('Upload button found:', button);
        
        button.addEventListener('click', function(e) {
            console.log('Upload button clicked');
            e.preventDefault();
            
            // Находим ближайшую модалку и форму внутри неё
            const uploadButton = this;
            const modal = uploadButton.closest('.modal');
            const form = modal.querySelector('form');
            
            if (!form) {
                console.error('Форма не найдена внутри модального окна');
                return;
            }
            
            const formData = new FormData(form);
            const progressContainer = modal.querySelector('.upload-progress');
            
            // Добавляем вывод информации о передаваемых данных для отладки
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);
            for (let [key, value] of formData.entries()) {
                console.log('Form data:', key, value instanceof File ? value.name : value);
            }
            
            // Если контейнер прогресса не найден, отправляем форму стандартным способом
            if (!progressContainer) {
                console.warn('Контейнер прогресса загрузки не найден, отправляем форму стандартным способом');
                form.submit();
                return;
            }
            
            const progressBar = progressContainer.querySelector('.progress-bar');
            const progressInfo = progressContainer.querySelector('.progress-info');
            
            // Показываем прогресс загрузки
            form.classList.add('d-none');
            progressContainer.classList.remove('d-none');
            progressBar.style.width = '0%';
            progressInfo.textContent = 'Подготовка к загрузке...';
            
            // Отключаем кнопки
            const buttons = modal.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
            
            // Запрос на загрузку файла
            axios.post(form.action, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                onUploadProgress: function(progressEvent) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    progressBar.style.width = percentCompleted + '%';
                    progressInfo.textContent = `Загрузка: ${percentCompleted}%`;
                }
            })
            .then(function(response) {
                // Обрабатываем успешную загрузку
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.remove('progress-bar-striped');
                progressBar.classList.add('bg-success');
                progressInfo.textContent = 'Файл успешно загружен!';
                
                // Перезагружаем страницу через 1 секунду
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            })
            .catch(function(error) {
                // Обрабатываем ошибку
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.remove('progress-bar-striped');
                progressBar.classList.add('bg-danger');
                
                console.error('Ошибка загрузки файла:', error);
                if (error.response) {
                    console.error('Ответ сервера:', error.response.data);
                    console.error('Статус HTTP:', error.response.status);
                    
                    if (error.response.data.errors) {
                        // Вывод всех ошибок валидации
                        const errorMessages = Object.values(error.response.data.errors).flat().join(', ');
                        progressInfo.textContent = 'Ошибка: ' + errorMessages;
                    } else if (error.response.data.error) {
                        progressInfo.textContent = 'Ошибка: ' + error.response.data.error;
                    } else if (error.response.data.message) {
                        progressInfo.textContent = 'Ошибка: ' + error.response.data.message;
                    } else {
                        progressInfo.textContent = `Ошибка сервера: ${error.response.status}`;
                    }
                } else {
                    progressInfo.textContent = 'Произошла ошибка при загрузке файла.';
                }
                
                // Включаем кнопки
                buttons.forEach(btn => btn.disabled = false);
                
                // Возвращаем форму через 2 секунды
                setTimeout(function() {
                    progressContainer.classList.add('d-none');
                    form.classList.remove('d-none');
                }, 2000);
            });
        });
    });
    
    // Специальный обработчик для схем с id uploadSchemeButton (если он существует отдельно)
    const uploadSchemeButton = document.getElementById('uploadSchemeButton');
    if (uploadSchemeButton) {
        uploadSchemeButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const modal = document.getElementById('uploadSchemeModal');
            const form = document.getElementById('uploadSchemeForm');
            
            if (!form) {
                console.error('Форма uploadSchemeForm не найдена');
                return;
            }
            
            const formData = new FormData(form);
            const progressContainer = modal.querySelector('.upload-progress');
            
            // Добавляем вывод информации о передаваемых данных для отладки
            console.log('Scheme form action:', form.action);
            console.log('Scheme form method:', form.method);
            for (let [key, value] of formData.entries()) {
                console.log('Scheme form data:', key, value instanceof File ? value.name : value);
            }
            
            // Если контейнер прогресса не найден, отправляем форму стандартным способом
            if (!progressContainer) {
                form.submit();
                return;
            }
            
            const progressBar = progressContainer.querySelector('.progress-bar');
            const progressInfo = progressContainer.querySelector('.progress-info');
            
            // Показываем прогресс загрузки
            form.style.display = 'none';
            progressContainer.classList.remove('d-none');
            progressBar.style.width = '0%';
            progressInfo.textContent = 'Подготовка к загрузке...';
            
            // Отключаем кнопки
            const buttons = modal.querySelectorAll('.modal-footer button');
            buttons.forEach(btn => btn.disabled = true);
            
            // Запрос на загрузку файла
            axios.post(form.action, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                onUploadProgress: function(progressEvent) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    progressBar.style.width = percentCompleted + '%';
                    progressInfo.textContent = `Загрузка: ${percentCompleted}%`;
                }
            })
            .then(function(response) {
                // Обрабатываем успешную загрузку
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.remove('progress-bar-striped');
                progressBar.classList.add('bg-success');
                progressInfo.textContent = 'Файл успешно загружен!';
                
                // Перезагружаем страницу через 1 секунду
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            })
            .catch(function(error) {
                // Обрабатываем ошибку
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.remove('progress-bar-striped');
                progressBar.classList.add('bg-danger');
                
                console.error('Ошибка загрузки файла схемы:', error);
                if (error.response) {
                    console.error('Ответ сервера:', error.response.data);
                    console.error('Статус HTTP:', error.response.status);
                    
                    if (error.response.data.errors) {
                        // Вывод всех ошибок валидации
                        const errorMessages = Object.values(error.response.data.errors).flat().join(', ');
                        progressInfo.textContent = 'Ошибка: ' + errorMessages;
                    } else if (error.response.data.error) {
                        progressInfo.textContent = 'Ошибка: ' + error.response.data.error;
                    } else if (error.response.data.message) {
                        progressInfo.textContent = 'Ошибка: ' + error.response.data.message;
                    } else {
                        progressInfo.textContent = `Ошибка сервера: ${error.response.status}`;
                    }
                } else {
                    progressInfo.textContent = 'Произошла ошибка при загрузке файла.';
                }
                
                // Включаем кнопки
                buttons.forEach(btn => btn.disabled = false);
                
                // Возвращаем форму через 2 секунды
                setTimeout(function() {
                    progressContainer.classList.add('d-none');
                    form.style.display = 'block';
                }, 2000);
            });
        });
    }
    
    // Обработчик удаления файлов
    document.querySelectorAll('.delete-file').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Вы уверены, что хотите удалить этот файл? Это действие невозможно отменить.')) {
                return;
            }
            
            const fileId = this.getAttribute('data-file-id');
            const projectId = this.getAttribute('data-project-id'); // Добавляем получение project_id из атрибута
            const fileItem = document.querySelector(`.file-item[data-file-id="${fileId}"]`);
            
            // Исправляем URL для соответствия маршрутам Laravel
            axios.delete(`/partner/projects/${projectId}/files/${fileId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(function(response) {
                // Анимация удаления элемента
                fileItem.style.opacity = '0';
                fileItem.style.transform = 'scale(0.8)';
                fileItem.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    fileItem.remove();
                    
                    // Проверяем, остались ли ещё файлы в контейнере
                    const container = document.querySelector('.files-container');
                    if (container && container.children.length === 0) {
                        // Если файлов не осталось, перезагружаем страницу
                        window.location.reload();
                    }
                }, 300);
            })
            .catch(function(error) {
                alert('Произошла ошибка при удалении файла.');
                console.error(error);
            });
        });
    });
});
