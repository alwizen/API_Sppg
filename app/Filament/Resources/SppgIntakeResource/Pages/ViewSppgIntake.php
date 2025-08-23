<?php

namespace App\Filament\Resources\SppgIntakeResource\Pages;

use App\Filament\Resources\SppgIntakeResource;
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
use Filament\Forms\Get;      // <-- tambah ini
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
                        Info\TextEntry::make('delivery_time')->label('Jam Kirim'),
                        Info\TextEntry::make('submitted_at')->label('Submitted')->since(),
                        Info\TextEntry::make('notes')->label('Catatan')->columnSpanFull()->visible(fn($record) => filled($record->notes)),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('allocate')
                ->label('Allocate ke Supplier')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('primary')
                ->modalHeading('Allocate Item ke Supplier')
                ->form(function () {
                    /** @var \App\Models\SppgIntake $record */
                    $record = $this->record->loadMissing('items');

                    // Siapkan state awal allocations: default qty = remaining
                    $allocations = $record->items->map(fn($it) => [
                        'sppg_intake_item_id' => $it->id,
                        'name'  => $it->name ?? $it->getAttribute('name'), // jaga-jaga
                        'unit'  => $it->unit,
                        'remaining' => (float) ($it->remaining_qty ?? ((float)$it->qty - (float)$it->supplierOrderItems()->sum('qty_allocated'))),
                        'qty'   => (float) ($it->remaining_qty ?? ((float)$it->qty - (float)$it->supplierOrderItems()->sum('qty_allocated'))),
                    ])->all();

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
                                    ->minValue(0) // atau 0.001 kalau wajib > 0
                                    // ✅ batasi maksimal sesuai sisa pada baris ini
                                    ->maxValue(fn(Get $get) => (float) ($get('remaining') ?? 0))
                                    // (opsional) custom message yang lebih enak dibaca:
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
