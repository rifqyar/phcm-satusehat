<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Jobs\SendSpecimenJob;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\SATUSEHAT\SS_Kode_API;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\DataTables;

class SpecimenController extends Controller
{
    use SATUSEHATTraits, LogTraits;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('pages.satusehat.specimen.index');
    }

    public function summary(Request $request)
    {
        $startDate  = $request->input('tgl_awal');
        $endDate    = $request->input('tgl_akhir');

        // Set default date range if empty
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfDay();
        $endDate   = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $connection = DB::connection('sqlsrv');

        $lab = $connection
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
            ->select(['rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.KARCIS_RUJUKAN', 'rd.ARRAY_TINDAKAN', DB::raw('COUNT(DISTINCT ss.id_satusehat_servicerequest) as SATUSEHAT')])
            ->distinct()
            ->whereBetween('rd.TANGGAL_ENTRI', [$startDate, $endDate])
            ->where('rd.IDUNIT', '001')
            ->where('rd.KLINIK_TUJUAN', '0017')
            ->whereNull('kc.TGL_BATAL')
            ->groupBy('rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.KARCIS_RUJUKAN', 'rd.ARRAY_TINDAKAN');

        $labAll = $lab->get();
        $labIntegrasi = $lab->whereNotNull('ss.id_satusehat_servicerequest')->get();

        $lab_ri = $connection
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
            ->select(['rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.KARCIS_RUJUKAN', 'rd.ARRAY_TINDAKAN', DB::raw('COUNT(DISTINCT ss.id_satusehat_servicerequest) as SATUSEHAT')])
            ->distinct()
            ->whereBetween('rd.TANGGAL_ENTRI', [$startDate, $endDate])
            ->where('rd.IDUNIT', '001')
            ->where('rd.KLINIK_TUJUAN', '0017')
            ->whereNull('kc.TGL_BATAL')
            ->groupBy('rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.KARCIS_RUJUKAN', 'rd.ARRAY_TINDAKAN');

        $lab_ri_all = $lab_ri->get();
        $lab_ri_integrasi = $lab_ri->whereNotNull('ss.id_satusehat_servicerequest')->get();

        $total_all_lab = $labAll->count() + $lab_ri_all->count();
        $total_mapped_lab = $labIntegrasi->count() + $lab_ri_integrasi->count();

        // Calculate unmapped counts
        $total_unmapped_lab = $total_all_lab - $total_mapped_lab;

        // Return JSON response
        return response()->json([
            'total_all_lab' => $total_all_lab,
            'total_all_combined' => $total_all_lab,
            'total_mapped_lab' => $total_mapped_lab,
            'total_mapped_combined' => $total_mapped_lab,
            'total_unmapped_lab' => $total_unmapped_lab,
            'total_unmapped_combined' => $total_unmapped_lab,
        ]);
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $id_unit = Session::get('id_unit', $arrParam['id_unit'] ?? null);
        // dd($request->all());

        if (empty($tgl_awal) && empty($tgl_akhir)) {
            $tgl_awal  = Carbon::now()->startOfDay();
            $tgl_akhir = Carbon::now()->endOfDay();
        } else {
            if (empty($tgl_awal)) {
                $tgl_awal = Carbon::parse($tgl_akhir)->startOfDay();
            }
            if (empty($tgl_akhir)) {
                $tgl_akhir = Carbon::now()->endOfDay();
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

        $lab = DB::connection('sqlsrv')
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
            ->whereBetween('rd.TANGGAL_ENTRI', [$tgl_awal, $tgl_akhir])
            ->where('rd.IDUNIT', $id_unit)
            ->wherein('rd.KLINIK_TUJUAN', ['0017', '0031'])
            ->whereNull('kc.TGL_BATAL')
            ->groupBy('rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dkd.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.KARCIS_RUJUKAN', 'rd.ARRAY_TINDAKAN');

        $labAll = $lab->get();
        // dd($labAll);
        $labIntegrasi = $lab->whereNotNull('ss.id_satusehat_servicerequest')->get();

        $lab_ri = DB::connection('sqlsrv')
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
            ->whereBetween('rd.TANGGAL_ENTRI', [$tgl_awal, $tgl_akhir])
            ->where('rd.IDUNIT', $id_unit)
            ->wherein('rd.KLINIK_TUJUAN', ['0017', '0031'])
            ->whereNull('kc.TGL_BATAL')
            ->groupBy('rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dkd.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.KARCIS_RUJUKAN', 'rd.ARRAY_TINDAKAN');

        // dd($lab_ri->toSql());

        $lab_ri_all = $lab_ri->get();
        $lab_ri_integrasi = $lab_ri->whereNotNull('ss.id_satusehat_servicerequest')->get();

        // Merge outpatient and inpatient data
        $mergedAll = $labAll->merge($lab_ri_all)->sortByDesc('TANGGAL_ENTRI')->values();
        $mergedIntegrated = $labIntegrasi->merge($lab_ri_integrasi)->sortByDesc('TANGGAL_ENTRI')->values();

        if ($request->input('cari') == 'mapped') {
            $dataKunjungan = $mergedIntegrated;
        } else if ($request->input('cari') == 'unmapped') {
            $dataKunjungan = $mergedAll->filter(function ($item) {
                return $item->SATUSEHAT == '0';
            })->values();
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

        $specimenRaw = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING as map')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SPECIMEN as ms', 'map.KODE_SPECIMEN', '=', 'ms.CODE')
            ->select('map.KODE_TINDAKAN', 'map.KODE_SPECIMEN', 'ms.DISPLAY')
            ->get();

        $specimenMapping = $specimenRaw
            ->groupBy('KODE_TINDAKAN')
            ->map(function ($rows) {
                return $rows->map(function ($r) {
                    return [
                        'code' => $r->KODE_SPECIMEN,
                        'name' => $r->DISPLAY,
                    ];
                })->toArray();
            })
            ->toArray();
        // dd($specimenMappingRaw);

        $dataKunjungan = collect($dataKunjungan)->map(function ($item) use ($allTindakanIdsSS, $tindakanList, $specimenMapping) {
            $ids = array_filter(explode(',', $item->ARRAY_TINDAKAN ?? ''));

            // Check if all IDs exist
            $allExist = count($ids) > 0 && collect($ids)->every(function ($id) use ($allTindakanIdsSS) {
                return in_array((int)$id, $allTindakanIdsSS);
            });

            $item->AllServiceRequestExist = $allExist ? 1 : 0;

            $item->NM_TINDAKAN = implode(', ', array_filter(array_map(function ($id) use ($tindakanList) {
                return isset($tindakanList[$id]) ? $tindakanList[$id] : null;
            }, $ids)));

            $specimens = collect($ids)
                ->flatMap(function ($id) use ($specimenMapping) {
                    return isset($specimenMapping[trim($id)]) ? $specimenMapping[trim($id)] : [];
                })
                ->unique('code')
                ->values()
                ->toArray();

            $item->SPECIMEN_CODES = implode(', ', array_column($specimens, 'code'));
            $item->SPECIMEN_NAMES = implode(', ', array_column($specimens, 'name'));

            return $item;
        });

        $dataKunjungan = $dataKunjungan->sortByDesc('TANGGAL_ENTRI')->values();
        // dd($dataKunjungan);

        return DataTables::of($dataKunjungan)
            ->addIndexColumn()
            ->addColumn('checkbox', function ($row) {
                $idRiwayatElab = LZString::compressToEncodedURIComponent($row->ID_RIWAYAT_ELAB);
                $karcisAsal = LZString::compressToEncodedURIComponent($row->KARCIS_ASAL);
                $karcisRujukan = LZString::compressToEncodedURIComponent($row->KARCIS_RUJUKAN);
                $kdKlinik = LZString::compressToEncodedURIComponent($row->KLINIK_TUJUAN);
                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                $kdDokterSS = LZString::compressToEncodedURIComponent($row->idnakes);
                $paramSatuSehat = LZString::compressToEncodedURIComponent($idRiwayatElab . '+' . $karcisAsal . '+' . $karcisRujukan . '+' . $kdKlinik . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' . $kdDokterSS);

                $checkBox = '';
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
                            $uniqueId = 'checkbox_' . md5($paramSatuSehat);
                            $checkBox = "
                        <input type='checkbox' class='select-row chk-col-purple' value='$paramSatuSehat' id='$uniqueId' />
                        <label for='$uniqueId' style='margin-bottom: 0px !important; line-height: 25px !important; font-weight: 500'> &nbsp; </label>
                    ";
                        }
                    }
                }

                return $checkBox;
            })
            ->editColumn('KLINIK_TUJUAN', function ($row) {
                return $row->KLINIK_TUJUAN == '0017' ? '<span class="badge badge-pill badge-success p-2 w-100">Laboratory</span>' : '<span class="badge badge-pill badge-info p-2 w-100">Radiology</span>';
            })
            ->editColumn('TANGGAL_ENTRI', function ($row) {
                return date('Y-m-d H:i:s', strtotime($row->TANGGAL_ENTRI));
            })
            ->editColumn('nmDok', function ($row) {
                return $row->nmDok ?? 'Dokter tidak ditemukan';
            })
            ->addColumn('NM_TINDAKAN', function ($row) {
                return $row->NM_TINDAKAN ?? 'Tindakan tidak ditemukan';
            })
            ->addColumn('SPECIMEN_NAMES', function ($row) {
                return $row->SPECIMEN_NAMES ?? 'Specimen tidak ditemukan';
            })
            ->editColumn('JENIS_PERAWATAN', function ($row) {
                return $row->JENIS_PERAWATAN;
            })
            ->addColumn('action', function ($row) use ($id_unit) {
                $idRiwayatElab = LZString::compressToEncodedURIComponent($row->ID_RIWAYAT_ELAB);
                $karcisAsal = LZString::compressToEncodedURIComponent($row->KARCIS_ASAL);
                $karcisRujukan = LZString::compressToEncodedURIComponent($row->KARCIS_RUJUKAN);
                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                $kdDokterSS = LZString::compressToEncodedURIComponent($row->idnakes);
                $id_unit = LZString::compressToEncodedURIComponent($id_unit);
                $kdKlinik = LZString::compressToEncodedURIComponent($row->KLINIK_TUJUAN);
                $paramSatuSehat = LZString::compressToEncodedURIComponent($idRiwayatElab . '+' . $karcisAsal . '+' . $karcisRujukan . '+' . $kdKlinik . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' . $kdDokterSS . '+' . $id_unit);

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
                        $btn = '<a href="javascript:void(0)" onclick="resendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
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
            ->rawColumns(['checkbox', 'KLINIK_TUJUAN', 'JENIS_PERAWATAN', 'action', 'status_integrasi', 'status_mapping'])
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

    public function sendSatuSehat($param, $resend = false)
    {
        $startedAt = microtime(true);

        try {

            /**
             * ==================================================
             * DECODE PARAM
             * ==================================================
             */
            $p = $this->decodeSpecimenParam($param);

            $idRiwayat = $p['idRiwayatElab'];
            $karcisAsal = $p['karcisAsal'];
            $karcisRujukan = $p['karcisRujukan'];
            $kdKlinik = $p['kdKlinik'];
            $patientId = $p['kdPasienSS'];
            $doctorId = $p['kdDokterSS'];
            $idUnit = Session::get('id_unit', $p['idUnit']);

            /**
             * ==================================================
             * CONFIG + TOKEN (CACHE)
             * ==================================================
             */
            $config = $this->getFastSatuSehatConfig($idUnit);
            $token  = $this->getFastSatuSehatToken($idUnit);

            /**
             * ==================================================
             * MASTER DATA
             * ==================================================
             */
            $master = $this->getSpecimenMasterData(
                $idUnit,
                $karcisAsal,
                $karcisRujukan,
                $idRiwayat,
                $patientId,
                $kdKlinik
            );

            /**
             * ==================================================
             * RESEND MODE
             * ==================================================
             */
            $oldSpecimen = null;

            if ($resend) {
                $oldSpecimen = DB::connection('sqlsrv')
                    ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_SPECIMEN')
                    ->select('id_satusehat_specimen')
                    ->where('karcis', $karcisRujukan)
                    ->where('idriwayat', $idRiwayat)
                    ->where('idunit', $idUnit)
                    ->first();

                if (!$oldSpecimen) {
                    throw new Exception('Data resend specimen tidak ditemukan');
                }
            }

            /**
             * ==================================================
             * BUILD PAYLOAD
             * ==================================================
             */
            $payload = $this->buildFastSpecimenPayload(
                $master,
                $config['org_id'],
                $patientId,
                $idRiwayat,
                $oldSpecimen
            );

            /**
             * ==================================================
             * METHOD + URL
             * ==================================================
             */
            $method = $resend ? 'PUT' : 'POST';

            $url = $resend
                ? 'Specimen/' . $oldSpecimen->id_satusehat_specimen
                : 'Specimen';

            /**
             * ==================================================
             * SEND API
             * ==================================================
             */
            $response = $this->consumeSATUSEHATAPI(
                $method,
                $config['baseurl'],
                $url,
                json_decode(json_encode($payload)),
                true,
                $token
            );

            $result = json_decode(
                $response->getBody()->getContents(),
                true
            );

            if ($response->getStatusCode() >= 400) {
                throw new Exception(
                    $result['issue'][0]['details']['text']
                        ?? 'Gagal kirim specimen',
                    $response->getStatusCode()
                );
            }

            /**
             * ==================================================
             * SAVE LOG
             * ==================================================
             */
            $this->saveFastSpecimenLog(
                $karcisRujukan,
                $master,
                $result,
                $idRiwayat,
                $idUnit,
                $patientId,
                $doctorId,
                $resend,
                $payload
            );

            return response()->json([
                'status' => 200,
                'message' => $resend
                    ? 'Berhasil update Specimen'
                    : 'Berhasil kirim Specimen',
                'duration_ms' =>
                round((microtime(true) - $startedAt) * 1000),
            ], 200);
        } catch (Exception $e) {

            $this->logError('Specimen', 'Gagal kirim specimen', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ==========================================================
     * FAST CONFIG CACHE
     * ==========================================================
     */
    private function getFastSatuSehatConfig($idUnit)
    {
        return Cache::remember(
            'satusehat_cfg_' . $idUnit,
            3600,
            function () use ($idUnit) {

                $isDev =
                    strtoupper(env('SATUSEHAT')) == 'DEVELOPMENT';

                return [
                    'baseurl' => GlobalParameter::where(
                        'tipe',
                        $isDev
                            ? 'SATUSEHAT_BASEURL_STAGING'
                            : 'SATUSEHAT_BASEURL'
                    )->value('valStr'),

                    'org_id' => SS_Kode_API::where('idunit', $idUnit)
                        ->where('env', $isDev ? 'Dev' : 'Prod')
                        ->value('org_id')
                ];
            }
        );
    }

    private function decodeSpecimenParam($param)
    {
        $param = base64_decode($param);
        $param = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('+', $param);

        return [
            'idRiwayatElab' => LZString::decompressFromEncodedURIComponent($parts[0]),
            'karcisAsal'    => LZString::decompressFromEncodedURIComponent($parts[1]),
            'karcisRujukan' => LZString::decompressFromEncodedURIComponent($parts[2]),
            'kdKlinik'      => LZString::decompressFromEncodedURIComponent($parts[3]),
            'kdPasienSS'    => LZString::decompressFromEncodedURIComponent($parts[4]),
            'kdNakesSS'     => LZString::decompressFromEncodedURIComponent($parts[5]),
            'kdDokterSS'    => LZString::decompressFromEncodedURIComponent($parts[6]),
            'idUnit'        => LZString::decompressFromEncodedURIComponent($parts[7]),
        ];
    }

    /**
     * ==========================================================
     * FAST TOKEN CACHE
     * ==========================================================
     */
    private function getFastSatuSehatToken($idUnit)
    {
        return Cache::remember(
            'satusehat_token_' . $idUnit,
            3300,
            function () use ($idUnit) {

                $login = $this->login($idUnit);

                if (($login['metadata']['code'] ?? 500) != 200) {
                    throw new Exception('Login gagal');
                }

                return $login['response']['token'];
            }
        );
    }

    /**
     * ==========================================================
     * MASTER DATA MINIMAL QUERY
     * ==========================================================
     */
    private function getSpecimenMasterData(
        $idUnit,
        $karcisAsal,
        $karcisRujukan,
        $idRiwayat,
        $patientId,
        $kdKlinik
    ) {
        return [

            'encounter' => DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
                ->select('nota', 'id_satusehat_encounter')
                ->where('karcis', $karcisAsal)
                ->where('idunit', $idUnit)
                ->first(),

            'riwayat' => DB::connection('sqlsrv')
                ->table('vw_getData_Elab')
                ->select('TANGGAL_ENTRI')
                ->where('IDUNIT', $idUnit)
                ->where('ID_RIWAYAT_ELAB', $idRiwayat)
                ->first(),

            'service' => DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST')
                ->select('id_satusehat_servicerequest')
                ->where('karcis', $karcisRujukan)
                ->where('idunit', $idUnit)
                ->first(),

            'patient' => DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN')
                ->select('nama')
                ->where('idpx', $patientId)
                ->first(),

            'klinik' => DB::connection('sqlsrv')
                ->table('SIRS_PHCM.dbo.RJ_KLINIK_RADIOLOGI')
                ->select('KODE_KLINIK')
                ->where('IDUNIT', $idUnit)
                ->where('KODE_KLINIK', $kdKlinik)
                ->first()
        ];
    }

    /**
     * ==========================================================
     * FAST PAYLOAD
     * ==========================================================
     */
    private function buildFastSpecimenPayload(
        $master,
        $orgId,
        $patientId,
        $idRiwayat,
        $old = null
    ) {
        $now = now()->toIso8601String();

        $coding = $master['klinik']
            ? [
                [
                    'system' => 'http://snomed.info/sct',
                    'code' => '363679005',
                    'display' => 'Imaging'
                ]
            ]
            : [
                [
                    'system' => 'http://snomed.info/sct',
                    'code' => '108252007',
                    'display' => 'Laboratory procedure'
                ]
            ];

        $payload = [
            'resourceType' => 'Specimen',
            'identifier' => [[
                'system' =>
                "http://sys-ids.kemkes.go.id/specimen/{$orgId}",
                'value' => $idRiwayat
            ]],
            'status' => 'available',
            'type' => ['coding' => $coding],
            'subject' => [
                'reference' => 'Patient/' . $patientId,
                'display' => $master['patient']->nama
            ],
            'request' => [[
                'reference' =>
                'ServiceRequest/' .
                    $master['service']->id_satusehat_servicerequest
            ]],
            'receivedTime' => $now
        ];

        if ($old) {
            $payload['id'] = $old->id_satusehat_specimen;
        }

        return $payload;
    }

    private function saveFastSpecimenLog(
        $karcisRujukan,
        $master,
        $result,
        $idRiwayat,
        $idUnit,
        $patientId,
        $doctorId,
        $resend,
        $payload
    ) {
        $table = 'SATUSEHAT.dbo.SATUSEHAT_LOG_SPECIMEN';

        $now = now();

        /**
         * ===================================================
         * OPTIONAL MASTER DATA PESERTA / KARCIS
         * ===================================================
         */
        $karcisData = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_KARCIS')
            ->select('KBUKU', 'KDDOK', 'KLINIK')
            ->where('KARCIS', $karcisRujukan)
            ->where('IDUNIT', $idUnit)
            ->first();

        $peserta = null;

        if ($karcisData && !empty($karcisData->KBUKU)) {
            $peserta = DB::connection('sqlsrv')
                ->table('SIRS_PHCM.dbo.RIRJ_MASTERPX')
                ->select('NO_PESERTA')
                ->where('KBUKU', $karcisData->KBUKU)
                ->first();
        }

        /**
         * ===================================================
         * DATA LOG
         * ===================================================
         */
        $data = [
            'karcis'                      => $karcisRujukan,
            'nota'                        => $master['encounter']->nota ?? null,
            'idriwayat'                   => $idRiwayat,
            'idunit'                      => $idUnit,
            'tgl'                         => $now->format('Y-m-d'),

            'id_satusehat_encounter'      => $master['encounter']->id_satusehat_encounter ?? null,
            'id_satusehat_servicerequest' => $master['service']->id_satusehat_servicerequest ?? null,
            'id_satusehat_specimen'       => $result['id'] ?? null,

            'kbuku'                       => $karcisData->KBUKU ?? null,
            'no_peserta'                  => $peserta->NO_PESERTA ?? null,

            'id_satusehat_px'             => $patientId,
            'kddok'                       => $karcisData->KDDOK ?? null,
            'id_satusehat_dokter'         => $doctorId,
            'kdklinik'                    => $karcisData->KLINIK ?? null,

            'status_sinkron'              => 1,

            'sinkron_date'                => $now,
            'jam_datang'                  => $master['riwayat']->TANGGAL_ENTRI ?? $now,
            'jam_progress'                => $now,
            'jam_selesai'                 => $now,
        ];

        /**
         * ===================================================
         * CHECK EXISTING
         * ===================================================
         */
        $existing = DB::connection('sqlsrv')
            ->table($table)
            ->where('karcis', $karcisRujukan)
            ->where('idriwayat', $idRiwayat)
            ->where('idunit', $idUnit)
            ->first();

        /**
         * ===================================================
         * UPDATE
         * ===================================================
         */
        if ($existing) {

            DB::connection('sqlsrv')
                ->table($table)
                ->where('id', $existing->id)
                ->update($data);
        } else {

            $data['crtdt']  = $now;
            $data['crtusr'] = 'system';

            DB::connection('sqlsrv')
                ->table($table)
                ->insert($data);
        }

        /**
         * ===================================================
         * GLOBAL LOG API
         * ===================================================
         */
        $this->logDb(
            json_encode($result),
            'Specimen',
            json_encode($payload),
            'system',
            1
        );
    }

    public function resendSatusehat($param)
    {
        return $this->sendSatuSehat($param, true);
    }

    public function bulkSendSatuSehat(Request $request)
    {
        try {
            $selectedIds = $request->input('selected_ids', []);

            if (empty($selectedIds)) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Tidak ada data yang dipilih untuk dikirim'
                ], 422);
            }

            $dispatched = 0;
            $failed = 0;
            $errors = [];

            foreach ($selectedIds as $param) {
                try {
                    // Add base64 encoding before dispatching the job
                    $encodedParam = base64_encode($param);

                    // Dispatch job to queue for background processing
                    SendSpecimenJob::dispatch($encodedParam)->onQueue('specimen');
                    $dispatched++;
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Failed to dispatch param: " . substr($param, 0, 20) . "... - " . $e->getMessage();

                    Log::error('Failed to dispatch SendSpecimenJob', [
                        'param' => $param,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log the bulk dispatch
            Log::info('Bulk specimen jobs dispatched', [
                'total_dispatched' => $dispatched,
                'total_failed' => $failed,
                'user_id' => Session::get('nama', 'system'), // You can use Session::get('id') if needed
                'params_count' => count($selectedIds)
            ]);

            $message = "Berhasil mengirim {$dispatched} specimen ke antrian untuk diproses. Pengiriman akan berlanjut di background.";
            if ($failed > 0) {
                $message .= " {$failed} gagal dikirim.";
            }

            return response()->json([
                'status' => JsonResponse::HTTP_OK,
                'message' => $message,
                'data' => [
                    'dispatched_count' => $dispatched,
                    'failed_count' => $failed,
                    'total_selected' => count($selectedIds),
                    'errors' => array_slice($errors, 0, 3) // Show first 3 errors
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('Bulk specimen dispatch failed', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('nama', 'system') // Session::get('id')
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Gagal mengirim ke antrian specimen: ' . $e->getMessage()
            ], 500);
        }
    }

    public function receiveSatuSehat(Request $request)
    {
        $lab = DB::connection('sqlsrv')
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
            ->where('rd.KARCIS_RUJUKAN', $request->karcis)
            ->where('rd.IDUNIT', Session::get('id_unit', '001'))
            ->where('rd.KLINIK_TUJUAN', $request->klinik)
            ->whereNull('kc.TGL_BATAL')
            ->groupBy('rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dkd.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.KARCIS_RUJUKAN', 'rd.ARRAY_TINDAKAN')
            ->first();

        $idRiwayatElab = LZString::compressToEncodedURIComponent($lab->ID_RIWAYAT_ELAB);
        $karcisAsal = LZString::compressToEncodedURIComponent($lab->KARCIS_ASAL);
        $karcisRujukan = LZString::compressToEncodedURIComponent($lab->KARCIS_RUJUKAN);
        $kdPasienSS = LZString::compressToEncodedURIComponent($lab->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($lab->ID_NAKES_SS);
        // $kdPerformerSS = LZString::compressToEncodedURIComponent($lab->idnakes);
        $kdDokterSS = LZString::compressToEncodedURIComponent($lab->idnakes);
        $paramSatuSehat = LZString::compressToEncodedURIComponent($idRiwayatElab . '+' . $karcisAsal . '+' . $karcisRujukan . '+' . $request->klinik . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' . $kdDokterSS);

        $encodedParam = base64_encode($paramSatuSehat);
        SendSpecimenJob::dispatch($encodedParam)->onQueue('specimen');
        // self::sendSatuSehat(base64_encode($paramSatuSehat));
    }

    public function getDataSpecimenQueue($parts)
    {
        $idRiwayatElab = LZString::decompressFromEncodedURIComponent($parts[0]);
        $karcisAsal = LZString::decompressFromEncodedURIComponent($parts[1]);
        $karcisRujukan = LZString::decompressFromEncodedURIComponent($parts[2]);
        $kdKlinik = LZString::decompressFromEncodedURIComponent($parts[3]);
        $kdPasienSS = LZString::decompressFromEncodedURIComponent($parts[4]);
        $kdNakesSS = LZString::decompressFromEncodedURIComponent($parts[5]);
        $kdDokterSS = LZString::decompressFromEncodedURIComponent($parts[6]);
        $idUnit = LZString::decompressFromEncodedURIComponent($parts[7]);

        $id_unit = Session::get('id_unit', $idUnit ?? null);

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

        $resParam['Karcis'] = $karcisRujukan ?? 'not found';
        $resParam['Pasien'] = $patient ? $patient->nama : "not found";
        $resParam['Dokter'] = $nakes ? $nakes->nama : "not found";
        $resParam['Klinik'] = $klinik ? "Rad" : "Lab";

        return $resParam;
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
