<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/', function () {
    return response()->json(['message' => 'API is working'], 200);
});

Route::post('dispatch', [App\Http\Controllers\SatuSehat\DispatchController::class, 'dispatchController']);
// Route::get('encounter', [App\Http\Controllers\SatuSehat\EncounterController::class, 'receiveSatuSehat'])->name('encounter');
// Route::get('allergy-intolerance', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'receiveSatuSehat'])->name('allergy-intolerance');
// Route::get('observasi', [App\Http\Controllers\SatuSehat\ObservasiController::class, 'receiveSatuSehat'])->name('observasi');
// Route::get('procedure', [App\Http\Controllers\SatuSehat\ProcedureController::class, 'receiveSatuSehat'])->name('procedure');

// Route::post('service-request', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'receiveSatuSehat'])->name('service-request');
// Route::post('specimen', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'receiveSatuSehat'])->name('specimen');

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
