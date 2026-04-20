<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Widget Top Departemen Chart.
 *
 * Menampilkan top 5 departemen dengan frekuensi permintaan barang (OUT)
 * tertinggi. Saat ini disembunyikan dari dashboard (canView = false).
 */
class TopDepartmentChart extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Departemen Paling Boros (Frekuensi)';
    
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return false;
    }

    protected function getData(): array
    {
        $data = Transaction::query()
            ->join('departments', 'transactions.department_id', '=', 'departments.id')
            ->select('departments.name', DB::raw('count(*) as total'))
            ->where('transactions.type', 'OUT')
            ->groupBy('departments.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Jumlah Permintaan',
                    'data'            => $data->pluck('total'),
                    'backgroundColor' => [
                        '#3b82f6',
                        '#8b5cf6',
                        '#ec4899',
                        '#f97316',
                        '#eab308',
                    ],
                ],
            ],
            'labels' => $data->pluck('name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
