<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppgIntakeItem extends Model
{
    protected $fillable = [
        'sppg_intake_id',
        'external_item_id',
        'kitchen_unit_price',
        'name',
        'qty',
        'unit',
        'note'
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'kitchen_unit_price' => 'decimal:2',
    ];

    public function intake(): BelongsTo
    {
        return $this->belongsTo(SppgIntake::class, 'sppg_intake_id');
    }

    public function supplierOrderItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupplierOrderItem::class, 'sppg_intake_item_id');
    }

    public function getAllocatedQtyAttribute(): string
    {
        // sum qty_allocated sebagai string decimal
        return (string) ($this->supplierOrderItems()->sum('qty_allocated') ?? 0);
    }

    public function getRemainingQtyAttribute(): string
    {
        $allocated = (float) $this->allocated_qty;
        return number_format((float)$this->qty - $allocated, 3, '.', '');
    }

    protected $appends = ['allocated_qty', 'remaining_qty'];
}
