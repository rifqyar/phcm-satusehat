<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\SATUSEHAT\SS_Kode_API;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\DataTables;

class ServiceRequestController extends Controller
{
    use SATUSEHATTraits, LogTraits;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('pages.satusehat.service-request.index');
    }

    public function summary(Request $request)
    {
        $startDate  = $request->input('tgl_awal');
        $endDate = $request->input('tgl_akhir');

        if (empty($startDate) && empty($endDate)) {
            $startDate  = Carbon::now()->subDays(30)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } else {
            if (empty($startDate)) {
                $startDate = Carbon::parse($endDate)->subDays(30)->startOfDay();
            }
            if (empty($endDate)) {
                $endDate = Carbon::parse($startDate)->addDays(30)->endOfDay();
            } else {
                // Force the end date to be at 23:59:59 (end of that day)
                $endDate = Carbon::parse($endDate)->endOfDay();
            }
        }

        // Base query for lab and rad
        $lab = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.v_kunjungan_rj as a')
            ->join('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as b', function ($join) {
                $join->on('b.karcis', '=', 'a.ID_TRANSAKSI')
                    ->on('b.idunit', '=', 'a.ID_UNIT')
                    ->on('b.kbuku', '=', 'a.KBUKU')
                    ->on('b.no_peserta', '=', 'a.NO_PESERTA');
            })
            ->join('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as c', function ($join) {
                $join->on('c.KARCIS_ASAL', '=', 'b.karcis')
                    ->on('c.IDUNIT', '=', 'b.idunit')
                    ->on('c.KBUKU', '=', 'b.kbuku')
                    ->on('c.NO_PESERTA', '=', 'b.no_peserta');
            })
            ->whereBetween('c.TANGGAL_ENTRI', [$startDate, $endDate])
            ->where('c.IDUNIT', '001')
            ->where('c.KLINIK_TUJUAN', '0017');

        $rad = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.v_kunjungan_rj as a')
            ->join('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as b', function ($join) {
                $join->on('b.karcis', '=', 'a.ID_TRANSAKSI')
                    ->on('b.idunit', '=', 'a.ID_UNIT')
                    ->on('b.kbuku', '=', 'a.KBUKU')
                    ->on('b.no_peserta', '=', 'a.NO_PESERTA');
            })
            ->join('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as c', function ($join) {
                $join->on('c.KARCIS_ASAL', '=', 'b.karcis')
                    ->on('c.IDUNIT', '=', 'b.idunit')
                    ->on('c.KBUKU', '=', 'b.kbuku')
                    ->on('c.NO_PESERTA', '=', 'b.no_peserta');
            })
            ->whereBetween('c.TANGGAL_ENTRI', [$startDate, $endDate])
            ->where('c.IDUNIT', '001')
            ->whereIn('c.KLINIK_TUJUAN', function ($sub) {
                $sub->select('KODE_KLINIK')
                    ->from('SIRS_PHCM.dbo.RJ_KLINIK_RADIOLOGI')
                    ->where('AKTIF', 'true')
                    ->where('IDUNIT', '001');
            });

        // === Counts ===
        $total_all_lab = (clone $lab)->distinct()->count('c.ID_RIWAYAT_ELAB');
        $total_mapped_lab = (clone $lab)
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as d', 'a.KBUKU', '=', 'd.kbuku')
            ->whereNotNull('d.id_satusehat_servicerequest')
            ->where('d.id_satusehat_servicerequest', '<>', '')
            ->distinct()->count('c.ID_RIWAYAT_ELAB');

        $total_unmapped_lab = $total_all_lab - $total_mapped_lab;

        $total_all_rad = (clone $rad)->distinct()->count('c.ID_RIWAYAT_ELAB');
        $total_mapped_rad = (clone $rad)
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as d', 'a.KBUKU', '=', 'd.kbuku')
            ->whereNotNull('d.id_satusehat_servicerequest')
            ->where('d.id_satusehat_servicerequest', '<>', '')
            ->distinct()->count('c.ID_RIWAYAT_ELAB');

        $total_unmapped_rad = $total_all_rad - $total_mapped_rad;

        return response()->json([
            'total_all_lab' => $total_all_lab,
            'total_all_rad' => $total_all_rad,
            'total_all_combined' => $total_all_lab + $total_all_rad,
            'total_mapped_lab' => $total_mapped_lab,
            'total_mapped_rad' => $total_mapped_rad,
            'total_mapped_combined' => $total_mapped_lab + $total_mapped_rad,
            'total_unmapped_lab' => $total_unmapped_lab,
            'total_unmapped_rad' => $total_unmapped_rad,
            'total_unmapped_combined' => $total_unmapped_lab + $total_unmapped_rad,
        ]);
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $id_unit   = '001'; // session('id_klinik');
        // dd($request->all());

        if (empty($tgl_awal) && empty($tgl_akhir)) {
            $tgl_awal  = Carbon::now()->subDays(30)->startOfDay();
            $tgl_akhir = Carbon::now()->endOfDay();
        } else {
            if (empty($tgl_awal)) {
                $tgl_awal = Carbon::parse($tgl_akhir)->subDays(30)->startOfDay();
            }
            if (empty($tgl_akhir)) {
                $tgl_akhir = Carbon::parse($tgl_awal)->addDays(30)->endOfDay();
            } else {
                // Force the end date to be at 23:59:59 (end of that day)
                $tgl_akhir = Carbon::parse($tgl_akhir)->endOfDay();
            }
        }

        if (!$this->checkDateFormat($tgl_awal) || !$this->checkDateFormat($tgl_akhir)) {
            return DataTables::of([])->make(true);
        }

        // $tgl_awal_db  = Carbon::parse($tgl_awal)->format('Y-m-d H:i:s');
        // $tgl_akhir_db = Carbon::parse($tgl_akhir)->format('Y-m-d H:i:s');

        $rad = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.v_kunjungan_rj as rj')
            ->join('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as nt', function ($join) {
                $join->on('nt.karcis', '=', 'rj.ID_TRANSAKSI')
                    ->on('nt.idunit', '=', 'rj.ID_UNIT')
                    ->on('nt.kbuku', '=', 'rj.KBUKU')
                    ->on('nt.no_peserta', '=', 'rj.NO_PESERTA');
            })
            ->join('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as rd', function ($join) {
                $join->on('rd.KARCIS_ASAL', '=', 'nt.karcis')
                    ->on('rd.IDUNIT', '=', 'nt.idunit')
                    ->on('rd.KBUKU', '=', 'nt.kbuku')
                    ->on('rd.NO_PESERTA', '=', 'nt.no_peserta');
            })
            ->join('SIRS_PHCM.dbo.DR_MDOKTER as dk', 'rd.KDDOK', '=', 'dk.kdDok')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as ss', 'nt.karcis', '=', 'ss.karcis')
            ->leftJoin('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES as nk', 'rd.KDDOK', '=', 'nk.kddok')
            ->select(['rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.ARRAY_TINDAKAN', DB::raw('COUNT(DISTINCT ss.id_satusehat_servicerequest) as SATUSEHAT')])
            ->distinct()
            ->whereBetween('rd.TANGGAL_ENTRI', [$tgl_awal, $tgl_akhir])
            ->where('rd.IDUNIT', '001')
            ->whereIn('rd.KLINIK_TUJUAN', function ($sub) {
                $sub->select('KODE_KLINIK')
                    ->from('SIRS_PHCM.dbo.RJ_KLINIK_RADIOLOGI')
                    ->where('AKTIF', 'true')
                    ->where('IDUNIT', '001');
            })
            ->groupBy('rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.ARRAY_TINDAKAN');
        // dd($rad->toSql());

        $radAll = $rad->get();
        // dd($radAll);
        $radIntegrasi = $rad->whereNotNull('ss.id_satusehat_servicerequest')->get();
        // dd($radIntegrasi);

        $lab = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.v_kunjungan_rj as rj')
            ->join('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as nt', function ($join) {
                $join->on('nt.karcis', '=', 'rj.ID_TRANSAKSI')
                    ->on('nt.idunit', '=', 'rj.ID_UNIT')
                    ->on('nt.kbuku', '=', 'rj.KBUKU')
                    ->on('nt.no_peserta', '=', 'rj.NO_PESERTA');
            })
            ->join('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as rd', function ($join) {
                $join->on('rd.KARCIS_ASAL', '=', 'nt.karcis')
                    ->on('rd.IDUNIT', '=', 'nt.idunit')
                    ->on('rd.KBUKU', '=', 'nt.kbuku')
                    ->on('rd.NO_PESERTA', '=', 'nt.no_peserta');
            })
            ->join('SIRS_PHCM.dbo.DR_MDOKTER as dk', 'rd.KDDOK', '=', 'dk.kdDok')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as ss', 'nt.karcis', '=', 'ss.karcis')
            ->leftJoin('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES as nk', 'rd.KDDOK', '=', 'nk.kddok')
            ->select(['rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.ARRAY_TINDAKAN', DB::raw('COUNT(DISTINCT ss.id_satusehat_servicerequest) as SATUSEHAT')])
            ->distinct()
            ->whereBetween('rd.TANGGAL_ENTRI', [$tgl_awal, $tgl_akhir])
            ->where('rd.IDUNIT', '001')
            ->where('rd.KLINIK_TUJUAN', '0017')
            ->groupBy('rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.ARRAY_TINDAKAN');

        $labAll = $lab->get();
        $labIntegrasi = $lab->whereNotNull('ss.id_satusehat_servicerequest')->get();

        $mergedAll = $radAll->merge($labAll)
            ->sortByDesc('rd.TANGGAL_ENTRI')
            ->values();
        // dd($mergedAll);

        $mergedIntegrated = $radIntegrasi->merge($labIntegrasi)
            ->sortByDesc('rd.TANGGAL_ENTRI')
            ->values();

        if ($request->input('cari') == 'mapped') {
            $dataKunjungan = $mergedIntegrated;
        } else if ($request->input('cari') == 'unmapped') {
            $dataKunjungan = $mergedAll->filter(function ($item) {
                return $item->SATUSEHAT == '0';
            })->values();
        } else if ($request->input('cari') == 'rad') {
            $dataKunjungan = $radAll;
        } else if ($request->input('cari') == 'lab') {
            $dataKunjungan = $labAll;
        } else {
            $dataKunjungan = $mergedAll;
        }
        // dd($dataKunjungan);

        $allTindakanIds = collect($dataKunjungan)
            ->pluck('ARRAY_TINDAKAN')
            ->filter()
            ->flatMap(function ($t) {
                return explode(',', $t);
            })
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        $allTindakanIdsSS = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE')
            ->pluck('ID')
            ->toArray();

        $tindakanList = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RIRJ_MTINDAKAN')
            ->whereIn('KD_TIND', $allTindakanIds)
            ->pluck('NM_TIND', 'KD_TIND')
            ->toArray();
        // dd($tindakanList);

        $dataKunjungan = $dataKunjungan->map(function ($item) use ($allTindakanIdsSS, $tindakanList) {
            $ids = array_filter(explode(',', $item->ARRAY_TINDAKAN ?? ''));

            $item->AllServiceRequestExist = count($ids) > 0 && collect($ids)->every(fn($id) => in_array((int)$id, $allTindakanIdsSS)) ? 1 : 0;

            $names = array_filter(array_map(fn($id) => $tindakanList[$id] ?? null, $ids));
            $item->NM_TINDAKAN = implode(', ', $names);

            return $item;
        });

        // foreach ($dataKunjungan as $item) {
        //     $ids = explode(',', $item->ARRAY_TINDAKAN ?? '');
        //     $names = array_filter(array_map(function ($id) use ($tindakanList) {
        //         return $tindakanList[$id] ?? null;
        //     }, $ids));
        //     $item->NM_TINDAKAN = implode(', ', $names);
        // }
        // dd($dataKunjungan);
        $dataKunjungan = $dataKunjungan->sortByDesc('TANGGAL_ENTRI')->values();
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
            ->addColumn('NM_TINDAKAN', function ($row) {
                return $row->NM_TINDAKAN ?? '<span class="text-muted">-</span>';
            })
            ->addColumn('action', function ($row) {
                $kdbuku = LZString::compressToEncodedURIComponent($row->KBUKU);
                $kdDok = LZString::compressToEncodedURIComponent($row->kdDok);
                $kdKlinik = LZString::compressToEncodedURIComponent($row->KLINIK_TUJUAN);
                // $idUnit = LZString::compressToEncodedURIComponent($row->ID_UNIT);
                // $param = LZString::compressToEncodedURIComponent($kdbuku . '+' . $kdDok . '+' . $kdKlinik . '+' . $idUnit);

                $idRiwayatElab = LZString::compressToEncodedURIComponent($row->ID_RIWAYAT_ELAB);
                $karcis = LZString::compressToEncodedURIComponent($row->KARCIS_ASAL);
                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                // $kdPerformerSS = LZString::compressToEncodedURIComponent($row->idnakes);
                $kdDokterSS = LZString::compressToEncodedURIComponent($row->idnakes);
                $paramSatuSehat = LZString::compressToEncodedURIComponent($idRiwayatElab . '+' . $karcis . '+' . $kdKlinik . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' . $kdDokterSS);

                if ($row->ID_PASIEN_SS == null) {
                    $btn = '<i class="text-muted">Pasien Belum Mapping Satu Sehat</i>';
                } else if ($row->ID_NAKES_SS == null) {
                    $btn = '<i class="text-muted">Nakes Belum Mapping Satu Sehat</i>';
                } else if ($row->idnakes == null) {
                    $btn = '<i class="text-muted">Dokter Penindak Lanjut Belum Mapping Satu Sehat</i>';
                } else if ($row->AllServiceRequestExist == 0) {
                    $btn = '<i class="text-muted">Tindakan Belum Mapping</i>';
                } else {
                    if ($row->SATUSEHAT == 0) {
                        if ($row->STATUS_SELESAI != "9" && $row->STATUS_SELESAI != "10") {
                            $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                        } else {
                            $btn = '<i class="text-muted">Tunggu Verifikasi Pasien</i>';
                        }
                    } else {
                        $btn = '<a href="#" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                    }
                }
                return $btn;
            })
            ->addColumn('status_integrasi', function ($row) {
                if ($row->SATUSEHAT > 0) {
                    return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                } else {
                    return '<span class="badge badge-pill badge-danger p-2 w-100">Belum Integrasi</span>';
                }
            })
            ->addColumn('status_mapping', function ($row) {
                if ($row->AllServiceRequestExist == 1) {
                    return '<span class="badge badge-pill badge-success p-2 w-100">Semua Tindakan Sudah Mapping</span>';
                } else {
                    return '<span class="badge badge-pill badge-danger p-2 w-100">Tindakan Belum Mapping</span>';
                }
            })
            ->rawColumns(['KLINIK_TUJUAN', 'action', 'status_integrasi', 'status_mapping'])
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

    public function sendSatuSehat(Request $request, $param)
    {
        $param = base64_decode($param);
        $param = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('+', $param);

        $idRiwayatElab = LZString::decompressFromEncodedURIComponent($parts[0]);
        $karcis = LZString::decompressFromEncodedURIComponent($parts[1]);
        $kdKlinik = LZString::decompressFromEncodedURIComponent($parts[2]);
        $kdPasienSS = LZString::decompressFromEncodedURIComponent($parts[3]);
        $kdNakesSS = LZString::decompressFromEncodedURIComponent($parts[4]);
        $kdDokterSS = LZString::decompressFromEncodedURIComponent($parts[5]);
        $id_unit      = '001'; // session('id_klinik');

        $encounter = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
            ->where('karcis', $karcis)
            ->where('idunit', $id_unit)
            ->first();

        $patient = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN')
            ->where('idpx', $kdPasienSS)
            ->first();

        $nakes = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES')
            ->where('idnakes', $kdNakesSS)
            ->first();

        $klinik = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_KLINIK_RADIOLOGI')
            ->where('IDUNIT', $id_unit)
            ->where('KODE_KLINIK', $kdKlinik)
            ->first();

        $dokter = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES')
            ->where('idnakes', $kdDokterSS)
            ->first();

        $kdTindakan = DB::connection('sqlsrv')
            ->table('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB')
            ->where('IDUNIT', $id_unit)
            ->where('ID_RIWAYAT_ELAB', $idRiwayatElab)
            ->value('ARRAY_TINDAKAN');

        $tindakanList = [];
        if ($kdTindakan) {
            // Convert '12,53,24' → [12, 53, 24]
            $ids = array_filter(explode(',', $kdTindakan));
            // dd($ids);

            $serviceRequestCodes = DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE')
                ->whereIn('ID', $ids)
                ->select('ID', 'code', 'codesystem', 'display')
                ->get();
            // dd($serviceRequestCodes);

            $tindakanList = $serviceRequestCodes->map(function ($item) {
                return [
                    "system" => $item->codesystem,
                    "code" => $item->code,
                    "display" => $item->display
                ];
            })->toArray();
        } else {
            $serviceRequestCodes = collect();
        }
        // dd($tindakanList);

        $dateTimeNow = Carbon::now()->toIso8601String();

        $riwayat = DB::connection('sqlsrv')
            ->table('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB')
            ->where('IDUNIT', $id_unit)
            ->where('ID_RIWAYAT_ELAB', $idRiwayatElab)
            ->first();

        $jenisService = [];
        if ($klinik != null) {
            $jenisService = [[
                "system" => "http://snomed.info/sct",
                "code" => "363679005",
                "display" => "Imaging"
            ]];
        } else {
            $jenisService = [[
                "system" => "http://snomed.info/sct",
                "code" => "108252007",
                "display" => "Laboratory procedure"
            ]];
        }

        $baseurl = '';
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }
        // dd($baseurl);

        try {
            $data = [
                "resourceType" => "ServiceRequest",
                "identifier" => [[
                    "system" => "http://sys-ids.kemkes.go.id/servicerequest/{$organisasi}",
                    "value" => "$idRiwayatElab"
                ]],
                "status" => "active",
                "intent" => "original-order",
                "priority" => "routine",
                "category" => [[
                    "coding" => $jenisService
                ]],
                "code" => [
                    "coding" => $tindakanList
                ],
                "subject" => [
                    "reference" => "Patient/{$kdPasienSS}",
                    "display" => "$patient->nama"
                ],
                "encounter" => [
                    "reference" => "Encounter/{$encounter->id_satusehat_encounter}",
                ],
                "occurrenceDateTime" => $dateTimeNow,
                "requester" => [
                    "reference" => "Practitioner/{$kdNakesSS}",
                    "display" => "$nakes->nama"
                ],
                "performer" => [[
                    "reference" => "Practitioner/{$kdDokterSS}",
                    "display" => "$dokter->nama"
                ]],
            ];
            // dd($data);

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }
            // dd($login);

            $token = $login['response']['token'];

            $url = 'ServiceRequest';
            $data = json_decode(json_encode($data));
            $dataServiceRequest = $this->consumeSATUSEHATAPI('POST', $baseurl, $url, $data, true, $token);
            $result = json_decode($dataServiceRequest->getBody()->getContents(), true);
            if ($dataServiceRequest->getStatusCode() >= 400) {
                $response = json_decode($dataServiceRequest->getBody(), true);
                dd($response);

                $this->logError('servicerequest', 'Gagal kirim data service request', [
                    'payload' => $data,
                    'response' => $response,
                    'user_id' => 'system' //Session::get('id')
                ]);

                $this->logDb(json_encode($response), 'ServiceRequest', json_encode($data), 'system'); //Session::get('id')

                $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Kirim Data Service Request';
                throw new Exception($msg, $dataServiceRequest->getStatusCode());
            } else {
                try {
                    $dataKarcis = DB::connection('sqlsrv')
                        ->table('SIRS_PHCM.dbo.RJ_KARCIS as rk')
                        ->select('rk.KARCIS', 'rk.IDUNIT', 'rk.KLINIK', 'rk.TGL', 'rk.KDDOK', 'rk.KBUKU')
                        ->where('rk.KARCIS', $karcis)
                        ->where('rk.IDUNIT', $id_unit)
                        ->orderBy('rk.TGL', 'DESC')
                        ->first();

                    $dataPeserta = DB::connection('sqlsrv')
                        ->table('SIRS_PHCM.dbo.RIRJ_MASTERPX')
                        ->where('KBUKU', $dataKarcis->KBUKU)
                        ->first();

                    DB::connection('sqlsrv')
                        ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST')
                        ->insert([
                            'karcis'                      => $karcis,
                            'nota'                        => $encounter->nota,
                            'idunit'                      => $id_unit,
                            'tgl'                         => Carbon::parse($dataKarcis->TGL, 'Asia/Jakarta')->format('Y-m-d'),
                            'id_satusehat_encounter'      => $encounter->id_satusehat_encounter,
                            'id_satusehat_servicerequest' => $result['id'],
                            'kbuku'                       => $dataPeserta->KBUKU,
                            'no_peserta'                  => $dataPeserta->NO_PESERTA,
                            'id_satusehat_px'             => $kdPasienSS,
                            'kddok'                       => $dataKarcis->KDDOK,
                            'id_satusehat_dokter'         => $kdDokterSS,
                            'kdklinik'                    => $dataKarcis->KLINIK,
                            'status_sinkron'              => 1,
                            'crtdt'                       => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                            'crtusr'                      => 'system', // Session::get('id'),
                            'sinkron_date'                => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                            'jam_datang'                  => Carbon::parse($riwayat->TANGGAL_ENTRI, 'Asia/Jakarta')->format('Y-m-d H:i:s'),
                        ]);

                    $this->logInfo('servicerequest', 'Sukses kirim data service request', [
                        'payload' => $data,
                        'response' => $result,
                        'user_id' => 'system' //Session::get('id')
                    ]);
                    $this->logDb(json_encode($result), 'ServiceRequest', json_encode($data), 'system'); //Session::get('id')

                    return response()->json([
                        'status' => JsonResponse::HTTP_OK,
                        'message' => 'Berhasil Kirim Data Service Request',
                        'redirect' => [
                            'need' => false,
                            'to' => null,
                        ]
                    ], 200);
                } catch (Exception $th) {
                    // dd($th);
                    throw new Exception($th->getMessage(), $th->getCode());
                }
            }
        } catch (Exception $th) {
            return response()->json([
                'status' => $th->getCode() != '' ? $th->getCode() : 500,
                'message' => $th->getMessage() != '' ? $th->getMessage() : 'Gagal Kirim Data Service Request',
                'redirect' => [
                    'need' => false,
                    'to' => null,
                ]
            ], $th->getCode() != '' ? $th->getCode() : 500);
        }
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
