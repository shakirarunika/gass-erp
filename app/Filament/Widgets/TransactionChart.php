<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;

/**
 * Widget Tren Transaksi Chart.
 *
 * Menampilkan grafik garis (line chart) tren pergerakan kuantitas
 * barang masuk (IN) dan barang keluar (OUT) selama 7 hari terakhir.
 */
class TransactionChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;
    
    protected static ?string $heading = 'Tren Pergerakan Barang (7 Hari Terakhir)';
    
    protected static ?string $maxHeight = '200px';

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

        $dataIn = $days->map(
            fn ($date) =>
            Transaction::where('type', 'IN')
                ->whereDate('transactions.created_at', $date)
                ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
                ->sum('transaction_details.quantity')
        );

        $dataOut = $days->map(
            fn ($date) =>
            Transaction::where('type', 'OUT')
                ->whereDate('transactions.created_at', $date)
                ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
                ->sum('transaction_details.quantity')
        );

        return [
            'datasets' => [
                [
                    'label'       => 'Barang Masuk (IN)',
                    'data'        => $dataIn->toArray(),
                    'borderColor' => '#10b981',
                ],
                [
                    'label'       => 'Barang Keluar (OUT)',
                    'data'        => $dataOut->toArray(),
                    'borderColor' => '#ef4444',
                ],
            ],
            'labels' => $days->map(fn ($date) => date('d M', strtotime($date)))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
