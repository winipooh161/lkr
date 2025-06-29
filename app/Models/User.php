<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'partner_id',
        'is_active',
        'bank',
        'bik',
        'checking_account',
        'correspondent_account',
        'recipient_bank',
        'inn',
        'kpp',
        'signature_file',
        'stamp_file'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    /**
     * Получить завершенные пользователем туры.
     */
    public function completedTours()
    {
        return $this->hasMany(UserCompletedTour::class);
    }
    
    /**
     * Проверить, завершил ли пользователь указанный тур.
     *
     * @param string $tourKey
     * @return bool
     */
    public function hasTourCompleted(string $tourKey): bool
    {
        return $this->completedTours()->where('tour_key', $tourKey)->exists();
    }
    
    /**
     * Отмечает тур как завершенный для пользователя.
     *
     * @param string $tourKey
     * @return void
     */
    public function markTourCompleted(string $tourKey): void
    {
        UserCompletedTour::updateOrCreate(
            ['user_id' => $this->id, 'tour_key' => $tourKey],
            ['user_id' => $this->id, 'tour_key' => $tourKey]
        );
    }
    
    /**
     * Сбрасывает все завершенные туры для пользователя.
     *
     * @return void
     */
    public function resetTours(): void
    {
        $this->completedTours()->delete();
    }
    
    // Отношения определены ниже
    
    /**
     * Проверяет, является ли пользователь администратором.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    
    /**
     * Проверяет, является ли пользователь партнером.
     *
     * @return bool
     */
    public function isPartner(): bool
    {
        return $this->role === 'partner';
    }
    
    /**
     * Проверяет, является ли пользователь клиентом.
     *
     * @return bool
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }
    
    /**
     * Проверяет, является ли пользователь сметчиком.
     *
     * @return bool
     */
    public function isEstimator(): bool
    {
        return $this->role === 'estimator';
    }
    
    /**
     * Получает URL аватара пользователя.
     *
     * @return string
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }
    
    /**
     * Получает URL файла подписи пользователя.
     *
     * @return string
     */
    public function getSignatureUrl()
    {
        if ($this->signature_file && Storage::disk('public')->exists('signatures/' . $this->signature_file)) {
            return Storage::url('signatures/' . $this->signature_file);
        }
        
        return '/images/no-signature.png';
    }
    
    /**
     * Получает URL файла печати пользователя.
     *
     * @return string
     */
    public function getStampUrl()
    {
        if ($this->stamp_file && Storage::disk('public')->exists('stamps/' . $this->stamp_file)) {
            return Storage::url('stamps/' . $this->stamp_file);
        }
        
        return '/images/no-stamp.png';
    }
    
    /**
     * Получает проекты, связанные с клиентом по номеру телефона.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clientProjects()
    {
        return $this->hasMany(Project::class, 'phone', 'phone');
    }

    /**
     * Получает все проекты, связанные с партнером.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projects()
    {
        return $this->hasMany(Project::class, 'partner_id');
    }
}
