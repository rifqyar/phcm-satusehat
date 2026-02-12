<?php

namespace App\Services;

use App\Http\Traits\LogTraits;
use App\Jobs\SendDiagnosticReport;
use App\Jobs\SendResumeMedis;
use App\Lib\LZCompressor\LZString;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosticReportService
{
    use LogTraits;

    public function process(array $payload): void
    {
        DB::disableQueryLog();
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('DiagnosticReport', 'Process Diagnostic Report dari SIMRS', [
            'payload' => $payload,
            'iddokumen' => $payload['iddokumen'],
            'karcis' => $payload['karcis'],
            'karcis_rujukan' => $payload['karcis_rujukan'],
            'user_id' => 'system',
        ]);

        try {
            $compositionId = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_DIAGNOSTICREPORT')
                ->where('iddokumen', $payload['iddokumen'])
                ->where('KARCIS', $payload['karcis'])
                ->where('KARCIS_RUJUKAN', $payload['karcis_rujukan'])
                ->first();

            $data = $this->getKunjunganData($payload, $id_unit);
            if (! $data) {
                throw new Exception('Data kunjungan tidak ditemukan');
            }

            if (
                $data->id_satusehat_encounter == null
            ) {
                return;
            }

            $param = $this->buildEncryptedParam($payload, $data);
            SendDiagnosticReport::dispatch($param, (bool) $compositionId)->onQueue('DiagnosticReport');
        } catch (Exception $th) {
            $this->logError('DiagnosticReport', 'Gagal Process Diagnostic Report dari SIMRS', [
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
            EXEC dbo.sp_getDataDiagnosticReportDetail ?, ?, ?, ?, ?
        ", [
            $id_unit,
            $payload['iddokumen'],
            $payload['karcis'],
            $payload['karcis_rujukan']
        ]);

        if (! $data) {
            throw new \Exception('Data Kunjungan tidak ditemukan');
        }

        if ($data->id_satusehat_encounter == null || $data->id_satusehat_encounter == '') return;

        return $data;
    }

    protected function buildEncryptedParam(array $payload, $data): string
    {
        $paramSatuSehat = LZString::compressToEncodedURIComponent("id=" . $data->id . "&karcis_asal=" . $data->karcis_asal . "&karcis_rujukan=" . $data->karcis_rujukan);

        return $paramSatuSehat;
    }
}
