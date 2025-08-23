<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SupplierOrder extends Model
{
    protected $fillable = ['sppg_intake_id', 'supplier_id', 'status', 'notes'];

    public function orderItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\SupplierOrderItem::class, 'supplier_order_id');
    }
    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Supplier::class);
    }
    public function intake(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\SppgIntake::class, 'sppg_intake_id');
    }


    public function getTotalAttribute(): ?string
    {
        $sum = $this->orderItems()->sum('subtotal');
        return $sum !== null ? number_format((float)$sum, 2, '.', '') : null;
    }
    protected $appends = ['total'];
}
