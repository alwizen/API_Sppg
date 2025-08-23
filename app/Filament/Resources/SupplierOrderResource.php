<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierOrderResource\Pages;
use App\Filament\Resources\SupplierOrderResource\RelationManagers\ItemsRelationManager;
use App\Models\SupplierOrder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class SupplierOrderResource extends Resource
{
    protected static ?string $model = SupplierOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Supplier Orders';
    protected static ?string $modelLabel = 'Supplier Order';

    public static function form(Form $form): Form
    {
        // read-only header; item & harga lewat RelationManager
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $q) {
                // aman: eager load + hitung count via withCount
                // $q->with(['supplier', 'intake'])->withCount('orderItems');
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('intake.po_number')
                    ->label('No. PO')->searchable()->copyable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')->searchable(),

                Tables\Columns\TextColumn::make('status')->badge(),

                // gunakan kolom hasil withCount
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')->numeric(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->label('Dibuat'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')->label('Supplier'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft',
                    'Quoted' => 'Quoted',
                    'Fulfilled' => 'Fulfilled'
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierOrders::route('/'),
            'view'  => Pages\ViewSupplierOrder::route('/{record}'),
        ];
    }
}
