<?php

namespace App\Filament\Resources\SppgIntakeResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierOrders';
    protected static ?string $title = 'Supplier Orders';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with(['orderItems', 'supplier']))
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('order_items_count')
                    ->counts('orderItems')
                    ->label('Total Items'),
                Tables\Columns\TextColumn::make('orderItems.name')
                    ->label('Items')
                    ->listWithLineBreaks()
                    ->limitList(5)
                    ->expandableLimitedList(),
                Tables\Columns\TextColumn::make('items_quantity')
                    ->label('Quantity')
                    ->state(function ($record) {
                        return $record->orderItems->map(function ($item) {
                            // Format angka untuk menghilangkan trailing zeros
                            $qty = rtrim(rtrim(number_format($item->qty_allocated, 3, '.', ''), '0'), '.');
                            return $qty . ' ' . $item->unit;
                        })->toArray();
                    })
                    ->listWithLineBreaks()
                    ->limitList(5)
                    ->expandableLimitedList(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Dibuat'),
            ]);
    }
}
