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
    // Home
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

    Route::group(['prefix' => 'satusehat'], function () {
        // Encounter
        Route::get('/encounter', [App\Http\Controllers\SatuSehat\EncounterController::class, 'index'])->name('satusehat.encounter.index');
        Route::post('/encounter/datatable', [App\Http\Controllers\SatuSehat\EncounterController::class, 'datatable'])->name('satusehat.encounter.datatable');
        Route::get('/encounter/create', [App\Http\Controllers\SatuSehat\EncounterController::class, 'create'])->name('satusehat.encounter.create');
        Route::post('/encounter/store', [App\Http\Controllers\SatuSehat\EncounterController::class, 'store'])->name('satusehat.encounter.store');
        Route::get('/encounter/{id}/edit', [App\Http\Controllers\SatuSehat\EncounterController::class, 'edit'])->name('satusehat.encounter.edit');
        Route::put('/encounter/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'update'])->name('satusehat.encounter.update');
        Route::delete('/encounter/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'destroy'])->name('satusehat.encounter.destroy');

        // Diagnosa
        Route::get('/diagnosa', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'index'])->name('satusehat.diagnosa.index');
        Route::post('/diagnosa/datatable', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'datatable'])->name('satusehat.diagnosa.datatable');
        Route::get('/diagnosa/create', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'create'])->name('satusehat.diagnosa.create');
        Route::post('/diagnosa/store', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'store'])->name('satusehat.diagnosa.store');
        Route::get('/diagnosa/{id}/edit', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'edit'])->name('satusehat.diagnosa.edit');
        Route::put('/diagnosa/{id}', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'update'])->name('satusehat.diagnosa.update');
        Route::delete('/diagnosa/{id}', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'destroy'])->name('satusehat.diagnosa.destroy');

        // Observasi
        Route::get('/observation', [App\Http\Controllers\SatuSehat\EncounterController::class, 'index'])->name('satusehat.observasi.index');
        Route::post('/observation/datatable', [App\Http\Controllers\SatuSehat\EncounterController::class, 'datatable'])->name('satusehat.observasi.datatable');
        Route::get('/observation/create', [App\Http\Controllers\SatuSehat\EncounterController::class, 'create'])->name('satusehat.observasi.create');
        Route::post('/observation/store', [App\Http\Controllers\SatuSehat\EncounterController::class, 'store'])->name('satusehat.observasi.store');
        Route::get('/observation/{id}/edit', [App\Http\Controllers\SatuSehat\EncounterController::class, 'edit'])->name('satusehat.observasi.edit');
        Route::put('/observation/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'update'])->name('satusehat.observasi.update');
        Route::delete('/observation/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'destroy'])->name('satusehat.observasi.destroy');

        // Tindakan
        Route::get('/procedure', [App\Http\Controllers\SatuSehat\EncounterController::class, 'index'])->name('satusehat.procedure.index');
        Route::post('/procedure/datatable', [App\Http\Controllers\SatuSehat\EncounterController::class, 'datatable'])->name('satusehat.procedure.datatable');
        Route::get('/procedure/create', [App\Http\Controllers\SatuSehat\EncounterController::class, 'create'])->name('satusehat.procedure.create');
        Route::post('/procedure/store', [App\Http\Controllers\SatuSehat\EncounterController::class, 'store'])->name('satusehat.procedure.store');
        Route::get('/procedure/{id}/edit', [App\Http\Controllers\SatuSehat\EncounterController::class, 'edit'])->name('satusehat.procedure.edit');
        Route::put('/procedure/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'update'])->name('satusehat.procedure.update');
        Route::delete('/procedure/{id}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'destroy'])->name('satusehat.procedure.destroy');

        // Allergy Intolerance
        Route::get('/allergy-intolerance', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'index'])->name('satusehat.allergy-intolerance.index');
        Route::post('/allergy-intolerance/datatable', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'datatable'])->name('satusehat.allergy-intolerance.datatable');
        Route::get('/allergy-intolerance/create', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'create'])->name('satusehat.allergy-intolerance.create');
        Route::post('/allergy-intolerance/store', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'store'])->name('satusehat.allergy-intolerance.store');
        Route::get('/allergy-intolerance/{id}/edit', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'edit'])->name('satusehat.allergy-intolerance.edit');
        Route::put('/allergy-intolerance/{id}', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'update'])->name('satusehat.allergy-intolerance.update');
        Route::delete('/allergy-intolerance/{id}', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'destroy'])->name('satusehat.allergy-intolerance.destroy');

        // Service Request
        Route::get('/service-request', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'index'])->name('satusehat.service-request.index');
        Route::post('/service-request/datatable', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'datatable'])->name('satusehat.service-request.datatable');
        Route::get('/service-request/create', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'create'])->name('satusehat.service-request.create');
        Route::post('/service-request/store', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'store'])->name('satusehat.service-request.store');
        Route::get('/service-request/{id}/edit', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'edit'])->name('satusehat.service-request.edit');
        Route::put('/service-request/{id}', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'update'])->name('satusehat.service-request.update');
        Route::delete('/service-request/{id}', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'destroy'])->name('satusehat.service-request.destroy');

        // Imaging Study
        Route::get('/imaging-study', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'index'])->name('satusehat.imaging-study.index');
        Route::post('/imaging-study/datatable', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'datatable'])->name('satusehat.imaging-study.datatable');
        Route::get('/imaging-study/create', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'create'])->name('satusehat.imaging-study.create');
        Route::post('/imaging-study/store', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'store'])->name('satusehat.imaging-study.store');
        Route::get('/imaging-study/{id}/edit', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'edit'])->name('satusehat.imaging-study.edit');
        Route::put('/imaging-study/{id}', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'update'])->name('satusehat.imaging-study.update');
        Route::delete('/imaging-study/{id}', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'destroy'])->name('satusehat.imaging-study.destroy');

        // Specimen
        Route::get('/specimen', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'index'])->name('satusehat.specimen.index');
        Route::post('/specimen/datatable', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'datatable'])->name('satusehat.specimen.datatable');
        Route::get('/specimen/create', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'create'])->name('satusehat.specimen.create');
        Route::post('/specimen/store', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'store'])->name('satusehat.specimen.store');
        Route::get('/specimen/{id}/edit', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'edit'])->name('satusehat.specimen.edit');
        Route::put('/specimen/{id}', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'update'])->name('satusehat.specimen.update');
        Route::delete('/specimen/{id}', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'destroy'])->name('satusehat.specimen.destroy');
        Route::delete('/imaging-study/{id}', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'destroy'])->name('satusehat.imaging-study.destroy');

        // Medication Request
        Route::get('/medication-request', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'index'])->name('satusehat.medication-request.index');
        Route::post('/medication-request/datatable', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'datatable'])->name('satusehat.medication-request.datatable');
        Route::get('/medication-request/create', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'create'])->name('satusehat.medication-request.create');
        Route::post('/medication-request/store', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'store'])->name('satusehat.medication-request.store');
        Route::get('/medication-request/{id}/edit', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'edit'])->name('satusehat.medication-request.edit');
        Route::put('/medication-request/{id}', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'update'])->name('satusehat.medication-request.update');
        Route::delete('/medication-request/{id}', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'destroy'])->name('satusehat.medication-request.destroy');

        // Medication Dispense
        Route::get('/medication-dispense', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'index'])->name('satusehat.medication-dispense.index');
        Route::post('/medication-dispense/datatable', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'datatable'])->name('satusehat.medication-dispense.datatable');
        Route::get('/medication-dispense/create', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'create'])->name('satusehat.medication-dispense.create');
        Route::post('/medication-dispense/store', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'store'])->name('satusehat.medication-dispense.store');
        Route::get('/medication-dispense/{id}/edit', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'edit'])->name('satusehat.medication-dispense.edit');
        Route::put('/medication-dispense/{id}', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'update'])->name('satusehat.medication-dispense.update');
        Route::delete('/medication-dispense/{id}', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'destroy'])->name('satusehat.medication-dispense.destroy');

        // Medication
        Route::get('/medication', [App\Http\Controllers\SatuSehat\MedicationController::class, 'index'])->name('satusehat.medication.index');
        Route::post('/medication/datatable', [App\Http\Controllers\SatuSehat\MedicationController::class, 'datatable'])->name('satusehat.medication.datatable');
        Route::get('/medication/create', [App\Http\Controllers\SatuSehat\MedicationController::class, 'create'])->name('satusehat.medication.create');
        Route::post('/medication/store', [App\Http\Controllers\SatuSehat\MedicationController::class, 'store'])->name('satusehat.medication.store');
        Route::get('/medication/{id}/edit', [App\Http\Controllers\SatuSehat\MedicationController::class, 'edit'])->name('satusehat.medication.edit');
        Route::put('/medication/{id}', [App\Http\Controllers\SatuSehat\MedicationController::class, 'update'])->name('satusehat.medication.update');
        Route::delete('/medication/{id}', [App\Http\Controllers\SatuSehat\MedicationController::class, 'destroy'])->name('satusehat.medication.destroy');
    });
});
