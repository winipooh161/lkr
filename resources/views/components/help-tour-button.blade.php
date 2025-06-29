@if(auth()->check() && (auth()->user()->role === 'partner' || auth()->user()->role === 'client'))
<div class="help-tour-button">
    <button type="button" class="btn btn-primary rounded-circle" id="helpTourButton" title="Показать обучение">
        <i class="fas fa-question"></i>
    </button>
</div>

<style>
.help-tour-button {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1030;
}
.help-tour-button .btn {
    width: 50px;
    height: 50px;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const helpButton = document.getElementById('helpTourButton');
    if (helpButton) {
        helpButton.addEventListener('click', function() {
            // Определяем текущую страницу
            const path = window.location.pathname;
            
            // Выбор тура в зависимости от страницы
            let pageKey = 'dashboard'; // По умолчанию
            
            if (path.includes('/partner')) {
                if (path === '/partner/dashboard' || path === '/partner') {
                    pageKey = 'dashboard';
                } else if (path === '/partner/projects') {
                    pageKey = 'projects-list';
                } else if (path === '/partner/estimates') {
                    pageKey = 'estimates-list';
                } else if (path === '/partner/employees') {
                    pageKey = 'employees';
                } else if (path === '/partner/calculator') {
                    pageKey = 'calculator';
                } else if (path === '/partner/estimates/create') {
                    pageKey = 'estimate-create';
                } else if (path.match(/\/partner\/estimates\/\d+\/edit$/)) {
                    pageKey = 'estimate-edit';
                } else if (path.match(/\/partner\/projects\/\d+$/)) {
                    pageKey = 'project';
                } else if (path.match(/\/partner\/projects\/\d+\/files$/)) {
                    pageKey = 'project-files';
                } else if (path.match(/\/partner\/projects\/\d+\/photos$/)) {
                    pageKey = 'project-photos';
                } else if (path.match(/\/partner\/projects\/\d+\/estimates$/)) {
                    pageKey = 'project-estimates';
                } else if (path.match(/\/partner\/projects\/\d+\/schedule$/)) {
                    pageKey = 'project-schedule';
                } else if (path.match(/\/partner\/projects\/\d+\/finance$/)) {
                    pageKey = 'project-finance';
                } else if (path.match(/\/partner\/projects\/\d+\/checks$/)) {
                    pageKey = 'project-checks';
                }
            } else if (path.includes('/client')) {
                if (path === '/client/dashboard' || path === '/client') {
                    pageKey = 'client-dashboard';
                } else if (path === '/client/projects') {
                    pageKey = 'client-projects-list';
                } else if (path.match(/\/client\/projects\/\d+$/)) {
                    pageKey = 'client-project';
                } else if (path.match(/\/client\/projects\/\d+\/files$/)) {
                    pageKey = 'client-project-files';
                } else if (path.match(/\/client\/projects\/\d+\/photos$/)) {
                    pageKey = 'client-project-photos';
                } else if (path.match(/\/client\/projects\/\d+\/estimates$/)) {
                    pageKey = 'client-project-estimates';
                } else if (path.match(/\/client\/projects\/\d+\/schedule$/)) {
                    pageKey = 'client-project-schedule';
                }
            }
            
            console.log('Запуск тура вручную для страницы:', pageKey);
            
            // Запускаем тур
            if (typeof window.manualStartTour === 'function') {
                window.manualStartTour(pageKey);
            } else {
                console.error('Функция manualStartTour не определена');
            }
        });
    } else {
        console.error('Кнопка помощи не найдена');
    }
});
</script>
@endpush
@endif
