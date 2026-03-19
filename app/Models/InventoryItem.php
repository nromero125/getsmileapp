<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id', 'inventory_category_id', 'name', 'description',
        'unit', 'stock', 'min_stock', 'cost_per_unit', 'supplier', 'sku', 'is_active',
    ];

    protected $casts = [
        'stock'         => 'decimal:2',
        'min_stock'     => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    protected $appends = ['is_low_stock'];

    public function clinic()     { return $this->belongsTo(Clinic::class); }
    public function category()   { return $this->belongsTo(InventoryCategory::class, 'inventory_category_id'); }
    public function movements()  { return $this->hasMany(InventoryMovement::class); }
    public function treatments() {
        return $this->belongsToMany(Treatment::class, 'inventory_item_treatment')
                    ->withPivot('quantity_used')
                    ->withTimestamps();
    }

    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->stock <= (float) $this->min_stock;
    }
}
