<?php

namespace App\Filament\Resources\SppgIntakeResource\Pages;

use App\Filament\Resources\SppgIntakeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSppgIntake extends CreateRecord
{
    protected static string $resource = SppgIntakeResource::class;

    protected static bool $canCreateAnother = false;

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
