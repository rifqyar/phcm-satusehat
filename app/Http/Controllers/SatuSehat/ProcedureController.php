<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Jobs\SendProcedureToSATUSEHAT;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\SATUSEHAT\SATUSEHAT_PROCEDURE;
use App\Models\SATUSEHAT\SS_Kode_API;
use App\Models\SATUSEHAT\SS_Nakes;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ProcedureController extends Controller
{
    use SATUSEHATTraits, LogTraits;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $startDate = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
        $endDate   = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

        $data = DB::table('v_kunjungan_rj as vkr')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RM_IRJA as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.KARCIS')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                    ->on('vkr.KBUKU', '=', 'rsn.kbuku');
            })
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE as rsp', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsp.KARCIS')
                    ->on('vkr.KBUKU', '=', 'rsp.KBUKU');
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
                MAX(rsp.ID_SATUSEHAT_PROCEDURE) as ID_SATUSEHAT_PROCEDURE,
                'RAWAT_JALAN' AS JENIS_PERAWATAN,
                CASE
                    WHEN
                        (
                            NOT EXISTS (
                                SELECT 1 FROM E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI op
                                WHERE op.KARCIS = vkr.ID_TRANSAKSI
                            )
                            OR EXISTS (
                                SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p1
                                WHERE p1.KARCIS = vkr.ID_TRANSAKSI
                                AND p1.JENIS_TINDAKAN = 'operasi'
                                AND p1.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND
                        (
                            NOT EXISTS (
                                SELECT 1 FROM vw_getData_Elab lab
                                WHERE lab.KARCIS_ASAL = vkr.ID_TRANSAKSI
                                AND lab.KLINIK_TUJUAN = '0017'
                            )
                            OR EXISTS (
                                SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p2
                                WHERE p2.KARCIS = vkr.ID_TRANSAKSI
                                AND p2.JENIS_TINDAKAN = 'lab'
                                AND p2.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND
                        (
                            NOT EXISTS (
                                SELECT 1 FROM vw_getData_Elab rad
                                WHERE rad.KARCIS_ASAL = vkr.ID_TRANSAKSI
                                AND rad.KLINIK_TUJUAN = '0015' OR rad.KLINIK_TUJUAN = '0016'
                            )
                            OR EXISTS (
                                SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p3
                                WHERE p3.KARCIS = vkr.ID_TRANSAKSI
                                AND p3.JENIS_TINDAKAN = 'rad'
                                AND p3.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND
                        (
                            EXISTS (
                                SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p4
                                WHERE p4.KARCIS = vkr.ID_TRANSAKSI
                                AND p4.JENIS_TINDAKAN = 'anamnese'
                                AND p4.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                    THEN 1
                    ELSE 0
                END AS sudah_integrasi,
                CASE WHEN MAX(eri.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
            ")
            ->groupBy('vkr.ID_TRANSAKSI')
            ->orderByDesc(DB::raw('MAX(vkr.TANGGAL)'))
            ->get();

        $dataRi = DB::table('v_kunjungan_ri as vkr')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.NOREG')
            ->leftJoin('vw_getData_Elab as ere', 'eri.NOREG', '=', 'ere.KARCIS_ASAL')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI as erflo', 'eri.NOREG', '=', 'erflo.NOREG')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                    ->on('vkr.KBUKU', '=', 'rsn.kbuku');
            })
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE as rsp', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsp.KARCIS')
                    ->on('vkr.KBUKU', '=', 'rsp.KBUKU');
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
                MAX(rsp.ID_SATUSEHAT_PROCEDURE) as ID_SATUSEHAT_PROCEDURE,
                'RAWAT_INAP' AS JENIS_PERAWATAN,

                CASE
                    WHEN
                    (
                        NOT EXISTS (
                            SELECT 1 FROM E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI op
                            WHERE op.KARCIS = vkr.ID_TRANSAKSI
                        )
                        OR EXISTS (
                            SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p1
                            WHERE p1.KARCIS = vkr.ID_TRANSAKSI
                            AND p1.JENIS_TINDAKAN = 'operasi'
                            AND p1.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                        )
                    )
                    AND
                    (
                        NOT EXISTS (
                            SELECT 1 FROM vw_getData_Elab lab
                            WHERE lab.KARCIS_ASAL = vkr.ID_TRANSAKSI
                            AND lab.KLINIK_TUJUAN = '0017'
                        )
                        OR EXISTS (
                            SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p2
                            WHERE p2.KARCIS = vkr.ID_TRANSAKSI
                            AND p2.JENIS_TINDAKAN = 'lab'
                            AND p2.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                        )
                    )
                    AND
                    (
                        NOT EXISTS (
                            SELECT 1 FROM vw_getData_Elab rad
                            WHERE rad.KARCIS_ASAL = vkr.ID_TRANSAKSI
                            AND rad.KLINIK_TUJUAN = '0015' OR rad.KLINIK_TUJUAN = '0016'
                        )
                        OR EXISTS (
                            SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p3
                            WHERE p3.KARCIS = vkr.ID_TRANSAKSI
                            AND p3.JENIS_TINDAKAN = 'rad'
                            AND p3.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                        )
                    )
                    AND
                    (
                        EXISTS (
                            SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p4
                            WHERE p4.KARCIS = vkr.ID_TRANSAKSI
                            AND p4.JENIS_TINDAKAN = 'anamnese'
                            AND p4.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                        )
                    )
                THEN 1
                ELSE 0
                END AS sudah_integrasi,

                CASE WHEN MAX(eri.NOREG) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
            ")
            ->groupBy('vkr.ID_TRANSAKSI')
            ->orderByDesc(DB::raw('MAX(vkr.TANGGAL)'))
            ->get();

        $mergedAll = $data->merge($dataRi)
            ->sortByDesc('TANGGAL')
            ->values();

        $totalAll = $mergedAll->count();
        $totalSudahIntegrasi = $mergedAll->where('sudah_integrasi', 1)->count();
        $totalBelumIntegrasi = $totalAll - $totalSudahIntegrasi;

        $result = [
            'total_semua' => $totalAll,
            'total_sudah_integrasi' => $totalSudahIntegrasi,
            'total_belum_integrasi' => $totalBelumIntegrasi,
            'total_rawat_jalan' => $mergedAll->where('JENIS_PERAWATAN', 'RAWAT_JALAN')->count(),
            'total_rawat_inap' => $mergedAll->where('JENIS_PERAWATAN', 'RAWAT_INAP')->count(),
        ];

        return view('pages.satusehat.procedure.index', compact('result'));
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $id_unit = Session::get('id_unit', '001');

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

        $dataQuery = DB::table('v_kunjungan_rj as vkr')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RM_IRJA as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.KARCIS')
            ->leftJoin('vw_getData_Elab as ere', 'eri.KARCIS', 'ere.KARCIS_ASAL')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI as erflo', 'eri.KARCIS', 'erflo.KARCIS')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                    ->on('vkr.KBUKU', '=', 'rsn.kbuku');
            })
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE as rsp', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsp.KARCIS')
                    ->on('vkr.KBUKU', '=', 'rsp.KBUKU');
            })
            ->where('eri.AKTIF', 1)
            ->whereBetween('vkr.TANGGAL', [$tgl_awal_db, $tgl_akhir_db])
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
                MAX(rsp.ID_SATUSEHAT_PROCEDURE) as ID_SATUSEHAT_PROCEDURE,
                'RAWAT_JALAN' AS JENIS_PERAWATAN,
                CASE
                    WHEN
                        NOT (
                            EXISTS (
                                SELECT 1
                                FROM E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI op
                                WHERE op.KARCIS = vkr.ID_TRANSAKSI
                            )
                            AND NOT EXISTS (
                                SELECT 1
                                FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p
                                WHERE p.KARCIS = vkr.ID_TRANSAKSI
                                AND p.JENIS_TINDAKAN = 'operasi'
                                AND p.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND NOT (
                            EXISTS (
                                SELECT 1
                                FROM vw_getData_Elab lab
                                WHERE lab.KARCIS_ASAL = vkr.ID_TRANSAKSI
                                AND lab.KLINIK_TUJUAN = '0017'
                            )
                            AND NOT EXISTS (
                                SELECT 1
                                FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p
                                WHERE p.KARCIS = vkr.ID_TRANSAKSI
                                AND p.JENIS_TINDAKAN = 'lab'
                                AND p.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND NOT (
                            EXISTS (
                                SELECT 1
                                FROM vw_getData_Elab rad
                                WHERE rad.KARCIS_ASAL = vkr.ID_TRANSAKSI
                                AND rad.KLINIK_TUJUAN IN ('0015', '0016')
                            )
                            AND NOT EXISTS (
                                SELECT 1
                                FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p
                                WHERE p.KARCIS = vkr.ID_TRANSAKSI
                                AND p.JENIS_TINDAKAN = 'rad'
                                AND p.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND EXISTS (
                            SELECT 1
                            FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p
                            WHERE p.KARCIS = vkr.ID_TRANSAKSI
                            AND p.JENIS_TINDAKAN = 'anamnese'
                            AND p.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                        )
                    THEN 1
                    ELSE 0
                END AS sudah_integrasi,
                CASE WHEN MAX(eri.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
            ")
            ->groupBy('vkr.ID_TRANSAKSI');

        $riQuery = DB::table('v_kunjungan_ri as vkr')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.NOREG')
            ->leftJoin('vw_getData_Elab as ere', 'eri.NOREG', '=', 'ere.KARCIS_ASAL')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI as erflo', 'eri.NOREG', '=', 'erflo.NOREG')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                    ->on('vkr.KBUKU', '=', 'rsn.kbuku');
            })
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE as rsp', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsp.KARCIS')
                    ->on('vkr.KBUKU', '=', 'rsp.KBUKU');
            })
            ->where('eri.AKTIF', 1)
            ->whereBetween('vkr.TANGGAL', [$tgl_awal_db, $tgl_akhir_db])
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
                MAX(rsp.ID_SATUSEHAT_PROCEDURE) as ID_SATUSEHAT_PROCEDURE,
                'RAWAT_INAP' AS JENIS_PERAWATAN,
                CASE
                    WHEN
                        NOT (
                            EXISTS (
                                SELECT 1
                                FROM E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI op
                                WHERE op.KARCIS = vkr.ID_TRANSAKSI
                            )
                            AND NOT EXISTS (
                                SELECT 1
                                FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p
                                WHERE p.KARCIS = vkr.ID_TRANSAKSI
                                AND p.JENIS_TINDAKAN = 'operasi'
                                AND p.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND NOT (
                            EXISTS (
                                SELECT 1
                                FROM vw_getData_Elab lab
                                WHERE lab.KARCIS_ASAL = vkr.ID_TRANSAKSI
                                AND lab.KLINIK_TUJUAN = '0017'
                            )
                            AND NOT EXISTS (
                                SELECT 1
                                FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p
                                WHERE p.KARCIS = vkr.ID_TRANSAKSI
                                AND p.JENIS_TINDAKAN = 'lab'
                                AND p.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND NOT (
                            EXISTS (
                                SELECT 1
                                FROM vw_getData_Elab rad
                                WHERE rad.KARCIS_ASAL = vkr.ID_TRANSAKSI
                                AND rad.KLINIK_TUJUAN IN ('0015', '0016')
                            )
                            AND NOT EXISTS (
                                SELECT 1
                                FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p
                                WHERE p.KARCIS = vkr.ID_TRANSAKSI
                                AND p.JENIS_TINDAKAN = 'rad'
                                AND p.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND EXISTS (
                            SELECT 1
                            FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p
                            WHERE p.KARCIS = vkr.ID_TRANSAKSI
                            AND p.JENIS_TINDAKAN = 'anamnese'
                            AND p.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                        )
                    THEN 1
                    ELSE 0
                END AS sudah_integrasi,
                CASE WHEN MAX(eri.NOREG) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
            ")
            ->groupBy('vkr.ID_TRANSAKSI');

        $mergedQuery = $dataQuery->unionAll($riQuery);

        $dataAll = DB::query()
            ->fromSub($mergedQuery, 'x')
            ->groupBy([
                'x.JENIS_PERAWATAN',
                'x.SUDAH_INTEGRASI',
                'x.SUDAH_PROSES_DOKTER',
                'x.KARCIS',
                'x.TANGGAL',
                'x.NO_PESERTA',
                'x.KBUKU',
                'x.NAMA_PASIEN',
                'x.DOKTER',
                'x.ID_PASIEN_SS',
                'x.ID_NAKES_SS',
                'x.id_satusehat_encounter',
                'x.ID_SATUSEHAT_PROCEDURE',
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
            $dataAll->whereNotNull('x.ID_SATUSEHAT_PROCEDURE');
        } elseif ($cari === 'unmapped') {
            $dataAll->whereNull('x.ID_SATUSEHAT_PROCEDURE');
        }

        $data = $dataAll->orderByDesc(DB::raw('MAX(x.TANGGAL)'))->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('JENIS_PERAWATAN', function ($row) {
                return $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
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
                $jenisPerawatan = LZString::compressToEncodedURIComponent($row->JENIS_PERAWATAN);
                $paramSatuSehat = "sudah_integrasi=$row->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter&jenis_perawatan=$jenisPerawatan";
                $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

                $param = LZString::compressToEncodedURIComponent("karcis=$id_transaksi&kbuku=$KbBuku&jenis_perawatan=$jenisPerawatan");
                $btn = '';
                if ($row->ID_PASIEN_SS == null) {
                    $btn = '<i class="text-muted">Pasien Belum Mapping</i>';
                } else if ($row->ID_NAKES_SS == null) {
                    $btn .= '<i class="text-muted">Nakes Belum Mapping</i>';
                } else if ($row->id_satusehat_encounter == null) {
                    $btn .= '<i class="text-muted">Encounter Belum Kirim</i>';
                } else {
                    // if ($row->sudah_integrasi == '0') {
                    //     $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                    // } else {
                    //     $btn = '<a href="#" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                    // }
                    $btn .= '<a href="javascript:void(0)" onclick="lihatDetail(`' . $param . '`, `' . $paramSatuSehat . '`)" class="mt-2 btn btn-sm btn-info w-100"><i class="fas fa-info-circle mr-2"></i>Lihat Detail</a>';
                }
                return $btn;
            })
            ->addColumn('status_integrasi', function ($row) {
                if ($row->sudah_integrasi == '0') {
                    $html = '<span class="badge badge-pill badge-danger p-2">Belum Integrasi</span>';
                    $html .= $this->notif($row);
                } else {
                    $html = '<span class="badge badge-pill badge-success p-2">Sudah Integrasi</span>';
                }

                return $html;
            })
            ->rawColumns(['action', 'status_integrasi'])
            ->with($totalData)
            ->make(true);
    }

    private function notif($row)
    {
        $html = '';
        $karcis = $row->KARCIS;
        $sql = " SELECT
                (SELECT COUNT(1)
                FROM E_RM_PHCM.dbo.ERM_RM_IRJA
                WHERE karcis = ? AND AKTIF = 1) AS fisik_total,

                (SELECT COUNT(1)
                FROM E_RM_PHCM.dbo.ERM_RM_IRJA eri
                INNER JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE rsp
                    ON eri.KARCIS = rsp.KARCIS
                AND eri.NOMOR = rsp.ID_JENIS_TINDAKAN
                WHERE eri.KARCIS = ? AND eri.AKTIF = 1) AS fisik_integrated,

                (SELECT COUNT(1)
                FROM SIRS_PHCM.dbo.vw_getData_Elab
                WHERE KARCIS_ASAL = ? AND KLINIK_TUJUAN in ('0017', '0031')) AS lab_total,

                (SELECT COUNT(1)
                FROM SIRS_PHCM.dbo.vw_getData_Elab vgde
                INNER JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE rsp
                    ON vgde.KARCIS_ASAL = rsp.KARCIS
                AND vgde.ID_RIWAYAT_ELAB = rsp.ID_JENIS_TINDAKAN
                AND rsp.JENIS_TINDAKAN = 'lab'
                WHERE vgde.KARCIS_ASAL = ? AND KLINIK_TUJUAN in ('0017', '0031')) AS lab_integrated,

                (SELECT COUNT(1)
                FROM SIRS_PHCM.dbo.vw_getData_Elab
                WHERE KARCIS_ASAL = ? AND KLINIK_TUJUAN in (SELECT KODE_KLINIK
                                FROM SIRS_PHCM..RJ_KLINIK_RADIOLOGI)) AS rad_total,

                (SELECT COUNT(1)
                FROM SIRS_PHCM.dbo.vw_getData_Elab vr
                INNER JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE rsp
                    ON vr.KARCIS_ASAL = rsp.KARCIS
                AND vr.ID_RIWAYAT_ELAB = rsp.ID_JENIS_TINDAKAN
                AND rsp.JENIS_TINDAKAN = 'rad'
                WHERE vr.KARCIS_ASAL = ? AND KLINIK_TUJUAN IN (SELECT KODE_KLINIK
                                FROM SIRS_PHCM..RJ_KLINIK_RADIOLOGI)) AS rad_integrated
            ";

        $result = DB::selectOne($sql, [
            $karcis, // fisik_total
            $karcis, // fisik_integrated
            $karcis, // lab_total
            $karcis, // lab_integrated
            $karcis, // rad_total
            $karcis, // rad_integrated
        ]);

        $totalAllTindakan = $result->fisik_total + $result->lab_total + $result->rad_total;
        $totalAllIntegrated = $result->fisik_integrated + $result->lab_integrated + $result->rad_integrated;

        $colorStatus = $totalAllTindakan == $totalAllIntegrated ? 'text-success' : 'text-danger';
        $colorLab = $result->lab_total == $result->lab_integrated ? 'text-success' : 'text-danger';
        $colorRad = $result->rad_total == $result->rad_integrated ? 'text-success' : 'text-danger';
        $html .= "<br> <i class='small $colorStatus'>$totalAllIntegrated / $totalAllTindakan Tindakan Terintegrasi</i>";
        $html .= "<br> <i class='small $colorLab'>$result->lab_total Tindakan Lab</i>";
        $html .= "<br> <i class='small $colorRad'>$result->rad_total Tindakan Radiologi</i>";

        return $html;
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
                ->select([
                    'vkr.ID_TRANSAKSI',
                    'vkr.NAMA_PASIEN',
                    'vkr.TANGGAL',
                    'eri.KODE_DIAGNOSA_UTAMA',
                    'eri.DIAG_UTAMA',
                    'eri.KODE_DIAGNOSA_SEKUNDER',
                    'eri.DIAG_SEKUNDER',
                    'eri.KODE_DIAGNOSA_KOMPLIKASI',
                    'eri.DIAG_KOMPLIKASI',
                    'eri.KODE_DIAGNOSA_PENYEBAB',
                    'eri.PENYEBAB',
                    'eri.ANAMNESE',
                    'eri.BB',
                    'eri.TB',
                    'eri.DJ',
                    'eri.TD',
                    'eri.CRTUSR',
                    'eri.CRTDT',
                    'eri.NOMOR'
                ])
                ->leftJoin('E_RM_PHCM.dbo.ERM_RM_IRJA as eri', function ($join) {
                    $join->on('vkr.ID_TRANSAKSI', '=', 'eri.KARCIS');
                })
                ->where('vkr.KBUKU', $arrParam['kbuku'])
                ->where('vkr.ID_TRANSAKSI', $arrParam['karcis'])
                ->where('eri.AKTIF', 1)
                ->orderByDesc('vkr.TANGGAL')
                ->first();

            $dataErm->jenis_perawatan = 'RJ';
        } else if ($arrParam['jenis_perawatan'] == 'RAWAT_INAP') {
            $dataErm = DB::table('v_kunjungan_ri as vkr')
                ->selectRaw("
                    MAX(vkr.ID_TRANSAKSI) AS ID_TRANSAKSI,
                    MAX(vkr.TANGGAL) AS TANGGAL,
                    MAX(vkr.NO_PESERTA) AS NO_PESERTA,
                    MAX(vkr.KBUKU) AS KBUKU,
                    MAX(vkr.NAMA_PASIEN) AS NAMA_PASIEN,
                    MAX(vkr.DOKTER) AS DOKTER,
                    MAX(vkr.ID_PASIEN_SS) AS ID_PASIEN_SS,
                    MAX(vkr.ID_NAKES_SS) AS ID_NAKES_SS,
                    MAX(h.nmDok) as CRTUSR,
                    MAX(d.td) AS TD,
                    MAX(d.suhu) AS SUHU,
                    MAX(d.p) AS P,
                    MAX(d.nadi) AS NADI,
                    MAX(d.bb) AS BB,
                    MAX(d.tb) AS TB,
                    MAX(eri2.KODE_DIAGNOSA_UTAMA) as KODE_DIAGNOSA_UTAMA,
                    MAX(eri2.DIAG_UTAMA) as DIAG_UTAMA,
                    MAX(eri2.KODE_DIAGNOSA_SEKUNDER) as KODE_DIAGNOSA_SEKUNDER,
                    MAX(eri2.DIAG_SEKUNDER) as DIAG_SEKUNDER,
                    MAX(eri2.KODE_DIAGNOSA_KOMPLIKASI) as KODE_DIAGNOSA_KOMPLIKASI,
                    MAX(eri2.DIAG_KOMPLIKASI) as DIAG_KOMPLIKASI,
                    MAX(eri2.KODE_DIAGNOSA_PENYEBAB) as KODE_DIAGNOSA_PENYEBAB,
                    MAX(eri2.PENYEBAB) as PENYEBAB,
                    MAX(eri2.ANAMNESE) as ANAMNESE,
                    MAX(h.id_asuhan_header) AS NOMOR
                ")
                ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD as h', function ($join) {
                    $join->on('vkr.ID_TRANSAKSI', '=', 'h.noreg')
                        ->on('vkr.KBUKU', '=', 'h.norm');
                })
                ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_PENGKAJIAN_FISIK as d', 'h.id_asuhan_header', '=', 'd.id_asuhan_header')
                ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI as rso', function ($join) {
                    $join->on('vkr.ID_TRANSAKSI', '=', 'rso.KARCIS')
                        ->on('vkr.KBUKU', '=', 'rso.KBUKU');
                })
                ->leftjoin('RJ_KARCIS as rk', 'rk.NOREG', 'vkr.ID_TRANSAKSI')
                ->leftjoin('E_RM_PHCM.dbo.ERM_RM_IRJA as eri2', 'eri2.KARCIS', 'rk.KARCIS')
                ->where('vkr.KBUKU', $arrParam['kbuku'])
                ->where('vkr.ID_TRANSAKSI', $arrParam['karcis'])
                ->where('h.aktif', 1)
                ->first();
            $dataErm->jenis_perawatan = 'RJ';
        }

        if ($arrParam['jenis_perawatan'] == 'RAWAT_JALAN') {
            $ermTable = 'ERM_RM_IRJA';
            $karcisField = 'eri.KARCIS';
        } else {
            $ermTable = 'ERM_RI_F_ASUHAN_KEP_AWAL_HEAD';
            $karcisField = 'eri.NOREG';
        }
        // GET DATA ELAB
        $dataLab = DB::table('E_RM_PHCM.dbo.' . $ermTable . ' as eri')
            ->select([
                'ere.ID_RIWAYAT_ELAB',
                $karcisField,
                // 'eri.ANAMNESE',
                // 'ere.ARRAY_TINDAKAN',
                'ere.TANGGAL_ENTRI',
                'rmt.KD_TIND',
                'rmt.NM_TIND',
                'smsc.ICD9',
                'smsc.ICD9_TEXT',
            ])
            ->leftJoin('vw_getData_Elab as ere', $karcisField, 'ere.KARCIS_ASAL')
            // ->leftJoin('vw_getData_Elab_DETAIL as ered', 'ere.ID_RIWAYAT_ELAB', 'ered.ID_RIWAYAT_ELAB')
            ->leftJoin('RIRJ_MTINDAKAN as rmt', 'ere.KD_TINDAKAN', 'rmt.KD_TIND')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as smsc', 'rmt.NM_TIND', 'smsc.NM_TIND')
            ->where('eri.AKTIF', 1)
            ->where('ere.KLINIK_TUJUAN', '0017')
            ->where($karcisField, $arrParam['karcis'])
            ->get();

        // GET DATA ERAD
        $dataRad = DB::table('E_RM_PHCM.dbo.' . $ermTable . ' as eri')
            ->select([
                'ere.ID_RIWAYAT_ELAB',
                $karcisField,
                // 'eri.ANAMNESE',
                // 'ere.ARRAY_TINDAKAN',
                'ere.TANGGAL_ENTRI',
                'rmt.KD_TIND',
                'rmt.NM_TIND',
                'smsc.ICD9',
                'smsc.ICD9_TEXT',
            ])
            ->leftJoin('vw_getData_Elab as ere', $karcisField, 'ere.KARCIS_ASAL')
            // ->leftJoin('vw_getData_Elab_DETAIL as ered', 'ere.ID_RIWAYAT_ELAB', 'ered.ID_RIWAYAT_ELAB')
            ->leftJoin('RIRJ_MTINDAKAN as rmt', 'ere.KD_TINDAKAN', 'rmt.KD_TIND')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as smsc', 'rmt.NM_TIND', 'smsc.NM_TIND')
            ->where('eri.AKTIF', 1)
            ->where(function ($query) {
                $query->where('ere.KLINIK_TUJUAN', '0016')
                    ->orWhere('ere.KLINIK_TUJUAN', '0015');
            })
            ->where($karcisField, $arrParam['karcis'])
            ->get();

        // Pluck array tindakan untuk parameter where in
        // $kdTindakanLab = $dataLab
        //     ->pluck('ARRAY_TINDAKAN')
        //     ->filter()
        //     ->flatMap(function ($item) {
        //         return explode(',', $item);
        //     })
        //     ->filter()
        //     ->unique()
        //     ->values();

        // $kdTindakanRad = $dataRad
        //     ->pluck('ARRAY_TINDAKAN')
        //     ->filter()
        //     ->flatMap(function ($item) {
        //         return explode(',', $item);
        //     })
        //     ->map('trim')
        //     ->filter()
        //     ->unique()
        //     ->values();

        // Ambil data nama tindakan lab & radiologi

        $tindakanLab = $dataLab; //DB::table('RIRJ_MTINDAKAN')->whereIn('KD_TIND', $kdTindakanLab)->get();
        $tindakanRad = $dataRad; //DB::table('RIRJ_MTINDAKAN')->whereIn('KD_TIND', $kdTindakanRad)->get();

        $dataTindOp = DB::table('E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI as erflo')
            ->where('erflo.KARCIS', $arrParam['karcis'])
            ->get();

        $dataICDAnamnese = SATUSEHAT_PROCEDURE::where('KARCIS', $arrParam['karcis'])->where('ID_JENIS_TINDAKAN', $dataErm->NOMOR)->get();
        $statusIntegrasiAnamnese = $dataICDAnamnese->whereNotNull('ID_SATUSEHAT_PROCEDURE')->count();

        $dataICDLab = SATUSEHAT_PROCEDURE::where('KARCIS', $arrParam['karcis'])
            ->whereIn('ID_JENIS_TINDAKAN', $dataLab->pluck('ID_RIWAYAT_ELAB')->toArray() ?? null)
            ->get();

        $statusIntegrasiLab = $dataICDLab->whereNotNull('ID_SATUSEHAT_PROCEDURE')->count();

        $dataICDRad = SATUSEHAT_PROCEDURE::where('KARCIS', $arrParam['karcis'])
            ->where('ID_JENIS_TINDAKAN', $dataRad->first()->ID_RIWAYAT_ELAB ?? 0)
            ->get();
        $statusIntegrasiRad = $dataICDRad->whereNotNull('ID_SATUSEHAT_PROCEDURE')->count();

        $dataICDOp = SATUSEHAT_PROCEDURE::where('KARCIS', $arrParam['karcis'])
            ->where('ID_JENIS_TINDAKAN', $dataTindOp->first()->id_lap_operasi ?? null)
            ->get();
        $statusIntegrasiOp = $dataICDOp->whereNotNull('ID_SATUSEHAT_PROCEDURE')->count();

        return response()->json([
            'status' => JsonResponse::HTTP_OK,
            'message' => 'OK',
            'data' => [
                'dataErm' => $dataErm,
                'dataPasien' => $dataPasien,
                'dataLab' => $dataLab,
                'dataRad' => $dataRad,
                'tindakanLab' => $tindakanLab,
                'tindakanRad' => $tindakanRad,
                'tindakanOp' => $dataTindOp,
                'statusIntegrasiAnamnese' => $statusIntegrasiAnamnese,
                'statusIntegrasiLab' => $statusIntegrasiLab,
                'statusIntegrasiRad' => $statusIntegrasiRad,
                'statusIntegrasiOp' => $statusIntegrasiOp,
                'dataICD' => [
                    'pemeriksaanfisik' => $dataICDAnamnese,
                    'lab' => $dataICDLab,
                    'rad' => $dataICDRad,
                    'operasi' => $dataICDOp
                ],
            ],
            'redirect' => [
                'need' => false,
                'to' => null,
            ]
        ], 200);
    }

    public function getICD9(Request $request)
    {
        $param = strtoupper($request->search);
        $dataICD9 = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_ICD9')
            ->where(DB::raw('UPPER(CODE)'), 'like', "%$param%")
            ->orWhere(DB::raw('UPPER(NAME)'), 'like', "%$param%")
            ->limit(50)
            ->get();

        return response()->json($dataICD9);
    }

    public function sendSatuSehat(Request $request, $resend = false, $type = 'all')
    {
        $params = LZString::decompressFromEncodedURIComponent($request->param);
        $parts = explode('&', $params);

        $arrParam = [];
        $partsParam = explode('=', $parts[0]);
        $arrParam[$partsParam[0]] = $partsParam[1];
        for ($i = 1; $i < count($parts); $i++) {
            $partsParam = explode('=', $parts[$i]);
            $key = $partsParam[0];
            $val = $partsParam[1];
            $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
        }
        $id_unit = Session::get('id_unit', '001');

        /**
         * TO DO
         * Get Data ERM IRJA
         * Get Data Lab
         * Get Data Rad
         * Get Data Service Request
         * Get Data Operasi
         * 1. Buat Payload Procedure Pemeriksaan fisik
         * 2. Buat Payload Procedure Lab jika ada
         * 3. Buat Payload Procedure Rad jika ada
         * 4. Buat Payload Procedure OP jika ada
         * 5. Buat Queue Pengiriman ke satu sehat
         */

        if ($arrParam['jenis_perawatan'] == 'RAWAT_JALAN') {
            $dataErm = DB::table('E_RM_PHCM.dbo.ERM_RM_IRJA as eri')
                ->select([
                    'eri.NOMOR',
                    'eri.KODE_DIAGNOSA_UTAMA',
                    'eri.DIAG_UTAMA',
                    'eri.KODE_DIAGNOSA_SEKUNDER',
                    'eri.DIAG_SEKUNDER',
                    'eri.KODE_DIAGNOSA_KOMPLIKASI',
                    'eri.DIAG_KOMPLIKASI',
                    'eri.KODE_DIAGNOSA_PENYEBAB',
                    'eri.PENYEBAB',
                    'eri.CRTDT'
                ])
                ->where('karcis', $arrParam['karcis'])
                ->where('eri.aktif', 1)
                ->first();
        } else {
            $dataErm = DB::table('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD as eri')
                ->leftjoin('RJ_KARCIS as rk', 'rk.NOREG', 'eri.noreg')
                ->leftjoin('E_RM_PHCM.dbo.ERM_RM_IRJA as eri2', 'eri2.KARCIS', 'rk.KARCIS')
                ->select([
                    'eri.ID_ASUHAN_HEADER as NOMOR',
                    'eri.KODE_DIAGNOSA_UTAMA',
                    'eri.DIAG_UTAMA',
                    'eri.KODE_DIAGNOSA_SEKUNDER',
                    'eri.DIAG_SEKUNDER',
                    'eri.KODE_DIAGNOSA_KOMPLIKASI',
                    'eri.DIAG_KOMPLIKASI',
                    'eri.KODE_DIAGNOSA_PENYEBAB',
                    'eri.PENYEBAB',
                    'eri2.DIAG_UTAMA',
                    'eri.CRT_DT as CRTDT'
                ])
                ->where('eri.noreg', $arrParam['karcis'])
                ->where('eri.aktif', 1)
                ->first();
        }

        $patient = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN')
            ->where('idpx', $arrParam['id_pasien_ss'])
            ->first();

        $encounter = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
            ->where('id_satusehat_encounter', $arrParam['encounter_id'])
            ->first();

        $baseurl = '';
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

        try {
            $dataKarcis = DB::table('RJ_KARCIS as rk')
                ->select('rk.KARCIS', 'rk.IDUNIT', 'rk.KLINIK', 'rk.TGL', 'rk.KDDOK', 'rk.KBUKU', 'rk.NOREG')
                ->where($arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? 'rk.KARCIS' : 'rk.NOREG', $arrParam['karcis'])
                ->where('rk.IDUNIT', $id_unit)
                ->orderBy('rk.TGL', 'DESC')
                ->first();

            $dataPeserta = DB::table('RIRJ_MASTERPX')
                ->where('KBUKU', $dataKarcis->KBUKU)
                ->first();

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }

            $token = $login['response']['token'];

            if (!$resend) {
                $url = 'Procedure';
                switch ($type) {
                    case 'anamnese':
                        $payloadPemeriksaanFisik = $this->definePayloadAnamnese($arrParam, $patient, $request, $dataErm, $resend);
                        count($payloadPemeriksaanFisik['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadPemeriksaanFisik, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'anamnese')->onQueue('procedure');
                        break;
                    case 'lab':
                        $payloadLab = $this->definePayloadLab($arrParam, $patient, $request, $dataErm, $resend);
                        count($payloadLab['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadLab, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'lab')->onQueue('procedure');
                        break;
                    case 'rad':
                        $payloadRad = $this->definePayloadRad($arrParam, $patient, $request, $dataErm, $resend);
                        count($payloadRad['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadRad, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'rad')->onQueue('procedure');
                        $url = 'Procedure';
                        break;
                    case 'operasi':
                        $payloadOP = $this->definePayloadOp($arrParam, $patient, $request, $dataErm, $resend);
                        count($payloadOP['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadOP, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'operasi')->onQueue('procedure');
                        $url = 'Procedure';
                        break;
                    default:
                        $payloadPemeriksaanFisik = $this->definePayloadAnamnese($arrParam, $patient, $request, $dataErm, $resend);
                        $payloadLab = $this->definePayloadLab($arrParam, $patient, $request, $dataErm, $resend);
                        $payloadRad = $this->definePayloadRad($arrParam, $patient, $request, $dataErm, $resend);
                        $payloadOP = $this->definePayloadOp($arrParam, $patient, $request, $dataErm, $resend);
                        count($payloadPemeriksaanFisik['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadPemeriksaanFisik, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'anamnese')->onQueue('procedure');
                        count($payloadLab['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadLab, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'lab')->onQueue('procedure');
                        count($payloadRad['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadRad, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'rad')->onQueue('procedure');
                        count($payloadOP['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadOP, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'operasi')->onQueue('procedure');
                        break;
                }
            } else {
                switch ($type) {
                    case 'anamnese':
                        $payloadPemeriksaanFisik = $this->definePayloadAnamnese($arrParam, $patient, $request, $dataErm, $resend);
                        $urlAnamnse = 'Procedure/' . $payloadPemeriksaanFisik['currProcedure'];
                        count($payloadPemeriksaanFisik['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadPemeriksaanFisik, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $urlAnamnse, $token, 'anamnese', $resend)->onQueue('procedure');
                        break;
                    case 'lab':
                        $payloadLab = $this->definePayloadLab($arrParam, $patient, $request, $dataErm, $resend);
                        $urlLab = 'Procedure/' . $payloadLab['currProcedure'];
                        count($payloadLab['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadLab, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $urlLab, $token, 'lab', $resend)->onQueue('procedure');
                        break;
                    case 'rad':
                        $payloadRad = $this->definePayloadRad($arrParam, $patient, $request, $dataErm, $resend);
                        $urlRad = 'Procedure/' . $payloadRad['currProcedure'];
                        count($payloadRad['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadRad, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $urlRad, $token, 'rad', $resend)->onQueue('procedure');
                        $url = 'Procedure';
                        break;
                    case 'operasi':
                        $payloadOP = $this->definePayloadOp($arrParam, $patient, $request, $dataErm, $resend);
                        $urlOp = 'Procedure/' . $payloadOP['currProcedure'];
                        count($payloadOP['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadOP, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $urlOp, $token, 'operasi', $resend)->onQueue('procedure');
                        $url = 'Procedure';
                        break;
                    default:
                        $payloadPemeriksaanFisik = $this->definePayloadAnamnese($arrParam, $patient, $request, $dataErm, $resend);
                        $payloadLab = $this->definePayloadLab($arrParam, $patient, $request, $dataErm, $resend);
                        $payloadRad = $this->definePayloadRad($arrParam, $patient, $request, $dataErm, $resend);
                        $payloadOP = $this->definePayloadOp($arrParam, $patient, $request, $dataErm, $resend);

                        $urlAnamnse = 'Procedure/' . $payloadPemeriksaanFisik['currProcedure'];
                        $urlLab = 'Procedure/' . $payloadLab['currProcedure'];
                        $urlRad = 'Procedure/' . $payloadRad['currProcedure'];
                        $urlOp = 'Procedure/' . $payloadOP['currProcedure'];
                        count($payloadPemeriksaanFisik['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadPemeriksaanFisik, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $urlAnamnse, $token, 'anamnese', $resend)->onQueue('procedure');
                        count($payloadLab['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadLab, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $urlLab, $token, 'lab', $resend)->onQueue('procedure');
                        count($payloadRad['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadRad, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $urlRad, $token, 'rad', $resend)->onQueue('procedure');
                        count($payloadOP['payload']) > 0 && SendProcedureToSATUSEHAT::dispatch($payloadOP, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $urlOp, $token, 'operasi', $resend)->onQueue('procedure');
                        break;
                }
            }

            return response()->json([
                'status' => JsonResponse::HTTP_OK,
                'message' => 'Pengiriman Data Procedure Sedang Diproses oleh sistem',
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

    public function resendSatuSehat(Request $request)
    {
        return $this->sendSatuSehat($request, true);
    }

    public function receiveSatuSehat(Request $request)
    {
        $this->logInfo('Procedure', 'Receive Procedure dari SIMRS', [
            'request' => $request->all(),
            'karcis' => $request->karcis,
            'jenis' => $request->jenis,
            'user_id' => 'system'
        ]);

        $data['JALAN'] = DB::table('v_kunjungan_rj as vkr')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RM_IRJA as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.KARCIS')
            // ->leftJoin('vw_getData_Elab as ere', 'eri.KARCIS', 'ere.KARCIS_ASAL')
            ->leftJoin('vw_getData_Elab as ere', 'eri.KARCIS', 'ere.KARCIS_ASAL')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI as erflo', 'eri.KARCIS', 'erflo.KARCIS')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                    ->on('vkr.KBUKU', '=', 'rsn.kbuku');
            })
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE as rsp', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsp.KARCIS')
                    ->on('vkr.KBUKU', '=', 'rsp.KBUKU');
            })
            ->where('eri.AKTIF', 1)
            ->where(function ($query) use ($request) {
                $query->where('vkr.ID_TRANSAKSI', '=', $request->karcis)
                    ->orWhere('ere.KARCIS_RUJUKAN', '=', $request->karcis);
            })
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
                MAX(rsp.ID_SATUSEHAT_PROCEDURE) as ID_SATUSEHAT_PROCEDURE,
                'RAWAT_JALAN' AS JENIS_PERAWATAN,
                CASE
                    WHEN
                        (
                            NOT EXISTS (
                                SELECT 1 FROM E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI op
                                WHERE op.KARCIS = vkr.ID_TRANSAKSI
                            )
                            OR EXISTS (
                                SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p1
                                WHERE p1.KARCIS = vkr.ID_TRANSAKSI
                                AND p1.JENIS_TINDAKAN = 'operasi'
                                AND p1.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND
                        (
                            NOT EXISTS (
                                SELECT 1 FROM vw_getData_Elab lab
                                WHERE lab.KARCIS_ASAL = vkr.ID_TRANSAKSI
                                AND lab.KLINIK_TUJUAN = '0017'
                            )
                            OR EXISTS (
                                SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p2
                                WHERE p2.KARCIS = vkr.ID_TRANSAKSI
                                AND p2.JENIS_TINDAKAN = 'lab'
                                AND p2.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND
                        (
                            NOT EXISTS (
                                SELECT 1 FROM vw_getData_Elab rad
                                WHERE rad.KARCIS_ASAL = vkr.ID_TRANSAKSI
                                AND rad.KLINIK_TUJUAN = '0015' OR rad.KLINIK_TUJUAN = '0016'
                            )
                            OR EXISTS (
                                SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p3
                                WHERE p3.KARCIS = vkr.ID_TRANSAKSI
                                AND p3.JENIS_TINDAKAN = 'rad'
                                AND p3.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                        AND
                        (
                            EXISTS (
                                SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p4
                                WHERE p4.KARCIS = vkr.ID_TRANSAKSI
                                AND p4.JENIS_TINDAKAN = 'anamnese'
                                AND p4.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                            )
                        )
                    THEN 1
                    ELSE 0
                END AS sudah_integrasi,
                CASE WHEN MAX(eri.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
            ")
            ->groupBy('vkr.ID_TRANSAKSI')->first();

        $data['INAP'] = DB::table('v_kunjungan_ri as vkr')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD as eri', 'vkr.ID_TRANSAKSI', '=', 'eri.NOREG')
            ->leftJoin('vw_getData_Elab as ere', 'eri.NOREG', '=', 'ere.KARCIS_ASAL')
            ->leftJoin('E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI as erflo', 'eri.NOREG', '=', 'erflo.NOREG')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsn.karcis')
                    ->on('vkr.KBUKU', '=', 'rsn.kbuku');
            })
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE as rsp', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsp.KARCIS')
                    ->on('vkr.KBUKU', '=', 'rsp.KBUKU');
            })
            ->where('eri.AKTIF', 1)
            ->where(function ($query) use ($request) {
                $query->where('vkr.ID_TRANSAKSI', '=', $request->karcis)
                    ->orWhere('ere.KARCIS_RUJUKAN', '=', $request->karcis);
            })
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
                MAX(rsp.ID_SATUSEHAT_PROCEDURE) as ID_SATUSEHAT_PROCEDURE,
                'RAWAT_INAP' AS JENIS_PERAWATAN,
                CASE
                    WHEN
                    (
                        NOT EXISTS (
                            SELECT 1 FROM E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI op
                            WHERE op.KARCIS = vkr.ID_TRANSAKSI
                        )
                        OR EXISTS (
                            SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p1
                            WHERE p1.KARCIS = vkr.ID_TRANSAKSI
                            AND p1.JENIS_TINDAKAN = 'operasi'
                            AND p1.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                        )
                    )
                    AND
                    (
                        NOT EXISTS (
                            SELECT 1 FROM vw_getData_Elab lab
                            WHERE lab.KARCIS_ASAL = vkr.ID_TRANSAKSI
                            AND lab.KLINIK_TUJUAN = '0017'
                        )
                        OR EXISTS (
                            SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p2
                            WHERE p2.KARCIS = vkr.ID_TRANSAKSI
                            AND p2.JENIS_TINDAKAN = 'lab'
                            AND p2.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                        )
                    )
                    AND
                    (
                        NOT EXISTS (
                            SELECT 1 FROM vw_getData_Elab rad
                            WHERE rad.KARCIS_ASAL = vkr.ID_TRANSAKSI
                            AND rad.KLINIK_TUJUAN = '0015' OR rad.KLINIK_TUJUAN = '0016'
                        )
                        OR EXISTS (
                            SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p3
                            WHERE p3.KARCIS = vkr.ID_TRANSAKSI
                            AND p3.JENIS_TINDAKAN = 'rad'
                            AND p3.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                        )
                    )
                    AND
                    (
                        EXISTS (
                            SELECT 1 FROM SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE p4
                            WHERE p4.KARCIS = vkr.ID_TRANSAKSI
                            AND p4.JENIS_TINDAKAN = 'anamnese'
                            AND p4.ID_SATUSEHAT_PROCEDURE IS NOT NULL
                        )
                    )
                THEN 1
                ELSE 0
                END AS sudah_integrasi,
                CASE WHEN MAX(eri.NOREG) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
            ")
            ->groupBy('vkr.ID_TRANSAKSI')->first();

        $dataKunjungan = null;
        foreach ($data as $key => $value) {
            if (isset($request->jenis_layanan)) {
                if ($key == strtoupper($request->jenis_layanan)) {
                    $dataKunjungan = $value;
                    break;
                }
            } else {
                if ($data[$key] != null) {
                    $dataKunjungan = $value;
                    break;
                }
            }
        }

        if ($dataKunjungan && ($dataKunjungan->id_satusehat_encounter != '' || $dataKunjungan->id_satusehat_encounter != null)) {
            $id_transaksi = LZString::compressToEncodedURIComponent($dataKunjungan->KARCIS);
            $KbBuku = LZString::compressToEncodedURIComponent($dataKunjungan->KBUKU);
            $kdPasienSS = LZString::compressToEncodedURIComponent($dataKunjungan->ID_PASIEN_SS);
            $kdNakesSS = LZString::compressToEncodedURIComponent($dataKunjungan->ID_NAKES_SS);
            $idEncounter = LZString::compressToEncodedURIComponent($dataKunjungan->id_satusehat_encounter);
            $jenisPerawatan = LZString::compressToEncodedURIComponent($dataKunjungan->JENIS_PERAWATAN);
            $paramSatuSehat = "sudah_integrasi=$dataKunjungan->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter&jenis_perawatan=$jenisPerawatan";
            $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

            // get ICD 9 Anamnese
            $icd9 = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_ICD9 as icd9')
                ->where('icd9.ID', $request->diagnosa9cm)
                ->select('icd9.CODE as icd9_pm', 'icd9.NAME as text_icd9_pm')
                ->first();

            $resend = false;
            if ($request->type == 'anamnese') {
                $procedureData = SATUSEHAT_PROCEDURE::where('karcis', (int)$request->karcis)
                    ->where('JENIS_TINDAKAN', 'anamnese')
                    ->count();

                if ($procedureData > 0) {
                    $resend = true;
                }
            } else if ($request->type == 'lab') {
                $dataLab = DB::table('vw_getData_Elab as ere')
                    ->where('ere.KARCIS_RUJUKAN', $request->karcis)
                    ->where('ere.KLINIK_TUJUAN', '0017')
                    ->first();

                $procedureData = SATUSEHAT_PROCEDURE::where('karcis', (int)$request->karcis)
                    ->where('JENIS_TINDAKAN', 'lab')
                    ->where('ID_JENIS_TINDAKAN', $dataLab->ID_RIWAYAT_ELAB)
                    ->count();

                if ($procedureData > 0) {
                    $resend = true;
                }
            } else if ($request->type == 'rad') {
                $dataRad = DB::table('vw_getData_Elab as ere')
                    ->where('ere.KARCIS_RUJUKAN', $request->karcis)
                    ->where(function ($query) {
                        $query->where('ere.KLINIK_TUJUAN', '0016')
                            ->orWhere('ere.KLINIK_TUJUAN', '0015');
                    })
                    ->first();
                $procedureData = SATUSEHAT_PROCEDURE::where('karcis', (int)$request->karcis)
                    ->where('JENIS_TINDAKAN', 'rad')
                    ->where('ID_JENIS_TINDAKAN', $dataRad->ID_RIWAYAT_ELAB)
                    ->count();

                if ($procedureData > 0) {
                    $resend = true;
                }
            } else if ($request->type == 'operasi') {
                $procedureData = SATUSEHAT_PROCEDURE::where('karcis', (int)$request->karcis)
                    ->where('JENIS_TINDAKAN', 'operasi')
                    ->count();

                if ($procedureData > 0) {
                    $resend = true;
                }
            }

            if (($icd9->icd9_pm == '' || $icd9->text_icd9_pm == null) && $request->type == 'anamnese') {
                $this->logInfo('Procedure', 'Data Procedure Anamnese tidak diproses karena tidak ada ICd 9', [
                    'request' => $request->all(),
                    'user_id' => 'system'
                ]);

                return false;
            }

            self::sendSatuSehat(new Request([
                'param' => $paramSatuSehat,
                'icd9_pm' => $icd9->icd9_pm ?? null,
                'text_icd9_pm' => $icd9->text_icd9_pm ?? null
            ]), $resend, $request->type ?? 'all');
        } else {
            $this->logInfo('Procedure', 'Data Procedure tidak diproses karena tidak ada encounter', [
                'request' => $request->all(),
                'user_id' => 'system'
            ]);
        }
    }

    private function definePayloadAnamnese($param, $patient, $request, $dataErm, $resend)
    {
        $nakes = SS_Nakes::where('idnakes', $param['id_nakes_ss'])->first();

        $category = [
            "coding" => [
                [
                    "system" => "http://snomed.info/sct",
                    "code" => "103693007",
                    "display" => "Diagnostic procedure",
                ]
            ],
            "text" => "Diagnostic procedure",
        ];

        $kodeICD = $request->icd9_pm;
        $textICD = $request->text_icd9_pm;
        $code = [
            "coding" => [
                [
                    "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                    "code" => "$kodeICD",
                    "display" => "$textICD",
                ]
            ],
        ];

        $performer = [
            [
                "actor" => [
                    "reference" => "Practitioner/$nakes->idnakes",
                    "display" => "$nakes->nama",
                ],
            ]
        ];

        $kodeDiagnosa = '';
        $textDiagnosa = '';

        switch ($dataErm) {
            case $dataErm->KODE_DIAGNOSA_UTAMA:
                $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_UTAMA;
                $textDiagnosa = $dataErm->DIAG_UTAMA;
                break;
            case $dataErm->KODE_DIAGNOSA_SEKUNDER:
                $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_SEKUNDER;
                $textDiagnosa = $dataErm->DIAG_SEKUNDER;
                break;
            case $dataErm->KODE_DIAGNOSA_KOMPLIKASI:
                $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_KOMPLIKASI;
                $textDiagnosa = $dataErm->DIAG_KOMPLIKASI;
                break;
            case $dataErm->KODE_DIAGNOSA_PENYEBAB:
                $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_PENYEBAB;
                $textDiagnosa = $dataErm->DIAG_PENYEBAB;
                break;
            default:
                $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_UTAMA;
                $textDiagnosa = $dataErm->DIAG_UTAMA;
                break;
        }
        $reasonCode = [
            [
                "coding" => [
                    [
                        "system" => "http://hl7.org/fhir/sid/icd-10",
                        "code" => "$kodeDiagnosa",
                        "display" => "$textDiagnosa",
                    ]
                ],
            ]
        ];

        Carbon::setLocale('id');
        $tglText = Carbon::parse($dataErm->CRTDT)->translatedFormat('l, d F Y');
        $payload = [
            "resourceType" => "Procedure",
            "status" => "completed",
            "category" => $category,
            "code" => $code,
            "subject" => [
                "reference" => "Patient/$patient->idpx",
                "display" => "$patient->nama"
            ],
            "encounter" => [
                "reference" => "Encounter/" . $param['encounter_id'] . "",
                "display" => "Tindakan $textICD pasien A/n $patient->nama pada $tglText"
            ],
            "performer" => $performer,
            "reasonCode" => $reasonCode
        ];

        if ($resend) {
            $currProcedure = SATUSEHAT_PROCEDURE::where('ID_JENIS_TINDAKAN', $dataErm->NOMOR)
                ->where('JENIS_TINDAKAN', 'anamnese')
                ->first();

            $payload['id'] = $currProcedure->ID_SATUSEHAT_PROCEDURE;
        }

        return [
            "payload" => $payload,
            "kddok" => $nakes->kddok,
            "id_tindakan" => $dataErm->NOMOR,
            "kodeICD" => $kodeICD,
            "textICD" => $textICD,
            "currProcedure" => $currProcedure->ID_SATUSEHAT_PROCEDURE ?? null
        ];
    }

    private function definePayloadLab($param, $patient, $request, $dataErm, $resend)
    {
        $dataLab = DB::table('vw_getData_Elab as ere')
            // ->leftJoin('vw_getData_Elab_DETAIL as ered', 'ere.ID_RIWAYAT_ELAB', 'ered.ID_RIWAYAT_ELAB')
            ->leftJoin('RIRJ_MTINDAKAN as rmt', 'ere.KD_TINDAKAN', 'rmt.KD_TIND')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as smsc', 'rmt.NM_TIND', 'smsc.NM_TIND')
            ->leftJoin('RJ_KARCIS as rk', 'rk.KARCIS', 'ere.KARCIS_RUJUKAN')
            ->select([
                'rk.KDDOK as KDDOK',
                'ere.ID_RIWAYAT_ELAB',
                'ere.KD_TINDAKAN',
                'ere.TANGGAL_ENTRI',
                'smsc.ICD9',
                'smsc.ICD9_TEXT',
            ])
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE as rsp', function ($join) {
                $join->on('ere.KD_TINDAKAN', '=', 'rsp.ID_TINDAKAN')
                    ->on('ere.ID_RIWAYAT_ELAB', '=', 'rsp.ID_JENIS_TINDAKAN');
            })
            ->where('ere.KLINIK_TUJUAN', '0017')
            ->where(function ($query) use ($param) {
                $query->where('ere.KARCIS_ASAL', $param['karcis'])
                    ->orWhere('ere.KARCIS_RUJUKAN', '=', $param['karcis']);
            })
            ->where(function ($q) use ($resend) {
                if (!$resend) {
                    $q->whereNull('rsp.ID_TINDAKAN')
                        ->orWhereNull('rsp.ID_SATUSEHAT_PROCEDURE')
                        ->orWhere('rsp.ID_SATUSEHAT_PROCEDURE', '=', '');
                }
            })
            ->get();

        // $icd9Data = json_decode($request->icd9_lab);
        $icd9Data = json_decode($request->icd9_lab, true);
        if (empty($icd9Data)) {
            $icd9Data = $dataLab
                ->filter(function ($row) {
                    return !empty($row->ICD9);
                })
                ->map(function ($row) {
                    return [
                        'icd9'       => $row->ICD9,
                        'text_icd9'  => $row->ICD9_TEXT,
                    ];
                })
                ->values()   // reset index array
                ->toArray();
        }

        if (!empty($dataLab)) {
            $category = [
                "coding" => [
                    [
                        "system" => "http://snomed.info/sct",
                        "code" => "103693007",
                        "display" => "Diagnostic procedure",
                    ]
                ],
                "text" => "Diagnostic procedure",
            ];

            $code = [];
            for ($i = 0; $i < count($dataLab); $i++) {
                $icd9 = $icd9Data[$i]->icd9 ?? $dataLab[$i]->ICD9;
                $texticd9 = $icd9Data[$i]->text_icd9 ?? $dataLab[$i]->ICD9_TEXT;

                if (count($icd9Data) != count($dataLab) || (empty($icd9) || empty($texticd9))) {
                    $code = [];
                    break;
                }

                array_push($code, [
                    "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                    "code" => "$icd9",
                    "display" => "$texticd9",
                ]);
            }

            $performer = [];
            for ($i = 0; $i < count($dataLab); $i++) {
                $nakes = SS_Nakes::where('kddok', $dataLab[$i]->KDDOK)->first();

                $performer[] = [
                    "actor" => [
                        "reference" => "Practitioner/$nakes->idnakes",
                        "display"   => $nakes->nama,
                    ]
                ];
            }

            $kodeDiagnosa = '';
            $textDiagnosa = '';

            switch ($dataErm) {
                case $dataErm->KODE_DIAGNOSA_UTAMA:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_UTAMA;
                    $textDiagnosa = $dataErm->DIAG_UTAMA;
                    break;
                case $dataErm->KODE_DIAGNOSA_SEKUNDER:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_SEKUNDER;
                    $textDiagnosa = $dataErm->DIAG_SEKUNDER;
                    break;
                case $dataErm->KODE_DIAGNOSA_KOMPLIKASI:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_KOMPLIKASI;
                    $textDiagnosa = $dataErm->DIAG_KOMPLIKASI;
                    break;
                case $dataErm->KODE_DIAGNOSA_PENYEBAB:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_PENYEBAB;
                    $textDiagnosa = $dataErm->DIAG_PENYEBAB;
                    break;
                default:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_UTAMA;
                    $textDiagnosa = $dataErm->DIAG_UTAMA;
                    break;
            }
            $reasonCode = [
                [
                    "coding" => [
                        [
                            "system" => "http://hl7.org/fhir/sid/icd-10",
                            "code" => "$kodeDiagnosa",
                            "display" => "$textDiagnosa",
                        ]
                    ],
                ]
            ];

            Carbon::setLocale('id');
            if (count($code) > 0) {
                $payload = [
                    "resourceType" => "Procedure",
                    "status" => "completed",
                    "category" => $category,
                    "code" => [
                        "coding" => $code
                    ],
                    "subject" => [
                        "reference" => "Patient/$patient->idpx",
                        "display" => "$patient->nama"
                    ],
                    // "basedOn" => [
                    //     "reference" => "ServiceRequest/cc52bfcd-6cb2-4c0a-87a7-d5906f74bed9"
                    // ],
                    "encounter" => [
                        "reference" => "Encounter/" . $param['encounter_id'] . "",
                        "display" => "Tindakan Pemeriksaan Lab pasien A/n $patient->nama"
                    ],
                    "performer" => $performer,
                    "reasonCode" => $reasonCode
                ];
            } else {
                $payload = [];
            }

            if ($resend) {
                $currProcedure = SATUSEHAT_PROCEDURE::where('ID_JENIS_TINDAKAN', $dataLab->pluck('ID_RIWAYAT_ELAB')[0])
                    ->where('JENIS_TINDAKAN', 'lab')
                    ->first();

                $payload['id'] = $currProcedure->ID_SATUSEHAT_PROCEDURE;
            }
        }

        return [
            "payload" => $payload ?? [],
            "kddok" => $nakes->kddok ?? null,
            "id_tindakan" => $dataLab->pluck('ID_RIWAYAT_ELAB')->toArray() ?? [],
            "kd_tindakan" => $dataLab->pluck('KD_TINDAKAN')->toArray() ?? [],
            "dataICD" => $icd9Data,
            "currProcedure" => $currProcedure->ID_SATUSEHAT_PROCEDURE ?? null
        ];
    }

    private function definePayloadRad($param, $patient, $request, $dataErm, $resend)
    {
        $dataRad = DB::table('vw_getData_Elab as ere')
            // ->leftJoin('vw_getData_Elab_DETAIL as ered', 'ere.ID_RIWAYAT_ELAB', 'ered.ID_RIWAYAT_ELAB')
            ->leftJoin('RIRJ_MTINDAKAN as rmt', 'ere.KD_TINDAKAN', 'rmt.KD_TIND')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as smsc', 'rmt.NM_TIND', 'smsc.NM_TIND')
            ->leftJoin('RJ_KARCIS as rk', 'rk.KARCIS', 'ere.KARCIS_RUJUKAN')
            ->select([
                'rk.KDDOK as KDDOK',
                'ere.ID_RIWAYAT_ELAB',
                'ere.KD_TINDAKAN',
                'ere.TANGGAL_ENTRI',
                'smsc.ICD9',
                'smsc.ICD9_TEXT',
            ])
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE as rsp', function ($join) {
                $join->on('ere.KD_TINDAKAN', '=', 'rsp.ID_TINDAKAN')
                    ->on('ere.ID_RIWAYAT_ELAB', '=', 'rsp.ID_JENIS_TINDAKAN');
            })
            ->where(function ($query) {
                $query->where('ere.KLINIK_TUJUAN', '0016')
                    ->orWhere('ere.KLINIK_TUJUAN', '0015');
            })
            ->where(function ($query) use ($param) {
                $query->where('ere.KARCIS_ASAL', $param['karcis']);
            })
            ->where(function ($q) use ($resend) {
                if (!$resend) {
                    $q->whereNull('rsp.ID_TINDAKAN')
                        ->orWhereNull('rsp.ID_SATUSEHAT_PROCEDURE')
                        ->orWhere('rsp.ID_SATUSEHAT_PROCEDURE', '=', '');
                }
            })
            ->get();

        // $icd9Data = json_decode($request->icd9_rad);
        $icd9Data = json_decode($request->icd9_lab, true);
        if (empty($icd9Data)) {
            $icd9Data = $dataRad
                ->filter(function ($row) {
                    return !empty($row->ICD9);
                })
                ->map(function ($row) {
                    return [
                        'icd9'       => $row->ICD9,
                        'text_icd9'  => $row->ICD9_TEXT,
                    ];
                })
                ->values()   // reset index array
                ->toArray();
        }
        if (!empty($dataRad)) {
            $category = [
                "coding" => [
                    [
                        "system" => "http://snomed.info/sct",
                        "code" => "103693007",
                        "display" => "Diagnostic procedure",
                    ]
                ],
                "text" => "Diagnostic procedure",
            ];

            $code = [];
            for ($i = 0; $i < count($dataRad); $i++) {
                $icd9 = $icd9Data[$i]->icd9 ?? $dataRad[$i]->ICD9;
                $texticd9 = $icd9Data[$i]->text_icd9 ?? $dataRad[$i]->ICD9_TEXT;

                if (count($icd9Data) != count($dataRad) || (empty($icd9) || empty($texticd9))) {
                    $code = [];
                    break;
                }

                array_push($code, [
                    "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                    "code" => "$icd9",
                    "display" => "$texticd9",
                ]);
            }

            $performer = [];
            for ($i = 0; $i < count($dataRad); $i++) {
                $nakes = SS_Nakes::where('kddok', $dataRad[$i]->KDDOK)->first();

                $performer[] = [
                    "actor" => [
                        "reference" => "Practitioner/$nakes->idnakes",
                        "display"   => $nakes->nama,
                    ]
                ];
            }

            $kodeDiagnosa = '';
            $textDiagnosa = '';

            switch ($dataErm) {
                case $dataErm->KODE_DIAGNOSA_UTAMA:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_UTAMA;
                    $textDiagnosa = $dataErm->DIAG_UTAMA;
                    break;
                case $dataErm->KODE_DIAGNOSA_SEKUNDER:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_SEKUNDER;
                    $textDiagnosa = $dataErm->DIAG_SEKUNDER;
                    break;
                case $dataErm->KODE_DIAGNOSA_KOMPLIKASI:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_KOMPLIKASI;
                    $textDiagnosa = $dataErm->DIAG_KOMPLIKASI;
                    break;
                case $dataErm->KODE_DIAGNOSA_PENYEBAB:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_PENYEBAB;
                    $textDiagnosa = $dataErm->DIAG_PENYEBAB;
                    break;
                default:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_UTAMA;
                    $textDiagnosa = $dataErm->DIAG_UTAMA;
                    break;
            }
            $reasonCode = [
                [
                    "coding" => [
                        [
                            "system" => "http://hl7.org/fhir/sid/icd-10",
                            "code" => "$kodeDiagnosa",
                            "display" => "$textDiagnosa",
                        ]
                    ],
                ]
            ];


            Carbon::setLocale('id');
            if (count($code) > 0) {
                $payload = [
                    "resourceType" => "Procedure",
                    "status" => "completed",
                    "category" => $category,
                    "code" => [
                        "coding" => $code,
                    ],
                    "subject" => [
                        "reference" => "Patient/$patient->idpx",
                        "display" => "$patient->nama"
                    ],
                    "encounter" => [
                        "reference" => "Encounter/" . $param['encounter_id'] . "",
                        "display" => "Tindakan Pemeriksaan Radiologi pasien A/n $patient->nama"
                    ],
                    "performer" => $performer,
                    "reasonCode" => $reasonCode
                ];
            }

            if ($resend) {
                $currProcedure = SATUSEHAT_PROCEDURE::where('ID_JENIS_TINDAKAN', $dataRad->pluck('ID_RIWAYAT_ELAB')[0])
                    ->where('JENIS_TINDAKAN', 'rad')
                    ->first();

                $payload['id'] = $currProcedure->ID_SATUSEHAT_PROCEDURE;
            }
        }

        return [
            "payload" => $payload ?? [],
            "kddok" => $nakes->kddok ?? null,
            "id_tindakan" => $dataRad->pluck('ID_RIWAYAT_ELAB')->toArray() ?? null,
            "kd_tindakan" => $dataRad->pluck('KD_TINDAKAN')->toArray() ?? null,
            "dataICD" => $icd9Data,
            "currProcedure" => $currProcedure->ID_SATUSEHAT_PROCEDURE ?? null
        ];
    }

    private function definePayloadOp($param, $patient, $request, $dataErm, $resend)
    {
        $dataTindOp = DB::table('E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI as erflo')
            ->select('erflo.*')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE as rsp', 'erflo.ID_LAP_OPERASI', '=', 'rsp.ID_JENIS_TINDAKAN')
            ->where('erflo.KARCIS', $param['karcis'])
            ->where(function ($q) use ($resend) {
                if (!$resend) {
                    $q->whereNull('rsp.ID_JENIS_TINDAKAN')
                        ->orWhereNull('rsp.ID_SATUSEHAT_PROCEDURE')
                        ->orWhere('rsp.ID_SATUSEHAT_PROCEDURE', '=', '');
                }
            })
            ->first();

        if (!empty($dataTindOp)) {
            $category = [
                "coding" => [
                    [
                        "system" => "http://snomed.info/sct",
                        "code" => "387713003",
                        "display" => "Surgical procedure",
                    ]
                ],
                "text" => "Surgical procedure",
            ];

            $code = [];
            $icd9 = json_decode($request->icd9_op, true);
            $texticd9 = json_decode($request->text_icd9_op, true);

            for ($i = 0; $i < count($icd9); $i++) {
                array_push($code, [
                    "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                    "code" => "$icd9[$i]",
                    "display" => "$texticd9[$i]",
                ]);
            }

            $nakes = SS_Nakes::where('kddok', $dataTindOp->kddok)->first();
            $performer = [
                [
                    "actor" => [
                        "reference" => "Practitioner/$nakes->idnakes",
                        "display" => "$nakes->nama",
                    ],
                ]
            ];

            $kodeDiagnosa = '';
            $textDiagnosa = '';

            switch ($dataErm) {
                case $dataErm->KODE_DIAGNOSA_UTAMA:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_UTAMA;
                    $textDiagnosa = $dataErm->DIAG_UTAMA;
                    break;
                case $dataErm->KODE_DIAGNOSA_SEKUNDER:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_SEKUNDER;
                    $textDiagnosa = $dataErm->DIAG_SEKUNDER;
                    break;
                case $dataErm->KODE_DIAGNOSA_KOMPLIKASI:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_KOMPLIKASI;
                    $textDiagnosa = $dataErm->DIAG_KOMPLIKASI;
                    break;
                case $dataErm->KODE_DIAGNOSA_PENYEBAB:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_PENYEBAB;
                    $textDiagnosa = $dataErm->DIAG_PENYEBAB;
                    break;
                default:
                    $kodeDiagnosa = $dataErm->KODE_DIAGNOSA_UTAMA;
                    $textDiagnosa = $dataErm->DIAG_UTAMA;
                    break;
            }
            $reasonCode = [
                [
                    "coding" => [
                        [
                            "system" => "http://hl7.org/fhir/sid/icd-10",
                            "code" => "$kodeDiagnosa",
                            "display" => "$textDiagnosa",
                        ]
                    ],
                ]
            ];


            Carbon::setLocale('id');
            $tglText = Carbon::parse($dataTindOp->tanggal_operasi)->translatedFormat('l, d F Y');
            $payload = [
                "resourceType" => "Procedure",
                "status" => "completed",
                "category" => $category,
                "code" => [
                    "coding" => $code
                ],
                "subject" => [
                    "reference" => "Patient/$patient->idpx",
                    "display" => "$patient->nama"
                ],
                "encounter" => [
                    "reference" => "Encounter/" . $param['encounter_id'] . "",
                    "display" => "Tindakan Operasi pasien A/n $patient->nama pada $tglText"
                ],
                "performer" => $performer,
                "reasonCode" => $reasonCode
            ];

            if ($resend) {
                $currProcedure = SATUSEHAT_PROCEDURE::where('ID_JENIS_TINDAKAN', $dataTindOp->id_lap_operasi)
                    ->where('JENIS_TINDAKAN', 'operasi')
                    ->first();

                $payload['id'] = $currProcedure->ID_SATUSEHAT_PROCEDURE;
            }
        }

        return [
            "payload" => $payload ?? [],
            "kddok" => $nakes->kddok ?? null,
            "id_tindakan" => $dataTindOp->id_lap_operasi ?? null,
            "kodeICD" => isset($icd9) ? implode(',', $icd9) : null,
            "textICD" => isset($texticd9) ? implode(',', $texticd9) : null,
            "currProcedure" => $currProcedure->ID_SATUSEHAT_PROCEDURE ?? null
        ];
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
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'icd9' => 'required',
                'text_icd9' => 'required',
            ], [
                'icd9.required' => 'Harap Masukan Kode ICD9',
                'text_icd9.required' => 'Harap Masukan Text ICD9'
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }
            $id_unit = Session::get('id_unit', '001');

            $params = LZString::decompressFromEncodedURIComponent($request->param);
            $parts = explode('&', $params);

            $arrParam = [];
            $partsParam = explode('=', $parts[0]);
            $arrParam[$partsParam[0]] = $partsParam[1];
            for ($i = 1; $i < count($parts); $i++) {
                $partsParam = explode('=', $parts[$i]);
                $key = $partsParam[0];
                $val = $partsParam[1];
                $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
            }

            // Check data ICD 9 di table satusehat
            $type = $request->type;
            $icd9 = '';
            $texticd9 = '';
            switch ($type) {
                case 'pemeriksaanfisik':
                    if ($arrParam['jenis_perawatan'] == 'RAWAT_JALAN') {
                        $table = 'E_RM_PHCM.dbo.ERM_RM_IRJA';
                        $karcisField = "KARCIS";
                        $selectField = "NOMOR";
                        $selectNakes = "";
                    } else {
                        $table = 'E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD';
                        $karcisField = "noreg";
                        $selectField = "id_asuhan_header";
                        $selectNakes = "";
                    }
                    $icd9 = $request->icd9;
                    $texticd9 = $request->text_icd9;
                    $nakes = SS_Nakes::where('idnakes', $arrParam['id_nakes_ss'])->first();
                    break;
                case 'lab':
                    // $table = 'ERM_RIWAYAT_ELAB as ere';
                    $table = 'vw_getData_Elab as ere';
                    $karcisField = "KARCIS_ASAL";
                    $selectField = "ID_RIWAYAT_ELAB";
                    $selectNakes = "KDDOK_TUJUAN";
                    $icd9 = json_decode($request->icd9, true);
                    $texticd9 = json_decode($request->text_icd9, true);
                    break;
                case 'rad':
                    // $table = 'ERM_RIWAYAT_ELAB as ere';
                    $table = 'vw_getData_Elab as ere';
                    $karcisField = "KARCIS_ASAL";
                    $selectField = "ID_RIWAYAT_ELAB";
                    $selectNakes = "KDDOK_TUJUAN";
                    $icd9 = json_decode($request->icd9, true);
                    $texticd9 = json_decode($request->text_icd9, true);
                    break;
                case 'operasi':
                    $table = 'E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI';
                    $karcisField = "KARCIS";
                    $selectField = "id_lap_operasi";
                    $selectNakes = "kddok";
                    $icd9 = json_decode($request->icd9, true);
                    $texticd9 = json_decode($request->text_icd9, true);
                    break;
                default:
                    if ($arrParam['jenis_perawatan'] == 'RAWAT_JALAN') {
                        $table = 'E_RM_PHCM.dbo.ERM_RM_IRJA';
                        $karcisField = "KARCIS";
                        $selectField = "NOMOR";
                        $selectNakes = "";
                    } else {
                        $table = 'E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD';
                        $karcisField = "noreg";
                        $selectField = "id_asuhan_header";
                        $selectNakes = "";
                    }
                    $icd9 = $request->icd9;
                    $texticd9 = $request->text_icd9;
                    $nakes = SS_Nakes::where('idnakes', $arrParam['id_nakes_ss'])->first();
                    break;
            }

            $dataErm = DB::table("$table")
                ->where($karcisField, $arrParam['karcis']);

            if ($type == 'lab') {
                $dataErm = $dataErm
                    ->select([
                        'ere.ID_RIWAYAT_ELAB',
                        'ere.KD_TINDAKAN',
                        'rk.KDDOK as KDDOK_TUJUAN'
                    ])
                    ->where('KLINIK_TUJUAN', '0017')
                    ->leftJoin('RJ_KARCIS as rk', 'rk.KARCIS', 'ere.KARCIS_RUJUKAN');
                // ->leftJoin('vw_getData_Elab_DETAIL as ered', 'ere.ID_RIWAYAT_ELAB', 'ered.ID_RIWAYAT_ELAB');
            } else if ($type == 'rad') {
                $dataErm = $dataErm
                    ->select([
                        'ere.ID_RIWAYAT_ELAB',
                        'ere.KD_TINDAKAN',
                        'rk.KDDOK as KDDOK_TUJUAN'
                    ])
                    ->whereIn('KLINIK_TUJUAN', ['0015', '0016'])
                    ->leftJoin('RJ_KARCIS as rk', 'rk.KARCIS', 'ere.KARCIS_RUJUKAN');
                // ->leftJoin('vw_getData_Elab_DETAIL as ered', 'ere.ID_RIWAYAT_ELAB', 'ered.ID_RIWAYAT_ELAB');
            } else {
                $dataErm = $dataErm->select('*');
            }
            $dataErm = $dataErm->get();

            for ($i = 0; $i < count($dataErm); $i++) {
                $dataSatuSehat = SATUSEHAT_PROCEDURE::where('ID_JENIS_TINDAKAN', $dataErm[$i]->{$selectField});

                if (count($dataSatuSehat->get()) > 0) {
                    if (!$dataSatuSehat->first()->ID_SATUSEHAT_PROCEDURE) {
                        // throw new Exception('Data tindakan ini sudah pernah kirim ke satu sehat, tidak bisa simpan ICD9', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                    }
                } else {
                    $dataKarcis = DB::table('RJ_KARCIS as rk')
                        ->select('rk.KARCIS', 'rk.IDUNIT', 'rk.KLINIK', 'rk.TGL', 'rk.KDDOK', 'rk.KBUKU', 'rk.NOREG')
                        ->where($arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? 'rk.KARCIS' : 'rk.NOREG', $arrParam['karcis'])
                        ->where('rk.IDUNIT', $id_unit)
                        ->orderBy('rk.TGL', 'DESC')
                        ->first();

                    $dataPeserta = DB::table('RIRJ_MASTERPX')
                        ->where('KBUKU', $dataKarcis->KBUKU)
                        ->first();

                    if ($type != 'pemeriksaanfisik') {
                        $nakes = SS_Nakes::where('kddok', $dataErm[$i]->{$selectNakes})->first();
                    }

                    if ($type == 'lab' || $type == 'rad') {
                        $dataICD = json_decode($request->icd9_data);
                        $procedureData = [
                            'KBUKU' => $dataKarcis->KBUKU,
                            'NO_PESERTA' => $dataPeserta->NO_PESERTA,
                            'ID_SATUSEHAT_ENCOUNTER' => $arrParam['encounter_id'],
                            'ID_JENIS_TINDAKAN' => $dataErm[$i]->{$selectField},
                            'ID_TINDAKAN' => $dataErm[$i]->KD_TINDAKAN,
                            'KD_ICD9' => $dataICD[$i]->icd9,
                            'DISP_ICD9' => $dataICD[$i]->text_icd9,
                            'JENIS_TINDAKAN' => $request->type == 'pemeriksaanfisik' ? 'anamnese' : $request->type,
                            'KDDOK' => $nakes->kddok ?? null,
                        ];

                        $existingProcedure = $dataSatuSehat->where('KARCIS', $arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$dataKarcis->KARCIS : (int)$dataKarcis->NOREG)
                            ->where('JENIS_TINDAKAN', $type)
                            ->where('ID_TINDAKAN', $dataErm[$i]->KD_TINDAKAN)
                            ->where('ID_JENIS_TINDAKAN', $dataErm[$i]->{$selectField})
                            ->first();

                        if ($existingProcedure) {
                            DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE')
                                ->where('KARCIS', $arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$dataKarcis->KARCIS : (int)$dataKarcis->NOREG)
                                ->where('JENIS_TINDAKAN', $type)
                                ->where('ID_TINDAKAN', $dataErm[$i]->KD_TINDAKAN)
                                ->where('ID_JENIS_TINDAKAN', $dataErm[$i]->{$selectField})
                                ->update($procedureData);
                        } else {
                            $procedureData['KARCIS'] = $arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$dataKarcis->KARCIS : (int)$dataKarcis->NOREG;
                            $procedureData['CRTDT'] = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                            $procedureData['CRTUSER'] = 'system';
                            SATUSEHAT_PROCEDURE::create($procedureData);
                        }
                    } else {
                        $procedureData = [
                            'KBUKU' => $dataKarcis->KBUKU,
                            'NO_PESERTA' => $dataPeserta->NO_PESERTA,
                            'ID_SATUSEHAT_ENCOUNTER' => $arrParam['encounter_id'],
                            'ID_JENIS_TINDAKAN' => $dataErm[$i]->{$selectField},
                            'ID_TINDAKAN' => 0,
                            'KD_ICD9' => is_array($icd9) ? implode(',', $icd9) : $icd9,
                            'DISP_ICD9' => is_array($texticd9) ? implode(',', $texticd9) : $texticd9,
                            'JENIS_TINDAKAN' => $request->type == 'pemeriksaanfisik' ? 'anamnese' : $request->type,
                            'KDDOK' => $nakes->kddok ?? null,
                        ];

                        $existingProcedure = $dataSatuSehat->where('KARCIS', $arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$dataKarcis->KARCIS : (int)$dataKarcis->NOREG)
                            ->where('JENIS_TINDAKAN', $type == 'pemeriksaanfisik' ? 'anamnese' : $type)
                            ->first();

                        if ($existingProcedure) {
                            DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE')
                                ->where('KARCIS', $arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$dataKarcis->KARCIS : (int)$dataKarcis->NOREG)
                                ->where('JENIS_TINDAKAN', $type == 'pemeriksaanfisik' ? 'anamnese' : $type)
                                ->update($procedureData);
                        } else {
                            $procedureData['KARCIS'] = $arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$dataKarcis->KARCIS : (int)$dataKarcis->NOREG;
                            $procedureData['CRTDT'] = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                            $procedureData['CRTUSER'] = 'system';
                            SATUSEHAT_PROCEDURE::create($procedureData);
                        }
                    }
                }


                $this->logInfo('Procedure', 'Sukses Simpan Data ICD 9', [
                    'payload' => [
                        'icd9' => $icd9,
                        'text_icd9' => $texticd9,
                    ],
                    'user_id' => Session::get('nama', 'system') //Session::get('id')
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => JsonResponse::HTTP_OK,
                'message' => 'Berhasil Simpan Data ICD9',
                'redirect' => [
                    'need' => false,
                    'to' => null,
                ]
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            DB::beginTransaction();
            $this->logError('Procedure', 'Gagal Simpan Data ICD 9', [
                'status' => [
                    'msg' => $e->getMessage() != '' ? $e->getMessage() : 'Err',
                    'code' => $e->getCode() != '' ? $e->getCode() : 500,
                ],
                'err_detail' => $e,
                'message' => $e->getMessage() != '' ? $e->getMessage() : 'Terjadi Kesalahan Saat Kirim Data, Harap Coba lagi!'
            ]);
            DB::commit();
            return response()->json([
                'status' => [
                    'msg' => $e->getMessage() != '' ? $e->getMessage() : 'Err',
                    'code' => $e->getCode() != '' ? $e->getCode() : 500,
                ],
                'data' => null,
                'err_detail' => $e,
                'message' => $e->getMessage() != '' ? $e->getMessage() : 'Terjadi Kesalahan Saat Kirim Data, Harap Coba lagi!'
            ], 500);
        }
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
