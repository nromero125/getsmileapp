<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Paddle\Billable;

class Clinic extends Model
{
    use HasFactory, Billable;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'address',
        'logo_path', 'website', 'tax_id', 'settings', 'is_active', 'trial_ends_at',
    ];

    protected $appends = ['logo_url'];

    protected $casts = [
        'settings'      => 'array',
        'is_active'     => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    public function onLocalTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function hasActiveAccess(): bool
    {
        return $this->onLocalTrial() || $this->subscribed('default');
    }

    public function activeUserCount(): int
    {
        return $this->users()->where('is_active', true)->count();
    }

    public function extraSeatCount(): int
    {
        return max(0, $this->activeUserCount() - 3);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($clinic) {
            if (empty($clinic->slug)) {
                $clinic->slug = Str::slug($clinic->name);
            }
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function users() { return $this->hasMany(User::class); }
    public function patients() { return $this->hasMany(Patient::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function treatments() { return $this->hasMany(Treatment::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function dentists() { return $this->hasMany(User::class)->where('role', 'dentist'); }
}
