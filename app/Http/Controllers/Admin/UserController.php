<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $roles = ['admin', 'partner', 'client', 'estimator'];
    protected $roleTranslations = [
        'admin' => 'Администратор',
        'partner' => 'Партнер',
        'client' => 'Клиент',
        'estimator' => 'Сметчик'
    ];
    
    /**
     * Отображает список всех пользователей с возможностью фильтрации
     */
    public function index(Request $request)
    {
        $query = User::query();
        
        // Фильтрация по роли
        if ($request->has('role') && $request->role != 'all') {
            $query->where('role', $request->role);
        }
        
        // Сортировка
        $sortField = $request->sort ?? 'created_at';
        $sortDirection = $request->direction ?? 'desc';
        $query->orderBy($sortField, $sortDirection);
        
        $users = $query->paginate(15);
        $users->appends($request->query());
        
        // Получаем статистику по пользователям
        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'partners' => User::where('role', 'partner')->count(),
            'clients' => User::where('role', 'client')->count(),
            'estimators' => User::where('role', 'estimator')->count(),
        ];
        
        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Метод для поиска пользователей
     */
    public function search(Request $request)
    {
        $search = $request->input('q');
        $query = User::query();
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Фильтрация по роли, если указана
        if ($request->has('role') && $request->role != 'all') {
            $query->where('role', $request->role);
        }
        
        $users = $query->paginate(15);
        $users->appends($request->query());
        
        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'partners' => User::where('role', 'partner')->count(),
            'clients' => User::where('role', 'client')->count(),
            'estimators' => User::where('role', 'estimator')->count(),
        ];
        
        return view('admin.users.index', compact('users', 'stats', 'search'));
    }
    
    /**
     * Показывает форму для создания нового пользователя
     */
    public function create()
    {
        // Получаем список партнеров для привязки пользователя (для роли estimator)
        $partners = User::where('role', 'partner')->orderBy('name')->get();
        
        return view('admin.users.create', compact('partners'));
    }
    
    /**
     * Сохраняет нового пользователя
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:admin,partner,client,estimator'],
            'partner_id' => ['nullable', 'exists:users,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
        
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'partner_id' => ($validated['role'] === 'estimator' && isset($validated['partner_id'])) ? $validated['partner_id'] : null,
        ];
        
        // Обработка аватара, если загружен
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $filename = time() . '_' . Str::random(10) . '.' . $avatar->getClientOriginalExtension();
            
            // Сохраняем файл
            $avatarPath = $avatar->storeAs('avatars', $filename, 'public');
            $userData['avatar'] = $filename;
        }
        
        $user = User::create($userData);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь успешно создан.');
    }

    /**
     * Отображает информацию о конкретном пользователе
     */
    public function show(User $user)
    {
        // Получаем связанные с пользователем данные в зависимости от роли
        $relatedData = [];
        
        if ($user->isPartner()) {
            // Количество проектов партнера
            $relatedData['projects_count'] = Project::where('partner_id', $user->id)->count();
            // Последние проекты
            $relatedData['recent_projects'] = Project::where('partner_id', $user->id)
                                                    ->latest()
                                                    ->take(5)
                                                    ->get();
        } elseif ($user->isClient()) {
            // Проекты, связанные с клиентом
            $relatedData['projects'] = $user->clientProjects()->latest()->take(5)->get();
        } elseif ($user->isEstimator()) {
            // Проекты, где пользователь является сметчиком
            $relatedData['projects'] = Project::where('estimator_id', $user->id)
                                             ->latest()
                                             ->take(5)
                                             ->get();
        }
        
        return view('admin.users.show', compact('user', 'relatedData'));
    }

    /**
     * Показывает форму для редактирования пользователя
     */
    public function edit(User $user)
    {
        // Получаем список партнеров для привязки пользователя (для роли estimator)
        $partners = User::where('role', 'partner')->orderBy('name')->get();
        
        return view('admin.users.edit', compact('user', 'partners'));
    }

    /**
     * Обновляет данные пользователя
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:admin,partner,client,estimator'],
            'partner_id' => ['nullable', 'exists:users,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'role' => $validated['role'],
            'partner_id' => ($validated['role'] === 'estimator' && isset($validated['partner_id'])) ? $validated['partner_id'] : null,
        ];

        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        // Обработка аватара, если загружен
        if ($request->hasFile('avatar')) {
            // Удаляем старый аватар, если он был
            if ($user->avatar) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
            }
            
            $avatar = $request->file('avatar');
            $filename = time() . '_' . Str::random(10) . '.' . $avatar->getClientOriginalExtension();
            
            // Сохраняем новый файл
            $avatarPath = $avatar->storeAs('avatars', $filename, 'public');
            $userData['avatar'] = $filename;
        }

        $user->update($userData);

        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь успешно обновлен.');
    }

    /**
     * Удаляет пользователя
     */
    public function destroy(User $user)
    {
        // Проверяем, не удаляет ли администратор сам себя
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Вы не можете удалить собственную учетную запись.');
        }
        
        // Удаляем аватар, если он есть
        if ($user->avatar) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
        }
        
        // Удаляем пользователя
        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь успешно удален.');
    }
    
    /**
     * Изменяет статус активности пользователя
     */
    public function toggleStatus(User $user)
    {
        // Проверяем, не блокирует ли администратор сам себя
        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'Вы не можете изменить статус своей учетной записи.');
        }
        
        // У меня нет поля is_active в модели User, поэтому создадим его
        // В реальном проекте нужно добавить миграцию для этого поля
        if (!in_array('is_active', $user->getFillable())) {
            return redirect()->back()
                ->with('error', 'Необходимо добавить поле is_active в модель User.');
        }
        
        $user->is_active = !$user->is_active;
        $user->save();
        
        $status = $user->is_active ? 'активирован' : 'деактивирован';
        return redirect()->back()
            ->with('success', "Пользователь успешно {$status}.");
    }
    
    /**
     * Изменяет роль пользователя
     */
    public function changeRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,partner,client,estimator'],
            'partner_id' => ['nullable', 'exists:users,id'],
        ]);
        
        // Проверяем, не меняет ли администратор роль сам себе
        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'Вы не можете изменить роль своей учетной записи.');
        }
        
        $user->role = $validated['role'];
        
        // Если роль сметчик, устанавливаем partner_id
        if ($validated['role'] === 'estimator' && isset($validated['partner_id'])) {
            $user->partner_id = $validated['partner_id'];
        } else {
            $user->partner_id = null;
        }
        
        $user->save();
        
        return redirect()->back()
            ->with('success', 'Роль пользователя успешно изменена.');
    }
    
    /**
     * Сбрасывает пароль пользователя на случайный и отправляет его на email
     */
    public function resetPassword(User $user)
    {
        // Генерируем случайный пароль
        $newPassword = Str::random(10);
        
        // Обновляем пароль пользователя
        $user->forceFill([
            'password' => Hash::make($newPassword)
        ])->save();
        
        // В реальном проекте здесь нужно отправить email с новым паролем
        // Но пока просто вернем пароль в сессии для демонстрации
        
        return redirect()->back()
            ->with('success', "Пароль пользователя успешно сброшен. Новый пароль: {$newPassword}")
            ->with('new_password', $newPassword);
    }
    
    /**
     * Отправить уведомление выбранному пользователю
     */
    public function sendNotification(Request $request, User $user)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'channel' => ['required', 'string', 'in:email,sms,both'],
        ]);
        
        $notificationService = new \App\Services\UserNotificationService();
        $success = $notificationService->sendNotification(
            $user, 
            $validated['subject'], 
            $validated['message'], 
            $validated['channel']
        );
        
        if ($success) {
            return redirect()->back()
                ->with('success', 'Уведомление успешно отправлено пользователю.');
        } else {
            return redirect()->back()
                ->with('error', 'Не удалось отправить уведомление пользователю. Проверьте наличие email/телефона.');
        }
    }
    
    /**
     * Назначение проектов пользователю (для партнеров и сметчиков)
     */
    public function assignProjects(Request $request, User $user)
    {
        if (!$user->isPartner() && !$user->isEstimator()) {
            return redirect()->back()
                ->with('error', 'Назначать проекты можно только партнерам или сметчикам.');
        }
        
        $validated = $request->validate([
            'project_ids' => ['required', 'array'],
            'project_ids.*' => ['exists:projects,id'],
        ]);
        
        if ($user->isPartner()) {
            // Для партнера обновляем partner_id в проектах
            Project::whereIn('id', $validated['project_ids'])
                ->update(['partner_id' => $user->id]);
                
            return redirect()->back()
                ->with('success', 'Проекты успешно назначены партнеру.');
        } else {
            // Для сметчика обновляем estimator_id в проектах
            Project::whereIn('id', $validated['project_ids'])
                ->update(['estimator_id' => $user->id]);
                
            return redirect()->back()
                ->with('success', 'Проекты успешно назначены сметчику.');
        }
    }
    
    /**
     * Массовые действия с пользователями
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'action' => ['required', 'string', 'in:delete,activate,deactivate,change_role'],
            'role' => ['nullable', 'required_if:action,change_role', 'in:admin,partner,client,estimator'],
        ]);
        
        // Проверяем, не включен ли текущий администратор в список
        if (in_array(auth()->id(), $validated['user_ids'])) {
            return redirect()->back()
                ->with('error', 'Вы не можете выполнить массовое действие, включающее вашу учетную запись.');
        }
        
        $users = User::whereIn('id', $validated['user_ids'])->get();
        $successCount = 0;
        
        foreach ($users as $user) {
            switch ($validated['action']) {
                case 'delete':
                    // Удаляем аватар, если он есть
                    if ($user->avatar) {
                        Storage::disk('public')->delete('avatars/' . $user->avatar);
                    }
                    $user->delete();
                    $successCount++;
                    break;
                    
                case 'activate':
                    if (in_array('is_active', $user->getFillable())) {
                        $user->is_active = true;
                        $user->save();
                        $successCount++;
                    }
                    break;
                    
                case 'deactivate':
                    if (in_array('is_active', $user->getFillable())) {
                        $user->is_active = false;
                        $user->save();
                        $successCount++;
                    }
                    break;
                    
                case 'change_role':
                    $user->role = $validated['role'];
                    if ($validated['role'] !== 'estimator') {
                        $user->partner_id = null;
                    }
                    $user->save();
                    $successCount++;
                    break;
            }
        }
        
        $action = match($validated['action']) {
            'delete' => 'удалены',
            'activate' => 'активированы',
            'deactivate' => 'деактивированы',
            'change_role' => 'изменена роль для',
            default => 'обработаны'
        };
        
        return redirect()->back()
            ->with('success', "Успешно {$action} {$successCount} пользователей.");
    }
    
    /**
     * Импорт пользователей из файла
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx', 'max:2048'],
        ]);
        
        $importExport = new \App\Services\UserImportExport();
        $result = $importExport->import($request->file('file'));
        
        if ($result['success']) {
            Log::info('Импорт пользователей завершен успешно', [
                'imported' => $result['imported'],
                'errors' => $result['errors'] ?? []
            ]);
            
            return redirect()->back()
                ->with('success', $result['message']);
        } else {
            Log::error('Ошибка при импорте пользователей', [
                'message' => $result['message'],
                'errors' => $result['errors'] ?? []
            ]);
            
            return redirect()->back()
                ->with('error', $result['message'])
                ->with('import_errors', $result['errors'] ?? []);
        }
    }
    
    /**
     * Экспорт пользователей в файл
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx');
        $template = $request->boolean('template', false);
        
        $importExport = new \App\Services\UserImportExport();
        
        $filename = 'users_' . date('Y-m-d') . '.' . $format;
        $contentType = $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        
        if ($template) {
            $filename = 'users_template.' . $format;
            $content = $importExport->generateTemplate($format);
        } else {
            $content = $format === 'csv' 
                ? $importExport->exportCsv() 
                : $importExport->exportXlsx();
        }
        
        Log::info('Экспорт пользователей', [
            'format' => $format,
            'template' => $template,
            'filename' => $filename
        ]);
        
        return response($content)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
