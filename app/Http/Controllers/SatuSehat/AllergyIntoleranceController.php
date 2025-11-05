<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\SATUSEHAT\SATUSEHAT_ALLERGY_INTOLERANCE;
use App\Models\SATUSEHAT\SS_Kode_API;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Yajra\DataTables\DataTables;

class AllergyIntoleranceController extends Controller
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
            $tgl_awal  = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $tgl_akhir = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
        } else {
            $tgl_awal = Carbon::parse($tgl_akhir)->startOfDay()->format('Y-m-d H:i:s');
            $tgl_akhir = Carbon::parse($tgl_awal)->endOfDay()->format('Y-m-d H:i:s');
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


        $totalData = $dataQuery->get();
        $totalAll = $totalData->count();
        $totalSudahIntegrasi = $totalData->where('sudah_integrasi', 1)->count();
        $totalBelumIntegrasi = $totalAll - $totalSudahIntegrasi;

        $totalData = [
            'total_semua' => $totalAll,
            'total_sudah_integrasi' => $totalSudahIntegrasi,
            'total_belum_integrasi' => $totalBelumIntegrasi,
        ];

        $cari = $request->input('cari');
        if ($cari === 'mapped') {
            $dataQuery->whereNotNull('rsi.karcis');
        } elseif ($cari === 'unmapped') {
            $dataQuery->whereNull('rsi.karcis');
        }

        $data = $dataQuery->orderByDesc('vkr.TANGGAL')->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('checkbox', function ($row) {
                $checkBox = '';
                if ($row->sudah_integrasi == '0' && ($row->ID_PASIEN_SS != null && $row->ID_NAKES_SS != null && $row->id_satusehat_encounter != null)) {
                    $checkBox = "
                        <input type='checkbox' class='select-row chk-col-purple' value='$row->KARCIS' id='$row->KARCIS' />
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
                $paramSatuSehat = "karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter";
                $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

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

    public function sendSatuSehat($param)
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
        $id_unit      = '001'; // session('id_klinik');

        $patient = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN')
            ->where('idpx', $arrParam['id_pasien_ss'])
            ->first();

        $encounter = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
            ->where('id_satusehat_encounter', $arrParam['encounter_id'])
            ->first();

        Carbon::setLocale('id');
        $displayTextEncounter = "Kunjungan Pasien A/n $patient->nama Pada " . Carbon::parse($encounter->jam_datang)->translatedFormat('l, d F Y');

        $baseurl = '';
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

        // Get Data Alergi
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
            ->where('eri.KARCIS', $arrParam['karcis'])
            ->where('ea.STATUS_AKTIF', '1')
            ->get();

        // Define Payload Alergy if Alergen > 1
        $payloadAlergyCategory = [];
        $payloadAlergyDetail = [];
        $text = "Pasien A/n $patient->nama Memiliki Alergi Terhadap ";
        foreach ($dataAlergi as $val) {
            // Allergy Category
            if (!in_array($val->JENIS, $payloadAlergyCategory)) {
                array_push($payloadAlergyCategory, $val->JENIS);
            }

            //Alergy Detail
            $codeSystem = $val->JENIS == 'food' ? 'http://snomed.info/sct' : 'http://sys-ids.kemkes.go.id/kfa';
            $value = $val->ID_ALERGEN_SS;
            $display = $val->ALERGEN;
            $text .= "$display, ";

            array_push($payloadAlergyDetail, [
                "system" => "$codeSystem",
                "code" => "$value",
                "display" => "$display",
            ]);
        }

        $text = rtrim($text, ', ');
        $identifier = now()->timestamp;

        try {
            $payload = [
                "resourceType" => "AllergyIntolerance",
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/allergy/$organisasi",
                        "use" => "official",
                        "value" => "$identifier",
                    ],
                ],
                "clinicalStatus" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical",
                            "code" => "active",
                            "display" => "Active",
                        ],
                    ],
                ],
                "verificationStatus" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/allergyintolerance-verification",
                            "code" => "confirmed",
                            "display" => "Confirmed",
                        ],
                    ],
                ],
                "category" => $payloadAlergyCategory,
                "code" => [
                    "coding" => $payloadAlergyDetail,
                    "text" => "$text",
                ],
                "patient" => [
                    "reference" => "Patient/" . $arrParam['id_pasien_ss'],
                    "display" => "$patient->nama",
                ],
                "encounter" => [
                    "reference" => "Encounter/" . $arrParam['encounter_id'],
                    "display" => "$displayTextEncounter",
                ],
                "recordedDate" => Carbon::now('Asia/Jakarta')->toIso8601String(),
                "recorder" => [
                    "reference" => "Practitioner/" . $arrParam['id_nakes_ss'],
                ],
            ];

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }

            $token = $login['response']['token'];

            $url = 'AllergyIntolerance';
            $dataencounter = $this->consumeSATUSEHATAPI('POST', $baseurl, $url, $payload, true, $token);
            $result = json_decode($dataencounter->getBody()->getContents(), true);
            if ($dataencounter->getStatusCode() >= 400) {
                $response = json_decode($dataencounter->getBody(), true);

                $this->logError($url, 'Gagal kirim data Allergy Intolerance', [
                    'payload' => $payload,
                    'response' => $response,
                    'user_id' => 'system' //Session::get('id')
                ]);

                $this->logDb(json_encode($response), $url, json_encode($payload), 'system'); //Session::get('id')

                $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Kirim Data Encounter';
                throw new Exception($msg, $dataencounter->getStatusCode());
            } else {
                DB::beginTransaction();
                try {
                    $dataKarcis = DB::table('RJ_KARCIS as rk')
                        ->select('rk.KARCIS', 'rk.IDUNIT', 'rk.KLINIK', 'rk.TGL', 'rk.KDDOK', 'rk.KBUKU')
                        ->where('rk.KARCIS', $arrParam['karcis'])
                        ->where('rk.IDUNIT', $id_unit)
                        ->orderBy('rk.TGL', 'DESC')
                        ->first();

                    $dataPeserta = DB::table('RIRJ_MASTERPX')
                        ->where('KBUKU', $dataKarcis->KBUKU)
                        ->first();

                    $allergy_satusehat = new SATUSEHAT_ALLERGY_INTOLERANCE();
                    $allergy_satusehat->karcis = (int)$dataKarcis->KARCIS;
                    $allergy_satusehat->no_peserta = $dataPeserta->NO_PESERTA;
                    $allergy_satusehat->kbuku = $dataKarcis->KBUKU;
                    $allergy_satusehat->id_satusehat_encounter = $arrParam['encounter_id'];
                    $allergy_satusehat->id_allergy_intolerance = $result['id'];
                    $allergy_satusehat->crtdt = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $allergy_satusehat->crtusr = 'system';
                    $allergy_satusehat->status_sinkron = 1;
                    $allergy_satusehat->sinkron_date = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $allergy_satusehat->save();

                    $this->logInfo($url, 'Sukses kirim data Allergy intolerance', [
                        'payload' => $payload,
                        'response' => $result,
                        'user_id' => 'system' //Session::get('id')
                    ]);
                    $this->logDb(json_encode($result), $url, json_encode($payload), 'system'); //Session::get('id')

                    DB::commit();
                    return response()->json([
                        'status' => JsonResponse::HTTP_OK,
                        'message' => 'Berhasil Kirim Data Allergy Intolerance',
                        'redirect' => [
                            'need' => false,
                            'to' => null,
                        ]
                    ], 200);
                } catch (Exception $th) {
                    throw new Exception($th->getMessage(), $th->getCode());
                }
            }
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

    public function sendBulking(Request $request)
    {
        $selectedAll = isset($request->selectAll) ? true : false;
        $id_unit      = '001'; // session('id_klinik');
        if (!$selectedAll) {
            /**
             * To Do
             * - Ambil data pasien dari karcis
             * - Ambil data pasien Mapping SS join kbuku
             * - Ambil data E_RM_IRJA dari data karcis
             * - Ambil data dokter dari E_RM_IRJA join ke dokter mapping SS
             * - Ambil data Encounter sesuai karcis
             * - Ambil data alergi join dari E_RM_IRJA
             */

            $arrKarcis = $request->karcis;
            $dataKunjungan = DB::table('v_kunjungan_rj as vkr')
                ->select([
                    'vkr.ID_TRANSAKSI as KARCIS',
                    'vkr.NO_PESERTA',
                    'vkr.KBUKU',
                    'vkr.NAMA_PASIEN',
                    'vkr.ID_PASIEN_SS',
                    'vkr.DOKTER',
                    'vkr.ID_NAKES_SS',
                    'rsn.id_satusehat_encounter'
                ])
                ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as rsn', 'vkr.ID_TRANSAKSI', 'rsn.karcis')
                ->whereIn("vkr.ID_TRANSAKSI", $arrKarcis)
                ->groupBy([
                    'vkr.ID_TRANSAKSI',
                    'vkr.NO_PESERTA',
                    'vkr.KBUKU',
                    'vkr.NAMA_PASIEN',
                    'vkr.ID_PASIEN_SS',
                    'vkr.DOKTER',
                    'vkr.ID_NAKES_SS',
                    'rsn.id_satusehat_encounter'
                ])
                ->get();
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
