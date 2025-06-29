<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PhoneVerificationController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
        $this->middleware('auth');
    }

    /**
     * Отправка кода подтверждения на новый номер телефона
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendVerificationCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $request->input('phone');
        
        // Отправляем код
        $code = $this->smsService->sendVerificationCode($phone);
        
        if ($code) {
            // В режиме отладки возвращаем код
            if (config('app.debug')) {
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
     * Проверка кода и изменение номера телефона
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyAndUpdatePhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|numeric|digits:4',
        ]);

        $phone = $request->input('phone');
        $code = $request->input('code');
        $user = Auth::user();
        
        // Проверяем код
        $isValid = $this->smsService->verifyCode($phone, $code);
        
        if (!$isValid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Неверный код подтверждения'
            ], 400);
        }
        
        // Обновляем телефон пользователя
        $user->phone = $phone;
        $user->update();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Номер телефона успешно обновлен'
        ]);
    }
}
