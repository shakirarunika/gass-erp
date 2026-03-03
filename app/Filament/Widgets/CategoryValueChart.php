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
            'indexAxis' => 'y', // Tetap horizontal
            'plugins' => [
                'legend' => ['display' => false], // Hapus legend biar luas
                // --- INI KONFIGURASI DATALABELS ---
                'datalabels' => [
                    'anchor' => 'end', // Taruh di ujung batang
                    'align' => 'end',   // Rata kanan dari ujung
                    'color' => '#555',  // Warna teks angka
                    'font' => [
                        'weight' => 'bold',
                        'size' => 12,
                    ],
                    // Fungsi buat format angka jadi "Rp 55.9 Jt"
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
                    'display' => false, // Hapus garis bawah biar bersih
                    'grid' => ['display' => false],
                ],
                'y' => [
                    'grid' => ['display' => false], // Hapus garis vertikal
                    'ticks' => [
                        'font' => ['weight' => 'bold', 'size' => 13],
                    ],
                ],
            ],
            // PENTING: Kasih padding kanan biar angkanya gak kepotong
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
