<?php

namespace App\Filament\Resources\SppgIntakeResource\Pages;

use App\Filament\Resources\SppgIntakeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSppgIntakes extends ListRecords
{
    protected static string $resource = SppgIntakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
