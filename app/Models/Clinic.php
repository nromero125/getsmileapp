<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'address',
        'logo_path', 'website', 'tax_id', 'settings', 'is_active',
    ];

    protected $appends = ['logo_url'];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

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
