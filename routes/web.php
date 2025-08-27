<?php

use App\Http\Controllers\SppgIntakePrintController;
use Illuminate\Support\Facades\Route;

Route::get('/sppg-intake/{intake}/print', [SppgIntakePrintController::class, 'print'])
    ->name('sppg-intake.print')
    ->middleware('auth');

Route::get('/sppg-intake/{intake}/print-pdf', [SppgIntakePrintController::class, 'printPdf'])
    ->name('sppg-intake.print-pdf')
    ->middleware('auth');

// Route::get('/', function () {
//     return view('welcome');
// });
