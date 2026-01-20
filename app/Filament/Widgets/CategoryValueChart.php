<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Item;
use Filament\Widgets\ChartWidget;

class CategoryValueChart extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Nilai Aset Per Kategori';

    // Atur ukuran agar proporsional di dashboard
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '260px';

    protected function getData(): array
    {
        // 1. Ambil semua kategori yang punya barang
        $categories = Category::with('items.stocks')->get();

        $labels = [];
        $values = [];

        foreach ($categories as $category) {
            // 2. Hitung total valuasi per kategori
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
                    'label' => 'Total Nilai (IDR)',
                    'data' => $values,
                    // Warna formal: Slate, Emerald, Amber, Rose, Indigo
                    'backgroundColor' => [
                        '#64748b',
                        '#10b981',
                        '#f59e0b',
                        '#f43f5e',
                        '#6366f1',
                        '#8b5cf6'
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
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => [
                            'callback' => 'function(context) {
                                const value = context.raw ?? 0;
                                return new Intl.NumberFormat("id-ID", {
                                    style: "currency",
                                    currency: "IDR",
                                    maximumFractionDigits: 0,
                                }).format(value);
                            }',
                        ],
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'callback' => 'function(value) {
                            return new Intl.NumberFormat("id-ID", {
                                notation: "compact",
                                compactDisplay: "short",
                                maximumFractionDigits: 1,
                            }).format(value);
                        }',
                    ],
                ],
                'y' => [
                    'ticks' => [
                        'autoSkip' => false,
                    ],
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
