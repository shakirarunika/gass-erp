<?php

namespace App\Exports;

use App\Models\StockOpname;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockOpnameExport implements FromCollection, WithHeadings, WithMapping
{
    protected $stockOpname;

    public function __construct(StockOpname $stockOpname)
    {
        $this->stockOpname = $stockOpname;
    }

    public function collection()
    {
        // Kita ambil detail yang sudah dibuat (saat create)
        return $this->stockOpname->details()->with('item')->get();
    }

    public function headings(): array
    {
        return ['ID_DETAIL', 'KODE', 'NAMA_BARANG', 'STOK_SISTEM', 'STOK_FISIK', 'KETERANGAN'];
    }

    public function map($detail): array
    {
        return [
            $detail->id,
            $detail->item->code,
            $detail->item->name,
            $detail->system_qty,
            $detail->physical_qty, // Kosongkan atau isi 0
            $detail->description,
        ];
    }
}
