<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Support\RawJs; // --- IMPORT INI WAJIB ---
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Filament\Support\HtmlString;

class CategoryValueChart extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Nilai Aset Per Kategori';

    // Atur sort agar widget ini di posisi yang benar
    protected static ?int $sort = 2;

    // Atur agar grafik memanjang
    protected int | string | array $columnSpan = 1;

    // --- STEP 1: BERSIHKAN LABEL ---
    protected function getData(): array
    {
        $data = \App\Models\Category::with(['items.stocks'])
            ->get()
            ->map(function ($category) {
                $value = $category->items->sum(function ($item) {
                    return $item->stocks->sum('quantity') * $item->avg_cost;
                });
                return [
                    'name' => $category->name,
                    'value' => (float) $value
                ];
            });

        return [
            'datasets' => [
                [
                    'label' => 'Nilai Aset',
                    'data' => $data->pluck('value')->toArray(),
                    'backgroundColor' => '#36A2EB', // Kasih satu warna biar gak berantakan
                ],
            ],
            // --- INI KUNCINYA: Cuma kirim nama kategori saja ---
            'labels' => $data->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    // --- STEP 2: AKTIFKAN DATALABELS ---
    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y', // Biar tetap horizontal
            'plugins' => [
                'legend' => ['display' => false], // Hapus legend biar luas
                'tooltip' => ['enabled' => false], // Matikan hover

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
                    'formatter' => RawJs::make(<<<JS
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
                        // Pastikan sumbu Y bersih dari teks rupiah
                        'callback' => RawJs::make(<<<JS
                            function(value) {
                                return value;
                            }
                        JS),
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
}
