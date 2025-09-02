<?php

namespace App\Filament\Resources\SupplierOrderResource\RelationManagers;

use App\Models\SupplierOrderItem;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';
    protected static ?string $title = 'Items';

    public function getTableHeading(): string
    {
        $po = optional($this->getOwnerRecord()->intake)->po_number ?? '—';
        return "Daftar Pesanan — {$po}";
    }

    protected function getTableQuery(): Builder
    {
        return $this->getOwnerRecord()
            ->orderItems()
            ->getQuery()
            ->with(['order.intake', 'intakeItem']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),

                TextColumn::make('intakeItem.delivery_time_item')
                    ->label('Jam Kirim')
                    ->placeholder('-'),

                TextColumn::make('qty_allocated')
                    ->label('Diminta')
                    ->numeric()
                    ->suffix(fn(SupplierOrderItem $record) => ' ' . $record->unit),

                TextColumn::make('qty_real')
                    ->label('Jml. Realisasi')
                    ->numeric()
                    ->placeholder('-')
                    ->suffix(fn(SupplierOrderItem $record) => $record->qty_real !== null ? ' ' . $record->unit : ''),

                TextColumn::make('price')
                    ->label('Harga (Rp)')
                    ->formatStateUsing(fn($state) => $state !== null ? 'Rp ' . number_format((float) $state, 0, ',', '.') : '—'),

                TextColumn::make('verified_qty')
                    ->label('Verifikasi (SPPG)')
                    ->numeric()
                    ->placeholder('-')
                    ->suffix(fn(SupplierOrderItem $record) => $record->verified_qty !== null ? ' ' . $record->unit : '')
                    ->tooltip(fn(SupplierOrderItem $record) => $record->verification_note ?: null),

                TextColumn::make('variance_pct')
                    ->label('Var % (real → verif)')
                    ->state(function (SupplierOrderItem $record) {
                        $real = (float) ($record->qty_real ?? 0);
                        $ver  = (float) ($record->verified_qty ?? 0);
                        if ($record->verified_qty === null || $real <= 0) return null;
                        $pct = ($ver - $real) / $real * 100.0;
                        return sprintf('%+.2f%%', $pct);
                    })
                    ->badge()
                    ->color(function (SupplierOrderItem $record) {
                        $real = (float) ($record->qty_real ?? 0);
                        $ver  = (float) ($record->verified_qty ?? 0);
                        if ($record->verified_qty === null || $real <= 0) return 'gray';
                        $pct = abs(($ver - $real) / max($real, 0.000001) * 100.0);
                        return $pct <= 2 ? 'success' : 'warning';
                    })
                    ->placeholder('—'),

                // Total penawaran: qty_real × price
                TextColumn::make('subtotal')
                    ->label('Subtotal (Rp)')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) ($state ?? 0), 0, ',', '.'))
                    ->summarize(
                        Sum::make()
                            ->label('Total Penawaran')
                            ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) ($state ?? 0), 0, ',', '.'))
                    ),

                // Total tagih: price × verified_qty - FIX: gunakan getStateUsing() bukan state()
                TextColumn::make('billed_total')
                    ->label('Tagih (verif × harga)')
                    ->getStateUsing(fn(SupplierOrderItem $record) => (float) ($record->price ?? 0) * (float) ($record->verified_qty ?? 0))
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->summarize(
                        Summarizer::make()
                            ->using(function ($query) {
                                // FIX: gunakan query builder untuk menghitung sum
                                $sum = $query->get()->sum(function ($record) {
                                    return (float) ($record->price ?? 0) * (float) ($record->verified_qty ?? 0);
                                });
                                return 'Rp ' . number_format($sum, 0, ',', '.');
                            })
                            ->label('Grand Total Tagih')
                    ),
            ])
            ->actions([
                Action::make('inputPrice')
                    ->label(fn(SupplierOrderItem $record) => $record->price ? 'Edit Harga / Real' : 'Isi Harga / Real')
                    ->icon(fn(SupplierOrderItem $record) => $record->price ? 'heroicon-o-pencil' : 'heroicon-o-currency-dollar')
                    ->color(fn(SupplierOrderItem $record) => $record->price ? 'warning' : 'primary')
                    ->visible(function (SupplierOrderItem $record) {
                        return (auth()->user()?->hasRole('supplier') || auth()->user()?->hasRole('admin'))
                            && optional($record->order)->status === 'Draft';
                    })
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('item_name')
                                ->label('Item')
                                ->default(fn(SupplierOrderItem $record) => $record->name)
                                ->disabled()
                                ->dehydrated(false),

                            Forms\Components\TextInput::make('qty_requested')
                                ->label('Diminta')
                                ->default(fn(SupplierOrderItem $record) => number_format((float) $record->qty_allocated, 3, ',', '.') . ' ' . $record->unit)
                                ->disabled()
                                ->dehydrated(false),
                        ]),

                        Forms\Components\TextInput::make('qty_real')
                            ->label('Qty Real (yang bisa dikirim)')
                            ->numeric()
                            ->step('0.001')
                            ->minValue(0)
                            ->default(fn(SupplierOrderItem $record) => $record->qty_real ?? $record->qty_allocated)
                            ->rule(function (SupplierOrderItem $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($record) {
                                    if ($value === null || $value === '') return;
                                    if (! is_numeric($value)) {
                                        $fail('Qty real harus angka.');
                                        return;
                                    }
                                    if ((float) $value > (float) $record->qty_allocated) {
                                        $fail('Qty real tidak boleh melebihi jumlah diminta.');
                                    }
                                };
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get, SupplierOrderItem $record) {
                                $price = (float) ($get('price') ?? $record->price ?? 0);
                                $qty   = (float) (($state !== null && $state !== '') ? $state : ($record->qty_real ?? $record->qty_allocated));
                                $set('subtotal_preview', number_format($qty * $price, 0, ',', '.'));
                            }),

                        Forms\Components\TextInput::make('price')
                            ->label('Harga per Unit (IDR)')
                            ->numeric()
                            ->minValue(0.01)
                            ->default(fn(SupplierOrderItem $record) => $record->price)
                            ->prefix('Rp')
                            ->placeholder('Masukkan harga…')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get, SupplierOrderItem $record) {
                                $qty = (float) ($get('qty_real') ?? $record->qty_real ?? $record->qty_allocated);
                                $set('subtotal_preview', number_format($qty * (float) $state, 0, ',', '.'));
                            }),

                        Forms\Components\TextInput::make('subtotal_preview')
                            ->label('Subtotal (preview qty_real × harga)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function (SupplierOrderItem $record) {
                                $qty   = (float) ($record->qty_real ?? $record->qty_allocated);
                                $price = (float) ($record->price ?? 0);
                                return number_format($qty * $price, 0, ',', '.');
                            }),
                    ])
                    ->action(function (array $data, SupplierOrderItem $record) {
                        $qtyReal  = $data['qty_real'] ?? null;
                        $qty      = ($qtyReal !== null && $qtyReal !== '') ? (float) $qtyReal : (float) $record->qty_allocated;
                        $price    = (float) ($data['price'] ?? 0);
                        $subtotal = $qty * $price;

                        $record->update([
                            'qty_real' => ($qtyReal !== null && $qtyReal !== '') ? (float) $qtyReal : null,
                            'price'    => $price,
                            'subtotal' => $subtotal,
                        ]);

                        Notification::make()
                            ->title('Disimpan')
                            ->body(
                                'Qty real: ' . number_format($qty, 3, ',', '.') . ' ' . $record->unit .
                                    ' | Harga: Rp ' . number_format($price, 0, ',', '.') .
                                    ' | Subtotal: Rp ' . number_format($subtotal, 0, ',', '.')
                            )
                            ->success()
                            ->send();

                        $order = $record->order()->with('orderItems')->first();
                        if ($order && $order->status === 'Draft') {
                            $complete = $order->orderItems->every(fn($i) => $i->price !== null && $i->price > 0);
                            if ($complete) {
                                Notification::make()
                                    ->title('Semua item sudah ada harga.')
                                    ->body('Anda bisa Kirim Penawaran dari halaman Order.')
                                    ->success()
                                    ->send();
                            }
                        }
                    })
                    ->modalHeading(fn(SupplierOrderItem $record) => ($record->price ? 'Edit' : 'Isi') . ' Harga & Jml. Realisasi')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalWidth('md')
                    ->button(),
            ])
            ->headerActions([])
            ->bulkActions([]);
    }
}
