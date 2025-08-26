<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SppgIntakeResource\Pages;
use App\Filament\Resources\SppgIntakeResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\SppgIntakeResource\RelationManagers\SupplierOrdersRelationManager;
use App\Models\SppgIntake;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;


class SppgIntakeResource extends Resource
{
    protected static ?string $model = SppgIntake::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Yayasan';

    protected static ?string $navigationLabel = 'PO dari SPPG';

    protected static ?string $modelLabel = 'Daftar PO SPPG';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        // Tidak ada Create/Edit untuk sekarang
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->with('sppg')->withCount('items');
            })
            // ->modifyQueryUsing(fn($q) => $q->with('sppg')->withCount('items'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('No. PO')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sppg.code')
                    ->label('SPPG')
                    ->badge()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('requested_at')
                    ->label('Tgl. Diminta')
                    ->date()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivery_time')
                    ->label('Jam Kirim')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jml Item')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->color(fn(string $state) => match ($state) {
                        SppgIntake::STATUS_RECEIVED  => 'gray',
                        SppgIntake::STATUS_ALLOCATED => 'info',
                        SppgIntake::STATUS_QUOTED    => 'warning',
                        SppgIntake::STATUS_MARKEDUP  => 'primary',
                        SppgIntake::STATUS_INVOICED  => 'success',
                        default => 'secondary',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->since()    // tampil "x minutes ago"
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sppg_id')
                    ->label('SPPG')
                    ->relationship('sppg', 'code'),

                SelectFilter::make('status')
                    ->options([
                        SppgIntake::STATUS_RECEIVED  => 'Received',
                        SppgIntake::STATUS_ALLOCATED => 'Allocated',
                        SppgIntake::STATUS_QUOTED    => 'Quoted',
                        SppgIntake::STATUS_MARKEDUP  => 'MarkedUp',
                        SppgIntake::STATUS_INVOICED  => 'Invoiced',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // (Nanti) Action Allocate di sini
            ])
            ->bulkActions([
                // none for now
            ])
            ->emptyStateHeading('Belum ada intake')
            ->emptyStateDescription('Intake dari SPPG akan muncul di sini setelah SPPG submit PO.');
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            SupplierOrdersRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSppgIntakes::route('/'),
            'view'  => Pages\ViewSppgIntake::route('/{record}'),
            // Tidak ada create/edit/delete
        ];
    }
}
