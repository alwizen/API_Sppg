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
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('items_count')->counts('orderItems')->label('Items'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Dibuat'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
