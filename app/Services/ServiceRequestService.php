<?php

namespace App\Services;

use App\Http\Controllers\SatuSehat\AllergyIntoleranceController;
use App\Http\Controllers\SatuSehat\ProcedureController;
use App\Http\Traits\LogTraits;
use App\Jobs\SendAllergyIntolerance;
use App\Jobs\SendEncounter;
use App\Jobs\SendServiceRequestJob;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_ALLERGY_INTOLERANCE;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA;
use App\Models\SATUSEHAT\SATUSEHAT_PROCEDURE;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceRequestService
{
    use LogTraits;

    public function process(array $payload): void
    {
        DB::disableQueryLog();
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('servicerequest', 'Receive Service Request dari SIMRS', [
            'request' => $payload,
            'karcis' => $payload['karcis'],
            'user_id' => 'system'
        ]);

        $data = $this->getKunjunganData($payload, $id_unit);
        if (! $data) {
            throw new \Exception('Data Service Request tidak ditemukan');
        }

        if (empty($data)) return;

        $param = $this->buildEncryptedParam($payload, $data);
        SendServiceRequestJob::dispatch($param)->onQueue('ServiceRequest');
    }

    protected function getKunjunganData(array $payload, $id_unit)
    {
        $data['rj'] = DB::selectOne(
            'EXEC dbo.sp_getServiceRequestRJ ?, ?, ?, ?',
            [
                $id_unit,
                $payload['klinik'],
                $payload['karcis'],
                $payload['idElab']
            ]
        );

        $data['ri'] = DB::selectOne(
            'EXEC dbo.sp_getServiceRequestRI ?, ?, ?, ?',
            [
                $id_unit,
                $payload['klinik'],
                $payload['karcis'],
                $payload['idElab']
            ]
        );

        $dataKunjungan = null;
        foreach ($data as $key => $value) {
            if ($data[$key] != null) {
                $dataKunjungan = $value;
                break;
            }
        }

        return $dataKunjungan;
    }

    protected function buildEncryptedParam(array $payload, $data): string
    {
        $idRiwayatElab = LZString::compressToEncodedURIComponent($data->ID_RIWAYAT_ELAB);
        $karcis = LZString::compressToEncodedURIComponent($data->KARCIS_RUJUKAN);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $kdDokterSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $paramSatuSehat = LZString::compressToEncodedURIComponent($idRiwayatElab . '+' . $karcis . '+' . $payload['klinik'] . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' . $kdDokterSS);

        return $paramSatuSehat;
    }
}
