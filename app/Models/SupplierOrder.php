<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SupplierOrder extends Model
{
    protected $fillable = [
        'sppg_intake_id',
        'supplier_id',
        'status',
        'notes'
    ];

    public function recalcVerificationStatus(): void
    {
        $items = $this->orderItems()->get(['verified_qty']);
        if ($items->isEmpty()) return;

        $allHave = $items->every(fn($i) => $i->verified_qty !== null);
        $someHave = $items->some(fn($i) => $i->verified_qty !== null);

        $to = $this->status;
        if ($allHave) $to = 'Verified';
        elseif ($someHave && $this->status !== 'Verified') $to = 'PartiallyVerified';

        if ($to !== $this->status) $this->update(['status' => $to]);
    }


    public function orderItems(): HasMany
    {
        return $this->hasMany(SupplierOrderItem::class, 'supplier_order_id');
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function intake(): BelongsTo
    {
        return $this->belongsTo(SppgIntake::class, 'sppg_intake_id');
    }

    public function getTotalAttribute(): ?string
    {
        $sum = $this->orderItems()->sum('subtotal');
        return $sum !== null ? number_format((float)$sum, 2, '.', '') : null;
    }
    protected $appends = ['total'];
}
