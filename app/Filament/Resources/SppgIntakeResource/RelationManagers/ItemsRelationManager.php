<?php

namespace App\Filament\Resources\SppgIntakeResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Diterima';

    public function getTableHeading(): string
    {
        return 'Daftar Pesanan';
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama'),
                Tables\Columns\TextColumn::make('qty')->label('Qty')->numeric(),
                Tables\Columns\TextColumn::make('unit')->label('Satuan'),
                Tables\Columns\TextColumn::make('supplierOrderItems.price')->label('Harga Supplier')->numeric()->placeholder('Belum Ditetapkan'),
                Tables\Columns\TextColumn::make('kitchen_unit_price')->label('Harga Jual')->numeric()->placeholder('Belum Ditetapkan'),
                Tables\Columns\TextColumn::make('delivery_time_item')->label('Waktu Pengiriman')->time('H:i'),
                Tables\Columns\TextColumn::make('note')->label('Catatan')->wrap()->placeholder('Tidak Ada Catatan'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
