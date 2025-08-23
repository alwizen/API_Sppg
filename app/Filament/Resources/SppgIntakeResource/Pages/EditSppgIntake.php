<?php

namespace App\Filament\Resources\SppgIntakeResource\Pages;

use App\Filament\Resources\SppgIntakeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSppgIntake extends EditRecord
{
    protected static string $resource = SppgIntakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
