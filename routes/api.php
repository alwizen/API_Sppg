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
    Route::get('sppgs/{code}/intakes/{po_number}', [SppgIntakeController::class, 'show']); // opsional
});


// Route::post('/login', [AuthController::class, 'login']);
