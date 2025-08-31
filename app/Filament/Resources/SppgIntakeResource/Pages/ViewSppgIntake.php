<?php

namespace App\Filament\Resources\SppgIntakeResource\Pages;

use App\Filament\Resources\SppgIntakeResource;
use App\Models\SppgIntake;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components as Info;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use App\Models\Supplier;
use App\Models\SppgIntakeItem;
use Filament\Forms\Get;
use Closure;

class ViewSppgIntake extends ViewRecord
{
    protected static string $resource = SppgIntakeResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Info\Section::make('Ringkasan')
                    ->schema([
                        Info\TextEntry::make('po_number')->label('No. PO')->copyable(),
                        Info\TextEntry::make('sppg.code')->label('SPPG')->badge(),
                        Info\TextEntry::make('status')->badge(),
                        Info\TextEntry::make('requested_at')->label('Tgl. Diminta')->date(),
                        // Info\TextEntry::make('delivery_time')->label('Jam Kirim'),
                        // Info\TextEntry::make('submitted_at')->label('Submitted')->since(),
                        Info\TextEntry::make('notes')->label('Catatan')->columnSpanFull()->visible(fn($record) => filled($record->notes)),
                    ])
                    ->columns(4),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('applyMarkup')
                ->label('Terapkan Harga Jual')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->visible(function () {
                    $u = auth()->user();
                    if (! $u?->hasAnyRole(['yayasan', 'admin', 'super_admin'])) return false;

                    // tombol muncul jika semua SO untuk intake ini sudah Quoted
                    return ! \App\Models\SupplierOrder::where('sppg_intake_id', $this->record->id)
                        ->where('status', '!=', 'Quoted')
                        ->exists();
                })
                ->form(function () {
                    // hitung total cost sekarang (untuk info)
                    $intake = $this->record->load(['items.supplierOrderItems', 'supplierOrders.orderItems']);
                    $totalCost = 0.0;
                    foreach ($intake->supplierOrders as $order) {
                        foreach ($order->orderItems as $oi) {
                            $totalCost += (float) ($oi->subtotal ?? ((float)$oi->qty_allocated * (float)($oi->price ?? 0)));
                        }
                    }

                    // siapkan daftar item untuk mode per item: qty total, avg harga supplier
                    $rows = $intake->items->map(function (SppgIntakeItem $it) {
                        $qty = (float) $it->qty;
                        $sumCost = 0.0;
                        $sumQty = 0.0;
                        foreach ($it->supplierOrderItems as $soi) {
                            if ($soi->price !== null) {
                                $sumCost += (float)$soi->price * (float)$soi->qty_allocated;
                                $sumQty  += (float)$soi->qty_allocated;
                            }
                        }
                        $avgSupplier = $sumQty > 0 ? $sumCost / $sumQty : null;

                        return [
                            'intake_item_id'      => $it->id,
                            'name'                => $it->name,
                            'unit'                => $it->unit,
                            'total_qty'           => $qty,
                            'supplier_avg_price'  => $avgSupplier,                 // readonly
                            'kitchen_unit_price'  => $it->kitchen_unit_price,      // input
                        ];
                    })->values()->all();

                    return [
                        Forms\Components\Placeholder::make('total_cost_view')
                            ->label('Total Cost (supplier)')
                            ->content('Rp ' . number_format($totalCost, 0, ',', '.')),

                        Forms\Components\Select::make('mode')
                            ->label('Metode Hitung')
                            ->options([
                                'percent' => 'Persentase',
                                'manual'  => 'Grand Total Manual',
                                'per_item' => 'Per Item (harga satuan dapur)',
                            ])
                            ->default('percent')
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('markup_percent')
                            ->label('Markup (%)')
                            ->numeric()->minValue(0)->maxValue(100)
                            ->default(10)
                            ->visible(fn(Get $get) => $get('mode') === 'percent'),

                        Forms\Components\TextInput::make('grand_total_manual')
                            ->label('Grand Total (Manual)')
                            ->numeric()->minValue(0)
                            ->visible(fn(Get $get) => $get('mode') === 'manual'),

                        // 🔥 Mode per item: tampilkan daftar item + harga supplier & input harga dapur/unit
                        Forms\Components\Repeater::make('items_pricing')
                            ->label('Harga per Item')
                            ->default($rows)
                            ->visible(fn(Get $get) => $get('mode') === 'per_item')
                            ->columns(6)
                            ->schema([
                                Forms\Components\Hidden::make('intake_item_id'),
                                Forms\Components\TextInput::make('name')->disabled()->dehydrated(false)->label('Nama'),
                                Forms\Components\TextInput::make('unit')->disabled()->dehydrated(false)->label('Sat')->extraInputAttributes(['style' => 'width:80px']),
                                Forms\Components\TextInput::make('total_qty')->disabled()->dehydrated(false)->numeric()->label('Qty'),
                                Forms\Components\TextInput::make('supplier_avg_price')
                                    ->label('Harga Supplier (avg)')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->numeric()
                                    ->prefix('Rp'),
                                Forms\Components\TextInput::make('kitchen_unit_price')
                                    ->label('Harga Dapur / unit')
                                    ->numeric()->minValue(0)
                                    ->prefix('Rp')
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')->rows(3)->columnSpanFull(),
                    ];
                })
                ->successNotificationTitle('Markup diterapkan & intake dipublikasikan.')
                ->failureNotificationTitle('Semua Supplier Order harus berstatus Quoted dan memiliki harga.')
                ->action(function (Actions\Action $action, array $data) {
                    $intake = $this->record->load(['items', 'supplierOrders.orderItems']);

                    // total cost (supplier)
                    $totalCost = 0.0;
                    foreach ($intake->supplierOrders as $order) {
                        foreach ($order->orderItems as $oi) {
                            $totalCost += (float) ($oi->subtotal ?? ((float)$oi->qty_allocated * (float)($oi->price ?? 0)));
                        }
                    }

                    $mode = $data['mode'] ?? 'percent';

                    if ($mode === 'per_item') {
                        $rows = collect($data['items_pricing'] ?? []);
                        if ($rows->isEmpty()) {
                            $action->failure();
                            return;
                        }

                        $grandTotal = 0.0;

                        foreach ($rows as $r) {
                            $item = SppgIntakeItem::find($r['intake_item_id'] ?? null);
                            if (! $item) continue;

                            $unitPrice = (float) ($r['kitchen_unit_price'] ?? 0);
                            $grandTotal += (float) $item->qty * $unitPrice;

                            // simpan harga dapur per item
                            $item->update(['kitchen_unit_price' => $unitPrice]);
                        }

                        $totalMarkup = round($grandTotal - $totalCost, 2);
                        $pct         = $totalCost > 0 ? round(($totalMarkup / $totalCost) * 100, 2) : 0.0;
                    } elseif ($mode === 'manual') {
                        $grandTotal  = max(0, (float) ($data['grand_total_manual'] ?? 0));
                        $totalMarkup = round($grandTotal - $totalCost, 2);
                        $pct         = $totalCost > 0 ? round(($totalMarkup / $totalCost) * 100, 2) : 0.0;
                    } else { // percent
                        $pct         = max(0, (float) ($data['markup_percent'] ?? 0));
                        $totalMarkup = round($totalCost * $pct / 100, 2);
                        $grandTotal  = round($totalCost + $totalMarkup, 2);
                    }

                    $intake->update([
                        'total_cost'     => round($totalCost, 2),
                        'markup_percent' => $pct,
                        'total_markup'   => $totalMarkup,
                        'grand_total'    => $grandTotal,
                        'marked_up_at'   => now(),
                        'status'         => \App\Models\SppgIntake::STATUS_MARKEDUP,
                        'notes'          => filled($data['notes'] ?? null)
                            ? trim((string)$intake->notes . "\n" . $data['notes'])
                            : $intake->notes,
                    ]);

                    $action->success();

                    // refresh halaman
                    return $this->redirect(
                        $this->getResource()::getUrl('view', ['record' => $intake])
                    );
                }),

            Actions\Action::make('allocate')
                ->label('Allocate ke Supplier')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('primary')
                ->modalHeading('Allocate Item ke Supplier')
                ->form(function () {
                    /** @var \App\Models\SppgIntake $record */
                    $record = $this->record->loadMissing('items');

                    // Siapkan state awal allocations: hanya item yang masih punya sisa
                    $allocations = $record->items
                        ->map(function ($it) {
                            $remaining = (float) ($it->remaining_qty ?? ((float)$it->qty - (float)$it->supplierOrderItems()->sum('qty_allocated')));

                            return [
                                'sppg_intake_item_id' => $it->id,
                                'name'  => $it->name ?? $it->getAttribute('name'),
                                'unit'  => $it->unit,
                                'remaining' => $remaining,
                                'qty'   => $remaining,
                            ];
                        })
                        ->filter(fn($item) => $item['remaining'] > 0) // ✅ Filter hanya yang masih ada sisa
                        ->values()
                        ->all();

                    // Jika tidak ada item yang bisa dialokasikan
                    if (empty($allocations)) {
                        throw new \RuntimeException('Semua item sudah teralokasi penuh. Tidak ada yang bisa dialokasikan lagi.');
                    }

                    return [
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->options(Supplier::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Repeater::make('allocations')
                            ->label('Item yang Dialokasikan')
                            ->schema([
                                Forms\Components\Hidden::make('sppg_intake_item_id'),
                                Forms\Components\TextInput::make('name')->disabled()->dehydrated(false)->label('Nama'),
                                Forms\Components\TextInput::make('unit')->disabled()->dehydrated(false)->label('Satuan')->extraInputAttributes(['style' => 'width:100px']),
                                Forms\Components\TextInput::make('remaining')
                                    ->disabled()
                                    ->numeric()
                                    ->dehydrated(false)
                                    ->label('Sisa'),
                                Forms\Components\TextInput::make('qty')
                                    ->label('Qty → supplier')
                                    ->numeric()
                                    ->step('0.001')
                                    ->minValue(0)
                                    ->maxValue(fn(Get $get) => (float) ($get('remaining') ?? 0))
                                    ->rule(fn(Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                        $remaining = (float) ($get('remaining') ?? 0);
                                        if ((float) $value > $remaining) {
                                            $fail("Qty melebihi sisa ($remaining).");
                                        }
                                    })
                                    ->default(0),
                            ])
                            ->columns(5)
                            ->default($allocations)
                            ->minItems(1)
                            ->collapsed(false)
                    ];
                })
                ->visible(function () {
                    // ✅ Sembunyikan tombol jika tidak ada item yang bisa dialokasikan
                    /** @var \App\Models\SppgIntake $record */
                    $record = $this->record->loadMissing('items');

                    $hasRemainingItems = $record->items->some(function ($item) {
                        $remaining = (float) ($item->remaining_qty ?? ((float)$item->qty - (float)$item->supplierOrderItems()->sum('qty_allocated')));
                        return $remaining > 0;
                    });

                    return $hasRemainingItems;
                })
                ->action(function (array $data) {
                    /** @var \App\Models\SppgIntake $intake */
                    $intake = $this->record->fresh(['items.supplierOrderItems']);

                    // Filter qty > 0
                    $rows = collect($data['allocations'] ?? [])->filter(fn($r) => (float)($r['qty'] ?? 0) > 0)->values();
                    if ($rows->isEmpty()) {
                        throw new \RuntimeException('Tidak ada qty yang dialokasikan.');
                    }

                    DB::transaction(function () use ($intake, $data, $rows) {
                        $order = SupplierOrder::create([
                            'sppg_intake_id' => $intake->id,
                            'supplier_id'    => $data['supplier_id'],
                            'status'         => 'Draft',
                            'notes'          => null,
                        ]);

                        foreach ($rows as $r) {
                            /** @var SppgIntakeItem $item */
                            $item = $intake->items->firstWhere('id', $r['sppg_intake_item_id']);
                            if (!$item) {
                                continue;
                            }

                            // hitung remaining aktual dari DB
                            $allocatedSum = (float) $item->supplierOrderItems()->sum('qty_allocated');
                            $remaining = (float) $item->qty - $allocatedSum;

                            $qty = min((float) $r['qty'], $remaining);
                            if ($qty <= 0) {
                                continue;
                            }

                            SupplierOrderItem::create([
                                'supplier_order_id'    => $order->id,
                                'sppg_intake_item_id'  => $item->id,
                                'name'                 => $item->name,
                                'unit'                 => $item->unit,
                                'qty_allocated'        => $qty,
                                'qty_real'             => null,
                                'price'                => null,
                                'subtotal'             => null,
                            ]);
                        }

                        // Update status intake jika semua item sudah teralokasi penuh
                        $intake->load('items.supplierOrderItems');
                        $allAllocated = $intake->items->every(function ($it) {
                            $allocated = (float) $it->supplierOrderItems->sum('qty_allocated');
                            return $allocated >= (float) $it->qty;
                        });

                        if ($allAllocated && $intake->status === \App\Models\SppgIntake::STATUS_RECEIVED) {
                            $intake->update(['status' => \App\Models\SppgIntake::STATUS_ALLOCATED]);
                        }
                    });
                }),
        ];
    }
}
