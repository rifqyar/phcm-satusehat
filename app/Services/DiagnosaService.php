<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
// use App\Http\Controllers\SatuSehat\MedicationRequestController;
use App\Http\Controllers\SatuSehat\DiagnosisController;

class DiagnosaService
{
    public function process(array $payload): void
    {
        $karcis = isset($payload['karcis']) && $payload['karcis'] !== '' ? $payload['karcis']: null;

        $idunit = $payload['id_unit'] ?? null;
        $user = 'system';

        if (!$karcis) {
            throw new \Exception('karcis wajib ada');
        }


        app(DiagnosisController::class)
            ->processSendDiagnosis($karcis,$idunit, $user);
    }
}
