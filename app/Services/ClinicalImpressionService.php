<?php

namespace App\Services;

use App\Http\Traits\LogTraits;
use App\Jobs\SendEncounter;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_CLINICALIMPRESSION;
use Illuminate\Support\Facades\DB;

class ClinicalImpressionService
{
    use LogTraits;

    public function process(array $payload): void
    {
        DB::disableQueryLog();
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('clinical_impression', 'Process Clinical Impression dari SIMRS', [
            'payload' => $payload,
            'user_id' => 'system',
        ]);

        $clinicalImpressionId = SATUSEHAT_CLINICALIMPRESSION::where(
            'karcis',
            (int) $payload['karcis']
        )
            ->where('idunit', $id_unit)
            ->first();

        $data = $this->getKunjunganData($payload, $id_unit);
        if (! $data) {
            throw new \Exception('Data kunjungan tidak ditemukan');
        }

        if (
            empty($data->ID_LOKASI_SS) ||
            empty($data->ID_NAKES_SS) ||
            empty($data->ID_PASIEN_SS)
        ) {
            return;
        }

        $param = $this->buildEncryptedParam($payload, $data);
        dd($param);
        // SendEncounter::dispatch(
        //     $param,
        //     (bool) $clinicalImpressionId
        // )->onQueue('clinical_impression');
    }

    protected function getKunjunganData(array $payload, $id_unit)
    {
        $data = collect(DB::select("
            EXEC dbo.sp_getClinicalImpression ?, ?, ?, ?, ?
        ", [
            $id_unit,
            null,
            null,
            'all',
            $payload['karcis'] ?? '',
        ]))->first();

        if (! $data) {
            throw new \Exception('Data Kunjungan tidak ditemukan');
        }

        if($data->id_satusehat_encounter == null || $data->id_satusehat_encounter == '') return;

        return $data;
    }

    protected function buildEncryptedParam(array $payload, $data): string
    {
        $id_transaksi = LZString::compressToEncodedURIComponent($payload['karcis']);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $kdLokasiSS = LZString::compressToEncodedURIComponent($data->ID_LOKASI_SS);
        $paramSatuSehat = "jenis_perawatan=" . $data->jenisPerawatan . "&id_transaksi=" . $id_transaksi . "&kd_pasien_ss=" . $kdPasienSS . "&kd_nakes_ss=" . $kdNakesSS . "&kd_lokasi_ss=" .  $kdLokasiSS;
        $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

        return $paramSatuSehat;
    }
}
