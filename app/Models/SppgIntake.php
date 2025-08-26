<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SppgIntake extends Model
{
    public const STATUS_RECEIVED  = 'Received';
    public const STATUS_ALLOCATED = 'Allocated';
    public const STATUS_QUOTED    = 'Quoted';
    public const STATUS_MARKEDUP  = 'MarkedUp';
    public const STATUS_INVOICED  = 'Invoiced';

    protected $fillable = [
        'sppg_id',
        'po_number',
        'requested_at',
        'delivery_time',
        'status',
        'notes',
        'submitted_at',
        'external_id',
        'external_meta',
        'external_hash',
        'total_cost',
        'markup_percent',
        'total_markup',
        'grand_total'
    ];

    protected $casts = [
        'requested_at' => 'date',
        'submitted_at' => 'datetime',
        'external_meta' => 'array',
    ];

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SppgIntakeItem::class);
    }

    public function supplierOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupplierOrder::class, 'sppg_intake_id');
    }
}
