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

Route::post('encounter', [App\Http\Controllers\SatuSehat\EncounterController::class, 'receiveSatuSehat']);
Route::post('allergy-intolerance', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'receiveSatuSehat']);
Route::get('procedure', [App\Http\Controllers\SatuSehat\ProcedureController::class, 'receiveSatuSehat']);
Route::post('service-request', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'receiveSatuSehat']);
Route::post('specimen', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'receiveSatuSehat']);
Route::post('observasi', [App\Http\Controllers\SatuSehat\ObservasiController::class, 'receiveSatuSehat']);

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
