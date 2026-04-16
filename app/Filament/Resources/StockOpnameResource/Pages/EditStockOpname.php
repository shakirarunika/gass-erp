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
            // STEP 1: EXPORT (Optimasi Memory & Time)
            Actions\Action::make('exportTemplate')
                ->label('1. Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    // Paksa server kasih napas lebih (Memory & Time)
                    ini_set('memory_limit', '512M');
                    ini_set('max_execution_time', '300');

                    // Bersihkan buffer biar file gak korup
                    if (ob_get_level() > 0) ob_end_clean();

                    return Excel::download(
                        new StockOpnameExport($this->record),
                        "Template-SO-{$this->record->code}.xlsx"
                    );
                }),

            // STEP 2: IMPORT (Optimasi Background Process)
            Actions\Action::make('importResults')
                ->label('2. Upload Hasil SO')
                ->icon('heroicon-o-document-arrow-up')
                ->color('warning')
                ->form([
                    Forms\Components\FileUpload::make('attachment')
                        ->label('Pilih File Excel yang Sudah Diisi')
                        ->disk('public')
                        ->directory('temp-imports')
                        ->required(),
                ])
                ->action(function (array $data) {
                    ini_set('memory_limit', '512M');
                    ini_set('max_execution_time', '300');

                    try {
                        Excel::import(new StockOpnameImport, storage_path('app/public/' . $data['attachment']));

                        Notification::make()
                            ->title('Data Fisik Berhasil Diupdate!')
                            ->success()
                            ->send();

                        // Refresh data supaya angka fisik muncul
                        $this->refreshFormData(['details']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Import: ' . $e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            // STEP 3: FINALIZE
            Actions\Action::make('Finalize')
                ->label('3. Finalisasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->hidden(fn($record) => $record->status === 'PROCESSED')
                ->action(function ($record) {
                    ini_set('max_execution_time', '600'); // Finalisasi stok banyak butuh waktu

                    DB::transaction(function () use ($record) {
                        // Pakai chunking atau direct update biar gak memory leak
                        foreach ($record->details as $detail) {
                            \App\Models\InventoryStock::where('item_id', $detail->item_id)
                                ->where('warehouse_id', $record->warehouse_id)
                                ->update(['quantity' => $detail->physical_qty]);
                        }
                        $record->update(['status' => 'PROCESSED']);
                    });

                    Notification::make()
                        ->title('Stok Berhasil Disinkronkan!')
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
