<?php

namespace App\Services;

use App\Http\Traits\LogTraits;
use App\Jobs\SendEncounter;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA;
use Illuminate\Support\Facades\DB;

class EncounterService
{
    use LogTraits;

    public function process(array $payload): void
    {
        DB::disableQueryLog();
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('encounter', 'Process Encounter dari SIMRS', [
            'payload' => $payload,
            'user_id' => 'system',
        ]);

        $encounterId = SATUSEHAT_NOTA::where(
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
        SendEncounter::dispatch(
            $param,
            (bool) $encounterId
        )->onQueue('encounter');
    }

    protected function getKunjunganData(array $payload, $id_unit)
    {
        $jenisPerawatan = 'RJ';
        if (str_contains(strtoupper($payload['aktivitas']), 'RAWAT JALAN')) {
            $jenisPerawatan = 'RJ';

            $rj = DB::table('v_kunjungan_rj as v')
                ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as n', function ($join) {
                    $join->on('n.KARCIS', '=', 'v.ID_TRANSAKSI')
                        ->on('n.IDUNIT', '=', 'v.ID_UNIT')
                        ->on('n.KBUKU', '=', 'v.KBUKU')
                        ->on('n.NO_PESERTA', '=', 'v.NO_PESERTA');
                })
                ->select(
                    'v.*',
                    DB::raw('COUNT(DISTINCT n.ID_SATUSEHAT_ENCOUNTER) as JUMLAH_NOTA_SATUSEHAT')
                )
                ->groupBy('v.ICD9', 'v.DIPLAY_ICD9', 'v.JENIS_PERAWATAN', 'v.STATUS_SELESAI', 'v.STATUS_KUNJUNGAN', 'v.DOKTER', 'v.DEBITUR', 'v.LOKASI', 'v.STATUS_MAPPING_PASIEN', 'v.STATUS_MAPPING_LOKASI', 'v.ID_PASIEN_SS', 'v.ID_NAKES_SS', 'v.KODE_DOKTER', 'v.ID_LOKASI_SS', 'v.UUID', 'v.STATUS_MAPPING_NAKES', 'v.ID_TRANSAKSI', 'v.ID_UNIT', 'v.KODE_KLINIK', 'v.KBUKU', 'v.NO_PESERTA', 'v.TANGGAL', 'v.NAMA_PASIEN')
                ->where('v.ID_UNIT', $id_unit)
                ->where('v.ID_TRANSAKSI', $payload['karcis'])
                ->first();
            $data = $rj;
        } else {
            $jenisPerawatan = 'RI';

            $ri = DB::table('v_kunjungan_ri as v')
                ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as n', function ($join) {
                    $join->on('n.KARCIS', '=', 'v.ID_TRANSAKSI')
                        ->on('n.IDUNIT', '=', 'v.ID_UNIT')
                        ->on('n.KBUKU', '=', 'v.KBUKU')
                        ->on('n.NO_PESERTA', '=', 'v.NO_PESERTA');
                })
                ->select(
                    'v.*',
                    DB::raw('COUNT(DISTINCT n.ID_SATUSEHAT_ENCOUNTER) as JUMLAH_NOTA_SATUSEHAT')
                )
                ->groupBy('v.ICD9', 'v.DIPLAY_ICD9', 'v.JENIS_PERAWATAN', 'v.STATUS_SELESAI', 'v.STATUS_KUNJUNGAN', 'v.DOKTER', 'v.DEBITUR', 'v.LOKASI', 'v.STATUS_MAPPING_PASIEN', 'v.STATUS_MAPPING_LOKASI', 'v.ID_PASIEN_SS', 'v.ID_NAKES_SS', 'v.KODE_DOKTER', 'v.ID_LOKASI_SS', 'v.UUID', 'v.STATUS_MAPPING_LOKASI', 'v.STATUS_MAPPING_NAKES', 'v.ID_TRANSAKSI', 'v.ID_UNIT', 'v.KODE_KLINIK', 'v.KBUKU', 'v.NO_PESERTA', 'v.TANGGAL', 'v.NAMA_PASIEN')
                ->where('v.ID_UNIT', $id_unit)
                ->where('v.ID_TRANSAKSI', $payload['karcis'])
                ->first();
            $data = $ri;
        }

        if (! $data) {
            throw new \Exception('Data Kunjungan ' . $jenisPerawatan . ' tidak ditemukan');
        }

        $data->jenisPerawatan = $jenisPerawatan;
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
