<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockOpname extends EditRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Finalize')
                ->label('Finalisasi & Update Stok')
                ->color('success')
                ->requiresConfirmation()
                ->hidden(fn($record) => $record->status === 'PROCESSED')
                ->action(function ($record) {
                    \DB::transaction(function () use ($record) {
                        foreach ($record->details as $detail) {
                            $inventory = \App\Models\InventoryStock::where('item_id', $detail->item_id)
                                ->where('warehouse_id', $record->warehouse_id)
                                ->first();

                            if ($inventory) {
                                // Update stok gudang jadi angka FISIK
                                $inventory->update(['quantity' => $detail->physical_qty]);
                            }
                        }

                        // Ubah status jadi PROCESSED
                        $record->update(['status' => 'PROCESSED']);
                    });

                    \Filament\Notifications\Notification::make()
                        ->title('Stok Gudang Berhasil Disinkronkan!')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
