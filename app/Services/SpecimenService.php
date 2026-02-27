<?php

namespace App\Services;

use App\Http\Controllers\SatuSehat\AllergyIntoleranceController;
use App\Http\Controllers\SatuSehat\ProcedureController;
use App\Http\Traits\LogTraits;
use App\Jobs\SendAllergyIntolerance;
use App\Jobs\SendEncounter;
use App\Jobs\SendSpecimenJob;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_ALLERGY_INTOLERANCE;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA;
use App\Models\SATUSEHAT\SATUSEHAT_PROCEDURE;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpecimenService
{
    use LogTraits;

    public function process(array $payload): void
    {
        DB::disableQueryLog();
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('Specimen', 'Receive Specimen dari SIMRS', [
            'request' => $payload,
            'karcis' => $payload['karcis'],
            'user_id' => 'system'
        ]);

        $data = $this->getKunjunganData($payload, $id_unit);
        if (! $data) {
            throw new \Exception('Data Specimen tidak ditemukan');
        }

        if (empty($data)) return;

        $param = $this->buildEncryptedParam($payload, $data);

        SendSpecimenJob::dispatch($param)->onQueue('specimen');
    }

    protected function getKunjunganData(array $payload, $id_unit)
    {
        $jenisLayanan = isset($payload['jenis_layanan']) ? (str_contains(strtoupper($payload['jenis_layanan']), 'INAP') ? 'INAP' : 'JALAN') : 'JALAN';
        if ($jenisLayanan == 'INAP') {
            $data = DB::connection('sqlsrv')
                ->table('SIRS_PHCM.dbo.v_kunjungan_ri as rj')
                ->join('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as nt', function ($join) {
                    $join->on('nt.karcis', '=', 'rj.ID_TRANSAKSI')
                        ->on('nt.idunit', '=', 'rj.ID_UNIT')
                        ->on('nt.kbuku', '=', 'rj.KBUKU')
                        ->on('nt.no_peserta', '=', 'rj.NO_PESERTA');
                })
                ->leftJoin('SIRS_PHCM.dbo.RJ_KARCIS as kc', function ($join) {
                    $join->on('kc.noreg', '=', 'nt.karcis')
                        ->on('kc.IDUNIT', '=', 'nt.idunit')
                        ->on('kc.KBUKU', '=', 'nt.kbuku')
                        ->on('kc.NO_PESERTA', '=', 'nt.no_peserta');
                })
                ->join('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as rd', function ($join) {
                    $join->on('rd.KARCIS_ASAL', '=', 'nt.karcis')
                        ->on('rd.IDUNIT', '=', 'nt.idunit')
                        ->on('rd.KBUKU', '=', 'nt.kbuku')
                        ->on('rd.NO_PESERTA', '=', 'nt.no_peserta')
                        ->on('rd.KLINIK_TUJUAN', '=', 'kc.KLINIK');
                })
                ->join('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as sr', 'rd.KARCIS_RUJUKAN', '=', 'sr.karcis')
                ->join('SIRS_PHCM.dbo.DR_MDOKTER as dk', 'rd.KDDOK', '=', 'dk.kdDok')
                ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SPECIMEN as ss', 'rd.KARCIS_RUJUKAN', '=', 'ss.karcis')
                ->leftJoin('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES as nk', 'rd.KDDOK', '=', 'nk.kddok')
                ->join('SIRS_PHCM.dbo.DR_MDOKTER as dkd', 'rd.KDDOK', '=', 'dkd.kdDok')
                ->select(['rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dkd.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.KARCIS_RUJUKAN', 'rd.ARRAY_TINDAKAN', DB::raw('COUNT(DISTINCT ss.id_satusehat_servicerequest) as SATUSEHAT'), DB::raw("'RAWAT INAP' as JENIS_PERAWATAN")])
                ->distinct()
                ->where('rd.KARCIS_RUJUKAN', $payload['karcis'])
                ->where('rd.IDUNIT', $id_unit)
                ->where('rd.KLINIK_TUJUAN', $payload['klinik'])
                ->whereNull('kc.TGL_BATAL')
                ->groupBy('rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dkd.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.KARCIS_RUJUKAN', 'rd.ARRAY_TINDAKAN')
                ->first();
        } else {
            $data = DB::connection('sqlsrv')
                ->table('SIRS_PHCM.dbo.v_kunjungan_rj as rj')
                ->join('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as nt', function ($join) {
                    $join->on('nt.karcis', '=', 'rj.ID_TRANSAKSI')
                        ->on('nt.idunit', '=', 'rj.ID_UNIT')
                        ->on('nt.kbuku', '=', 'rj.KBUKU')
                        ->on('nt.no_peserta', '=', 'rj.NO_PESERTA');
                })
                ->leftJoin('SIRS_PHCM.dbo.RJ_KARCIS as kc', function ($join) {
                    $join->on('kc.KARCIS_RUJUKAN', '=', 'nt.karcis')
                        ->on('kc.IDUNIT', '=', 'nt.idunit')
                        ->on('kc.KBUKU', '=', 'nt.kbuku')
                        ->on('kc.NO_PESERTA', '=', 'nt.no_peserta');
                })
                ->join('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as rd', function ($join) {
                    $join->on('rd.KARCIS_ASAL', '=', 'nt.karcis')
                        ->on('rd.IDUNIT', '=', 'nt.idunit')
                        ->on('rd.KBUKU', '=', 'nt.kbuku')
                        ->on('rd.NO_PESERTA', '=', 'nt.no_peserta')
                        ->on('rd.KLINIK_TUJUAN', '=', 'kc.KLINIK');
                })
                ->join('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as sr', 'rd.KARCIS_RUJUKAN', '=', 'sr.karcis')
                ->join('SIRS_PHCM.dbo.DR_MDOKTER as dk', 'rd.KDDOK', '=', 'dk.kdDok')
                ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SPECIMEN as ss', 'rd.KARCIS_RUJUKAN', '=', 'ss.karcis')
                ->leftJoin('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES as nk', 'rd.KDDOK', '=', 'nk.kddok')
                ->join('SIRS_PHCM.dbo.DR_MDOKTER as dkd', 'rd.KDDOK', '=', 'dkd.kdDok')
                ->select(['rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dkd.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.KARCIS_RUJUKAN', 'rd.ARRAY_TINDAKAN', DB::raw('COUNT(DISTINCT ss.id_satusehat_servicerequest) as SATUSEHAT'), DB::raw("'RAWAT JALAN' as JENIS_PERAWATAN")])
                ->distinct()
                ->where('rd.KARCIS_RUJUKAN', $payload['karcis'])
                ->where('rd.IDUNIT', $id_unit)
                ->where('rd.KLINIK_TUJUAN', $payload['klinik'])
                ->whereNull('kc.TGL_BATAL')
                ->groupBy('rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dkd.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.KARCIS_RUJUKAN', 'rd.ARRAY_TINDAKAN')
                ->first();
        }

        if (! $data) {
            throw new \Exception('Tidak ada data Specimen untuk karcis ' . $payload['karcis']);
        }

        return $data;
    }

    protected function buildEncryptedParam(array $payload, $data): string
    {
        $idRiwayatElab = LZString::compressToEncodedURIComponent($data->ID_RIWAYAT_ELAB);
        $karcisAsal = LZString::compressToEncodedURIComponent($data->KARCIS_ASAL);
        $karcisRujukan = LZString::compressToEncodedURIComponent($data->KARCIS_RUJUKAN);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $kdDokterSS = LZString::compressToEncodedURIComponent($data->idnakes);
        $id_unit = LZString::compressToEncodedURIComponent($payload['id_unit']);
        $kdKlinik = LZString::compressToEncodedURIComponent($payload['klinik']);
        $paramSatuSehat = LZString::compressToEncodedURIComponent($idRiwayatElab . '+' . $karcisAsal . '+' . $karcisRujukan . '+' . $kdKlinik . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' . $kdDokterSS . '+' . $id_unit);

        $paramSatuSehat = base64_encode($paramSatuSehat);
        return $paramSatuSehat;
    }
}
