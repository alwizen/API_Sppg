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
        'qty_real',
        'price',
        'subtotal'
    ];
    protected $casts = [
        'qty_allocated' => 'decimal:2',
        'qty_real'      => 'decimal:2',
        'verified_qty'  => 'decimal:2',
        'price'         => 'decimal:2',
        'subtotal'      => 'decimal:2',
    ];

    public function getBilledSubtotalAttribute(): ?string
    {
        if ($this->price === null || $this->verified_qty === null) return null;
        return number_format((float)$this->price * (float)$this->verified_qty, 2, '.', '');
    }

    // protected $casts = [
    //     'qty_allocated' => 'decimal:2',
    //     'qty_real'      => 'decimal:2',
    //     'price'         => 'decimal:2',
    //     'subtotal'      => 'decimal:2',
    // ];

    public function intakeItem(): BelongsTo
    {
        return $this->belongsTo(SppgIntakeItem::class, 'sppg_intake_item_id');
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class, 'supplier_order_id');
    }
}
