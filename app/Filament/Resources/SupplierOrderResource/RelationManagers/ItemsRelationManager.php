<?php

namespace App\Filament\Resources\SupplierOrderResource\RelationManagers;

use App\Models\SupplierOrderItem;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\Summarizers\Sum;

class ItemsRelationManager extends RelationManager
{
    // relasi di model SupplierOrder: orderItems()
    protected static string $relationship = 'orderItems';
    protected static ?string $title = 'Items';

    public function table(Table $table): Table
    {
        $canEdit = auth()->user()?->hasRole('supplier') || auth()->user()?->hasRole('admin');

        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),

                TextColumn::make('qty_allocated')
                    ->label('Qty')
                    ->numeric()
                    ->suffix(fn(SupplierOrderItem $r) => ' ' . $r->unit),

                TextInputColumn::make('price')
                    ->label('Harga (IDR)')
                    ->type('number')
                    ->rules(['numeric', 'min:0'])
                    ->disabled(
                        fn(SupplierOrderItem $item) =>
                        ! (auth()->user()?->hasRole('supplier') || auth()->user()?->hasRole('admin'))
                            || optional($item->order)->status !== 'Draft'
                    )
                    ->afterStateUpdated(function ($state, SupplierOrderItem $record) {
                        // Simpan harga yang diinput
                        $record->update(['price' => $state]);

                        // Update subtotal langsung
                        $subtotal = $record->qty_allocated * floatval($state);
                        $record->update(['subtotal' => $subtotal]);

                        // Jika semua item sudah ada harga, ubah status order → Quoted
                        $order = $record->order()->with('orderItems')->first();
                        if ($order && $order->status === 'Draft') {
                            $allPriced = $order->orderItems->every(fn($i) => $i->price !== null && $i->price > 0);
                            if ($allPriced) {
                                $order->update(['status' => 'Quoted']);
                            }
                        }
                    }),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
