<?php

namespace App\Services;

use App\Http\Traits\LogTraits;
use App\Jobs\SendCarePlan;
use App\Jobs\SendEpisodeOfCareJobs;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_EPISODEOFCARE;
use Exception;
use Illuminate\Support\Facades\DB;

class EpisodeOfCareService
{
    use LogTraits;

    public function process(array $payload): void
    {
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('EpisodeOfCare', 'Process Episode Of Care dari SIMRS', [
            'payload' => $payload,
            'user_id' => 'system',
        ]);

        try {
            if(!isset($payload['noPeserta'])){
                $noPeserta = DB::selectOne("
                    SELECT NO_PESERTA FROM fn_getDataKunjungan(?, ?) WHERE ID_TRANSAKSI = ?
                ", [
                    $id_unit,
                    'RAWAT_JALAN',
                    $payload['karcis'] ?? '',
                ])->NO_PESERTA;
            } else {
                $noPeserta = $payload['noPeserta'];
            }

            $sql = "SELECT TOP 1 dk.ID_TRANSAKSI, edk.STATUS_AKTIF
                    FROM fn_getDataKunjungan(?, ?) dk
                    INNER JOIN E_RM_PHCM.dbo.ERM_DX_KHUSUSPX edk
                        ON dk.ID_TRANSAKSI = edk.KARCIS
                        AND dk.NO_PESERTA = edk.NO_PESERTA
                    WHERE dk.NO_PESERTA = ?
                    ORDER BY edk.CRTDT DESC";

            $dxKhusus = DB::selectOne($sql, [
                $id_unit,
                'RAWAT_JALAN',
                $noPeserta
            ]);

            if ($dxKhusus == null) {
                throw new Exception('Data Dx Khusus tidak ditemukan');
            }

            if ($dxKhusus->STATUS_AKTIF == 1 && ($payload['karcis'] != $dxKhusus->ID_TRANSAKSI)) {
                throw new Exception('Data Dx Khusus pasien masih aktif');
            }

            $EpisodeOfCareID = SATUSEHAT_EPISODEOFCARE::where(
                'KARCIS',
                (int) $dxKhusus->ID_TRANSAKSI
            )
                ->where('ID_UNIT', $id_unit)
                ->first();

            $data = $this->getKunjunganData($payload, $id_unit, $dxKhusus->ID_TRANSAKSI);
            if (! $data) {
                throw new Exception('Data kunjungan tidak ditemukan');
            }

            if (
                $data->ID_SATUSEHAT_ENCOUNTER == null || $data->ID_SATUSEHAT_ENCOUNTER == '' ||
                $data->id_satusehat_condition == null || $data->id_satusehat_condition == ''
            ) {
                return;
            }

            $param = $this->buildEncryptedParam($payload, $data);
            SendEpisodeOfCareJobs::dispatch($param, (bool) $EpisodeOfCareID)->onQueue('EpisodeOfCare');
        } catch (Exception $th) {
            $this->logError('EpisodeOfCare', 'Gagal Process Episode of Care dari SIMRS', [
                'payload' => $payload,
                'user_id' => 'system',
                'error' => $th->getMessage(),
                'trace' => $th->getTrace(),
            ]);
        }
    }

    protected function getKunjunganData(array $payload, $id_unit, $karcisOld)
    {
        $data = DB::selectOne("
            EXEC dbo.sp_getDataEpisodeOfCare ?, ?, ?, ?, ?, ?, ?, ?
        ", [
            $id_unit,
            null,
            null,
            'all',
            1,
            1,
            $karcisOld ?? '',
            null,
        ]);

        if (! $data) {
            throw new \Exception('Data Kunjungan tidak ditemukan');
        }

        if ($data->ID_SATUSEHAT_ENCOUNTER == null || $data->ID_SATUSEHAT_ENCOUNTER == '') return;
        if ($data->id_satusehat_condition == null || $data->id_satusehat_condition == '') return;

        return $data;
    }

    protected function buildEncryptedParam(array $payload, $data): string
    {
        $jenis = LZString::compressToEncodedURIComponent($data->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI');
        $id_transaksi = LZString::compressToEncodedURIComponent($data->ID_TRANSAKSI);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $kdLokasiSS = LZString::compressToEncodedURIComponent($data->ID_LOKASI_SS);
        $id_unit = LZString::compressToEncodedURIComponent($payload['id_unit']);
        $paramSatuSehat = "jenis_perawatan=" . $jenis . "&id_transaksi=" . $id_transaksi . "&kd_pasien_ss=" . $kdPasienSS . "&kd_nakes_ss=" . $kdNakesSS . "&kd_lokasi_ss=" .  $kdLokasiSS . "&id_unit=" .  $id_unit;
        $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

        return $paramSatuSehat;
    }
}
