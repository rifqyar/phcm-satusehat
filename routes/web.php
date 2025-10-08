<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/master_obat', [App\Http\Controllers\MasterObatController::class, 'index'])->name('master_obat');
Route::post('/master_obat/detail', [App\Http\Controllers\MasterObatController::class, 'show'])->name('master_obat.show');


// Radiology
Route::get('/master_radiology', [App\Http\Controllers\MasterRadiologyController::class, 'index'])->name('master_radiology');
Route::post('/master-radiology/save-loinc', [App\Http\Controllers\MasterRadiologyController::class, 'saveLoinc'])->name('master_radiology.save_loinc');

// Laboratory
Route::get('/master_laboratory', [App\Http\Controllers\MasterLaboratoryController::class, 'index'])->name('master_laboratory');
Route::post('/master-laboratory/save-loinc', [App\Http\Controllers\MasterLaboratoryController::class, 'saveLoinc'])->name('master_laboratory.save_loinc');

