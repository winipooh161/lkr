<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use App\Services\SmsService;

class ProfileController extends Controller
{
    protected $smsService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SmsService $smsService)
    {
        $this->middleware('auth');
        $this->smsService = $smsService;
    }

    /**
     * Показать профиль пользователя.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('profile.index', ['user' => Auth::user()]);
    }

    /**
     * Показать форму для редактирования профиля пользователя.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    /**
     * Обновить профиль пользователя.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[А-Яа-яЁё\s]+$/u'],
            'phone' => ['required', 'string', Rule::unique('users')->ignore($user->id)],
            // Валидация для банковских реквизитов
            'bank' => ['nullable', 'string', 'max:255'],
            'bik' => ['nullable', 'string', 'max:9'],
            'checking_account' => ['nullable', 'string', 'max:20'],
            'correspondent_account' => ['nullable', 'string', 'max:20'],
            'recipient_bank' => ['nullable', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:12'],
            'kpp' => ['nullable', 'string', 'max:9'],
            // Валидация для файлов
            'signature_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'stamp_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ], [
            'name.regex' => 'Имя должно содержать только русские буквы и пробелы (без цифр и других символов).',
        ]);

        // Обработка аватара
        if ($request->hasFile('avatar')) {
            // Удаление старого аватара
            if ($user->avatar) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
            }

            // Сохранение нового аватара
            $avatarName = time() . '.' . $request->avatar->extension();
            $request->avatar->storeAs('avatars', $avatarName, 'public');
            $user->avatar = $avatarName;
        }

        // Очистка номера телефона от форматирования
        $phone = preg_replace('/[^0-9]/', '', $request->phone);

        // Обработка файла подписи
        if ($request->hasFile('signature_file')) {
            // Удаление старого файла подписи
            if ($user->signature_file) {
                Storage::disk('public')->delete('signatures/' . $user->signature_file);
            }

            // Сохранение нового файла подписи
            $signatureName = time() . '_signature.' . $request->signature_file->extension();
            $request->signature_file->storeAs('signatures', $signatureName, 'public');
            $user->signature_file = $signatureName;
        }

        // Обработка файла печати
        if ($request->hasFile('stamp_file')) {
            // Удаление старого файла печати
            if ($user->stamp_file) {
                Storage::disk('public')->delete('stamps/' . $user->stamp_file);
            }

            // Сохранение нового файла печати
            $stampName = time() . '_stamp.' . $request->stamp_file->extension();
            $request->stamp_file->storeAs('stamps', $stampName, 'public');
            $user->stamp_file = $stampName;
        }

        // Сохранение банковских реквизитов
        $user->bank = $request->bank;
        $user->bik = $request->bik;
        $user->checking_account = $request->checking_account;
        $user->correspondent_account = $request->correspondent_account;
        $user->recipient_bank = $request->recipient_bank;
        $user->inn = $request->inn;
        $user->kpp = $request->kpp;

        $user->name = $request->name;
        $user->phone = $phone;
        $user->email = $request->email;
        $user->save();

        return redirect()->route('profile.index')->with('success', 'Профиль успешно обновлен.');
    }

    /**
     * Показать форму для изменения пароля.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function showChangePasswordForm()
    {
        return view('profile.change-password');
    }

    /**
     * Изменить пароль пользователя.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        // Проверка текущего пароля
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Текущий пароль указан неверно.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('profile.index')->with('success', 'Пароль успешно изменен.');
    }

    /**
     * Отправить код подтверждения на текущий номер телефона
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPhoneVerificationCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'type' => 'required|string|in:current'
        ]);

        $user = Auth::user();
        $phone = $request->phone;

        // Проверяем, что номер соответствует текущему номеру пользователя
        $cleanUserPhone = preg_replace('/[^0-9]/', '', $user->phone);
        $cleanRequestPhone = preg_replace('/[^0-9]/', '', $phone);

        if ($cleanUserPhone !== $cleanRequestPhone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Номер телефона не соответствует вашему текущему номеру'
            ], 400);
        }

        try {
            $code = $this->smsService->generateCode();
            
            // Сохраняем код в кеше на 10 минут
            $cacheKey = 'phone_verification_' . $user->id . '_' . $cleanRequestPhone;
            Cache::put($cacheKey, $code, now()->addMinutes(10));

            // Отправляем SMS
            $result = $this->smsService->send($phone, "Код подтверждения для смены номера телефона: {$code}");

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Код подтверждения отправлен на ваш номер телефона'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ошибка при отправке SMS'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Произошла ошибка при отправке SMS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Проверить код подтверждения номера телефона
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPhoneCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string|size:4',
            'type' => 'required|string|in:current'
        ]);

        $user = Auth::user();
        $phone = $request->phone;
        $code = $request->code;

        // Проверяем, что номер соответствует текущему номеру пользователя
        $cleanUserPhone = preg_replace('/[^0-9]/', '', $user->phone);
        $cleanRequestPhone = preg_replace('/[^0-9]/', '', $phone);

        if ($cleanUserPhone !== $cleanRequestPhone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Номер телефона не соответствует вашему текущему номеру'
            ], 400);
        }

        // Получаем код из кеша
        $cacheKey = 'phone_verification_' . $user->id . '_' . $cleanRequestPhone;
        $cachedCode = Cache::get($cacheKey);

        if (!$cachedCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Код устарел или не найден. Запросите новый код.'
            ], 400);
        }

        if ($cachedCode != $code) {
            return response()->json([
                'status' => 'error',
                'message' => 'Неверный код подтверждения'
            ], 400);
        }

        // Код правильный, помечаем номер как подтвержденный
        $verificationKey = 'phone_verified_' . $user->id . '_' . $cleanRequestPhone;
        Cache::put($verificationKey, true, now()->addMinutes(30)); // 30 минут на смену номера

        // Удаляем использованный код
        Cache::forget($cacheKey);

        return response()->json([
            'status' => 'success',
            'message' => 'Номер телефона успешно подтвержден'
        ]);
    }

    /**
     * Обновить номер телефона после подтверждения
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePhone(Request $request)
    {
        $request->validate([
            'new_phone' => 'required|string|unique:users,phone'
        ]);

        $user = Auth::user();
        $newPhone = $request->new_phone;

        // Очищаем номер от форматирования
        $cleanUserPhone = preg_replace('/[^0-9]/', '', $user->phone);
        $cleanNewPhone = preg_replace('/[^0-9]/', '', $newPhone);

        // Проверяем, что текущий номер был подтвержден
        $verificationKey = 'phone_verified_' . $user->id . '_' . $cleanUserPhone;
        $isVerified = Cache::get($verificationKey);

        if (!$isVerified) {
            return response()->json([
                'status' => 'error',
                'message' => 'Сначала необходимо подтвердить текущий номер телефона'
            ], 400);
        }

        // Проверяем, что новый номер отличается от текущего
        if ($cleanUserPhone === $cleanNewPhone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Новый номер не может совпадать с текущим'
            ], 400);
        }

        try {
            // Обновляем номер телефона
            $user->phone = $cleanNewPhone;
            $user->save();

            // Удаляем флаг подтверждения
            Cache::forget($verificationKey);

            return response()->json([
                'status' => 'success',
                'message' => 'Номер телефона успешно изменен'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Произошла ошибка при сохранении нового номера: ' . $e->getMessage()
            ], 500);
        }
    }
}
