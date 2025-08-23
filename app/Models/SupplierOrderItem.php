<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierOrderItem extends Model
{
    protected $fillable = [
        'supplier_order_id',
        'sppg_intake_item_id',
        'name',
        'unit',
        'qty_allocated',
        'price',
        'subtotal'
    ];
    protected $casts = ['qty_allocated' => 'decimal:3', 'price' => 'decimal:2', 'subtotal' => 'decimal:2'];

    public function intakeItem(): BelongsTo
    {
        return $this->belongsTo(SppgIntakeItem::class, 'sppg_intake_item_id');
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class, 'supplier_order_id');
    }
}
