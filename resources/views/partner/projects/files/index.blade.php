@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">{{ $project->name }} - Файлы</h2>
                <div>
                    <a href="{{ route('partner.projects.show', $project) }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Вернуться к проекту
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Список файлов</h5>
                </div>
                <div class="card-body">
                    @if($files->count() > 0)
                        <div class="table-responsive files-list">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Тип</th>
                                        <th>Размер</th>
                                        <th>Загружен</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($files as $file)
                                    <tr>
                                        <td>{{ $file->original_name }}</td>
                                        <td>{{ ucfirst($file->file_type) }}</td>
                                        <td>{{ $file->size_formatted }}</td>
                                        <td>{{ $file->created_at->format('d.m.Y H:i') }}</td>
                                        <td class="file-actions">
                                            <a href="{{ route('partner.projects.files.download', [$project, $file]) }}" class="btn btn-sm btn-outline-primary" title="Скачать">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-outline-danger delete-file" data-id="{{ $file->id }}" data-project-id="{{ $project->id }}" title="Удалить">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            Файлы еще не загружены для этого проекта.
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Загрузить новый файл</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('partner.projects.files.store', $project) }}" method="POST" enctype="multipart/form-data" id="fileUploadForm">
                        @csrf
                        
                        <div class="drop-zone mb-3" id="dropZone">
                            <div class="drop-zone-prompt">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                <p>Перетащите файл сюда или нажмите для выбора</p>
                            </div>
                            <input type="file" name="file" id="fileInput" class="drop-zone-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.rar">
                        </div>
                        
                        <div class="mb-3">
                            <label for="fileType" class="form-label">Тип файла</label>
                            <select class="form-select" name="file_type" id="fileType" required>
                                <option value="" selected disabled>Выберите тип файла</option>
                                <option value="design">Дизайн-проект</option>
                                <option value="scheme">Схема/Чертеж</option>
                                <option value="document">Документ</option>
                                <option value="contract">Договор</option>
                                <option value="other">Другое</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fileDescription" class="form-label">Описание (необязательно)</label>
                            <textarea class="form-control" name="description" id="fileDescription" rows="2"></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Загрузить файл
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Удаление файла</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы действительно хотите удалить этот файл? Это действие нельзя отменить.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <form id="deleteFileForm" action="" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация Drag and Drop для зоны загрузки файлов
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    
    if (dropZone && fileInput) {
        // Обработчик события перетаскивания файла над зоной
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropZone.classList.add('drop-zone-over');
        });
        
        // Обработчик события покидания зоны перетаскивания
        ['dragleave', 'dragend'].forEach(type => {
            dropZone.addEventListener(type, function() {
                dropZone.classList.remove('drop-zone-over');
            });
        });
        
        // Обработчик события сброса файла в зону
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.classList.remove('drop-zone-over');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateDropzoneDisplay(e.dataTransfer.files[0]);
            }
        });
        
        // Обработчик клика по зоне
        dropZone.addEventListener('click', function() {
            fileInput.click();
        });
        
        // Обработчик выбора файла
        fileInput.addEventListener('change', function() {
            if (fileInput.files.length) {
                updateDropzoneDisplay(fileInput.files[0]);
            }
        });
        
        // Функция обновления отображения после выбора файла
        function updateDropzoneDisplay(file) {
            const dropZonePrompt = dropZone.querySelector('.drop-zone-prompt');
            
            if (dropZone.querySelector('.drop-zone-preview')) {
                dropZone.removeChild(dropZone.querySelector('.drop-zone-preview'));
            }
            
            // Создаем превью файла
            const preview = document.createElement('div');
            preview.classList.add('drop-zone-preview');
            
            // Определяем иконку в зависимости от типа файла
            let iconClass = 'fa-file';
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                iconClass = 'fa-file-image';
            } else if (['pdf'].includes(fileExtension)) {
                iconClass = 'fa-file-pdf';
            } else if (['doc', 'docx'].includes(fileExtension)) {
                iconClass = 'fa-file-word';
            } else if (['xls', 'xlsx'].includes(fileExtension)) {
                iconClass = 'fa-file-excel';
            } else if (['zip', 'rar'].includes(fileExtension)) {
                iconClass = 'fa-file-archive';
            }
            
            preview.innerHTML = `
                <div class="drop-zone-preview-icon">
                    <i class="fas ${iconClass} fa-2x"></i>
                </div>
                <div class="drop-zone-preview-info">
                    <div class="drop-zone-preview-name">${file.name}</div>
                    <div class="drop-zone-preview-size">${formatFileSize(file.size)}</div>
                </div>
                <div class="drop-zone-preview-remove">
                    <i class="fas fa-times"></i>
                </div>
            `;
            
            dropZone.appendChild(preview);
            dropZonePrompt.style.display = 'none';
            
            // Обработчик для кнопки удаления превью
            const removeButton = preview.querySelector('.drop-zone-preview-remove');
            removeButton.addEventListener('click', function(e) {
                e.stopPropagation();
                fileInput.value = '';
                dropZone.removeChild(preview);
                dropZonePrompt.style.display = 'flex';
            });
        }
        
        // Функция форматирования размера файла
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    }
    
    // Инициализация обработчиков для удаления файлов
    const deleteButtons = document.querySelectorAll('.delete-file');
    const deleteFileForm = document.getElementById('deleteFileForm');
    const deleteFileModal = new bootstrap.Modal(document.getElementById('deleteFileModal'));
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const fileId = this.dataset.id;
            const projectId = this.dataset.projectId;
            deleteFileForm.action = `/partner/projects/${projectId}/files/${fileId}`;
            deleteFileModal.show();
        });
    });
});
</script>

<style>
/* Стили для зоны перетаскивания файлов */
.drop-zone {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 150px;
    border: 2px dashed #adb5bd;
    border-radius: 5px;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
    cursor: pointer;
}

.drop-zone:hover {
    border-color: #6c757d;
    background-color: #f1f3f5;
}

.drop-zone_over {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
}

.drop-zone-input {
    display: none;
}

.drop-zone-prompt {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.drop-zone-prompt p {
    margin: 0;
}

.drop-zone-preview {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 10px;
}

.drop-zone-preview-icon {
    margin-right: 15px;
    color: #6c757d;
}

.drop-zone-preview-info {
    flex-grow: 1;
}

.drop-zone-preview-name {
    font-weight: bold;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
}

.drop-zone-preview-size {
    font-size: 0.8rem;
    color: #6c757d;
}

.drop-zone-preview-remove {
    cursor: pointer;
    color: #dc3545;
    margin-left: 10px;
}
</style>
@endpush
