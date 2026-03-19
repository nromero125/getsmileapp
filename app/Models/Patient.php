<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Patient extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'clinic_id', 'first_name', 'last_name', 'email', 'phone', 'phone_alt',
        'date_of_birth', 'gender', 'address', 'city', 'state', 'zip_code',
        'blood_type', 'allergies', 'medical_notes', 'insurance_provider',
        'insurance_policy_number', 'emergency_contact_name', 'emergency_contact_phone',
        'emergency_contact_relation', 'avatar_path', 'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active'     => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('xrays')
            ->useDisk(config('filesystems.default') === 's3' ? 's3' : 'public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);

        $this->addMediaCollection('photos')
            ->useDisk(config('filesystems.default') === 's3' ? 's3' : 'public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('documents')
            ->useDisk(config('filesystems.default') === 's3' ? 's3' : 'public')
            ->acceptsMimeTypes(['application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

        $this->addMediaCollection('other')
            ->useDisk(config('filesystems.default') === 's3' ? 's3' : 'public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->performOnCollections('photos', 'xrays')
            ->nonQueued();
    }

    // Relationships
    public function clinic()       { return $this->belongsTo(Clinic::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function dentalRecords(){ return $this->hasMany(DentalRecord::class); }
    public function clinicalNotes(){ return $this->hasMany(ClinicalNote::class); }
    public function invoices()     { return $this->hasMany(Invoice::class); }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path) {
            return asset('storage/' . $this->avatar_path);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&background=0F1F3D&color=00BFA6';
    }
}
