<div class="mb-4">
    <h5 class="mb-3">Рабочая документация</h5>

    @if($project->documentFiles->isEmpty())
        <div class="alert alert-info">
            <div class="d-flex">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    В этом разделе будут отображаться документы по объекту.
                </div>
            </div>
        </div>
    @else
        <!-- Фильтры по типам документов -->
        <div class="mb-3">
            <div class="document-filter">
                <button type="button" class="btn btn-sm btn-outline-secondary active" data-filter="all">
                    Все
                </button>
                @php
                    $docTypes = $project->documentFiles->pluck('document_type')->unique();
                @endphp
                
                @foreach($docTypes as $type)
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="{{ $type }}">
                        {{ ucfirst($type) }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Список документов -->
        <div class="documents-container">
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mobile-optimized ">
                @foreach($project->documentFiles as $file)
                    <div class="col document-item" data-type="{{ $file->document_type }}">
                        <div class="card h-100 document-card ">
                            <div class="card-body">
                                <div class="d-flex align-items-start align-items-sm-center mb-3">
                                    <div class="document-icon me-3">
                                        <i class="far {{ $file->file_icon }} fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <h6 class="card-title mb-0 text-truncate" title="{{ $file->original_name }}">
                                            {{ $file->original_name }}
                                        </h6>
                                        <div class="document-meta small text-muted">
                                            <span class="document-type">{{ ucfirst($file->document_type) }}</span>
                                            <span class="mx-1">•</span>
                                            <span class="document-date">{{ $file->created_at->format('d.m.Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                @if($file->description)
                                    <p class="card-text small mb-3">{{ $file->description }}</p>
                                @endif
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-secondary">{{ $file->size_formatted }}</span>
                                    <div class="document-actions">
                                        @if($file->is_image)
                                            <a href="{{ $file->file_url }}" class="btn btn-sm btn-outline-info me-1" target="_blank" title="Просмотр">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif                                        <a href="{{ $file->client_download_url }}" class="btn btn-sm btn-outline-primary" title="Скачать">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Обработчики для фильтрации документов
        const filterButtons = document.querySelectorAll('.document-filter button');
        const documentItems = document.querySelectorAll('.document-item');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Снимаем active со всех кнопок
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Добавляем active текущей кнопке
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                
                // Фильтруем документы
                documentItems.forEach(item => {
                    if (filter === 'all' || item.dataset.type === filter) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    });
</script>

<style>
/* Стили для адаптивного отображения на мобильных */
.document-filter {
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: 5px;
    margin-bottom: 10px;
}

.document-filter::-webkit-scrollbar {
    display: none;
}

/* Стили для фильтрации документов */
.document-filter {
    display: flex;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 5px;
    margin-bottom: 15px;
    scrollbar-width: none; /* Firefox */
}

.document-filter::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Edge */
}

.document-filter .btn {
    flex-shrink: 0;
    margin-right: 6px;
}

.document-filter .btn.active {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

/* Стили для карточек документов */
.document-card {
    transition: all 0.2s ease;
}

.document-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.document-icon i {
    background-color: rgba(0, 123, 255, 0.1);
    padding: 12px;
    border-radius: 8px;
    color: #007bff;
}

.document-meta {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.min-width-0 {
    min-width: 0;
}

@media (max-width: 576px) {
    .document-card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 10px;
    }
    
    .document-card .card-body {
        padding: 0.75rem;
    }
    
    .document-card h6 {
        font-size: 0.9rem;
    }
    
    .document-card .document-icon i {
        font-size: 1.5rem;
        padding: 8px;
    }
    
    .document-card .btn-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
    
    .document-filter .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        margin-right: 4px;
    }
    
    /* Улучшенный контейнер фильтров */
    .document-filter {
        display: flex;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 5px;
        margin-bottom: 10px;
    }
    
    /* Улучшение для блоков документов */
    .mobile-optimized {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
    }
    
    .mobile-optimized .col {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
}
</style>
