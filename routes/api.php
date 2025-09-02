<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SppgIntakeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware(['sppg.auth', 'throttle:sppg'])->group(function () {
    Route::post('sppgs/{code}/intakes', [SppgIntakeController::class, 'store']);
    Route::get('sppgs/{code}/intakes/{po_number}', [SppgIntakeController::class, 'show']);
});

Route::middleware(['sppg.auth'])->group(function () {
    Route::post('v1/sppgs/{code}/receipts', [\App\Http\Controllers\Api\SppgReceiptController::class, 'store']);
});

Route::middleware(['sppg.auth'])->prefix('v1/sppgs/{code}')->group(function () {
    Route::get('receipts/open', [\App\Http\Controllers\Api\SppgReceiptController::class, 'open']);
    Route::post('receipts',      [\App\Http\Controllers\Api\SppgReceiptController::class, 'store']); // sudah kamu buat
});


// Route::post('/login', [AuthController::class, 'login']);
