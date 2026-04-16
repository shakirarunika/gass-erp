<?php

namespace App\Imports;

use App\Models\StockOpnameDetail;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockOpnameImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $detail = StockOpnameDetail::find($row['id_detail']);

        if ($detail) {
            $detail->update([
                'physical_qty' => $row['stok_fisik'] ?? 0,
                'description'  => $row['keterangan'] ?? null,
            ]);
        }
        return null;
    }
}
