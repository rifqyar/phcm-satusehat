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

// mapping obat
Route::get('/master_obat', [App\Http\Controllers\MasterObatController::class, 'index'])->name('master_obat');
Route::post('/master_obat/detail', [App\Http\Controllers\MasterObatController::class, 'show'])->name('master_obat.show');
Route::post('/master_obat/save-mapping', [App\Http\Controllers\MasterObatController::class, 'saveMapping'])->name('master_obat.saveMapping');

Route::get('/satusehat/kfa-search', [App\Http\Controllers\SatusehatKfaController::class, 'search'])->name('satusehat.kfa.search');

// Radiology
Route::get('/master_radiology', [App\Http\Controllers\MasterRadiologyController::class, 'index'])->name('master_radiology');
Route::post('/master_radiology/detail', [App\Http\Controllers\MasterRadiologyController::class, 'show'])->name('master_radiology.show');
Route::post('/master-radiology/save-loinc', [App\Http\Controllers\MasterRadiologyController::class, 'saveLoinc'])->name('master_radiology.save_loinc');
Route::get('/master-radiology/loinc-search', [App\Http\Controllers\MasterRadiologyController::class, 'searchLoinc'])->name('master_radiology.search_loinc');

// Laboratory
Route::get('/master_laboratory', [App\Http\Controllers\MasterLaboratoryController::class, 'index'])->name('master_laboratory');
Route::post('/master-laboratory/save-loinc', [App\Http\Controllers\MasterLaboratoryController::class, 'saveLoinc'])->name('master_laboratory.save_loinc');
Route::post('/master_laboratory/detail', [App\Http\Controllers\MasterLaboratoryController::class, 'show'])->name('master_laboratory.show');

// Specimen
Route::resource('master_specimen', MasterSpecimenController::class);
Route::post('master_specimen/datatable', [App\Http\Controllers\MasterSpecimenController::class, 'datatable'])->name('master_specimen.datatable');
