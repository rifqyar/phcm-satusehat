<?php

namespace App\Services;

use App\Http\Controllers\SatuSehat\ObservasiController;
use App\Http\Traits\LogTraits;
use App\Jobs\SendEncounter;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA;
use Illuminate\Support\Facades\DB;

class ObservasiService
{
    use LogTraits;

    public function process(array $payload): void
    {
        DB::disableQueryLog();
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('Observation', 'Receive Observation dari SIMRS', [
            'payload' => $payload,
            'user_id' => 'system'
        ]);

        $data = $this->getKunjunganData($payload, $id_unit);
        if (! $data) {
            throw new \Exception('Data kunjungan tidak ditemukan');
        }

        if ($data->id_satusehat_encounter == null || $data->id_satusehat_encounter == '') return;

        $param = $this->buildEncryptedParam($data, $payload);
        $resp = app(ObservasiController::class)->sendSatuSehat(base64_encode($param), (bool) $data->sudah_integrasi);
    }

    protected function getKunjunganData(array $payload, $id_unit)
    {
        if (str_contains(strtoupper($payload['aktivitas']), 'RAWAT JALAN')) {
            $data = DB::table('v_kunjungan_rj as vkr')
                ->leftJoin('E_RM_PHCM.dbo.ERM_RM_IRJA as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.KARCIS')
                ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                    $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                        ->on('vkr.KBUKU', '=', 'rsn.kbuku');
                })
                ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI as rso', function ($join) {
                    $join->on('vkr.ID_TRANSAKSI', '=', 'rso.KARCIS')
                        ->on('vkr.KBUKU', '=', 'rso.KBUKU');
                })
                ->where('eri.AKTIF', 1)
                ->where('vkr.ID_TRANSAKSI', $payload['karcis'])
                ->selectRaw("
                    MAX(vkr.JENIS_PERAWATAN) as JENIS_PERAWATAN,
                    MAX(vkr.ID_TRANSAKSI) as KARCIS,
                    MAX(vkr.TANGGAL) as TANGGAL,
                    MAX(vkr.NO_PESERTA) as NO_PESERTA,
                    MAX(vkr.KBUKU) as KBUKU,
                    MAX(vkr.NAMA_PASIEN) as NAMA_PASIEN,
                    MAX(vkr.DOKTER) as DOKTER,
                    MAX(vkr.ID_PASIEN_SS) as ID_PASIEN_SS,
                    MAX(vkr.ID_NAKES_SS) as ID_NAKES_SS,
                    MAX(rsn.id_satusehat_encounter) as id_satusehat_encounter,
                    MAX(rso.ID_SATUSEHAT_OBSERVASI) as ID_SATUSEHAT_OBSERVASI,
                    CASE
                        WHEN (
                            (SELECT COUNT(DISTINCT rso2.JENIS)
                            FROM SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI rso2
                            WHERE rso2.KARCIS = vkr.ID_TRANSAKSI
                            AND rso2.ID_ERM = eri.NOMOR
                            AND rso2.ID_SATUSEHAT_OBSERVASI IS NOT NULL) > 0
                        ) THEN 1 ELSE 0 END as sudah_integrasi,
                    CASE WHEN MAX(eri.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
                ")
                ->groupBy(['vkr.ID_TRANSAKSI', 'eri.NOMOR'])
                ->first();
        } else {
            if (!str_contains(strtoupper($payload['aktivitas']), 'CPPT')) {
                $data = DB::table('v_kunjungan_ri as vkr')
                    ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.noreg')
                    ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                        $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                            ->on('vkr.KBUKU', '=', 'rsn.kbuku');
                    })
                    ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI as rso', function ($join) {
                        $join->on('vkr.ID_TRANSAKSI', '=', 'rso.KARCIS')
                            ->on('vkr.KBUKU', '=', 'rso.KBUKU')
                            ->on('rso.ERM_FORM', '=', DB::raw("'soap'"));
                    })
                    ->where('eri.AKTIF', 1)
                    ->where('vkr.ID_TRANSAKSI', $payload['karcis'])
                    ->selectRaw("
                        MAX(vkr.JENIS_PERAWATAN) as JENIS_PERAWATAN,
                        MAX(vkr.ID_TRANSAKSI) as KARCIS,
                        MAX(vkr.TANGGAL) as TANGGAL,
                        MAX(vkr.NO_PESERTA) as NO_PESERTA,
                        MAX(vkr.KBUKU) as KBUKU,
                        MAX(vkr.NAMA_PASIEN) as NAMA_PASIEN,
                        MAX(vkr.DOKTER) as DOKTER,
                        MAX(vkr.ID_PASIEN_SS) as ID_PASIEN_SS,
                        MAX(vkr.ID_NAKES_SS) as ID_NAKES_SS,
                        MAX(rsn.id_satusehat_encounter) as id_satusehat_encounter,
                        MAX(rso.ID_SATUSEHAT_OBSERVASI) as ID_SATUSEHAT_OBSERVASI,
                        CASE
                            WHEN (
                                (SELECT COUNT(DISTINCT rso2.JENIS)
                                FROM SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI rso2
                                WHERE rso2.KARCIS = vkr.ID_TRANSAKSI
                                AND rso2.ID_ERM = eri.id_asuhan_header
                                AND rso2.ID_SATUSEHAT_OBSERVASI IS NOT NULL) = 19
                            ) THEN 1
                            ELSE 0
                        END as sudah_integrasi,
                        CASE WHEN MAX(eri.NOREG) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
                    ")
                    ->groupBy(['vkr.ID_TRANSAKSI', 'eri.id_asuhan_header'])
                    ->first();
            } else {
                $data = DB::table('v_kunjungan_ri as vkr')
                    ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_CPPT as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.noreg')
                    ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                        $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                            ->on('vkr.KBUKU', '=', 'rsn.kbuku');
                    })
                    ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI as rso', function ($join) {
                        $join->on('vkr.ID_TRANSAKSI', '=', 'rso.KARCIS')
                            ->on('vkr.KBUKU', '=', 'rso.KBUKU')
                            ->on('rso.ERM_FORM', '=', DB::raw("'cppt'"));
                    })
                    ->where('eri.AKTIF', 1)
                    ->where('vkr.ID_TRANSAKSI', $payload['karcis'])
                    ->selectRaw("
                        MAX(vkr.JENIS_PERAWATAN) as JENIS_PERAWATAN,
                        MAX(vkr.ID_TRANSAKSI) as KARCIS,
                        MAX(vkr.TANGGAL) as TANGGAL,
                        MAX(vkr.NO_PESERTA) as NO_PESERTA,
                        MAX(vkr.KBUKU) as KBUKU,
                        MAX(vkr.NAMA_PASIEN) as NAMA_PASIEN,
                        MAX(vkr.DOKTER) as DOKTER,
                        MAX(vkr.ID_PASIEN_SS) as ID_PASIEN_SS,
                        MAX(vkr.ID_NAKES_SS) as ID_NAKES_SS,
                        MAX(rsn.id_satusehat_encounter) as id_satusehat_encounter,
                        MAX(rso.ID_SATUSEHAT_OBSERVASI) as ID_SATUSEHAT_OBSERVASI,
                        CASE
                            WHEN COUNT(DISTINCT rso.JENIS) > 0 THEN 1 ELSE 0
                        END as sudah_integrasi,
                        CASE WHEN MAX(eri.NOREG) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
                    ")
                    ->groupBy(['vkr.ID_TRANSAKSI', 'eri.id_asuhan_header'])
                    ->first();
            }
        }

        if (! $data) {
            throw new \Exception('Data Observasi ' . strtoupper($payload['aktivitas']) . ' tidak ditemukan');
        }

        return $data;
    }

    protected function buildEncryptedParam($data, $payload): string
    {
        $id_transaksi = LZString::compressToEncodedURIComponent($data->KARCIS);
        $KbBuku = LZString::compressToEncodedURIComponent($data->KBUKU);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $id_unit = LZString::compressToEncodedURIComponent($payload['id_unit']);
        $idEncounter = LZString::compressToEncodedURIComponent($data->id_satusehat_encounter);
        $aktivitas = LZString::compressToEncodedURIComponent($payload['aktivitas'] ?? 'RAWAT JALAN');
        $paramSatuSehat = "sudah_integrasi=$data->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter&jenis_perawatan=" . LZString::compressToEncodedURIComponent($data->JENIS_PERAWATAN) . "&id_unit=" .  $id_unit . "&aktivitas=" .  $aktivitas;
        $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

        return $paramSatuSehat;
    }
}
