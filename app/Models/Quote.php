<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'clinic_id', 'patient_id', 'quote_number', 'quote_date', 'valid_until',
        'status', 'subtotal', 'discount_percent', 'discount_amount',
        'tax_percent', 'tax_amount', 'total', 'notes', 'converted_to_invoice_id',
    ];

    protected $casts = [
        'quote_date'  => 'date',
        'valid_until' => 'date',
        'subtotal'    => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount'  => 'decimal:2',
        'total'       => 'decimal:2',
    ];

    public function clinic()   { return $this->belongsTo(Clinic::class); }
    public function patient()  { return $this->belongsTo(Patient::class); }
    public function items()    { return $this->hasMany(QuoteItem::class); }
    public function invoice()  { return $this->belongsTo(Invoice::class, 'converted_to_invoice_id'); }

    public static function generateNumber(int $clinicId): string
    {
        $last = static::where('clinic_id', $clinicId)->latest()->value('quote_number');
        $number = $last ? (int) substr($last, -5) + 1 : 1;
        return 'COT-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}
