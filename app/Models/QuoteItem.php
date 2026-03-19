<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id', 'treatment_id', 'description', 'quantity', 'unit_price', 'discount', 'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function quote()     { return $this->belongsTo(Quote::class); }
    public function treatment() { return $this->belongsTo(Treatment::class); }
}
