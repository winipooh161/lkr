@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2>Отладка файлов проектов</h2>
    <p class="text-muted">Эта страница доступна только в локальном окружении</p>

    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <strong>Информация:</strong> Здесь отображаются последние 5 проектов с их файлами для отладки.
            </div>
        </div>
    </div>

    @foreach ($projects as $project)
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Проект #{{ $project->id }}: {{ $project->name }}
            </div>
            <div class="card-body">
                <h5>Файлы проекта ({{ $project->files->count() }})</h5>
                
                @if ($project->files->count() > 0)
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Путь</th>
                                <th>Тип</th>
                                <th>Размер</th>
                                <th>Существует</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($project->files as $file)
                                <tr>
                                    <td>{{ $file->id }}</td>
                                    <td>{{ $file->name }}</td>
                                    <td>{{ $file->path }}</td>
                                    <td>{{ $file->mime_type }}</td>
                                    <td>{{ $file->size }} байт</td>
                                    <td>
                                        @if ($file->exists)
                                            <span class="badge bg-success">Да</span>
                                        @else
                                            <span class="badge bg-danger">Нет</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>У этого проекта нет файлов.</p>
                @endif
            </div>
        </div>
    @endforeach

    <div class="mt-4">
        <h3>Тестирование ошибок</h3>
        <p>Используйте ссылки ниже для проверки обработки ошибок в локальном окружении:</p>
        
        <a href="/debug/error-test" class="btn btn-danger">
            Тестировать ошибку с APP_DEBUG
        </a>
    </div>
</div>
@endsection
