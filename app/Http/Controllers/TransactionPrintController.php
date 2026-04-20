<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionPrintController extends Controller
{
    /**
     * Handle the incoming request to print a transaction.
     */
    public function __invoke(Transaction $record)
    {
        // Load required relationships to ensure all data is available in the view
        $record->loadMissing([
            'warehouse.plant', 
            'details.item.unit', 
            'department'
        ]);

        return view('transaction.print', ['transaction' => $record]);
    }
}
