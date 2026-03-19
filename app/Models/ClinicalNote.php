<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id', 'patient_id', 'appointment_id', 'dentist_id',
        'subjective', 'objective', 'assessment', 'plan', 'notes',
    ];

    public function clinic() { return $this->belongsTo(Clinic::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function dentist() { return $this->belongsTo(User::class, 'dentist_id'); }
}
