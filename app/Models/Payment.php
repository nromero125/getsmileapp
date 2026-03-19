<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id', 'invoice_id', 'amount', 'payment_date', 'method', 'reference', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function clinic() { return $this->belongsTo(Clinic::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
