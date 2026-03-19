<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DentalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id', 'patient_id', 'appointment_id', 'dentist_id',
        'tooth_number', 'condition', 'surface', 'notes',
    ];

    public static array $conditions = [
        'healthy'    => ['label' => 'Healthy',     'color' => '#10B981'],
        'cavity'     => ['label' => 'Cavity',      'color' => '#EF4444'],
        'crown'      => ['label' => 'Crown',       'color' => '#F59E0B'],
        'extraction' => ['label' => 'Extracted',   'color' => '#6B7280'],
        'root_canal' => ['label' => 'Root Canal',  'color' => '#8B5CF6'],
        'implant'    => ['label' => 'Implant',     'color' => '#3B82F6'],
        'filling'    => ['label' => 'Filling',     'color' => '#F97316'],
        'bridge'     => ['label' => 'Bridge',      'color' => '#06B6D4'],
        'veneer'     => ['label' => 'Veneer',      'color' => '#EC4899'],
        'missing'    => ['label' => 'Missing',     'color' => '#1F2937'],
        'other'      => ['label' => 'Other',       'color' => '#9CA3AF'],
    ];

    public function clinic() { return $this->belongsTo(Clinic::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function dentist() { return $this->belongsTo(User::class, 'dentist_id'); }

    public function getConditionColorAttribute(): string
    {
        return self::$conditions[$this->condition]['color'] ?? '#9CA3AF';
    }
}
