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
            Actions\Action::make('finalizeInvoice')
                ->label('Finalisasi Nota')
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->visible(fn() => $this->record->status === 'Verified' && auth()->user()?->hasAnyRole(['yayasan', 'admin', 'super_admin']))
                ->requiresConfirmation()
                ->action(function () {
                    // di sini kamu bisa buat tabel invoices + items, atau cukup set status
                    $this->record->update(['status' => 'Invoiced']);
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
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
