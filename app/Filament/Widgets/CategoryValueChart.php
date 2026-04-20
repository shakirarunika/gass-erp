<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

/**
 * Widget Chart Valuasi Kategori.
 *
 * Menampilkan komposisi nilai aset gudang yang dikelompokkan
 * berdasarkan kategori barang menggunakan bar chart.
 */
class CategoryValueChart extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Nilai Aset Per Kategori';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $categories = Category::with('items.stocks')->get();

        $labels = [];
        $values = [];

        foreach ($categories as $category) {
            $categoryValuation = $category->items->sum(function ($item) {
                $totalQty = $item->stocks->sum('quantity');

                return $totalQty * $item->avg_cost;
            });

            if ($categoryValuation > 0) {
                $labels[] = sprintf(
                    '%s (%s)',
                    $category->name,
                    $this->formatRupiahCompact($categoryValuation)
                );
                $values[] = $categoryValuation;
            }
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Total Nilai (IDR)',
                    'data'            => $values,
                    'backgroundColor' => [
                        '#64748b',
                        '#10b981',
                        '#f59e0b',
                        '#f43f5e',
                        '#6366f1',
                        '#8b5cf6',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins'   => [
                'legend'     => ['display' => false],
                'datalabels' => [
                    'anchor'    => 'end',
                    'align'     => 'end',
                    'color'     => '#555',
                    'font'      => [
                        'weight' => 'bold',
                        'size'   => 12,
                    ],
                    'formatter' => \Filament\Support\RawJs::make(<<<JS
                        function(value) {
                            if (value === null || value === 0) return '';
                            if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
                            if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + ' Rb';
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    JS),
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => false,
                    'grid'    => ['display' => false],
                ],
                'y' => [
                    'grid'  => ['display' => false],
                    'ticks' => [
                        'font' => ['weight' => 'bold', 'size' => 13],
                    ],
                ],
            ],
            'layout' => [
                'padding' => [
                    'right' => 70,
                ],
            ],
        ];
    }

    private function formatRupiahCompact(float $value): string
    {
        $absValue = abs($value);

        if ($absValue >= 1_000_000_000) {
            return 'Rp' . number_format($value / 1_000_000_000, 1, ',', '.') . ' M';
        }

        if ($absValue >= 1_000_000) {
            return 'Rp' . number_format($value / 1_000_000, 1, ',', '.') . ' Jt';
        }

        if ($absValue >= 1_000) {
            return 'Rp' . number_format($value / 1_000, 1, ',', '.') . ' Rb';
        }

        return 'Rp' . number_format($value, 0, ',', '.');
    }
}
