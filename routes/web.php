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

Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->name('do.login');
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
Route::middleware(['checkLogin'])->group(function () {
    Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);
    // Home
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // mapping obat
    Route::get('/master_obat', [App\Http\Controllers\MasterObatController::class, 'index'])->name('master_obat');
    Route::post('/master_obat/detail', [App\Http\Controllers\MasterObatController::class, 'show'])->name('master_obat.show');
    Route::post('/master_obat/save-mapping', [App\Http\Controllers\MasterObatController::class, 'saveMapping'])->name('master_obat.saveMapping');

    Route::get('/satusehat/kfa-search', [App\Http\Controllers\SatusehatKfaController::class, 'search'])->name('kfa.search');

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

    Route::group(['prefix' => 'satu-sehat', 'as' => 'satusehat.'], function () {
        // Encounter
        Route::get('/encounter', [App\Http\Controllers\SatuSehat\EncounterController::class, 'index'])->name('encounter.index');
        Route::post('/encounter/datatable', [App\Http\Controllers\SatuSehat\EncounterController::class, 'datatable'])->name('encounter.datatable');
        Route::get('/encounter/send/{param}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'sendSatuSehat'])->name('encounter.send');
        Route::get('/encounter/lihat-erm/{param}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'lihatERM'])->name('encounter.lihat-erm');
        // Route::post('/encounter/store', [App\Http\Controllers\SatuSehat\EncounterController::class, 'store'])->name('encounter.store');
        // Route::get('/encounter/{id}/edit', [App\Http\Controllers\SatuSehat\EncounterController::class, 'edit'])->name('encounter.edit');
        // Route::put('/encounter/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'update'])->name('encounter.update');
        // Route::delete('/encounter/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'destroy'])->name('encounter.destroy');

        // Diagnosa
        Route::get('/diagnosa', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'index'])->name('diagnosa.index');
        Route::post('/diagnosa/datatable', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'datatable'])->name('diagnosa.datatable');
        Route::get('/diagnosa/create', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'create'])->name('diagnosa.create');
        Route::post('/diagnosa/store', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'store'])->name('diagnosa.store');
        Route::get('/diagnosa/{id}/edit', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'edit'])->name('diagnosa.edit');
        Route::put('/diagnosa/{id}', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'update'])->name('diagnosa.update');
        Route::delete('/diagnosa/{id}', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'destroy'])->name('diagnosa.destroy');

        // Observasi
        Route::get('/observation', [App\Http\Controllers\SatuSehat\EncounterController::class, 'index'])->name('observasi.index');
        Route::post('/observation/datatable', [App\Http\Controllers\SatuSehat\EncounterController::class, 'datatable'])->name('observasi.datatable');
        Route::get('/observation/create', [App\Http\Controllers\SatuSehat\EncounterController::class, 'create'])->name('observasi.create');
        Route::post('/observation/store', [App\Http\Controllers\SatuSehat\EncounterController::class, 'store'])->name('observasi.store');
        Route::get('/observation/{id}/edit', [App\Http\Controllers\SatuSehat\EncounterController::class, 'edit'])->name('observasi.edit');
        Route::put('/observation/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'update'])->name('observasi.update');
        Route::delete('/observation/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'destroy'])->name('observasi.destroy');

        // Tindakan
        Route::get('/procedure', [App\Http\Controllers\SatuSehat\EncounterController::class, 'index'])->name('procedure.index');
        Route::post('/procedure/datatable', [App\Http\Controllers\SatuSehat\EncounterController::class, 'datatable'])->name('procedure.datatable');
        Route::get('/procedure/create', [App\Http\Controllers\SatuSehat\EncounterController::class, 'create'])->name('procedure.create');
        Route::post('/procedure/store', [App\Http\Controllers\SatuSehat\EncounterController::class, 'store'])->name('procedure.store');
        Route::get('/procedure/{id}/edit', [App\Http\Controllers\SatuSehat\EncounterController::class, 'edit'])->name('procedure.edit');
        Route::put('/procedure/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'update'])->name('procedure.update');
        Route::delete('/procedure/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'destroy'])->name('procedure.destroy');

        // Allergy Intolerance
        Route::get('/allergy-intolerance', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'index'])->name('allergy-intolerance.index');
        Route::post('/allergy-intolerance/datatable', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'datatable'])->name('allergy-intolerance.datatable');
        Route::get('/allergy-intolerance/create', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'create'])->name('allergy-intolerance.create');
        Route::post('/allergy-intolerance/store', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'store'])->name('allergy-intolerance.store');
        Route::get('/allergy-intolerance/{id}/edit', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'edit'])->name('allergy-intolerance.edit');
        Route::put('/allergy-intolerance/{id}', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'update'])->name('allergy-intolerance.update');
        Route::delete('/allergy-intolerance/{id}', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'destroy'])->name('allergy-intolerance.destroy');

        // Service Request
        Route::get('/service-request', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'index'])->name('service-request.index');
        Route::post('/service-request/datatable', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'datatable'])->name('service-request.datatable');
        Route::get('/service-request/create', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'create'])->name('service-request.create');
        Route::post('/service-request/store', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'store'])->name('service-request.store');
        Route::get('/service-request/{id}/edit', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'edit'])->name('service-request.edit');
        Route::put('/service-request/{id}', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'update'])->name('service-request.update');
        Route::delete('/service-request/{id}', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'destroy'])->name('service-request.destroy');

        // Imaging Study
        Route::get('/imaging-study', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'index'])->name('imaging-study.index');
        Route::post('/imaging-study/datatable', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'datatable'])->name('imaging-study.datatable');
        Route::get('/imaging-study/create', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'create'])->name('imaging-study.create');
        Route::post('/imaging-study/store', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'store'])->name('imaging-study.store');
        Route::get('/imaging-study/{id}/edit', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'edit'])->name('imaging-study.edit');
        Route::put('/imaging-study/{id}', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'update'])->name('imaging-study.update');
        Route::delete('/imaging-study/{id}', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'destroy'])->name('imaging-study.destroy');

        // Specimen
        Route::get('/specimen', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'index'])->name('specimen.index');
        Route::post('/specimen/datatable', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'datatable'])->name('specimen.datatable');
        Route::get('/specimen/create', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'create'])->name('specimen.create');
        Route::post('/specimen/store', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'store'])->name('specimen.store');
        Route::get('/specimen/{id}/edit', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'edit'])->name('specimen.edit');
        Route::put('/specimen/{id}', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'update'])->name('specimen.update');
        Route::delete('/specimen/{id}', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'destroy'])->name('specimen.destroy');
        Route::delete('/imaging-study/{id}', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'destroy'])->name('imaging-study.destroy');

        // Medication Request
        Route::get('/medication-request', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'index'])->name('medication-request.index');
        Route::post('/medication-request/datatable', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'datatable'])->name('medication-request.datatable');
        Route::get('/medication-request/create', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'create'])->name('medication-request.create');
        Route::post('/medication-request/store', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'store'])->name('medication-request.store');
        Route::get('/medication-request/{id}/edit', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'edit'])->name('medication-request.edit');
        Route::put('/medication-request/{id}', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'update'])->name('medication-request.update');
        Route::delete('/medication-request/{id}', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'destroy'])->name('medication-request.destroy');

        // Medication Dispense
        Route::get('/medication-dispense', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'index'])->name('medication-dispense.index');
        Route::post('/medication-dispense/datatable', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'datatable'])->name('medication-dispense.datatable');
        Route::get('/medication-dispense/create', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'create'])->name('medication-dispense.create');
        Route::post('/medication-dispense/store', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'store'])->name('medication-dispense.store');
        Route::get('/medication-dispense/{id}/edit', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'edit'])->name('medication-dispense.edit');
        Route::put('/medication-dispense/{id}', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'update'])->name('medication-dispense.update');
        Route::delete('/medication-dispense/{id}', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'destroy'])->name('medication-dispense.destroy');

        // Medication
        Route::get('/medication', [App\Http\Controllers\SatuSehat\MedicationController::class, 'index'])->name('medication.index');
        Route::post('/medication/datatable', [App\Http\Controllers\SatuSehat\MedicationController::class, 'datatable'])->name('medication.datatable');
        Route::get('/medication/create', [App\Http\Controllers\SatuSehat\MedicationController::class, 'create'])->name('medication.create');
        Route::post('/medication/store', [App\Http\Controllers\SatuSehat\MedicationController::class, 'store'])->name('medication.store');
        Route::get('/medication/{id}/edit', [App\Http\Controllers\SatuSehat\MedicationController::class, 'edit'])->name('medication.edit');
        Route::put('/medication/{id}', [App\Http\Controllers\SatuSehat\MedicationController::class, 'update'])->name('medication.update');
        Route::delete('/medication/{id}', [App\Http\Controllers\SatuSehat\MedicationController::class, 'destroy'])->name('medication.destroy');
    });
});
