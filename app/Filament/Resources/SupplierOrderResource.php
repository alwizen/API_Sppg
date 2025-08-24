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
        return $form; // kita tidak pakai modal Edit
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if ($user && $user->hasRole('supplier')) {
            $query->where('supplier_id', $user->supplier_id);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('intake.po_number')
                    ->label('No. PO')->searchable()->copyable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')->searchable(),

                Tables\Columns\TextColumn::make('status')->badge(),

                // Harga per item (dari relasi orderItems)
                Tables\Columns\TextColumn::make('prices')
                    ->label('Harga / Item')
                    ->state(
                        fn(SupplierOrder $record) =>
                        $record->orderItems
                            ->pluck('price')
                            ->filter(fn($v) => $v !== null)
                            ->map(fn($v) => 'Rp ' . number_format((float) $v, 0, ',', '.'))
                            ->values()
                            ->all() // <-- pastikan jadi array biasa
                    )
                    ->listWithLineBreaks(),


                // Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Dibuat'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')->label('Supplier'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft',
                    'Quoted' => 'Quoted',
                    'Fulfilled' => 'Fulfilled',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class, // tab Items dengan TextInputColumn price
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
