<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'clinic_id', 'role',
        'phone', 'avatar_path', 'specialty', 'working_hours', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'working_hours' => 'array',
        'is_active' => 'boolean',
    ];

    public function clinic() { return $this->belongsTo(Clinic::class); }
    public function appointments() { return $this->hasMany(Appointment::class, 'dentist_id'); }
    public function dentalRecords() { return $this->hasMany(DentalRecord::class, 'dentist_id'); }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isDentist(): bool { return $this->role === 'dentist'; }
    public function isReceptionist(): bool { return $this->role === 'receptionist'; }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path) {
            return asset('storage/' . $this->avatar_path);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=0F1F3D&color=00BFA6';
    }
}
