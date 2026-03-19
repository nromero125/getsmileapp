<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id', 'inventory_item_id', 'user_id', 'type', 'quantity', 'stock_after', 'reason',
    ];

    protected $casts = [
        'quantity'    => 'decimal:2',
        'stock_after' => 'decimal:2',
    ];

    public function item() { return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
