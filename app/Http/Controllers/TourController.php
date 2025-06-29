<?php

namespace App\Http\Controllers;

use App\Models\UserCompletedTour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TourController extends Controller
{
    /**
     * Конструктор.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Отметить тур как завершенный.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markCompleted(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'role' => 'required|string',
            'page' => 'required|string',
        ]);
        
        // Сохраняем информацию о завершенном туре в базу данных
        $tourKey = 'tour_' . $validated['role'] . '_' . $validated['page'];
        
        // Создаем или обновляем запись в базе данных
        UserCompletedTour::updateOrCreate(
            ['user_id' => $user->id, 'tour_key' => $tourKey],
            ['user_id' => $user->id, 'tour_key' => $tourKey]
        );
        
        // Записываем в лог для отладки
        Log::info('Тур отмечен как завершенный', [
            'user_id' => $user->id,
            'tour_key' => $tourKey
        ]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Сбросить прогресс всех туров для текущего пользователя.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetTours(Request $request)
    {
        try {
            // Проверяем авторизацию
            if (!Auth::check()) {
                Log::warning('Попытка сбросить туры без авторизации');
                return response()->json(['success' => false, 'message' => 'Необходима авторизация'], 401);
            }

            $user = Auth::user();
            
            // Удаляем все записи о завершенных турах для текущего пользователя
            $count = UserCompletedTour::where('user_id', $user->id)->delete();
            
            // Записываем в лог для отладки
            Log::info('Прогресс туров сброшен для пользователя', [
                'user_id' => $user->id,
                'deleted_count' => $count
            ]);
            
            return response()->json(['success' => true, 'message' => 'Туры успешно сброшены', 'count' => $count]);
        } catch (\Exception $e) {
            Log::error('Ошибка при сбросе туров', [
                'user_id' => Auth::id() ?? 'неавторизован',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Произошла ошибка при сбросе туров', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
