<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToothDiagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id', 'patient_id', 'tooth_number', 'diagnosis_catalog_id',
        'dentist_id', 'notes', 'diagnosed_at',
    ];

    protected $casts = [
        'diagnosed_at' => 'datetime',
        'tooth_number' => 'integer',
    ];

    public function clinic()    { return $this->belongsTo(Clinic::class); }
    public function patient()   { return $this->belongsTo(Patient::class); }
    public function catalog()   { return $this->belongsTo(DiagnosisCatalog::class, 'diagnosis_catalog_id'); }
    public function dentist()   { return $this->belongsTo(User::class, 'dentist_id'); }
}
