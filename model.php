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
