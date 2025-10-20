<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Lib\LZCompressor\LZString;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class AllergyIntoleranceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $startDate = Carbon::now()->subDays(280)->startOfDay();
        $endDate   = Carbon::now()->endOfDay();

        $data = DB::table('v_kunjungan_rj as vkr')
            ->select([
                'vkr.ID_TRANSAKSI as KARCIS',
                'vkr.TANGGAL',
                'vkr.NO_PESERTA',
                'vkr.KBUKU',
                'vkr.NAMA_PASIEN',
                'vkr.DOKTER',
                'vkr.ID_PASIEN_SS',
                'vkr.ID_NAKES_SS',
                'rsn.id_satusehat_encounter',
                DB::raw('CASE WHEN rsi.karcis IS NOT NULL THEN 1 ELSE 0 END as sudah_integrasi'),
            ])
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                $join->on('rsn.karcis', '=', 'vkr.ID_TRANSAKSI')
                    ->on('rsn.kbuku', '=', 'vkr.KBUKU');
            })
            ->leftJoin('E_RM_PHCM.dbo.ERM_ALERGIPX as ea', function ($join) {
                $join->on('ea.KARCIS', '=', 'vkr.ID_TRANSAKSI')
                    ->on('ea.KBUKU', '=', 'vkr.KBUKU');
            })
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_ALLERGYINTOLERANCE as rsi', function ($join) {
                $join->on('rsi.karcis', '=', 'vkr.ID_TRANSAKSI')
                    ->on('rsi.kbuku', '=', 'vkr.KBUKU');
            })
            ->where('ea.STATUS_AKTIF', 1)
            ->whereBetween('vkr.TANGGAL', [$startDate, $endDate])
            ->orderByDesc('vkr.TANGGAL')
            ->groupBy([
                'vkr.ID_TRANSAKSI',
                'vkr.KBUKU',
                'vkr.TANGGAL',
                'vkr.NO_PESERTA',
                'vkr.NAMA_PASIEN',
                'vkr.DOKTER',
                'vkr.ID_PASIEN_SS',
                'vkr.ID_NAKES_SS',
                'rsn.id_satusehat_encounter',
                'rsi.karcis'
            ])
            ->get();

        $totalAll = $data->count();
        $totalSudahIntegrasi = $data->where('sudah_integrasi', 1)->count();
        $totalBelumIntegrasi = $totalAll - $totalSudahIntegrasi;

        $result = [
            'total_semua' => $totalAll,
            'total_sudah_integrasi' => $totalSudahIntegrasi,
            'total_belum_integrasi' => $totalBelumIntegrasi,
        ];

        return view('pages.satusehat.allergyintolerance.index', compact('result'));
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $id_unit   = '001'; // session('id_klinik');

        if (empty($tgl_awal) && empty($tgl_akhir)) {
            $tgl_awal  = Carbon::now()->subDays(280)->startOfDay();
            $tgl_akhir = Carbon::now()->endOfDay();
        } else {
            if (empty($tgl_awal)) {
                $tgl_awal = Carbon::parse($tgl_akhir)->subDays(280)->startOfDay();
            }
            if (empty($tgl_akhir)) {
                $tgl_akhir = Carbon::parse($tgl_awal)->addDays(280)->endOfDay();
            }
        }

        if (!$this->checkDateFormat($tgl_awal) || !$this->checkDateFormat($tgl_akhir)) {
            return DataTables::of([])->make(true);
        }

        $tgl_awal_db  = Carbon::parse($tgl_awal)->format('Y-m-d H:i:s');
        $tgl_akhir_db = Carbon::parse($tgl_akhir)->format('Y-m-d H:i:s');

        $dataQuery = DB::table('v_kunjungan_rj as vkr')
            ->select([
                'vkr.ID_TRANSAKSI as KARCIS',
                'vkr.TANGGAL',
                'vkr.NO_PESERTA',
                'vkr.KBUKU',
                'vkr.NAMA_PASIEN',
                'vkr.DOKTER',
                'vkr.ID_PASIEN_SS',
                'vkr.ID_NAKES_SS',
                'rsn.id_satusehat_encounter',
                DB::raw('CASE WHEN rsi.karcis IS NOT NULL THEN 1 ELSE 0 END as sudah_integrasi'),
            ])
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', function ($join) {
                $join->on('rsn.karcis', '=', 'vkr.ID_TRANSAKSI')
                    ->on('rsn.kbuku', '=', 'vkr.KBUKU');
            })
            ->leftJoin('E_RM_PHCM.dbo.ERM_ALERGIPX as ea', function ($join) {
                $join->on('ea.KARCIS', '=', 'vkr.ID_TRANSAKSI')
                    ->on('ea.KBUKU', '=', 'vkr.KBUKU');
            })
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_ALLERGYINTOLERANCE as rsi', function ($join) {
                $join->on('rsi.karcis', '=', 'vkr.ID_TRANSAKSI')
                    ->on('rsi.kbuku', '=', 'vkr.KBUKU');
            })
            ->where('ea.STATUS_AKTIF', '1')
            ->whereBetween('vkr.TANGGAL', [$tgl_awal_db, $tgl_akhir_db])
            ->groupBy([
                'vkr.ID_TRANSAKSI',
                'vkr.KBUKU',
                'vkr.TANGGAL',
                'vkr.NO_PESERTA',
                'vkr.NAMA_PASIEN',
                'vkr.DOKTER',
                'vkr.ID_PASIEN_SS',
                'vkr.ID_NAKES_SS',
                'rsn.id_satusehat_encounter',
                'rsi.karcis'
            ]);

        $cari = $request->input('cari');
        if ($cari === 'mapped') {
            $dataQuery->whereNotNull('rsi.karcis');
        } elseif ($cari === 'unmapped') {
            $dataQuery->whereNull('rsi.karcis');
        }

        $data = $dataQuery->orderByDesc('vkr.TANGGAL')->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('TANGGAL', function ($row) {
                return date('Y-m-d', strtotime($row->TANGGAL));
            })
            ->addColumn('action', function ($row) {
                $id_transaksi = LZString::compressToEncodedURIComponent($row->KARCIS);
                $KbBuku = LZString::compressToEncodedURIComponent($row->KBUKU);
                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                $idEncounter = LZString::compressToEncodedURIComponent($row->id_satusehat_encounter);
                $paramSatuSehat = LZString::compressToEncodedURIComponent($id_transaksi . '+' . $KbBuku . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' . $idEncounter);

                $param = LZString::compressToEncodedURIComponent($id_transaksi . '+' . $KbBuku);
                $btn = '';
                if ($row->ID_PASIEN_SS == null) {
                    $btn = '<i class="text-muted">Pasien Belum Mapping</i>';
                } else if ($row->ID_NAKES_SS == null) {
                    $btn .= '<i class="text-muted">Nakes Belum Mapping</i>';
                } else if ($row->id_satusehat_encounter == null) {
                    $btn .= '<i class="text-muted">Encounter Belum Kirim</i>';
                } else {
                    if ($row->sudah_integrasi == '0') {
                        $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                    } else {
                        $btn = '<a href="#" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                    }
                }

                $btn .= '<br>';
                $btn .= '<a href="javascript:void(0)" onclick="lihatDetailAlergi(`' . $param . '`)" class="mt-2 btn btn-sm btn-info w-100"><i class="fas fa-info-circle mr-2"></i>Lihat Alergi</a>';
                return $btn;
            })
            ->addColumn('status_integrasi', function ($row) {
                if ($row->sudah_integrasi == '0') {
                    return '<span class="badge badge-pill badge-danger p-2 w-100">Belum Integrasi</span>';
                } else {
                    return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                }
            })
            ->rawColumns(['action', 'status_integrasi'])
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

    public function lihatAlergi($param)
    {
        $param = base64_decode($param);
        $params = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('+', $params);
        $karcis = LZString::decompressFromEncodedURIComponent($parts[0]);
        $kdbuku = LZString::decompressFromEncodedURIComponent($parts[1]);

        $dataPasien = DB::table('RIRJ_MASTERPX')->select('NAMA', 'KBUKU', 'NO_PESERTA')->where("KBUKU", $kdbuku)->first();
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
                'eri.CRTUSR',
                'eri.CRTDT',
                'ead.JENIS',
                'ead.ALERGEN',
            ])
            ->leftJoin('E_RM_PHCM.dbo.ERM_RM_IRJA as eri', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'eri.KARCIS');
            })
            ->leftJoin('E_RM_PHCM.dbo.ERM_ALERGIPX as ea', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'ea.KARCIS');
            })
            ->leftJoin('E_RM_PHCM.dbo.vw_alergipx_detail as ead', function ($join) {
                $join->on('ea.ID_ALERGI_PX', '=', 'ead.ID_HDR');
            })
            ->where('vkr.KBUKU', $kdbuku)
            ->where('vkr.ID_TRANSAKSI', $karcis)
            ->orderByDesc('vkr.TANGGAL')
            ->first();

        $dataAlergi = DB::table('E_RM_PHCM.dbo.ERM_RM_IRJA as eri')
            ->select([
                'ead.JENIS',
                'ead.ALERGEN',
                'ead.ID_ALERGEN_SS'
            ])
            ->leftJoin('E_RM_PHCM.dbo.ERM_ALERGIPX as ea', function ($join) {
                $join->on('eri.KARCIS', '=', 'ea.KARCIS');
            })
            ->leftJoin('E_RM_PHCM.dbo.ERM_ALERGIPX_DTL as ead', function ($join) {
                $join->on('ea.ID_ALERGI_PX', '=', 'ead.ID_HDR');
            })
            ->where('eri.KARCIS', $karcis)
            ->where('ea.STATUS_AKTIF', '1')
            ->get();

        return response()->json([
            'status' => JsonResponse::HTTP_OK,
            'message' => 'OK',
            'data' => [
                'dataErm' => $dataErm,
                'dataPasien' => $dataPasien,
                'dataAlergi' => $dataAlergi
            ],
            'redirect' => [
                'need' => false,
                'to' => null,
            ]
        ], 200);
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
