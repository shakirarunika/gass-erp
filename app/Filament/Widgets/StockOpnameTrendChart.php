<?php

namespace App\Filament\Widgets;

use App\Models\StockOpnameDetail;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class StockOpnameTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Trend Akurasi & Valuasi SO';
    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $data = StockOpnameDetail::query()
            ->join('stock_opnames', 'stock_opnames.id', '=', 'stock_opname_details.stock_opname_id')
            ->selectRaw("
                DATE_FORMAT(stock_opnames.opname_date, '%b %Y') as month,
                DATE_FORMAT(stock_opnames.opname_date, '%Y-%m') as sort_key,
                SUM(physical_qty * cost_at_opname) as total_valuation,
                (SUM(CASE WHEN physical_qty = system_qty THEN 1 ELSE 0 END) / COUNT(*)) * 100 as accuracy
            ")
            ->where('stock_opnames.status', 'PROCESSED')
            ->groupBy('month', 'sort_key')
            ->orderBy('sort_key', 'asc')
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
                    'label' => 'Valuasi Fisik (Rp)',
                    'data' => $data->pluck('total_valuation')->map(fn($v) => (float) $v)->toArray(),
                    'backgroundColor' => '#36A2EB',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Akurasi (%)',
                    'data' => $data->pluck('accuracy')->map(fn($v) => (float) $v)->toArray(),
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
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'min' => 0,
                    'max' => 100,
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
