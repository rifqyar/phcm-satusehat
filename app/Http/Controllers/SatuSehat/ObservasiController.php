<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\SATUSEHATTraits;
use App\Jobs\SendObservationToSATUSEHAT;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\SATUSEHAT\SATUSEHAT_OBSERVATION;
use App\Models\SATUSEHAT\SS_Kode_API;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\DataTables;

class ObservasiController extends Controller
{
    use SATUSEHATTraits;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $startDate = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
        $endDate   = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

        $rj = DB::table('v_kunjungan_rj as vkr')
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
            ->whereBetween('vkr.TANGGAL', [$startDate, $endDate])
            ->selectRaw("
                vkr.ID_TRANSAKSI as KARCIS,
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
                        AND rso2.ID_SATUSEHAT_OBSERVASI IS NOT NULL) = 5
                    ) THEN 1
                    ELSE 0
                END as sudah_integrasi,
                CASE WHEN MAX(eri.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
            ")
            ->groupBy(['vkr.ID_TRANSAKSI', 'eri.NOMOR'])
            ->orderByDesc(DB::raw('MAX(vkr.TANGGAL)'));

        $rjAll = $rj->get();
        $rjIntegrasi = $rj->whereNotNull('rsn.ID_SATUSEHAT_ENCOUNTER')->get();

        $ri = DB::table('v_kunjungan_ri as vkr')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.noreg')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                    ->on('vkr.KBUKU', '=', 'rsn.kbuku');
            })
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI as rso', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rso.KARCIS')
                    ->on('vkr.KBUKU', '=', 'rso.KBUKU');
            })
            ->where('eri.AKTIF', 1)
            ->whereBetween('vkr.TANGGAL', [$startDate, $endDate])
            ->selectRaw("
                vkr.ID_TRANSAKSI as KARCIS,
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
            ->orderByDesc(DB::raw('MAX(vkr.TANGGAL)'))
            ->get();

        $mergedAll = $rjAll->merge($ri)
            ->sortByDesc('TANGGAL')
            ->values();

        $mergedIntegrated = $rjIntegrasi->merge($ri)
            ->sortByDesc('TANGGAL')
            ->values();

        $totalAll = $rj->count();
        $totalSudahIntegrasi = $rj->count();
        $totalBelumIntegrasi = $totalAll - $totalSudahIntegrasi;

        $result = [
            'total_semua' => 0,
            'total_sudah_integrasi' => 0,
            'total_belum_integrasi' => 0,
            'total_rawat_jalan' => $rjAll->count(),
            'total_rawat_inap' => $ri->count(),
        ];

        return view('pages.satusehat.observation.index', compact('result'));
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $id_unit = Session::get('id_unit_simrs', '001');

        if (empty($tgl_awal) && empty($tgl_akhir)) {
            $tgl_awal  = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $tgl_akhir = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
        } else {
            $tgl_awal = Carbon::parse($tgl_awal)->startOfDay()->format('Y-m-d H:i:s');
            $tgl_akhir = Carbon::parse($tgl_akhir)->endOfDay()->format('Y-m-d H:i:s');
        }

        if (!$this->checkDateFormat($tgl_awal) || !$this->checkDateFormat($tgl_akhir)) {
            return DataTables::of([])->make(true);
        }

        $tgl_awal_db  = Carbon::parse($tgl_awal)->format('Y-m-d H:i:s');
        $tgl_akhir_db = Carbon::parse($tgl_akhir)->format('Y-m-d H:i:s');

        // QUERY RAWAT JALAN
        $rjQuery = DB::table('v_kunjungan_rj as vkr')
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
            ->whereBetween('vkr.TANGGAL', [$tgl_awal_db, $tgl_akhir_db])
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
                        AND rso2.ID_SATUSEHAT_OBSERVASI IS NOT NULL) = 5
                    ) THEN 1 ELSE 0 END as sudah_integrasi,
                CASE WHEN MAX(eri.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
            ")
            ->groupBy(['vkr.ID_TRANSAKSI', 'eri.NOMOR']);

        // QUERY RAWAT INAP (Kolom disesuaikan agar UNION tidak error)
        $riQuery = DB::table('v_kunjungan_ri as vkr')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.noreg')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                    ->on('vkr.KBUKU', '=', 'rsn.kbuku');
            })
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI as rso', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rso.KARCIS')
                    ->on('vkr.KBUKU', '=', 'rso.KBUKU');
            })
            ->where('eri.AKTIF', 1)
            ->whereBetween('vkr.TANGGAL', [$tgl_awal_db, $tgl_akhir_db])
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
            ->groupBy(['vkr.ID_TRANSAKSI', 'eri.id_asuhan_header']);
        $mergedQuery = $rjQuery->unionAll($riQuery);

        $dataAll = DB::query()->fromSub($mergedQuery, 'x')
            ->groupBy([
                'x.JENIS_PERAWATAN',
                'x.KARCIS',
                'x.TANGGAL',
                'x.NO_PESERTA',
                'x.KBUKU',
                'x.NAMA_PASIEN',
                'x.DOKTER',
                'x.ID_PASIEN_SS',
                'x.ID_NAKES_SS',
                'x.id_satusehat_encounter',
                'x.ID_SATUSEHAT_OBSERVASI',
                'x.sudah_integrasi',
                'x.sudah_proses_dokter',
            ]);

        $totalData = $dataAll->get();
        $totalAll = $totalData->count();
        $totalSudahIntegrasi = $totalData->where('sudah_integrasi', 1)->count();
        $totalBelumIntegrasi = $totalAll - $totalSudahIntegrasi;

        $totalData = [
            'total_semua' => $totalAll,
            'total_sudah_integrasi' => $totalSudahIntegrasi,
            'total_belum_integrasi' => $totalBelumIntegrasi,
            'total_rawat_jalan' => $totalData->where('JENIS_PERAWATAN', 'RAWAT_JALAN')->count(),
            'total_rawat_inap' => $totalData->where('JENIS_PERAWATAN', 'RAWAT_INAP')->count(),
        ];

        $cari = $request->input('cari');
        if ($cari === 'mapped') {
            $dataAll->whereNotNull('rso.karcis');
        } elseif ($cari === 'unmapped') {
            $dataAll->whereNull('rso.karcis');
        }

        $data = $dataAll->orderByDesc(DB::raw('MAX(x.TANGGAL)'))->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('JENIS_PERAWATAN', function ($row) {
                return $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
            })
            ->addColumn('checkbox', function ($row) {
                $checkBox = '';
                $id_transaksi = LZString::compressToEncodedURIComponent($row->KARCIS);
                $KbBuku = LZString::compressToEncodedURIComponent($row->KBUKU);
                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                $idEncounter = LZString::compressToEncodedURIComponent($row->id_satusehat_encounter);
                $paramSatuSehat = "sudah_integrasi=$row->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter&jenis_perawatan=" . LZString::compressToEncodedURIComponent($row->JENIS_PERAWATAN);
                $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

                if ($row->sudah_integrasi == '0' && ($row->ID_PASIEN_SS != null && $row->ID_NAKES_SS != null && $row->id_satusehat_encounter != null)) {
                    $checkBox = "
                        <input type='checkbox' class='select-row chk-col-purple' value='$row->KARCIS' data-param='$paramSatuSehat' id='$row->KARCIS' />
                        <label for='$row->KARCIS' style='margin-bottom: 0px !important; line-height: 25px !important; font-weight: 500'> &nbsp; </label>
                    ";
                }

                return $checkBox;
            })
            ->editColumn('TANGGAL', function ($row) {
                return date('Y-m-d', strtotime($row->TANGGAL));
            })
            ->addColumn('action', function ($row) {
                $id_transaksi = LZString::compressToEncodedURIComponent($row->KARCIS);
                $KbBuku = LZString::compressToEncodedURIComponent($row->KBUKU);
                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                $idEncounter = LZString::compressToEncodedURIComponent($row->id_satusehat_encounter);
                $paramSatuSehat = "sudah_integrasi=$row->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter&jenis_perawatan=" . LZString::compressToEncodedURIComponent($row->JENIS_PERAWATAN);
                $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

                $param = LZString::compressToEncodedURIComponent("karcis=$id_transaksi&kbuku=$KbBuku&jenis_perawatan=" . LZString::compressToEncodedURIComponent($row->JENIS_PERAWATAN));
                $btn = '';

                $dataErm = null;
                if ($row->JENIS_PERAWATAN == 'RAWAT_INAP') {
                    $dataErm = DB::table('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD')
                        ->where('noreg', $row->KARCIS)
                        ->first();
                }

                if ($row->ID_PASIEN_SS == null) {
                    $btn = '<i class="text-muted">Pasien Belum Mapping</i>';
                } else if ($row->ID_NAKES_SS == null) {
                    $btn .= '<i class="text-muted">Nakes Belum Mapping</i>';
                } else if ($row->id_satusehat_encounter == null) {
                    $btn .= '<i class="text-muted">Encounter Belum Kirim</i>';
                } else if ($dataErm == null && $row->JENIS_PERAWATAN == 'RAWAT_INAP') {
                    $btn .= '<i class="text-muted">Assesmne Awal Pasien Masuk Belum Diisi</i>';
                } else {
                    if ($row->sudah_integrasi == '0') {
                        $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                    } else {
                        $btn = '<a href="javascript:void(0)" onclick="resendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                    }
                    $btn .= '<br>';
                    $btn .= '<a href="javascript:void(0)" onclick="lihatDetail(`' . $param . '`, `' . $paramSatuSehat . '`)" class="mt-2 btn btn-sm btn-info w-100"><i class="fas fa-info-circle mr-2"></i>Lihat Detail</a>';
                }
                return $btn;
            })
            ->addColumn('status_integrasi', function ($row) {
                if ($row->sudah_integrasi == '0') {
                    return '<span class="badge badge-pill badge-danger p-2 w-100">Belum Integrasi</span>';
                } else {
                    return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                }
            })
            ->rawColumns(['action', 'status_integrasi', 'checkbox'])
            ->with($totalData)
            ->make(true);
    }

    private function checkDateFormat($date)
    {
        try {
            if ($date instanceof \Carbon\Carbon) {
                return true;
            }

            \Carbon\Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function lihatDetail($param)
    {
        $param = base64_decode($param);
        $params = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('&', $params);

        $arrParam = [];
        for ($i = 0; $i < count($parts); $i++) {
            $partsParam = explode('=', $parts[$i]);
            $key = $partsParam[0];
            $val = $partsParam[1];
            $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
        }

        $dataPasien = DB::table('RIRJ_MASTERPX')->select('NAMA', 'KBUKU', 'NO_PESERTA')->where("KBUKU", $arrParam['kbuku'])->first();

        if ($arrParam['jenis_perawatan'] == 'RAWAT_JALAN') {
            $dataErm = DB::table('v_kunjungan_rj as vkr')
                ->leftJoin('E_RM_PHCM.dbo.ERM_RM_IRJA as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.KARCIS')
                ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI as rso', function ($join) {
                    $join->on('vkr.ID_TRANSAKSI', '=', 'rso.KARCIS')
                        ->on('vkr.KBUKU', '=', 'rso.KBUKU');
                })
                ->where('eri.AKTIF', 1)
                ->where('vkr.KBUKU', $arrParam['kbuku'])
                ->where('vkr.ID_TRANSAKSI', $arrParam['karcis'])
                ->selectRaw("
                    vkr.ID_TRANSAKSI as KARCIS,
                    MAX(vkr.TANGGAL) as TANGGAL,
                    MAX(vkr.NO_PESERTA) as NO_PESERTA,
                    MAX(vkr.KBUKU) as KBUKU,
                    MAX(vkr.NAMA_PASIEN) as NAMA_PASIEN,
                    MAX(vkr.DOKTER) as DOKTER,
                    MAX(vkr.ID_PASIEN_SS) as ID_PASIEN_SS,
                    MAX(vkr.ID_NAKES_SS) as ID_NAKES_SS,
                    MAX(rso.ID_SATUSEHAT_OBSERVASI) as ID_SATUSEHAT_OBSERVASI,
                    MAX(eri.KODE_DIAGNOSA_UTAMA) as KODE_DIAGNOSA_UTAMA,
                    MAX(eri.DIAG_UTAMA) as DIAG_UTAMA,
                    MAX(eri.KODE_DIAGNOSA_SEKUNDER) as KODE_DIAGNOSA_SEKUNDER,
                    MAX(eri.DIAG_SEKUNDER) as DIAG_SEKUNDER,
                    MAX(eri.KODE_DIAGNOSA_KOMPLIKASI) as KODE_DIAGNOSA_KOMPLIKASI,
                    MAX(eri.DIAG_KOMPLIKASI) as DIAG_KOMPLIKASI,
                    MAX(eri.KODE_DIAGNOSA_PENYEBAB) as KODE_DIAGNOSA_PENYEBAB,
                    MAX(eri.PENYEBAB) as PENYEBAB,
                    MAX(eri.ANAMNESE) as ANAMNESE,
                    MAX(eri.BB) as BB,
                    MAX(eri.TB) as TB,
                    MAX(eri.DJ) as DJ,
                    MAX(eri.TD) as TD,
                    MAX(eri.CRTUSR) as CRTUSR,
                    MAX(eri.CRTDT) as CRTDT,
                    MAX(eri.NOMOR) as NOMOR,
                    CASE
                        WHEN (
                            (SELECT COUNT(DISTINCT rso2.JENIS)
                            FROM SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI rso2
                            WHERE rso2.KARCIS = vkr.ID_TRANSAKSI
                            AND rso2.ID_ERM = eri.NOMOR
                            AND rso2.ID_SATUSEHAT_OBSERVASI IS NOT NULL) = 5
                        ) THEN 1
                        ELSE 0
                    END as sudah_integrasi,
                    CASE WHEN MAX(eri.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
                ")
                ->groupBy(['vkr.ID_TRANSAKSI', 'eri.NOMOR'])
                ->orderByDesc(DB::raw('MAX(vkr.TANGGAL)'))
                ->first();

            $dataErm->jenis_perawatan = 'RJ';
        } else {
            $dataErm = DB::table('v_kunjungan_ri as vkr')
                ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD as h', function ($join) {
                    $join->on('vkr.ID_TRANSAKSI', '=', 'h.noreg')
                        ->on('vkr.KBUKU', '=', 'h.norm');
                })
                ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_PENGKAJIAN_FISIK as d', 'h.id_asuhan_header', '=', 'd.id_asuhan_header')
                ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI as rso', function ($join) {
                    $join->on('vkr.ID_TRANSAKSI', '=', 'rso.KARCIS')
                        ->on('vkr.KBUKU', '=', 'rso.KBUKU');
                })
                ->selectRaw("
                    MAX(vkr.ID_TRANSAKSI) AS KARCIS,
                    MAX(vkr.TANGGAL) AS TANGGAL,
                    MAX(vkr.NO_PESERTA) AS NO_PESERTA,
                    MAX(vkr.KBUKU) AS KBUKU,
                    MAX(vkr.NAMA_PASIEN) AS NAMA_PASIEN,
                    MAX(vkr.DOKTER) AS DOKTER,
                    MAX(vkr.ID_PASIEN_SS) AS ID_PASIEN_SS,
                    MAX(vkr.ID_NAKES_SS) AS ID_NAKES_SS,
                    MAX(h.nmDok) as CRTUSR,
                    MAX(d.mata_kanan) AS MATA_KANAN,
                    MAX(d.mata_kiri) AS MATA_KIRI,
                    MAX(d.td) AS TD,
                    MAX(d.suhu) AS SUHU,
                    MAX(d.p) AS P,
                    MAX(d.nadi) AS NADI,
                    MAX(d.bb) AS BB,
                    MAX(d.tb) AS TB,
                    MAX(d.kesadaran) AS KESADARAN,
                    MAX(d.kepala) AS KEPALA,
                    MAX(d.ket_kepala) AS KET_KEPALA,
                    MAX(d.rambut) AS RAMBUT,
                    MAX(d.ket_rambut) AS KET_RAMBUT,
                    MAX(d.muka) AS MUKA,
                    MAX(d.mata) AS MATA,
                    MAX(d.ket_mata) AS KET_MATA,
                    MAX(d.telinga) AS TELINGA,
                    MAX(d.ket_telinga) AS KET_TELINGA,
                    MAX(d.hidung) AS HIDUNG,
                    MAX(d.ket_hidung) AS KET_HIDUNG,
                    MAX(d.mulut) AS MULUT,
                    MAX(d.ket_mulut) AS KET_MULUT,
                    MAX(d.gigi) AS GIGI,
                    MAX(d.ket_gigi) AS KET_GIGI,
                    MAX(d.lidah) AS LIDAH,
                    MAX(d.ket_lidah) AS KET_LIDAH,
                    MAX(d.tenggorokan) AS TENGGOROKAN,
                    MAX(d.ket_tenggorokan) AS KET_TENGGOROKAN,
                    MAX(d.leher) AS LEHER,
                    MAX(d.ket_leher) AS KET_LEHER,
                    MAX(d.dada) AS DADA,
                    CASE
                        WHEN (
                            (SELECT COUNT(DISTINCT rso2.JENIS)
                            FROM SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI rso2
                            WHERE rso2.KARCIS = vkr.ID_TRANSAKSI
                            AND rso2.ID_ERM = h.id_asuhan_header
                            AND rso2.ID_SATUSEHAT_OBSERVASI IS NOT NULL) = 19
                        ) THEN 1
                        ELSE 0
                    END as sudah_integrasi,
                    CASE WHEN MAX(h.id_asuhan_header) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
                ")
                ->where('vkr.ID_TRANSAKSI', $arrParam['karcis'])
                ->where('vkr.KBUKU', $arrParam['kbuku'])
                ->where('h.aktif', '1')
                ->groupBy(['vkr.ID_TRANSAKSI', 'h.id_asuhan_header'])
                ->first();

            $dataErm->jenis_perawatan = 'RI';
        }

        return response()->json([
            'status' => JsonResponse::HTTP_OK,
            'message' => 'OK',
            'data' => [
                'dataErm' => $dataErm,
                'dataPasien' => $dataPasien,
            ],
            'redirect' => [
                'need' => false,
                'to' => null,
            ]
        ], 200);
    }

    public function sendSatuSehat($param, $resend = false)
    {
        $param = base64_decode($param);
        $params = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('&', $params);

        $arrParam = [];
        for ($i = 0; $i < count($parts); $i++) {
            $partsParam = explode('=', $parts[$i]);
            $key = $partsParam[0];
            $val = $partsParam[1];
            $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
        }
        $id_unit = Session::get('id_unit_simrs', '001');

        if ($arrParam['jenis_perawatan'] == 'RAWAT_INAP') {
            return $this->sendObservationRIToSATUSEHAT($arrParam, $id_unit, $resend);
        }

        $dataErm = DB::table('E_RM_PHCM.dbo.ERM_RM_IRJA as eri')
            ->where('eri.AKTIF', 1)
            ->where('eri.KARCIS', $arrParam['karcis'])
            ->selectRaw("
                eri.KARCIS,
                eri.KODE_DIAGNOSA_UTAMA,
                eri.DIAG_UTAMA,
                eri.KODE_DIAGNOSA_SEKUNDER,
                eri.DIAG_SEKUNDER,
                eri.KODE_DIAGNOSA_KOMPLIKASI,
                eri.DIAG_KOMPLIKASI,
                eri.KODE_DIAGNOSA_PENYEBAB,
                eri.PENYEBAB,
                eri.ANAMNESE,
                eri.BB,
                eri.TB,
                eri.DJ,
                eri.TD,
                eri.CRTUSR,
                eri.CRTDT,
                eri.NOMOR
            ")
            ->first();

        $patient = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN')
            ->where('idpx', $arrParam['id_pasien_ss'])
            ->first();

        $encounter = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
            ->where('id_satusehat_encounter', $arrParam['encounter_id'])
            ->first();

        Carbon::setLocale('id');
        $displayTextEncounter = "Pemeriksaan Fisik Pasien A/n $patient->nama Pada hari " . Carbon::parse($dataErm->CRTDT)->translatedFormat('l, d F Y');

        $baseurl = '';
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

        // Define observation mappings
        $vitalSignsMap = [
            'TD' => 'definePayloadTD',
            'DJ' => 'definePayloadDJ',
            'TB' => 'definePayloadTinggi',
            'BB' => 'definePayloadBerat',
        ];

        try {
            $basePayload = [
                'resourceType' => 'Observation',
                'status' => 'final',
                'category' => [
                    [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                                'code' => 'vital-signs',
                                'display' => 'Vital Signs',
                            ],
                        ],
                    ],
                ],
                'subject' => [
                    'reference' => 'Patient/' . $arrParam['id_pasien_ss'],
                ],
                'performer' => [
                    [
                        'reference' => 'Practitioner/' . $arrParam['id_nakes_ss'],
                    ],
                ],
                'encounter' => [
                    'reference' => 'Encounter/' . $arrParam['encounter_id'],
                    'display' => $displayTextEncounter,
                ],
                'effectiveDateTime' => Carbon::parse($dataErm->CRTDT)->toIso8601String(),
                'issued' => Carbon::now('Asia/Jakarta')->toIso8601String(),
            ];

            foreach ($vitalSignsMap as $key => $method) {
                if (!empty($dataErm->$key)) {
                    $payload = $this->$method($dataErm, $organisasi);
                    if ($payload) {
                        if ($resend && $key != 'TD') {
                            $dataObs = SATUSEHAT_OBSERVATION::where('karcis', $arrParam['karcis'])
                                ->where('kbuku', $arrParam['kbuku'])
                                ->where('id_erm', $dataErm->NOMOR)
                                ->where('jenis', strtolower($key))
                                ->first();

                            $basePayload['id'] = $dataObs ? $dataObs->ID_SATUSEHAT_OBSERVASI : null;
                        } else if ($resend && $key == 'TD') {
                            $dataObsSistolik = SATUSEHAT_OBSERVATION::where('karcis', $arrParam['karcis'])
                                ->where('kbuku', $arrParam['kbuku'])
                                ->where('id_erm', $dataErm->NOMOR)
                                ->where('jenis', strtolower($key) . '_sistolik')
                                ->first();

                            $dataObsDiastolik = SATUSEHAT_OBSERVATION::where('karcis', $arrParam['karcis'])
                                ->where('kbuku', $arrParam['kbuku'])
                                ->where('id_erm', $dataErm->NOMOR)
                                ->where('jenis', strtolower($key) . '_diastolik')
                                ->first();
                        }

                        if ($key == 'TD') {
                            $basePayloadSystolic = $basePayload;
                            $basePayloadDiastolic = $basePayload;
                            if ($resend) {
                                $basePayloadSystolic['id'] = $dataObsSistolik ? $dataObsSistolik->ID_SATUSEHAT_OBSERVASI : null;
                                $basePayloadDiastolic['id'] = $dataObsDiastolik ? $dataObsDiastolik->ID_SATUSEHAT_OBSERVASI : null;
                            }

                            $payloadSystolic = array_merge($basePayloadSystolic, $payload[0]);
                            $payloadDiastolic = array_merge($basePayloadDiastolic, $payload[1]);
                            $payloadObservations[] = ['payload' => $payloadSystolic, 'type' => strtolower($key) . '_sistolik'];
                            $payloadObservations[] = ['payload' => $payloadDiastolic, 'type' => strtolower($key) . '_diastolik'];
                        } else {
                            $payloadWithBase = array_merge($basePayload, $payload);
                            $payloadObservations[] = ['payload' => $payloadWithBase, 'type' => strtolower($key)];
                        }
                    }
                }
            }

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }

            $token = $login['response']['token'];

            $dataKarcis = DB::table('RJ_KARCIS as rk')
                ->select('rk.KARCIS', 'rk.IDUNIT', 'rk.KLINIK', 'rk.TGL', 'rk.KDDOK', 'rk.KBUKU')
                ->where('rk.KARCIS', $arrParam['karcis'])
                ->where('rk.IDUNIT', $id_unit)
                ->orderBy('rk.TGL', 'DESC')
                ->first();

            $dataPeserta = DB::table('RIRJ_MASTERPX')
                ->where('KBUKU', $dataKarcis->KBUKU)
                ->first();

            // Dispatch all observations
            $url = 'Observation';
            foreach ($payloadObservations as $obs) {
                if ($resend) {
                    $dataObs = SATUSEHAT_OBSERVATION::where('karcis', $arrParam['karcis'])
                        ->where('kbuku', $arrParam['kbuku'])
                        ->where('id_erm', $dataErm->NOMOR)
                        ->where('jenis', $obs['type'])
                        ->first();

                    $url = 'Observation/' . ($dataObs ? $dataObs->ID_SATUSEHAT_OBSERVASI : '');
                }

                SendObservationToSATUSEHAT::dispatch($obs['payload'], $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, $obs['type'], $resend);
            }

            return response()->json([
                'status' => JsonResponse::HTTP_OK,
                'message' => 'Pengiriman Data Observasi Pasien Sedang Diproses oleh sistem',
                'redirect' => [
                    'need' => false,
                    'to' => null,
                ]
            ], 200);
        } catch (Exception $th) {
            return response()->json([
                'status' => [
                    'msg' => $th->getMessage() != '' ? $th->getMessage() : 'Err',
                    'code' => $th->getCode() != '' ? $th->getCode() : 500,
                ],
                'data' => null,
                'err_detail' => $th,
                'message' => $th->getMessage() != '' ? $th->getMessage() : 'Terjadi Kesalahan Saat Kirim Data, Harap Coba lagi!'
            ], $th->getCode() != '' ? $th->getCode() : 500);
        }
    }

    private function sendObservationRIToSATUSEHAT($arrParam, $id_unit, $resend = false)
    {
        $dataErm = DB::table('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD as h')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_PENGKAJIAN_FISIK as d', 'h.id_asuhan_header', '=', 'd.id_asuhan_header')
            ->where('h.aktif', '1')
            ->where('h.noreg', $arrParam['karcis'])
            ->selectRaw("
                d.mata_kanan AS MATA_KANAN,
                d.mata_kiri AS MATA_KIRI,
                d.td AS TD,
                d.suhu AS SUHU,
                d.p AS P,
                d.nadi AS DJ,
                d.bb AS BB,
                d.tb AS TB,
                d.kesadaran AS KESADARAN,
                d.kepala AS KEPALA,
                d.rambut AS RAMBUT,
                d.muka AS MUKA,
                d.mata AS MATA,
                d.telinga AS TELINGA,
                d.hidung AS HIDUNG,
                d.mulut AS MULUT,
                d.gigi AS GIGI,
                d.lidah AS LIDAH,
                d.tenggorokan AS TENGGOROKAN,
                d.leher AS LEHER,
                d.dada AS DADA,
                h.nmDok AS CRTUSR,
                h.crt_dt AS CRTDT,
                h.id_asuhan_header AS NOMOR
            ")
            ->first();

        if (!$dataErm) {
            return response()->json([
                'status' => JsonResponse::HTTP_NOT_FOUND,
                'message' => 'Data Asuhan Awal Pasien Masuk Tidak Ditemukan di ERM',
                'redirect' => [
                    'need' => false,
                    'to' => null,
                ]
            ], 404);
        }

        $patient = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN')
            ->where('idpx', $arrParam['id_pasien_ss'])
            ->first();

        $encounter = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
            ->where('id_satusehat_encounter', $arrParam['encounter_id'])
            ->first();

        Carbon::setLocale('id');
        $displayTextEncounter = "Pemeriksaan Fisik Pasien A/n $patient->nama Pada hari " . Carbon::parse($dataErm->CRTDT)->translatedFormat('l, d F Y');

        $baseurl = '';
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

        try {
            $basePayload = [
                'resourceType' => 'Observation',
                'status' => 'final',
                'category' => [
                    [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                                'code' => 'vital-signs',
                                'display' => 'Vital Signs',
                            ],
                        ],
                    ],
                ],
                'subject' => [
                    'reference' => 'Patient/' . $arrParam['id_pasien_ss'],
                ],
                'performer' => [
                    [
                        'reference' => 'Practitioner/' . $arrParam['id_nakes_ss'],
                    ],
                ],
                'encounter' => [
                    'reference' => 'Encounter/' . $arrParam['encounter_id'],
                    'display' => $displayTextEncounter,
                ],
                'effectiveDateTime' => Carbon::parse($dataErm->CRTDT)->toIso8601String(),
                'issued' => Carbon::now('Asia/Jakarta')->toIso8601String(),
            ];

            $payloadObservations = [];

            // Define observation mappings
            $vitalSignsMap = [
                'TD' => 'definePayloadTD',
                'DJ' => 'definePayloadDJ',
                'TB' => 'definePayloadTinggi',
                'BB' => 'definePayloadBerat',
                'SUHU' => 'definePayloadSuhu',
                'P' => 'definePayloadP',
            ];

            $examMap = [
                'MATA' => 'definePayloadExam',
                'KEPALA' => 'definePayloadExam',
                'RAMBUT' => 'definePayloadExam',
                'MUKA' => 'definePayloadExam',
                'MATA' => 'definePayloadExam',
                'TELINGA' => 'definePayloadExam',
                'HIDUNG' => 'definePayloadExam',
                'MULUT' => 'definePayloadExam',
                'GIGI' => 'definePayloadExam',
                'LIDAH' => 'definePayloadExam',
                'TENGGOROKAN' => 'definePayloadExam',
                'LEHER' => 'definePayloadExam',
                'DADA' => 'definePayloadExam',
            ];

            // Process vital signs
            foreach ($vitalSignsMap as $key => $method) {
                if (!empty($dataErm->$key)) {
                    $payload = $this->$method($dataErm, $organisasi);
                    if ($payload) {
                        if ($resend && $key != 'TD') {
                            $dataObs = SATUSEHAT_OBSERVATION::where('karcis', $arrParam['karcis'])
                                ->where('kbuku', $arrParam['kbuku'])
                                ->where('id_erm', $dataErm->NOMOR)
                                ->where('jenis', strtolower($key))
                                ->first();

                            $basePayload['id'] = $dataObs ? $dataObs->ID_SATUSEHAT_OBSERVASI : null;
                        } else if ($resend && $key == 'TD') {
                            $dataObsSistolik = SATUSEHAT_OBSERVATION::where('karcis', $arrParam['karcis'])
                                ->where('kbuku', $arrParam['kbuku'])
                                ->where('id_erm', $dataErm->NOMOR)
                                ->where('jenis', strtolower($key) . '_sistolik')
                                ->first();

                            $dataObsDiastolik = SATUSEHAT_OBSERVATION::where('karcis', $arrParam['karcis'])
                                ->where('kbuku', $arrParam['kbuku'])
                                ->where('id_erm', $dataErm->NOMOR)
                                ->where('jenis', strtolower($key) . '_diastolik')
                                ->first();
                        }

                        if ($key == 'TD') {
                            $basePayloadSystolic = $basePayload;
                            $basePayloadDiastolic = $basePayload;

                            if ($resend) {
                                $basePayloadSystolic['id'] = $dataObsSistolik ? $dataObsSistolik->ID_SATUSEHAT_OBSERVASI : null;
                                $basePayloadDiastolic['id'] = $dataObsDiastolik ? $dataObsDiastolik->ID_SATUSEHAT_OBSERVASI : null;
                            }

                            $payloadSystolic = array_merge($basePayloadSystolic, $payload[0]);
                            $payloadDiastolic = array_merge($basePayloadDiastolic, $payload[1]);
                            $payloadObservations[] = ['payload' => $payloadSystolic, 'type' => strtolower($key) . '_sistolik'];
                            $payloadObservations[] = ['payload' => $payloadDiastolic, 'type' => strtolower($key) . '_diastolik'];
                        } else {
                            $payloadWithBase = array_merge($basePayload, $payload);
                            $payloadObservations[] = ['payload' => $payloadWithBase, 'type' => strtolower($key)];
                        }
                    }
                }
            }

            // Process exam observations
            foreach ($examMap as $key => $method) {
                if (!empty($dataErm->$key)) {
                    $payload = $this->$method($dataErm, $organisasi, $key);
                    if ($payload) {
                        if ($resend) {
                            $dataObs = SATUSEHAT_OBSERVATION::where('karcis', $arrParam['karcis'])
                                ->where('kbuku', $arrParam['kbuku'])
                                ->where('id_erm', $dataErm->NOMOR)
                                ->where('jenis', strtolower($key))
                                ->first();

                            $basePayload['id'] = $dataObs ? $dataObs->ID_SATUSEHAT_OBSERVASI : null;
                        }

                        $basePayloadExam = $basePayload;
                        $basePayloadExam['category'] = [[
                            'coding' => [[
                                'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                                'code' => 'exam',
                                'display' => 'Exam',
                            ]],
                        ]];
                        $payloadWithBase = array_merge($basePayloadExam, $payload);
                        $payloadObservations[] = ['payload' => $payloadWithBase, 'type' => strtolower($key)];
                    }
                }
            }

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                return $login;
            }

            $token = $login['response']['token'];
            $url = 'Observation';

            $dataKarcis = DB::table('RJ_KARCIS as rk')
                ->select('rk.NOREG as KARCIS', 'rk.IDUNIT', 'rk.KLINIK', 'rk.TGL', 'rk.KDDOK', 'rk.KBUKU')
                ->where('rk.NOREG', $arrParam['karcis'])
                ->where('rk.IDUNIT', $id_unit)
                ->orderBy('rk.TGL', 'DESC')
                ->first();

            $dataPeserta = DB::table('RIRJ_MASTERPX')
                ->where('KBUKU', $dataKarcis->KBUKU)
                ->first();

            // Dispatch all observations
            foreach ($payloadObservations as $obs) {
                if ($resend) {
                    $dataObs = SATUSEHAT_OBSERVATION::where('karcis', $arrParam['karcis'])
                        ->where('kbuku', $arrParam['kbuku'])
                        ->where('id_erm', $dataErm->NOMOR)
                        ->where('jenis', $obs['type'])
                        ->first();

                    $url .= '/' . ($dataObs ? $dataObs->ID_SATUSEHAT_OBSERVASI : '');
                }

                SendObservationToSATUSEHAT::dispatch($obs['payload'], $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, $obs['type'], $resend);
                $url = 'Observation';
            }

            return response()->json([
                'status' => JsonResponse::HTTP_OK,
                'message' => 'Pengiriman Data Observasi Pasien Sedang Diproses oleh sistem',
                'redirect' => [
                    'need' => false,
                    'to' => null,
                ]
            ], 200);
        } catch (Exception $th) {
            return response()->json([
                'status' => [
                    'msg' => $th->getMessage() != '' ? $th->getMessage() : 'Err',
                    'code' => $th->getCode() != '' ? $th->getCode() : 500,
                ],
                'data' => null,
                'err_detail' => $th,
                'message' => $th->getMessage() != '' ? $th->getMessage() : 'Terjadi Kesalahan Saat Kirim Data, Harap Coba lagi!'
            ], $th->getCode() != '' ? $th->getCode() : 500);
        }

        return response()->json([
            'status' => JsonResponse::HTTP_OK,
            'message' => 'Fungsi pengiriman observasi untuk RAWAT_INAP belum diimplementasikan sepenuhnya.',
            'redirect' => [
                'need' => false,
                'to' => null,
            ]
        ], 200);
    }

    public function resendSatuSehat($param)
    {
        return $this->sendSatuSehat($param, true);
    }

    public function bulkSend(Request $request)
    {
        $resp = null;
        foreach ($request->selected_ids as $selected) {
            $param = $selected['param'];
            $resp = $this->sendSatuSehat(base64_encode($param), false);
        }

        return $resp;
    }

    public function receiveSatuSehat(Request $request)
    {
        $this->logInfo('Observation', 'Receive Observation dari SIMRS', [
            'request' => $request->all(),
            'user_id' => 'system'
        ]);

        // QUERY RAWAT JALAN
        $rjQuery = DB::table('v_kunjungan_rj as vkr')
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
            ->where('vkr.ID_TRANSAKSI', $request->karcis)
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
                        AND rso2.ID_SATUSEHAT_OBSERVASI IS NOT NULL) = 5
                    ) THEN 1 ELSE 0 END as sudah_integrasi,
                CASE WHEN MAX(eri.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
            ")
            ->groupBy(['vkr.ID_TRANSAKSI', 'eri.NOMOR'])
            ->first();

        // QUERY RAWAT INAP (Kolom disesuaikan agar UNION tidak error)
        $riQuery = DB::table('v_kunjungan_ri as vkr')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.noreg')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                    ->on('vkr.KBUKU', '=', 'rsn.kbuku');
            })
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI as rso', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rso.KARCIS')
                    ->on('vkr.KBUKU', '=', 'rso.KBUKU');
            })
            ->where('eri.AKTIF', 1)
            ->where('vkr.ID_TRANSAKSI', $request->karcis)
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

        $data = null;
        if (str_contains(strtoupper($request->aktivitas), 'RAWAT JALAN')) {
            $data = $rjQuery;
        } else {
            $data = $riQuery;
        }

        $id_transaksi = LZString::compressToEncodedURIComponent($data->KARCIS);
        $KbBuku = LZString::compressToEncodedURIComponent($data->KBUKU);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $idEncounter = LZString::compressToEncodedURIComponent($data->id_satusehat_encounter);
        $paramSatuSehat = "sudah_integrasi=$data->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter&jenis_perawatan=" . LZString::compressToEncodedURIComponent($data->JENIS_PERAWATAN);
        $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

        if ($data->sudah_integrasi == 0) {
            // Kirim data baru jika encounter belum ada
            $resp = $this->sendSatuSehat(base64_encode($paramSatuSehat), false);
        } else {
            // resend jika data sudah ada
            $resp = $this->sendSatuSehat(base64_encode($paramSatuSehat), true);
        }

        return response()->json([
            'status' => JsonResponse::HTTP_OK,
            'message' => 'Pengiriman Data Encounter Pasien Sedang Diproses oleh sistem',
            'redirect' => [
                'need' => false,
                'to' => null,
            ]
        ], 200);
    }

    private function definePayloadDJ($dataErm, $organisasi)
    {
        $payloadDJ = null;
        if (!empty($dataErm->DJ)) {
            $identifier = now()->timestamp;
            $payloadDJ = [
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/observation/$organisasi",
                        "use" => "official",
                        "value" => "$identifier",
                    ],
                ],
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => '8867-4',
                            'display' => 'Heart rate',
                        ],
                    ],
                ],
                'valueQuantity' => [
                    'value' => (float) $dataErm->DJ,
                    'unit' => 'beats/minute',
                    'system' => 'http://unitsofmeasure.org',
                    'code' => '/min'
                ],
            ];
        }

        return $payloadDJ;
    }

    private function definePayloadTD($dataErm, $organisasi)
    {
        $payloadSystolic = null;
        $payloadDiastolic = null;
        if (!empty($dataErm->TD)) {
            $TD = explode('/', $dataErm->TD);
            $sistolik = $TD[0] ?? null;
            $diastolik = $TD[1] ?? null;

            $identifier = now()->timestamp;
            $payloadSystolic = [
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/observation/$organisasi",
                        "use" => "official",
                        "value" => "$identifier",
                    ],
                ],
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => '8480-6',
                            'display' => 'Systolic blood pressure',
                        ],
                    ],
                ],
                'valueQuantity' => [
                    'value' => (float) $sistolik,
                    'unit' => 'mmHg',
                    'system' => 'http://unitsofmeasure.org',
                    'code' => 'mm[Hg]'
                ],
            ];

            $payloadDiastolic = [
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/observation/$organisasi",
                        "use" => "official",
                        "value" => "$identifier",
                    ],
                ],
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => '8462-4',
                            'display' => 'Diastolic blood pressure',
                        ],
                    ],
                ],
                'valueQuantity' => [
                    'value' => (float) $diastolik,
                    'unit' => 'mmHg',
                    'system' => 'http://unitsofmeasure.org',
                    'code' => 'mm[Hg]'
                ],
            ];
        }

        return [
            $payloadSystolic,
            $payloadDiastolic
        ];
    }

    private function definePayloadTinggi($dataErm, $organisasi)
    {
        $payloadTinggi = null;
        if (!empty($dataErm->TB)) {
            $identifier = now()->timestamp;
            $payloadTinggi = [
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/observation/$organisasi",
                        "use" => "official",
                        "value" => "$identifier",
                    ],
                ],
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => '8302-2',
                            'display' => 'Body height',
                        ],
                    ],
                ],
                'valueQuantity' => [
                    'value' => (float) $dataErm->TB,
                    'unit' => 'cm',
                    'system' => 'http://unitsofmeasure.org',
                    'code' => 'cm'
                ],
            ];
        }

        return $payloadTinggi;
    }

    private function definePayloadBerat($dataErm, $organisasi)
    {
        $payloadBerat = null;
        if (!empty($dataErm->BB)) {
            $identifier = now()->timestamp;
            $payloadBerat = [
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/observation/$organisasi",
                        "use" => "official",
                        "value" => "$identifier",
                    ],
                ],
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => '29463-7',
                            'display' => 'Body weight',
                        ],
                    ],
                ],
                'valueQuantity' => [
                    'value' => (float) $dataErm->BB,
                    'unit' => 'kg',
                    'system' => 'http://unitsofmeasure.org',
                    'code' => 'kg'
                ],
            ];
        }

        return $payloadBerat;
    }

    private function definePayloadSuhu($dataErm, $organisasi)
    {
        $payloadSuhu = null;
        if (!empty($dataErm->SUHU)) {
            $identifier = now()->timestamp;
            $payloadSuhu = [
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/observation/$organisasi",
                        "use" => "official",
                        "value" => "$identifier",
                    ],
                ],
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => '8310-5',
                            'display' => 'Body Temperature',
                        ],
                    ],
                ],
                'valueQuantity' => [
                    'value' => (float) $dataErm->SUHU,
                    "unit" => "Cel",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "Cel"
                ],
            ];
        }

        return $payloadSuhu;
    }

    private function definePayloadP($dataErm, $organisasi)
    {
        $payloadP = null;
        if (!empty($dataErm->P)) {
            $identifier = now()->timestamp;
            $payloadP = [
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/observation/$organisasi",
                        "use" => "official",
                        "value" => "$identifier",
                    ],
                ],
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => '9279-1',
                            'display' => 'Respiratory rate',
                        ],
                    ],
                ],
                'valueQuantity' => [
                    'value' => (float) $dataErm->P,
                    "unit" => "breaths/min",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "/min"
                ],
            ];
        }

        return $payloadP;
    }

    private function definePayloadExam($dataErm, $organisasi, $key)
    {
        $payloadP = null;
        if (!empty($dataErm->$key)) {
            // Map exam keys to codes and display texts
            $codeMap = [
                'MATA' => '10197-2',
                'KEPALA' => '10199-8',
                'RAMBUT' => '32436-8',
                'MUKA' => '32432-7',
                'TELINGA' => '10195-6',
                'HIDUNG' => '10203-8',
                'MULUT' => '10201-2',
                'GIGI' => '85910-8',
                'LIDAH' => '32483-0',
                'TENGGOROKAN' => '56867-5',
                'LEHER' => '11411-6',
                'DADA' => '11391-0',
            ];

            $displayMap = [
                'MATA' => 'Physical findings of Eye Narrative',
                'KEPALA' => 'Physical findings of Head Narrative',
                'RAMBUT' => 'Physical findings of Hair',
                'MUKA' => 'Physical findings of Face',
                'TELINGA' => 'Physical findings of Ear Narrative',
                'HIDUNG' => 'Physical findings of Nose Narrative',
                'MULUT' => 'Physical findings of Mouth and Throat and Teeth Narrative',
                'GIGI' => 'Physical findings of Teeth and gum Narrative',
                'LIDAH' => 'Physical findings of Tongue',
                'TENGGOROKAN' => 'Physical findings of Throat Narrative',
                'LEHER' => 'Physical findings of Neck Narrative',
                'DADA' => 'Physical findings of Chest Narrative',
            ];

            $code = $codeMap[$key] ?? '00000-0';
            $display = $displayMap[$key] ?? 'General examination finding';

            $identifier = now()->timestamp;
            // For exam findings, use valueString to represent textual findings
            $payloadP = [
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/observation/$organisasi",
                        "use" => "official",
                        "value" => "$identifier",
                    ],
                ],
                'code' => [
                    'coding' => [
                        [
                            // use SNOMED or appropriate system for these codes; keeping original codes
                            'system' => 'http://snomed.info/sct',
                            'code' => $code,
                            'display' => $display,
                        ],
                    ],
                ],
                'valueString' => (string) $dataErm->$key,
            ];
        }

        return $payloadP;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
