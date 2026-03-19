<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'clinic_id', 'patient_id', 'appointment_id', 'uploaded_by',
        'name', 'original_name', 'path', 'mime_type', 'size', 'type', 'notes',
    ];

    public function clinic() { return $this->belongsTo(Clinic::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
