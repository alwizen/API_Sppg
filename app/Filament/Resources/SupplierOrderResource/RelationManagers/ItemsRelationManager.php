<?php

namespace App\Filament\Resources\SupplierOrderResource\RelationManagers;

use App\Models\SupplierOrderItem;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class ItemsRelationManager extends RelationManager
{
    // relasi di model SupplierOrder: orderItems()
    protected static string $relationship = 'orderItems';
    protected static ?string $title = 'Items';

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Nama'),

                TextColumn::make('qty_allocated')
                    ->label('Qty')
                    ->numeric()
                    ->suffix(fn(SupplierOrderItem $r) => ' ' . $r->unit),

                // TextColumn::make('price')
                //     ->label('Harga (IDR)')
                //     ->formatStateUsing(function ($state, SupplierOrderItem $record) {
                //         if ($state === null || $state == 0) {
                //             return '-';
                //         }
                //         return 'Rp ' . number_format($state, 0, ',', '.');
                //     })
                //     ->color(fn($state) => $state === null || $state == 0 ? 'warning' : 'success'),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ),
            ])
            ->actions([
                Action::make('inputPrice')
                    ->label(
                        fn(SupplierOrderItem $record) =>
                        $record->price ? 'Edit Harga' : 'Masukkan Harga'
                    )
                    ->icon(
                        fn(SupplierOrderItem $record) =>
                        $record->price ? 'heroicon-o-pencil' : 'heroicon-o-currency-dollar'
                    )
                    ->color(
                        fn(SupplierOrderItem $record) =>
                        $record->price ? 'warning' : 'primary'
                    )
                    ->visible(function (SupplierOrderItem $record) {
                        // Hanya tampil jika user adalah supplier/admin dan order masih Draft
                        return (auth()->user()?->hasRole('supplier') || auth()->user()?->hasRole('admin'))
                            && optional($record->order)->status === 'Draft';
                    })
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('item_name')
                                    ->label('Item')
                                    ->default(fn(SupplierOrderItem $record) => $record->name)
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('qty')
                                    ->label('Qty')
                                    ->default(fn(SupplierOrderItem $record) => $record->qty_allocated . ' ' . $record->unit)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Forms\Components\TextInput::make('price')
                            ->label('Harga per Unit (IDR)')
                            ->numeric()
                            ->default(fn(SupplierOrderItem $record) => $record->price)
                            ->required()
                            ->minValue(1)
                            ->prefix('Rp')
                            ->placeholder('Masukkan harga...')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, SupplierOrderItem $record) {
                                if ($state) {
                                    $subtotal = $record->qty_allocated * floatval($state);
                                    $set('subtotal_preview', number_format($subtotal, 0, ',', '.'));
                                }
                            }),

                        Forms\Components\TextInput::make('subtotal_preview')
                            ->label('Subtotal Preview')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function (SupplierOrderItem $record) {
                                if ($record->price) {
                                    $subtotal = $record->qty_allocated * $record->price;
                                    return number_format($subtotal, 0, ',', '.');
                                }
                                return '0';
                            }),
                    ])
                    ->action(function (array $data, SupplierOrderItem $record) {
                        $price = floatval($data['price']);
                        $subtotal = $record->qty_allocated * $price;

                        // Update price dan subtotal
                        $record->update([
                            'price' => $price,
                            'subtotal' => $subtotal,
                        ]);

                        // Notifikasi sukses
                        Notification::make()
                            ->title('Harga berhasil disimpan!')
                            ->body("Harga: Rp " . number_format($price, 0, ',', '.') .
                                " | Subtotal: Rp " . number_format($subtotal, 0, ',', '.'))
                            ->success()
                            ->send();

                        // Cek apakah semua item sudah ada harga
                        $order = $record->order()->with('orderItems')->first();
                        if ($order && $order->status === 'Draft') {
                            $allPriced = $order->orderItems->every(fn($i) => $i->price !== null && $i->price > 0);
                            if ($allPriced) {
                                Notification::make()
                                    ->title('Semua item sudah ada harga!')
                                    ->body('Anda sekarang bisa mengirim penawaran.')
                                    ->success()
                                    ->send();
                            }
                        }
                    })
                    ->modalHeading(
                        fn(SupplierOrderItem $record) => ($record->price ? 'Edit' : 'Masukkan') . ' Harga Item'
                    )
                    ->modalSubmitActionLabel('Simpan Harga')
                    ->modalWidth('md'),
            ])
            ->headerActions([])
            ->bulkActions([]);
    }
}
