@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Информация о файле</h2>
                <div>
                    <a href="{{ route('partner.projects.show', $project) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Вернуться к проекту
                    </a>
                    <a href="{{ route('partner.project-files.download', ['project' => $project->id, 'file' => $file->id]) }}" 
                       class="btn btn-primary">
                        <i class="fas fa-download"></i> Скачать файл
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>{{ $file->original_name }}</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tbody>
                            <tr>
                                <th>ID файла:</th>
                                <td>{{ $file->id }}</td>
                            </tr>
                            <tr>
                                <th>Оригинальное имя:</th>
                                <td>{{ $file->original_name }}</td>
                            </tr>
                            <tr>
                                <th>Тип файла:</th>
                                <td>
                                    @switch($file->file_type)
                                        @case('design')
                                            Дизайн-проект
                                            @break
                                        @case('scheme')
                                            Чертеж/Схема
                                            @break
                                        @case('document')
                                            Документ
                                            @break
                                        @case('contract')
                                            Договор
                                            @break
                                        @default
                                            Другое
                                    @endswitch
                                </td>
                            </tr>
                            <tr>
                                <th>MIME-тип:</th>
                                <td>{{ $file->mime_type }}</td>
                            </tr>
                            <tr>
                                <th>Размер:</th>
                                <td>{{ round($file->size / 1024, 2) }} КБ</td>
                            </tr>
                            <tr>
                                <th>Описание:</th>
                                <td>{{ $file->description ?? 'Нет описания' }}</td>
                            </tr>
                            <tr>
                                <th>Загружен:</th>
                                <td>{{ $file->created_at->format('d.m.Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Действия с файлом</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <a href="{{ route('partner.project-files.download', ['project' => $project->id, 'file' => $file->id]) }}" 
                           class="btn btn-primary btn-block w-100">
                            <i class="fas fa-download"></i> Скачать файл
                        </a>
                    </div>
                    <form action="{{ route('partner.project-files.destroy', ['project' => $project->id, 'file' => $file->id]) }}" 
                          method="POST" 
                          class="delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block w-100">
                            <i class="fas fa-trash"></i> Удалить файл
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Обработка формы удаления с подтверждением
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (confirm('Вы уверены, что хотите удалить этот файл? Это действие нельзя отменить.')) {
                    this.submit();
                }
            });
        });
    });
</script>
@endpush
