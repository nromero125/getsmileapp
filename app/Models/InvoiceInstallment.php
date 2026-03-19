<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'installment_number', 'due_date', 'amount', 'amount_paid', 'paid_at', 'notes',
    ];

    protected $casts = [
        'due_date'   => 'date',
        'paid_at'    => 'datetime',
        'amount'     => 'decimal:2',
        'amount_paid'=> 'decimal:2',
    ];

    public function invoice() { return $this->belongsTo(Invoice::class); }

    public function getStatusAttribute(): string
    {
        if ($this->amount_paid >= $this->amount) return 'paid';
        if ($this->due_date->isPast()) return 'overdue';
        return 'pending';
    }

    protected $appends = ['status'];
}
