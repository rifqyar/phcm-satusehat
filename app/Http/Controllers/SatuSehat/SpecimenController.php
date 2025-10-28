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
            ->join('SIRS_PHCM.dbo.DR_MDOKTER as dk', 'rd.KDDOK', '=', 'dk.kdDok')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as ss', 'nt.karcis', '=', 'ss.karcis')
            ->leftJoin('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES as nk', 'kc.KDDOK', '=', 'nk.kddok')
            ->select(['rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.ARRAY_TINDAKAN', DB::raw('COUNT(DISTINCT ss.id_satusehat_servicerequest) as SATUSEHAT')])
            ->distinct()
            ->whereBetween('rd.TANGGAL_ENTRI', [$startDate, $endDate])
            ->where('rd.IDUNIT', '001')
            ->where('rd.KLINIK_TUJUAN', '0017')
            ->groupBy('rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.ARRAY_TINDAKAN');

        $labAll = $lab->get();
        $labIntegrasi = $lab->whereNotNull('ss.id_satusehat_servicerequest')->get();

        $total_all_lab = $labAll->count();
        $total_mapped_lab = $labIntegrasi->count();

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
        $id_unit   = '001'; // session('id_klinik');
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
            ->join('SIRS_PHCM.dbo.DR_MDOKTER as dk', 'rd.KDDOK', '=', 'dk.kdDok')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_SPECIMEN as ss', 'nt.karcis', '=', 'ss.karcis')
            ->leftJoin('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES as nk', 'kc.KDDOK', '=', 'nk.kddok')
            ->select(['rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.ARRAY_TINDAKAN', DB::raw('COUNT(DISTINCT ss.id_satusehat_servicerequest) as SATUSEHAT')])
            ->distinct()
            ->whereBetween('rd.TANGGAL_ENTRI', [$tgl_awal, $tgl_akhir])
            ->where('rd.IDUNIT', '001')
            ->where('rd.KLINIK_TUJUAN', '0017')
            ->groupBy('rd.KLINIK_TUJUAN', 'rj.STATUS_SELESAI', 'rd.TANGGAL_ENTRI', 'rd.ID_RIWAYAT_ELAB', 'rj.ID_NAKES_SS', 'rj.NAMA_PASIEN', 'rj.ID_PASIEN_SS', 'dk.kdDok', 'nk.idnakes', 'dk.nmDok', 'rj.NO_PESERTA', 'rj.KBUKU', 'rd.KARCIS_ASAL', 'rd.ARRAY_TINDAKAN');
        // dd($lab->toSql());
        $labAll = $lab->get();
        $labIntegrasi = $lab->whereNotNull('ss.id_satusehat_servicerequest')->get();

        if ($request->input('cari') == 'mapped') {
            $dataKunjungan = $labIntegrasi;
        } else if ($request->input('cari') == 'unmapped') {
            $dataKunjungan = $labAll->filter(function ($item) {
                return $item->SATUSEHAT == '0';
            })->values();
        } else {
            $dataKunjungan = $labAll;
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
                        $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
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

    public function sendSatuSehat($param)
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

        $servicerequest = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST')
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

        $specimenList = [];

        if ($kdTindakan) {
            // Convert '12,53,24' → [12, 53, 24]
            $ids = array_filter(explode(',', $kdTindakan));

            // === 🔗 Get specimen info connected to tindakan IDs ===
            $specimenList = DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING as map')
                ->join('SATUSEHAT.dbo.SATUSEHAT_M_SPECIMEN as sp', 'map.KODE_SPECIMEN', '=', 'sp.CODE')
                ->whereIn('map.KODE_TINDAKAN', $ids)
                ->select('sp.CODE', 'sp.DISPLAY', 'sp.CODESYSTEM')
                ->distinct()
                ->get()
                ->map(function ($item) {
                    return [
                        'system' => $item->CODESYSTEM,
                        'code' => $item->CODE,
                        'display' => $item->DISPLAY,
                    ];
                })
                ->toArray();
        } else {
            $specimenList = [];
        }
        // dd($specimenList);

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
                "resourceType" => "Specimen",
                "identifier" => [[
                    "system" => "http://sys-ids.kemkes.go.id/specimen/{$organisasi}",
                    "value" => "$idRiwayatElab"
                ]],
                "status" => "available",
                "type" => [
                    "coding" => $specimenList
                ],
                "collection" => [
                    "collectedDateTime" => $dateTimeNow,
                    "extension" => [[
                        "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/CollectorOrganization",
                        "valueReference" => [
                            "reference" => "Organization/{$organisasi}",
                        ]
                    ]]
                ],
                "subject" => [
                    "reference" => "Patient/{$kdPasienSS}",
                    "display" => "$patient->nama"
                ],
                "request" => [[
                    "reference" => "ServiceRequest/{$servicerequest->id_satusehat_servicerequest}",
                ]],
                "receivedTime" => $dateTimeNow,
                "extension" => [[
                    "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/TransportedTime",
                    "valueDateTime" => $dateTimeNow
                ]]
            ];
            // dd($data);

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }
            // dd($login);

            $token = $login['response']['token'];

            $url = 'Specimen';
            $data = json_decode(json_encode($data));
            $dataServiceRequest = $this->consumeSATUSEHATAPI('POST', $baseurl, $url, $data, true, $token);
            $result = json_decode($dataServiceRequest->getBody()->getContents(), true);
            if ($dataServiceRequest->getStatusCode() >= 400) {
                $response = json_decode($dataServiceRequest->getBody(), true);
                // dd($response);

                $this->logError('specimen', 'Gagal kirim data specimen', [
                    'payload' => $data,
                    'response' => $response,
                    'user_id' => 'system' //Session::get('id')
                ]);

                $this->logDb(json_encode($response), 'Specimen', json_encode($data), 'system'); //Session::get('id')

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
                        ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_SPECIMEN')
                        ->insert([
                            'karcis'                      => $karcis,
                            'nota'                        => $encounter->nota,
                            'idriwayat'                   => $idRiwayatElab,
                            'idunit'                      => $id_unit,
                            'tgl'                         => Carbon::parse($dataKarcis->TGL, 'Asia/Jakarta')->format('Y-m-d'),
                            'id_satusehat_encounter'      => $encounter->id_satusehat_encounter,
                            'id_satusehat_servicerequest' => $servicerequest->id_satusehat_servicerequest,
                            'id_satusehat_specimen'       => $result['id'],
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

                    $this->logInfo('specimen', 'Sukses kirim data specimen', [
                        'payload' => $data,
                        'response' => $result,
                        'user_id' => 'system' //Session::get('id')
                    ]);
                    $this->logDb(json_encode($result), 'Specimen', json_encode($data), 'system'); //Session::get('id')

                    return response()->json([
                        'status' => JsonResponse::HTTP_OK,
                        'message' => 'Berhasil Kirim Data Specimen',
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
                'message' => $th->getMessage() != '' ? $th->getMessage() : 'Gagal Kirim Data Specimen',
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
