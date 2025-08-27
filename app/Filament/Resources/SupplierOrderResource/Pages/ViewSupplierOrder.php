<?php

namespace App\Filament\Resources\SupplierOrderResource\Pages;

use App\Filament\Resources\SupplierOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewSupplierOrder extends ViewRecord
{
    protected static string $resource = SupplierOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('submitQuote')
                ->label('Kirim Penawaran')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->status === 'Draft' && auth()->user()?->hasRole('supplier'))
                ->action(function () {
                    $order = $this->record->load('orderItems');

                    // Pastikan semua item sudah ada harga
                    $allPriced = $order->orderItems->every(fn($i) => $i->price !== null);
                    if (! $allPriced) {
                        Notification::make()
                            ->title('Gagal mengirim penawaran')
                            ->body('Lengkapi harga semua item terlebih dahulu.')
                            ->danger()
                            ->icon('heroicon-o-exclamation-triangle')
                            ->send();
                        return;
                    }

                    $order->update([
                        'status'    => 'Quoted',
                        'quoted_at' => now(),
                    ]);

                    // (opsional) jika semua SupplierOrder di intake sudah Quoted → update intake
                    $intake = $order->intake()->with('supplierOrders')->first();
                    if ($intake && $intake->supplierOrders->every(fn($so) => $so->status === 'Quoted')) {
                        $intake->update(['status' => \App\Models\SppgIntake::STATUS_QUOTED]);
                    }

                    Notification::make()
                        // ->title('Penawaran berhasil dikirim!')
                        ->body('Penawaran berhasil dikirim!.')
                        ->success()
                        ->icon('heroicon-o-check-circle')
                        ->duration(4000)
                        ->send();
                }),
        ];
    }
}
