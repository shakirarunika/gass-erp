<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionPrintController;

Route::get('/', function () {
    return redirect('/admin');
});

// Print (HTML) Transaction
Route::get('/admin/transactions/{record}/print', TransactionPrintController::class)
    ->name('transactions.print')
    ->middleware(['auth', 'can:view,record']);
