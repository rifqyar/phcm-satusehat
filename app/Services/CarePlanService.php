<?php

namespace App\Services;

use App\Http\Traits\LogTraits;
use App\Jobs\SendCarePlan;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_CARE_PLAN;
use Exception;
use Illuminate\Support\Facades\DB;

class CarePlanService
{
    use LogTraits;

    public function process(array $payload): void
    {
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('CarePlan', 'Process CarePlan dari SIMRS', [
            'payload' => $payload,
            'user_id' => 'system',
        ]);

        try {
            $CarePlanId = SATUSEHAT_CARE_PLAN::where(
                'KARCIS',
                (int) $payload['karcis']
            )
                ->where('ID_UNIT', $id_unit)
                ->first();

            $data = $this->getKunjunganData($payload, $id_unit);
            if (! $data) {
                throw new Exception('Data kunjungan tidak ditemukan');
            }

            if (
                $data->ID_SATUSEHAT_ENCOUNTER == null
            ) {
                return;
            }

            $param = $this->buildEncryptedParam($payload, $data);
            SendCarePlan::dispatch($param, (bool) $CarePlanId)->onQueue('CarePlan');
        } catch (Exception $th) {
            $this->logError('CarePlan', 'Gagal Process Care Plan dari SIMRS', [
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
            EXEC dbo.sp_getCarePlan ?, ?, ?, ?, ?
        ", [
            $id_unit,
            null,
            null,
            'all',
            $payload['karcis'] ?? '',
            null,
        ]);

        if (! $data) {
            throw new \Exception('Data Kunjungan tidak ditemukan');
        }

        if ($data->ID_SATUSEHAT_ENCOUNTER == null || $data->ID_SATUSEHAT_ENCOUNTER == '') return;

        return $data;
    }

    protected function buildEncryptedParam(array $payload, $data): string
    {
        $id_transaksi = LZString::compressToEncodedURIComponent($data->ID_TRANSAKSI);
        $KbBuku = LZString::compressToEncodedURIComponent($data->KBUKU);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $idEncounter = LZString::compressToEncodedURIComponent($data->ID_SATUSEHAT_ENCOUNTER);
        $id_unit = LZString::compressToEncodedURIComponent($payload['id_unit']);
        $paramSatuSehat = "sudah_integrasi=$data->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter&id_unit=$id_unit&jenis_perawatan=" . LZString::compressToEncodedURIComponent($data->JENIS_PERAWATAN);
        $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

        return $paramSatuSehat;
    }
}
