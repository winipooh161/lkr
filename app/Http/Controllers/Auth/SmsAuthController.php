<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SmsAuthController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Отправка кода подтверждения
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendCode(Request $request)
    {
        Log::info('SMS sendCode method called', ['request_data' => $request->all()]);
        
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $request->input('phone');
        Log::info('Phone number received', ['phone' => $phone]);
        
        // Проверяем существование пользователя
        $user = User::where('phone', $phone)->first();
        
        // Если пользователь не найден, создаем тестового пользователя в режиме отладки
        if (!$user && config('app.debug')) {
            // Генерируем уникальный email
            $email = 'test_' . str_replace(['+', ' ', '(', ')', '-'], '', $phone) . '@example.com';
            
            $user = User::create([
                'name' => 'Тестовый пользователь',
                'email' => $email,
                'phone' => $phone,
                'password' => bcrypt('password')
            ]);
            Log::info('Created test user in debug mode', ['user_id' => $user->id, 'phone' => $phone]);
        }
        
        Log::info('User lookup result', ['user_found' => !!$user, 'user_id' => $user ? $user->id : null]);
        
        if (!$user) {
            Log::warning('User not found for phone', ['phone' => $phone]);
            return response()->json(['status' => 'error', 'message' => 'Пользователь с таким номером телефона не найден'], 404);
        }
        
        // Отправляем код
        $code = $this->smsService->sendVerificationCode($phone);
        Log::info('SMS code generation result', ['code_generated' => !!$code, 'code' => $code]);
        
        if ($code) {
            if (config('app.debug')) {
                // В режиме отладки возвращаем код для удобства разработки
                return response()->json([
                    'status' => 'success',
                    'message' => 'Код подтверждения отправлен',
                    'debug_code' => $code
                ]);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Код подтверждения отправлен'
            ]);
        }
        
        return response()->json([
            'status' => 'error',
            'message' => 'Ошибка при отправке кода подтверждения'
        ], 500);
    }

    /**
     * Проверка кода и авторизация
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|numeric|digits:4',
        ]);

        $phone = $request->input('phone');
        $code = $request->input('code');
        
        // Проверяем код
        $isValid = $this->smsService->verifyCode($phone, $code);
        
        if (!$isValid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Неверный код подтверждения'
            ], 400);
        }
        
        // Получаем пользователя
        $user = User::where('phone', $phone)->first();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Пользователь с таким номером телефона не найден'
            ], 404);
        }
        
        // Авторизуем пользователя
        Auth::login($user);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Успешная авторизация',
            'redirect' => route('home')
        ]);
    }
    
    /**
     * Отображение формы входа
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }
    
    /**
     * Обработка формы входа
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|numeric|digits:4',
        ]);
        
        $phone = $request->input('phone');
        $code = $request->input('code');
        
        // Проверяем код
        $isValid = $this->smsService->verifyCode($phone, $code);
        
        if (!$isValid) {
            return back()->withErrors(['code' => 'Неверный код подтверждения'])->withInput();
        }
        
        // Получаем пользователя
        $user = User::where('phone', $phone)->first();
        
        if (!$user) {
            return back()->withErrors(['phone' => 'Пользователь с таким номером телефона не найден'])->withInput();
        }
        
        // Авторизуем пользователя
        Auth::login($user);
        
        return redirect()->intended(route('home'));
    }
}
