<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id', 'treatment_category_id', 'name', 'description',
        'default_price', 'duration_minutes', 'color', 'is_active',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function clinic() { return $this->belongsTo(Clinic::class); }
    public function category() { return $this->belongsTo(TreatmentCategory::class, 'treatment_category_id'); }
    public function appointments() { return $this->belongsToMany(Appointment::class, 'appointment_treatments')->withPivot('price', 'quantity', 'notes'); }
    public function invoiceItems() { return $this->hasMany(InvoiceItem::class); }
}
