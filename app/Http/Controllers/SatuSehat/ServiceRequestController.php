<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Lib\LZCompressor\LZString;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class ServiceRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $startDate = '2025-02-25 00:00:00';
        $endDate   = '2025-02-25 23:59:59';

        // Base query for lab and rad
        $lab = DB::connection('sqlsrv')
            ->table('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as a')
            ->whereBetween('a.TANGGAL_ENTRI', [$startDate, $endDate])
            ->where('a.IDUNIT', '001')
            ->where('a.KLINIK_TUJUAN', '0017');

        $rad = DB::connection('sqlsrv')
            ->table('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as a')
            ->whereBetween('a.TANGGAL_ENTRI', [$startDate, $endDate])
            ->where('a.IDUNIT', '001')
            ->whereIn('a.KLINIK_TUJUAN', function ($sub) {
                $sub->select('KODE_KLINIK')
                    ->from('SIRS_PHCM.dbo.RJ_KLINIK_RADIOLOGI')
                    ->where('AKTIF', 'true')
                    ->where('IDUNIT', '001');
            });

        // ===== LAB TOTALS =====
        $total_all_lab = (clone $lab)
            ->select('a.ID_RIWAYAT_ELAB')
            ->distinct()
            ->count('a.ID_RIWAYAT_ELAB');

        $total_mapped_lab = (clone $lab)
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as d', 'a.KBUKU', '=', 'd.kbuku')
            ->whereNotNull('d.id_satusehat_servicerequest')
            ->where('d.id_satusehat_servicerequest', '<>', '')
            ->select('a.ID_RIWAYAT_ELAB')
            ->distinct()
            ->count('a.ID_RIWAYAT_ELAB');

        $total_unmapped_lab = $total_all_lab - $total_mapped_lab;

        // ===== RAD TOTALS =====
        $total_all_rad = (clone $rad)
            ->select('a.ID_RIWAYAT_ELAB')
            ->distinct()
            ->count('a.ID_RIWAYAT_ELAB');

        $total_mapped_rad = (clone $rad)
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as d', 'a.KBUKU', '=', 'd.kbuku')
            ->whereNotNull('d.id_satusehat_servicerequest')
            ->where('d.id_satusehat_servicerequest', '<>', '')
            ->select('a.ID_RIWAYAT_ELAB')
            ->distinct()
            ->count('a.ID_RIWAYAT_ELAB');

        $total_unmapped_rad = $total_all_rad - $total_mapped_rad;

        // ===== COMBINED TOTALS =====
        $total_all_combined     = $total_all_lab + $total_all_rad;
        $total_mapped_combined  = $total_mapped_lab + $total_mapped_rad;
        $total_unmapped_combined = $total_unmapped_lab + $total_unmapped_rad;

        return view('pages.satusehat.service-request.index', compact(
            'total_all_lab',
            'total_all_rad',
            'total_all_combined',
            'total_mapped_lab',
            'total_mapped_rad',
            'total_mapped_combined',
            'total_unmapped_lab',
            'total_unmapped_rad',
            'total_unmapped_combined'
        ));
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $id_unit   = '001'; // session('id_klinik');
        // dd($request->all());

        if (empty($tgl_awal) && empty($tgl_akhir)) {
            $tgl_awal  = '2025-02-25 00:00:00';
            $tgl_akhir = '2025-02-25 23:59:59';
        } else {
            if (empty($tgl_awal)) {
                $tgl_awal = '2025-02-25 00:00:00';
            }
            if (empty($tgl_akhir)) {
                $tgl_akhir = '2025-02-25 23:59:59';
            }
        }

        if (!$this->checkDateFormat($tgl_awal) || !$this->checkDateFormat($tgl_akhir)) {
            return DataTables::of([])->make(true);
        }

        // $tgl_awal_db  = Carbon::parse($tgl_awal)->format('Y-m-d H:i:s');
        // $tgl_akhir_db = Carbon::parse($tgl_akhir)->format('Y-m-d H:i:s');

        $rad = DB::connection('sqlsrv')
            ->table('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as a')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as b', 'a.KBUKU', '=', 'b.kbuku')
            ->leftJoin('SIRS_PHCM.dbo.DR_MDOKTER as c', 'a.KDDOK', '=', 'c.kdDok')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as d', 'a.KARCIS_ASAL', '=', 'd.karcis')
            ->whereBetween('a.TANGGAL_ENTRI', [$tgl_awal, $tgl_akhir])
            ->where('a.IDUNIT', '001')
            ->whereIn('a.KLINIK_TUJUAN', function ($sub) {
                $sub->select('KODE_KLINIK')
                    ->from('SIRS_PHCM.dbo.RJ_KLINIK_RADIOLOGI')
                    ->where('AKTIF', 'true')
                    ->where('IDUNIT', '001');
            });
        // dd($rad->toSql());

        $radAll = $rad->get();
        // dd($radAll);
        $radIntegrasi = $rad->whereNotNull('b.id_satusehat_servicerequest')->get();
        // dd($radIntegrasi);

        $lab = DB::connection('sqlsrv')
            ->table('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as a')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as b', 'a.KBUKU', '=', 'b.kbuku')
            ->leftJoin('SIRS_PHCM.dbo.DR_MDOKTER as c', 'a.KDDOK', '=', 'c.kdDok')
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as d', 'a.KARCIS_ASAL', '=', 'd.karcis')
            ->whereBetween('a.TANGGAL_ENTRI', [$tgl_awal, $tgl_akhir])
            ->where('a.IDUNIT', '001')
            ->where('a.KLINIK_TUJUAN', '0017');

        $labAll = $lab->get();
        $labIntegrasi = $lab->whereNotNull('b.id_satusehat_servicerequest')->get();

        $mergedAll = $radAll->merge($labAll)
            ->sortByDesc('a.TANGGAL_ENTRI')
            ->values();
        // dd($mergedAll);

        $mergedIntegrated = $radIntegrasi->merge($labIntegrasi)
            ->sortByDesc('a.TANGGAL_ENTRI')
            ->values();

        if ($request->input('cari') == 'mapped') {
            $dataKunjungan = $mergedIntegrated;
        } else if ($request->input('cari') == 'unmapped') {
            $dataKunjungan = $mergedAll->filter(function ($item) {
                return $item->id_satusehat_servicerequest == null;
            })->values();
        } else {
            $dataKunjungan = $mergedAll;
        }
        // dd($dataKunjungan);

        return DataTables::of($dataKunjungan)
            ->addIndexColumn()
            ->editColumn('KLINIK_TUJUAN', function ($row) {
                return $row->KLINIK_TUJUAN == '0017' ? '<span class="badge badge-pill badge-success p-2 w-100">Laboratory</span>' : '<span class="badge badge-pill badge-info p-2 w-100">Radiology</span>';
            })
            ->editColumn('TANGGAL_ENTRI', function ($row) {
                return date('Y-m-d H:i:s', strtotime($row->TANGGAL_ENTRI));
            })
            ->editColumn('nmDok', function ($row) {
                return $row->nmDok ?? '<span class="text-muted">Dokter Tidak Ditemukan</span>';
            })
            // ->addColumn('action', function ($row) {
            //     $kdbuku = LZString::compressToEncodedURIComponent($row->KBUKU);
            //     $kdDok = LZString::compressToEncodedURIComponent($row->KDDOK);
            //     $kdKlinik = LZString::compressToEncodedURIComponent($row->KLINIK);
            //     $idUnit = LZString::compressToEncodedURIComponent($row->IDUNIT);
            //     $param = LZString::compressToEncodedURIComponent($kdbuku . '+' . $kdDok . '+' . $kdKlinik . '+' . $idUnit);

            //     $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
            //     $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
            //     $dokter = LZString::compressToEncodedURIComponent($row->KODE_DOKTER);
            //     $kdLokasiSS = LZString::compressToEncodedURIComponent($row->ID_LOKASI_SS);
            //     $paramSatuSehat = LZString::compressToEncodedURIComponent($kdbuku . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' . $dokter . '+' .  $kdLokasiSS);

            //     if ($row->ID_PASIEN_SS == null) {
            //         $btn = '<i class="text-muted">Pasien Belum Mapping Satu Sehat</i>';
            //     } else if ($row->ID_NAKES_SS == null) {
            //         $btn = '<i class="text-muted">Nakes Belum Mapping Satu Sehat</i>';
            //     } else if ($row->ID_LOKASI_SS == null) {
            //         $btn = '<i class="text-muted">Lokasi Belum Mapping Satu Sehat</i>';
            //     } else {
            //         if ($row->JENIS_PERAWATAN == 'RAWAT_JALAN') {
            //             if ($row->JUMLAH_NOTA_SATUSEHAT == 0) {
            //                 if ($row->STATUS_SELESAI != "9" && $row->STATUS_SELESAI != "10") {
            //                     $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
            //                 } else {
            //                     $btn = '<i class="text-muted">Tunggu Verifikasi Pasien</i>';
            //                 }
            //             } else {
            //                 $btn = '<a href="#" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
            //             }
            //         } else {
            //         }
            //     }
            //     return $btn;
            // })
            ->addColumn('status_integrasi', function ($row) {
                if ($row->id_satusehat_servicerequest > 0) {
                    return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                } else {
                    return '<span class="badge badge-pill badge-danger p-2 w-100">Belum Integrasi</span>';
                }
            })
            ->rawColumns(['KLINIK_TUJUAN', 'status_integrasi'])
            // ->rawColumns(['STATUS_SELESAI', 'action', 'status_integrasi'])
            ->make(true);
    }

    private function checkDateFormat($date)
    {
        try {
            // Kalau $date sudah Carbon instance
            if ($date instanceof Carbon) {
                return true;
            }

            // Kalau string tapi masih bisa di-parse ke Carbon
            Carbon::parse($date);
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
