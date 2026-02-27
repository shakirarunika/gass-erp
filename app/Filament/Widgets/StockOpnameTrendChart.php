<?php

namespace App\Filament\Widgets;

use App\Models\StockOpnameDetail;
use Filament\Widgets\ChartWidget;

class StockOpnameTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Trend Akurasi & Valuasi SO';
    protected static ?int $sort = 1; // Biar muncul paling atas

    protected function getData(): array
    {
        $data = \App\Models\StockOpnameDetail::query()
            ->join('stock_opnames', 'stock_opnames.id', '=', 'stock_opname_details.stock_opname_id')
            ->selectRaw("
            DATE_FORMAT(stock_opnames.opname_date, '%b %Y') as month,
            DATE_FORMAT(stock_opnames.opname_date, '%Y-%m') as sort_key, -- Buat pengurutan
            SUM(physical_qty * cost_at_opname) as total_valuation,
            (SUM(CASE WHEN physical_qty = system_qty THEN 1 ELSE 0 END) / COUNT(*)) * 100 as accuracy
        ")
            // Pastikan 'PROCESSED' pake tanda petik di kodingan lu
            ->where('stock_opnames.status', 'PROCESSED')
            ->groupBy('month', 'sort_key') // sort_key WAJIB masuk sini biar gak error
            ->orderBy('sort_key', 'asc') // Urutkan berdasarkan YYYY-MM biar gak ngacak
            ->get();

        if ($data->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Valuasi (Rp)',
                    'data' => $data->pluck('total_valuation')->toArray(),
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#36A2EB',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Akurasi (%)',
                    'data' => $data->pluck('accuracy')->toArray(),
                    'backgroundColor' => '#FF6384',
                    'borderColor' => '#FF6384',
                    'type' => 'line',
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => ['display' => true, 'text' => 'Total Rupiah'],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'grid' => ['drawOnChartArea' => false],
                    'title' => ['display' => true, 'text' => 'Akurasi (%)'],
                    'min' => 0,
                    'max' => 100,
                ],
            ],
        ];
    }
}
