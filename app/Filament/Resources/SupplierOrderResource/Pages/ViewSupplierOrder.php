<?php

namespace App\Filament\Resources\SupplierOrderResource\Pages;

use App\Filament\Resources\SupplierOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

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
                // ✅ pakai built-in notifications
                ->successNotificationTitle('Penawaran terkirim.')
                ->failureNotificationTitle('Lengkapi harga semua item dulu.')
                ->action(function (Actions\Action $action) {
                    $order = $this->record->load('orderItems');

                    // Pastikan semua item sudah ada harga
                    $allPriced = $order->orderItems->every(fn($i) => $i->price !== null);
                    if (! $allPriced) {
                        $action->failure();   // ⬅️ memicu failure toast dengan title di atas
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

                    $action->success();       // ⬅️ memicu success toast
                    $this->refreshRecord();
                }),
        ];
    }
}
