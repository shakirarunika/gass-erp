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
            DATE_FORMAT(stock_opnames.opname_date, '%Y-%m') as sort_key,
            SUM(physical_qty * cost_at_opname) as total_valuation,
            SUM((physical_qty - system_qty) * cost_at_opname) as variance_value, -- Nilai selisih (Plus/Minus)
            SUM(CASE WHEN physical_qty != system_qty THEN 1 ELSE 0 END) as total_discrepancies, -- Jumlah barang selisih
            (SUM(CASE WHEN physical_qty = system_qty THEN 1 ELSE 0 END) / COUNT(*)) * 100 as accuracy
        ")
            ->where('stock_opnames.status', 'PROCESSED')
            ->groupBy('month', 'sort_key')
            ->orderBy('sort_key', 'asc')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Valuasi Fisik (Rp)',
                    'data' => $data->pluck('total_valuation')->toArray(),
                    'backgroundColor' => '#36A2EB',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Akurasi (%)',
                    'data' => $data->pluck('accuracy')->toArray(),
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
                    'title' => ['display' => true, 'text' => 'Nilai Aset'],
                    'ticks' => [
                        // Fungsi untuk menyingkat angka jutaan
                        'callback' => "function(value) {
                        if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
                        if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + ' Rb';
                        return 'Rp ' + value;
                    }",
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'min' => 0,
                    'max' => 100,
                    'grid' => ['drawOnChartArea' => false],
                    'title' => ['display' => true, 'text' => 'Akurasi (%)'],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'tooltip' => [
                'callbacks' => [
                    'label' => "function(context) {
            let label = context.dataset.label || '';
            let value = context.parsed.y;
            if (label.includes('Valuasi')) {
                return label + ': Rp ' + value.toLocaleString('id-ID');
            }
            return label + ': ' + value.toFixed(2) + '%';
        }",
                ],
            ],
        ];
    }
}
