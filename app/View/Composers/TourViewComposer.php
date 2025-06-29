<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class TourViewComposer
{
    /**
     * Привязать данные к представлению.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Добавляем переменные, связанные с турами, в представление
            $view->with([
                'userRole' => $user->role,
                'hasTours' => in_array($user->role, ['partner', 'client']),
            ]);
        }
    }
}
