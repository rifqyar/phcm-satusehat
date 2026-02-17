<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
// use App\Http\Controllers\SatuSehat\MedicationRequestController;
use App\Http\Controllers\SatuSehat\MedStatementController;

class MedStatementService
{
    public function process(array $payload): void
    {
        $karcis = isset($payload['karcis']) && $payload['karcis'] !== '' ? $payload['karcis'] : null;
        $idunit = $payload['idunit'] ?? null;

        if (!$karcis) {
            throw new \Exception('karcis wajib ada');
        }


        app(MedStatementController::class)
            ->processSendMedStatement($karcis,$idunit);
    }
}
