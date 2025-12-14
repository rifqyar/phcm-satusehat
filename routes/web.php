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
Route::post('/login-direct', [App\Http\Controllers\Auth\LoginController::class, 'loginDirect'])->name('do.loginDirect');
Route::get('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// kirim satusehat tanpa middleware
Route::get('/medication-request/prepsatusehatnm', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'prepMedicationRequest'])->name('medication-request.prepsatusehatnm');
Route::get('/medication-dispense/sendsatusehatnm', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'prepMedicationDispense'])->name('medication-dispense.sendsehatnm');

Route::middleware(['checkLogin'])->group(function () {
    Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);
    // Home
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // mapping obat
    Route::get('/master_obat', [App\Http\Controllers\MasterObatController::class, 'index'])->name('master_obat');
    Route::post('/master_obat/datatable', [App\Http\Controllers\MasterObatController::class, 'getData'])->name('master_obat.datatable');
    Route::post('/master_obat/detail', [App\Http\Controllers\MasterObatController::class, 'show'])->name('master_obat.show');
    Route::post('/master_obat/save-mapping', [App\Http\Controllers\MasterObatController::class, 'saveMapping'])->name('master_obat.saveMapping');

    Route::get('/satusehat/kfa-search', [App\Http\Controllers\SatusehatKfaController::class, 'search'])->name('kfa.search');
    Route::post('/satusehat/getmedicationid', [App\Http\Controllers\SatusehatKfaController::class, 'setMedication'])->name('kfa.getmedicationid');

    // Dashboard Obat <=> Satu Sehat


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
        Route::post('/encounter/bulk-send/', [App\Http\Controllers\SatuSehat\EncounterController::class, 'bulkSend'])->name('encounter.bulk-send');
        Route::get('/encounter/resend/{param}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'resendSatuSehat'])->name('encounter.resend');
        Route::get('/encounter/lihat-erm/{param}', [App\Http\Controllers\SatuSehat\EncounterController::class, 'lihatERM'])->name('encounter.lihat-erm');

        // Diagnosa
        Route::get('/diagnosa', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'index'])->name('diagnosa.index');
        Route::post('/diagnosa/datatable', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'datatable'])->name('diagnosa.datatable');
        Route::get('/diagnosa/create', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'create'])->name('diagnosa.create');
        Route::post('/diagnosa/store', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'store'])->name('diagnosa.store');
        Route::get('/diagnosa/{id}/edit', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'edit'])->name('diagnosa.edit');
        Route::put('/diagnosa/{id}', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'update'])->name('diagnosa.update');
        Route::delete('/diagnosa/{id}', [App\Http\Controllers\SatuSehat\DiagnosaController::class, 'destroy'])->name('diagnosa.destroy');

        // Observasi
        Route::get('/observation', [App\Http\Controllers\SatuSehat\ObservasiController::class, 'index'])->name('observasi.index');
        Route::post('/observation/datatable', [App\Http\Controllers\SatuSehat\ObservasiController::class, 'datatable'])->name('observasi.datatable');
        Route::get('/observation/lihat-detail/{param}', [App\Http\Controllers\SatuSehat\ObservasiController::class, 'lihatDetail'])->name('observasi.lihat-detail');
        Route::get('/observation/send/{param}', [App\Http\Controllers\SatuSehat\ObservasiController::class, 'sendSatuSehat'])->name('observasi.send');
        Route::post('/observation/bulk-send', [App\Http\Controllers\SatuSehat\ObservasiController::class, 'bulkSend'])->name('observasi.bulk-send');
        Route::get('/observation/resend/{param}', [App\Http\Controllers\SatuSehat\ObservasiController::class, 'resendSatuSehat'])->name('observasi.resend');


        // Tindakan
        Route::get('/procedure', [App\Http\Controllers\SatuSehat\ProcedureController::class, 'index'])->name('procedure.index');
        Route::post('/procedure/datatable', [App\Http\Controllers\SatuSehat\ProcedureController::class, 'datatable'])->name('procedure.datatable');
        Route::get('/procedure/lihat-detail/{param}', [App\Http\Controllers\SatuSehat\ProcedureController::class, 'lihatDetail'])->name('procedure.lihat-detail');
        Route::get('/get-icd9', [App\Http\Controllers\SatuSehat\ProcedureController::class, 'getICD9'])->name('procedure.geticd9');
        Route::post('/procedure/send', [App\Http\Controllers\SatuSehat\ProcedureController::class, 'sendSatuSehat'])->name('procedure.send');
        Route::post('/procedure/resend', [App\Http\Controllers\SatuSehat\ProcedureController::class, 'resendSatuSehat'])->name('procedure.resend');
        Route::post('/procedure/save-icd', [App\Http\Controllers\SatuSehat\ProcedureController::class, 'store'])->name('procedure.saveICD9');

        // Allergy Intolerance
        Route::get('/allergy-intolerance', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'index'])->name('allergy-intolerance.index');
        Route::post('/allergy-intolerance/datatable', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'datatable'])->name('allergy-intolerance.datatable');
        Route::get('/allergy-intolerance/send/{param}', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'sendSatuSehat'])->name('allergy-intolerance.send');
        Route::get('/allergy-intolerance/resend/{param}', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'resendSatuSehat'])->name('allergy-intolerance.resend');
        Route::get('/allergy-intolerance/lihat-alergi/{param}', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'lihatAlergi'])->name('allergy-intolerance.lihat-alergi');
        Route::post('/allergy-intolerance/bulk-send', [App\Http\Controllers\SatuSehat\AllergyIntoleranceController::class, 'bulkSend'])->name('allergy-intolerance.bulk-send');

        // Service Request
        Route::get('/service-request', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'index'])->name('service-request.index');
        Route::post('/service-request/datatable', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'datatable'])->name('service-request.datatable');
        Route::post('/service-request/summary', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'summary'])->name('service-request.summary');
        Route::get('/service-request/send/{param}', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'sendSatuSehat'])->name('service-request.send');
        Route::post('/service-request/bulk-send', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'bulkSendSatuSehat'])->name('service-request.bulk-send');
        // Route::get('/service-request/create', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'create'])->name('service-request.create');
        // Route::post('/service-request/store', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'store'])->name('service-request.store');
        // Route::get('/service-request/{id}/edit', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'edit'])->name('service-request.edit');
        // Route::put('/service-request/{id}', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'update'])->name('service-request.update');
        // Route::delete('/service-request/{id}', [App\Http\Controllers\SatuSehat\ServiceRequestController::class, 'destroy'])->name('service-request.destroy');

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
        Route::post('/specimen/summary', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'summary'])->name('specimen.summary');
        Route::get('/specimen/send/{param}', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'sendSatuSehat'])->name('specimen.send');
        Route::post('/specimen/bulk-send', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'bulkSendSatuSehat'])->name('specimen.bulk-send');
        Route::get('/specimen/create', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'create'])->name('specimen.create');
        Route::post('/specimen/store', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'store'])->name('specimen.store');
        Route::get('/specimen/{id}/edit', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'edit'])->name('specimen.edit');
        Route::put('/specimen/{id}', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'update'])->name('specimen.update');
        Route::delete('/specimen/{id}', [App\Http\Controllers\SatuSehat\SpecimenController::class, 'destroy'])->name('specimen.destroy');
        Route::delete('/imaging-study/{id}', [App\Http\Controllers\SatuSehat\ImagingStudyController::class, 'destroy'])->name('imaging-study.destroy');

        // Medication Request
        Route::get('/medication-request', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'index'])->name('medication-request.index');
        Route::post('/medication-request/datatable', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'datatable'])->name('medication-request.datatable');
        Route::post('/medication-request/detail', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'getDetailObat'])->name('medication-request.detail');
        Route::get('/medication-request/sendsatusehat', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'sendMedicationRequest'])->name('medication-request.sendsehat');
        Route::get('/medication-request/{id}/edit', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'edit'])->name('medication-request.edit');
        Route::put('/medication-request/{id}', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'update'])->name('medication-request.update');
        Route::delete('/medication-request/{id}', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'destroy'])->name('medication-request.destroy');
        Route::get('/medication-request/prepsatusehat', [App\Http\Controllers\SatuSehat\MedicationRequestController::class, 'prepMedicationRequest'])->name('medication-request.prepsatusehat');

        // Medication Dispense Tebuus
        Route::get('/medication-dispense', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'index'])->name('medication-dispense.index');
        Route::post('/medication-dispense/datatable', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'datatable'])->name('medication-dispense.datatable');
        Route::post('/medication-dispense/detail', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'getDetailObat'])->name('medication-dispense.detail');
        Route::get('/medication-dispense/sendsatusehat', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'prepMedicationDispense'])->name('medication-dispense.sendsehat');
        Route::get('/medication-dispense/requestfromdispense', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'createMedicationRequestPayloadfromDispense'])->name('medication-dispense.payloadDispensetoreq');
        Route::post('/medication-dispense/cekDispenseExist', [App\Http\Controllers\SatuSehat\MedicationDispenseController::class, 'cekBelumMedicationRequest'])->name('medication-dispense.cekDispenseExist');

        // Clinical Impression
        Route::get('/clinical-impression', [App\Http\Controllers\SatuSehat\ClinicalImpressionController::class, 'index'])
            ->name('clinical-impression.index');
        Route::post('/clinical-impression/datatable', [App\Http\Controllers\SatuSehat\ClinicalImpressionController::class, 'datatable'])
            ->name('clinical-impression.datatable');
        Route::post('/clinical-impression/lihat-detail/{param}', [App\Http\Controllers\SatuSehat\ClinicalImpressionController::class, 'lihatDetail'])
            ->name('clinical-impression.lihat-detail');
        Route::get('/clinical-impression/send/{param}', [App\Http\Controllers\SatuSehat\ClinicalImpressionController::class, 'send'])
            ->name('clinical-impression.send');
        Route::get('/clinical-impression/resend/{param}', [App\Http\Controllers\SatuSehat\ClinicalImpressionController::class, 'resend'])
            ->name('clinical-impression.resend');

        // Care Plan
        Route::get('/care-plan', [App\Http\Controllers\SatuSehat\CarePlanController::class, 'index'])
            ->name('care-plan.index');
        Route::post('/care-plan/datatable', [App\Http\Controllers\SatuSehat\CarePlanController::class, 'datatable'])
            ->name('care-plan.datatable');
        Route::post('/care-plan/lihat-detail/{param}', [App\Http\Controllers\SatuSehat\CarePlanController::class, 'lihatDetail'])
            ->name('care-plan.lihat-detail');
        Route::get('/care-plan/send/{param}', [App\Http\Controllers\SatuSehat\CarePlanController::class, 'send'])
            ->name('care-plan.send');
        Route::get('/care-plan/resend/{param}', [App\Http\Controllers\SatuSehat\CarePlanController::class, 'resend'])
            ->name('care-plan.resend');

        // Medication
        Route::get('/medication', [App\Http\Controllers\SatuSehat\MedicationController::class, 'index'])->name('medication.index');
        Route::post('/medication/datatable', [App\Http\Controllers\SatuSehat\MedicationController::class, 'datatable'])->name('medication.datatable');
        Route::get('/medication/create', [App\Http\Controllers\SatuSehat\MedicationController::class, 'create'])->name('medication.create');
        Route::post('/medication/store', [App\Http\Controllers\SatuSehat\MedicationController::class, 'store'])->name('medication.store');
        Route::get('/medication/{id}/edit', [App\Http\Controllers\SatuSehat\MedicationController::class, 'edit'])->name('medication.edit');
        Route::put('/medication/{id}', [App\Http\Controllers\SatuSehat\MedicationController::class, 'update'])->name('medication.update');
        Route::delete('/medication/{id}', [App\Http\Controllers\SatuSehat\MedicationController::class, 'destroy'])->name('medication.destroy');

        // Diagnostic Report
        Route::get('/diagnostic-report', [App\Http\Controllers\SatuSehat\DiagnosticReportController::class, 'index'])
            ->name('diagnostic-report.index');
        Route::post('/diagnostic-report/datatable', [App\Http\Controllers\SatuSehat\DiagnosticReportController::class, 'datatable'])
            ->name('diagnostic-report.datatable');
        Route::post('/diagnostic-report/sendsatusehat/{id}', [App\Http\Controllers\SatuSehat\DiagnosticReportController::class, 'sendSatuSehat'])
            ->name('diagnostic-report.send-satu-sehat');
        Route::post('/diagnostic-report/bulk-send', [App\Http\Controllers\SatuSehat\DiagnosticReportController::class, 'bulkSend'])
            ->name('diagnostic-report.bulk-send');

        // Questionnaire Response
        Route::get('/questionnaire-response', [App\Http\Controllers\SatuSehat\QuestionnaireResponseController::class, 'index'])
            ->name('questionnaire-response.index');
        Route::post('/questionnaire-response/datatable', [App\Http\Controllers\SatuSehat\QuestionnaireResponseController::class, 'datatable'])
            ->name('questionnaire-response.datatable');
        Route::get('/questionnaire-response/questions', [App\Http\Controllers\SatuSehat\QuestionnaireResponseController::class, 'getQuestions'])
            ->name('questionnaire-response.questions');
        Route::post('/questionnaire-response/send', [App\Http\Controllers\SatuSehat\QuestionnaireResponseController::class, 'send'])
            ->name('questionnaire-response.send');
        Route::post('/questionnaire-response/resend', [App\Http\Controllers\SatuSehat\QuestionnaireResponseController::class, 'resend'])
            ->name('questionnaire-response.resend');

        // Resume Medis Routes
        Route::get('/resume-medis', [App\Http\Controllers\SatuSehat\ResumeMedisController::class, 'index'])
            ->name('resume-medis.index');
        Route::post('/resume-medis/datatable', [App\Http\Controllers\SatuSehat\ResumeMedisController::class, 'datatable'])
            ->name('resume-medis.datatable');
        Route::post('/resume-medis/lihat-detail/{param}', [App\Http\Controllers\SatuSehat\ResumeMedisController::class, 'lihatDetail'])
            ->name('resume-medis.lihat-detail');
        Route::get('/resume-medis/send/{param}', [App\Http\Controllers\SatuSehat\ResumeMedisController::class, 'send'])
            ->name('resume-medis.send');
        Route::get('/resume-medis/resend/{param}', [App\Http\Controllers\SatuSehat\ResumeMedisController::class, 'resend'])
            ->name('resume-medis.resend');

        // Diagnosis
        Route::get('/diagnosis', [App\Http\Controllers\SatuSehat\DiagnosisController::class, 'index'])->name('diagnosis.index');
        Route::post('/diagnosis/detail', [App\Http\Controllers\SatuSehat\DiagnosisController::class, 'getDetailDiagnosis'])->name('diagnosis.detail');

        // Imunisasi
        Route::get('/imunisasi', [App\Http\Controllers\SatuSehat\ImunisasiController::class, 'index'])->name('imunisasi.index');
        Route::post('/imunisasi/detail', [App\Http\Controllers\SatuSehat\ImunisasiController::class, 'getDetailDiagnosis'])->name('imunisasi.detail');
        Route::post('/imunisasi/datatabel', [App\Http\Controllers\SatuSehat\ImunisasiController::class, 'datatabel'])->name('imunisasi.datatabel');

        // Med Statement
        Route::get('/medstatement', [App\Http\Controllers\SatuSehat\MedStatementController::class, 'index'])->name('medstatement.index');
        Route::post('/medstatement/datatabel', [App\Http\Controllers\SatuSehat\MedStatementController::class, 'datatabel'])->name('medstatement.datatabel');
    });
});
