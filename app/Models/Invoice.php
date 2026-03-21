<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'clinic_id', 'patient_id', 'appointment_id', 'invoice_number',
        'ncf', 'ncf_type', 'ncf_void', 'voided_at',
        'invoice_date', 'due_date', 'status', 'subtotal', 'discount_amount',
        'discount_percent', 'tax_amount', 'tax_percent', 'total', 'amount_paid', 'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'voided_at'    => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function clinic() { return $this->belongsTo(Clinic::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function payments()      { return $this->hasMany(Payment::class); }
    public function installments()  { return $this->hasMany(InvoiceInstallment::class)->orderBy('installment_number'); }

    public function getBalanceDueAttribute(): float
    {
        return (float) $this->total - (float) $this->amount_paid;
    }

    public static function generateNumber(int $clinicId): string
    {
        $last = static::where('clinic_id', $clinicId)->orderByDesc('id')->value('invoice_number');
        $number = $last ? (int) substr($last, -5) + 1 : 1;
        return 'INV-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}
