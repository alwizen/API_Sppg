<?php

namespace App\Filament\Resources\SppgResource\Pages;

use App\Filament\Resources\SppgResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSppgs extends ManageRecords
{
    protected static string $resource = SppgResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
