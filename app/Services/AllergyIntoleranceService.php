<?php

namespace App\Services;

use App\Http\Controllers\SatuSehat\AllergyIntoleranceController;
use App\Http\Controllers\SatuSehat\ProcedureController;
use App\Http\Traits\LogTraits;
use App\Jobs\SendAllergyIntolerance;
use App\Jobs\SendEncounter;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_ALLERGY_INTOLERANCE;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA;
use App\Models\SATUSEHAT\SATUSEHAT_PROCEDURE;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AllergyIntoleranceService
{
    use LogTraits;

    public function process(array $payload): void
    {
        DB::disableQueryLog();
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('AllergyIntolerance', 'Receive Allergy Intolerance dari SIMRS', [
            'request' => $payload,
            'karcis' => $payload['karcis'],
            'user_id' => 'system'
        ]);

        $data = $this->getKunjunganData($payload, $id_unit);
        if (! $data) {
            throw new \Exception('Data Allergy Intolerance tidak ditemukan');
        }

        if (empty($data) && ($data->id_satusehat_encounter == '' || $data->id_satusehat_encounter == null)) return;

        $param = $this->buildEncryptedParam($payload, $data);

        $encounterId = SATUSEHAT_ALLERGY_INTOLERANCE::where('karcis', (int)$payload['karcis'])
            ->where('IDUNIT', $id_unit)
            ->select('karcis')
            ->first();

        $resp = SendAllergyIntolerance::dispatch($param, (bool) $encounterId)->onQueue('AllergyIntolerance');
    }

    protected function getKunjunganData(array $payload, $id_unit)
    {
        $data = DB::selectOne(
            'EXEC dbo.sp_getAllergyIntolerance @KARCIS = ?',
            [$payload['karcis']]
        );

        if (! $data) {
            throw new \Exception('Tidak ada data Allergy Intolerance untuk karcis ' . $payload['karcis']);
        }

        return $data;
    }

    protected function buildEncryptedParam(array $payload, $data): string
    {
        $id_transaksi = LZString::compressToEncodedURIComponent($payload['karcis']);
        $KbBuku = LZString::compressToEncodedURIComponent($data->KBUKU);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $idEncounter = LZString::compressToEncodedURIComponent($data->id_satusehat_encounter);
        $paramSatuSehat = "sudah_integrasi=$data->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter";
        $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

        return $paramSatuSehat;
    }
}
