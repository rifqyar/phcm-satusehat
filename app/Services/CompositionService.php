<?php

namespace App\Services;

use App\Http\Traits\LogTraits;
use App\Jobs\SendResumeMedis;
use App\Lib\LZCompressor\LZString;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompositionService
{
    use LogTraits;

    public function process(array $payload): void
    {
        DB::disableQueryLog();
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('Composition', 'Process Composition dari SIMRS', [
            'payload' => $payload,
            'karcis' => $payload['karcis'],
            'user_id' => 'system',
        ]);

        try {
            $compositionId = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_COMPOSITION')
                ->where('KARCIS', $payload['karcis'])
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
            SendResumeMedis::dispatch($param, (bool) $compositionId)->onQueue('Composition');
        } catch (Exception $th) {
            $this->logError('Composition', 'Gagal Process Composition dari SIMRS', [
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
            EXEC dbo.sp_getDataComposition ?, ?, ?, ?, ?
        ", [
            $id_unit,
            null,
            null,
            'all',
            $payload['karcis'] ?? '',
        ]);

        if (! $data) {
            throw new \Exception('Data Kunjungan tidak ditemukan');
        }

        if ($data->id_satusehat_encounter == null || $data->id_satusehat_encounter == '') return;

        return $data;
    }

    protected function buildEncryptedParam(array $payload, $data): string
    {
        $jenisPerawatan = $data->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
        $id_transaksi = LZString::compressToEncodedURIComponent($payload['karcis']);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $kdLokasiSS = LZString::compressToEncodedURIComponent($data->ID_LOKASI_SS);
        $id_unit = LZString::compressToEncodedURIComponent($payload['id_unit']);
        $paramSatuSehat = LZString::compressToEncodedURIComponent($jenisPerawatan . '+' . $id_transaksi . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' .  $kdLokasiSS . '+' .  $id_unit);

        return $paramSatuSehat;
    }
}
