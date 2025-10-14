<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Lib\LZCompressor\LZString;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class EncounterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate   = Carbon::now()->endOfDay();

        $rj = DB::table('v_kunjungan_rj as v')
            ->whereBetween('TANGGAL', [$startDate, $endDate])
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
            ->groupBy('v.JENIS_PERAWATAN', 'v.STATUS_SELESAI', 'v.STATUS_KUNJUNGAN', 'v.DOKTER', 'v.DEBITUR', 'v.LOKASI', 'v.STATUS_MAPPING_PASIEN', 'v.ID_PASIEN_SS', 'v.ID_NAKES_SS', 'v.KODE_DOKTER', 'v.ID_LOKASI_SS', 'v.UUID', 'v.STATUS_MAPPING_LOKASI', 'v.STATUS_MAPPING_NAKES', 'v.ID_TRANSAKSI', 'v.ID_UNIT', 'v.KODE_KLINIK', 'v.KBUKU', 'v.NO_PESERTA', 'v.TANGGAL', 'v.NAMA_PASIEN');

        $rjAll = $rj->get();
        $rjIntegrasi = $rj->whereNotNull('n.ID_SATUSEHAT_ENCOUNTER')->get();

        $ri = DB::table('v_kunjungan_ri')
            ->whereBetween('TANGGAL', [$startDate, $endDate])
            ->get();

        $mergedAll = $rjAll->merge($ri)
            ->sortByDesc('TANGGAL')
            ->values();

        $mergedIntegrated = $rjIntegrasi->merge($ri)
            ->sortByDesc('TANGGAL')
            ->values();

        $unmapped = count($mergedAll) - count($mergedIntegrated);
        return view('pages.satusehat.encounter.index', compact('mergedAll', 'mergedIntegrated', 'rjAll', 'rjIntegrasi', 'ri', 'unmapped'));
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $id_unit   = '001'; // session('id_klinik');

        if (empty($tgl_awal) && empty($tgl_akhir)) {
            $tgl_awal  = Carbon::now()->subDays(30)->startOfDay();
            $tgl_akhir = Carbon::now()->endOfDay();
        } else {
            if (empty($tgl_awal)) {
                $tgl_awal = Carbon::parse($tgl_akhir)->subDays(30)->startOfDay();
            }
            if (empty($tgl_akhir)) {
                $tgl_akhir = Carbon::parse($tgl_awal)->addDays(30)->endOfDay();
            }
        }

        if (!$this->checkDateFormat($tgl_awal) || !$this->checkDateFormat($tgl_akhir)) {
            return DataTables::of([])->make(true);
        }

        $tgl_awal_db  = Carbon::parse($tgl_awal)->format('Y-m-d H:i:s');
        $tgl_akhir_db = Carbon::parse($tgl_akhir)->format('Y-m-d H:i:s');

        $rj = DB::table('v_kunjungan_rj as v')
            ->whereBetween('TANGGAL', [$tgl_awal_db, $tgl_akhir_db])
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
            ->groupBy('v.JENIS_PERAWATAN', 'v.STATUS_SELESAI', 'v.STATUS_KUNJUNGAN', 'v.DOKTER', 'v.DEBITUR', 'v.LOKASI', 'v.STATUS_MAPPING_PASIEN', 'v.ID_PASIEN_SS', 'v.ID_NAKES_SS', 'v.KODE_DOKTER', 'v.ID_LOKASI_SS', 'v.UUID', 'v.STATUS_MAPPING_LOKASI', 'v.STATUS_MAPPING_NAKES', 'v.ID_TRANSAKSI', 'v.ID_UNIT', 'v.KODE_KLINIK', 'v.KBUKU', 'v.NO_PESERTA', 'v.TANGGAL', 'v.NAMA_PASIEN');

        $rjAll = $rj->get();
        $rjIntegrasi = $rj->whereNotNull('n.ID_SATUSEHAT_ENCOUNTER')->get();

        $ri = DB::table('v_kunjungan_ri')
            ->whereBetween('TANGGAL', [$tgl_awal_db, $tgl_akhir_db])
            ->get();

        $mergedAll = $rjAll->merge($ri)
            ->sortByDesc('TANGGAL')
            ->values();

        $mergedIntegrated = $rjIntegrasi->merge($ri)
            ->sortByDesc('TANGGAL')
            ->values();

        if ($request->input('cari') == 'mapped') {
            $dataKunjungan = $mergedIntegrated;
        } else if ($request->input('cari') == 'unmapped') {
            $dataKunjungan = $mergedAll->filter(function ($item) {
                return $item->JUMLAH_NOTA_SATUSEHAT == '0';
            })->values();
        } else {
            $dataKunjungan = $mergedAll;
        }

        return DataTables::of($dataKunjungan)
            ->addIndexColumn()
            ->editColumn('JENIS_PERAWATAN', function ($row) {
                return $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
            })
            ->editColumn('TANGGAL', function ($row) {
                return date('Y-m-d', strtotime($row->TANGGAL));
            })
            ->editColumn('STATUS_SELESAI', function ($row) {
                if ($row->JENIS_PERAWATAN == 'RAWAT_JALAN') {
                    if ($row->STATUS_SELESAI == "9" || $row->STATUS_SELESAI == "10") {
                        return '<span class="badge badge-pill badge-secondary p-2 w-100">Belum Verif</span>';
                    } else {
                        return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Verif</span>';
                    }
                } else {
                    return $row->STATUS_SELESAI == 1 ? 'Selesai' : 'Belum Selesai';
                }
            })
            ->addColumn('action', function ($row) {
                $kdbuku = LZString::compressToEncodedURIComponent($row->KBUKU);
                $kdDok = LZString::compressToEncodedURIComponent($row->KODE_DOKTER);
                $kdKlinik = LZString::compressToEncodedURIComponent($row->KODE_KLINIK);
                $idUnit = LZString::compressToEncodedURIComponent($row->ID_UNIT);
                $param = LZString::compressToEncodedURIComponent($kdbuku . '+' . $kdDok . '+' . $kdKlinik . '+' . $idUnit);

                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                $dokter = LZString::compressToEncodedURIComponent($row->KODE_DOKTER);
                $kdLokasiSS = LZString::compressToEncodedURIComponent($row->ID_LOKASI_SS);
                $paramSatuSehat = LZString::compressToEncodedURIComponent($kdbuku . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' . $dokter . '+' .  $kdLokasiSS);

                if ($row->ID_PASIEN_SS == null) {
                    $btn = '<i class="text-muted">Pasien Belum Mapping Satu Sehat</i>';
                } else if ($row->ID_NAKES_SS == null) {
                    $btn = '<i class="text-muted">Nakes Belum Mapping Satu Sehat</i>';
                } else if ($row->ID_LOKASI_SS == null) {
                    $btn = '<i class="text-muted">Lokasi Belum Mapping Satu Sehat</i>';
                } else {
                    if ($row->JENIS_PERAWATAN == 'RAWAT_JALAN') {
                        if ($row->JUMLAH_NOTA_SATUSEHAT == 0) {
                            if ($row->STATUS_SELESAI != "9" && $row->STATUS_SELESAI != "10") {
                                $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                            } else {
                                $btn = '<i class="text-muted">Tunggu Verifikasi Pasien</i>';
                            }
                        } else {
                            $btn = '<a href="#" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                        }
                    } else {
                        // return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                    }
                }
                // $btn .= '<br>';
                // $btn .= '<a href="' . route('satusehat.encounter.lihat-erm', $param) . '" class="mt-2 btn btn-sm btn-info w-100"><i class="fas fa-info-circle mr-2"></i>Lihat ERM</a>';
                return $btn;
            })
            ->addColumn('status_integrasi', function ($row) {
                if ($row->JENIS_PERAWATAN == 'RAWAT_JALAN') {
                    if ($row->JUMLAH_NOTA_SATUSEHAT > 0) {
                        return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                    } else {
                        return '<span class="badge badge-pill badge-danger p-2 w-100">Belum Integrasi</span>';
                    }
                } else {
                    return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                }
            })
            ->rawColumns(['STATUS_SELESAI', 'action', 'status_integrasi'])
            ->make(true);
    }

    private function checkDateFormat($date)
    {
        try {
            // Kalau $date sudah Carbon instance
            if ($date instanceof \Carbon\Carbon) {
                return true;
            }

            // Kalau string tapi masih bisa di-parse ke Carbon
            \Carbon\Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function lihatERM($param)
    {
        $params = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('|', $params);
        $kdbuku = LZString::decompressFromEncodedURIComponent($parts[0]);
        $kdDok = LZString::decompressFromEncodedURIComponent($parts[1]);
        $kdKlinik = LZString::decompressFromEncodedURIComponent($parts[2]);
        $idUnit = LZString::decompressFromEncodedURIComponent($parts[3]);

        return view('pages.satusehat.encounter.lihat-erm', compact('kdbuku', 'kdDok', 'kdKlinik', 'idUnit'));
    }

    public function sendSatuSehat(Request $request)
    {
        $id_transaksi = $request->input('id_transaksi');
        $id_unit      = '001'; // session('id_klinik');
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
