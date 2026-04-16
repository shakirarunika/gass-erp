<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use App\Exports\StockOpnameExport;
use App\Imports\StockOpnameImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class EditStockOpname extends EditRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // STEP 1: EXPORT (Ambil data dari database ke Excel)
            Actions\Action::make('exportTemplate')
                ->label('1. Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(fn() => Excel::download(
                    new StockOpnameExport($this->record),
                    "Template-SO-{$this->record->code}.xlsx"
                )),

            // STEP 2: IMPORT (Masukkan data dari Excel ke database)
            Actions\Action::make('importResults')
                ->label('2. Upload Hasil SO')
                ->icon('heroicon-o-document-arrow-up')
                ->color('warning')
                ->form([
                    Forms\Components\FileUpload::make('attachment')
                        ->label('Pilih File Excel yang Sudah Diisi')
                        ->disk('public')
                        ->directory('temp-imports')
                        ->visibility('public')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Eksekusi Import
                    Excel::import(new StockOpnameImport, storage_path('app/public/' . $data['attachment']));

                    Notification::make()
                        ->title('Data Fisik Berhasil Diupdate!')
                        ->success()
                        ->send();

                    // REFRESH DATA: Biar angka di Repeater bawah langsung berubah
                    $this->refreshFormData(['details']);
                }),

            // STEP 3: FINALIZE (Update stok gudang yang sesungguhnya)
            Actions\Action::make('Finalize')
                ->label('3. Finalisasi & Update Stok')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Finalisasi')
                ->modalDescription('Tindakan ini akan merubah jumlah stok di gudang secara permanen. Pastikan input fisik sudah benar!')
                ->hidden(fn($record) => $record->status === 'PROCESSED')
                ->action(function ($record) {
                    DB::transaction(function () use ($record) {
                        foreach ($record->details as $detail) {
                            $inventory = \App\Models\InventoryStock::where('item_id', $detail->item_id)
                                ->where('warehouse_id', $record->warehouse_id)
                                ->first();

                            if ($inventory) {
                                // Update stok gudang jadi angka FISIK hasil opname
                                $inventory->update(['quantity' => $detail->physical_qty]);
                            }
                        }

                        // Ubah status jadi PROCESSED
                        $record->update(['status' => 'PROCESSED']);
                    });

                    Notification::make()
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
