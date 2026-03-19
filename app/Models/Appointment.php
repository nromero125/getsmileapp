<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'clinic_id', 'patient_id', 'dentist_id', 'created_by',
        'appointment_date', 'duration_minutes', 'status',
        'reason', 'notes', 'cancellation_reason', 'total_cost',
        'confirmation_token', 'confirmed_at', 'confirmation_sent_at',
    ];

    protected $casts = [
        'appointment_date'     => 'datetime',
        'confirmed_at'         => 'datetime',
        'confirmation_sent_at' => 'datetime',
        'total_cost'           => 'decimal:2',
    ];

    public static array $statusColors = [
        'scheduled'  => '#3B82F6',
        'confirmed'  => '#00BFA6',
        'in_progress' => '#F59E0B',
        'completed'  => '#10B981',
        'cancelled'  => '#EF4444',
        'no_show'    => '#6B7280',
    ];

    public function clinic() { return $this->belongsTo(Clinic::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function dentist() { return $this->belongsTo(User::class, 'dentist_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function treatments() { return $this->belongsToMany(Treatment::class, 'appointment_treatments')->withPivot('price', 'quantity', 'notes')->withTimestamps(); }
    public function invoice() { return $this->hasOne(Invoice::class); }
    public function clinicalNotes() { return $this->hasMany(ClinicalNote::class); }
    public function dentalRecords() { return $this->hasMany(DentalRecord::class); }

    public function getStatusColorAttribute(): string
    {
        return self::$statusColors[$this->status] ?? '#6B7280';
    }

    public function getEndTimeAttribute()
    {
        return $this->appointment_date->addMinutes($this->duration_minutes);
    }
}
