<?php

namespace App\Exports;

use App\Models\InventoryStock;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InventoryReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->get();
    }

    // PENGATURAN JUDUL & TANGGAL (Di atas tabel)
    public function headings(): array
    {
        return [
            ['LAPORAN STOK REAL-TIME GUDANG (G.A.S.S)'],
            ['Tanggal Download: ' . date('d F Y H:i')],
            [''], // Baris Kosong
            [
                'Gudang',
                'Kategori',
                'Kode Barang',
                'Nama Barang',
                'Lokasi Rak',
                'Sisa Stok',
                'Satuan',
                'Total Valuasi (IDR)'
            ]
        ];
    }

    // MAPPING DATA KE KOLOM (Sesuai Web UI)
    public function map($stock): array
    {
        return [
            $stock->warehouse->name ?? '-',
            $stock->item->category->name ?? '-',
            $stock->item->code ?? '-',
            $stock->item->name ?? '-',
            $stock->rack_location ?? 'Belum set',
            $stock->quantity,
            $stock->item->unit->name ?? 'Unit',
            $stock->quantity * ($stock->item->avg_cost ?? 0),
        ];
    }

    // STYLING BIAR CAKEP (Bikin Judul Gede & Bold)
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]], // Judul Utama
            2 => ['font' => ['italic' => true]],           // Tanggal
            4 => [                                          // Header Tabel
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ],
        ];
    }
}
