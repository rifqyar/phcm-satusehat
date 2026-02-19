<?php

namespace App\Services;

use App\Http\Controllers\SatuSehat\ClinicalImpressionController;
use App\Http\Traits\LogTraits;
use App\Jobs\SendClinicalImpression;
use App\Jobs\SendEncounter;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_CLINICALIMPRESSION;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClinicalImpressionService
{
    use LogTraits;

    public function process(array $payload): void
    {
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('ClinicalImpression', 'Process Clinical Impression dari SIMRS', [
            'payload' => $payload,
            'user_id' => 'system',
        ]);

        try {
            $data = $this->getKunjunganData($payload, $id_unit);
            if (! $data) {
                throw new Exception('Data kunjungan tidak ditemukan');
            }

            if (
                $data->id_satusehat_encounter == null
            ) {
                return;
            }

            $clinicalImpressionId = SATUSEHAT_CLINICALIMPRESSION::where(
                'KARCIS',
                (int) $payload['karcis'],
            )
                ->where('ID_UNIT', $id_unit)
                ->where('ID_ERM', $data->ID_ERM)
                ->first();
            $param = $this->buildEncryptedParam($payload, $data);
            SendClinicalImpression::dispatch($param, (bool) $clinicalImpressionId)->onQueue('ClinicalImpression');
        } catch (Exception $th) {
            $this->logError('ClinicalImpression', 'Gagal Process Clinical Impression dari SIMRS', [
                'payload' => $payload,
                'user_id' => 'system',
                'error' => $th->getMessage(),
                'trace' => $th->getTrace(),
            ]);
        }
    }

    protected function getKunjunganData(array $payload, $id_unit)
    {
        $data = DB::selectOne("
            EXEC dbo.sp_getClinicalImpression ?, ?, ?, ?, ?, ?, ?, ?
        ", [
            $id_unit,
            null,
            null,
            'all',
            $payload['karcis'] ?? '',
            $payload['id_erm'] ?? null,
            1,
            1
        ]);

        if (! $data) {
            throw new \Exception('Data Kunjungan tidak ditemukan');
        }

        if ($data->id_satusehat_encounter == null || $data->id_satusehat_encounter == '') return;

        return $data;
    }

    protected function buildEncryptedParam(array $payload, $data): string
    {
        $id_transaksi = LZString::compressToEncodedURIComponent($payload['karcis']);
        $KbBuku = LZString::compressToEncodedURIComponent($data->KBUKU);
        $id_erm = LZString::compressToEncodedURIComponent($data->ID_ERM);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $idEncounter = LZString::compressToEncodedURIComponent($data->id_satusehat_encounter);
        $id_unit = LZString::compressToEncodedURIComponent($payload['id_unit']);
        $paramSatuSehat = "sudah_integrasi=$data->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter&id_erm=$id_erm&id_unit=$id_unit&jenis_perawatan=" . LZString::compressToEncodedURIComponent($data->JENIS_PERAWATAN);
        $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

        return $paramSatuSehat;
    }
}
