<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosisController extends Controller
{
    public function index()
    {
        return response()->view('pages.satusehat.diagnosis.index');
    }

    public function getDetailDiagnosis(Request $request)
    {
        // Optional: ambil ID dari request (kalau frontend kirim)
        $idTrans = $request->id;

        // Static JSON Diagnosis
        $mockDiagnosis = [
            'diagnosis_id' => 'DX-001',
            'patient_id' => 'PAT-12345',
            'encounter_id' => 'ENC-20251210-01',
            'code' => [
                'icd10' => 'J45.9',
                'description' => 'Asthma, unspecified',
            ],
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'severity' => 'moderate',
            'onset_date' => '2025-12-10',
            'recorded_date' => '2025-12-10T09:30:00+07:00',
            'note' => 'Pasien mengeluhkan sesak napas dan wheezing sejak 2 hari.',
        ];

        return response()->json([
            'status' => 'success',
            'data' => $mockDiagnosis,
        ]);
    }
}
