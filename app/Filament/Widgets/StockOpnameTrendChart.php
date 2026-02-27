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
        // KITA KASIH DATA PALSU BUAT NGETES
        return [
            'datasets' => [
                [
                    'label' => 'Valuasi (Test)',
                    'data' => [1000000, 2500000, 1800000], // Angka dummy
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Akurasi (Test)',
                    'data' => [90, 100, 95], // Persen dummy
                    'type' => 'line',
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar'],
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
