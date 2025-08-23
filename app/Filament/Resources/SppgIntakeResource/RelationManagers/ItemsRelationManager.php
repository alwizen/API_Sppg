<?php

namespace App\Filament\Resources\SppgIntakeResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Item Intake';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('qty')->label('Qty')->numeric(),
                Tables\Columns\TextColumn::make('unit')->label('Satuan'),
                // Tables\Columns\TextColumn::make('note')->label('Catatan')->wrap(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([])   // read-only
            ->actions([])         // no row actions
            ->bulkActions([]);    // no bulk actions
    }
}
