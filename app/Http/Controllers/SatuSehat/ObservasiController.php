<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\SATUSEHATTraits;
use App\Jobs\SendObservationToSATUSEHAT;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\SATUSEHAT\SS_Kode_API;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $data = DB::table('v_kunjungan_rj as vkr')
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
            ->orderByDesc(DB::raw('MAX(vkr.TANGGAL)'))
            ->get();

        $totalAll = $data->count();
        $totalSudahIntegrasi = $data->where('sudah_integrasi', 1)->count();
        $totalBelumIntegrasi = $totalAll - $totalSudahIntegrasi;

        $result = [
            'total_semua' => $totalAll,
            'total_sudah_integrasi' => $totalSudahIntegrasi,
            'total_belum_integrasi' => $totalBelumIntegrasi,
        ];

        return view('pages.satusehat.observation.index', compact('result'));
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
            ->groupBy(['vkr.ID_TRANSAKSI', 'eri.NOMOR']);

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
            $dataQuery->whereNotNull('rsp.karcis');
        } elseif ($cari === 'unmapped') {
            $dataQuery->whereNull('rsp.karcis');
        }

        $data = $dataQuery->orderByDesc(DB::raw('MAX(vkr.TANGGAL)'))->get();

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
                $paramSatuSehat = "sudah_integrasi=$row->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter";
                $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

                $param = LZString::compressToEncodedURIComponent("karcis=$id_transaksi&kbuku=$KbBuku");
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
        $id_unit = '001';

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

            $payloadTD = $this->definePayloadTD($dataErm, $organisasi);
            $payloadDJ = $this->definePayloadDJ($dataErm, $organisasi);
            $payloadTinggi = $this->definePayloadTinggi($dataErm, $organisasi);
            $payloadBerat = $this->definePayloadBerat($dataErm, $organisasi);

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }

            $token = $login['response']['token'];

            $url = 'Observation';

            $payloadSystolic = array_merge($basePayload, $payloadTD[0]);
            $payloadDiastolic = array_merge($basePayload, $payloadTD[1]);
            $payloadDJ = array_merge($basePayload, $payloadDJ);
            $payloadTinggi = array_merge($basePayload, $payloadTinggi);
            $payloadBerat = array_merge($basePayload, $payloadBerat);

            $dataKarcis = DB::table('RJ_KARCIS as rk')
                ->select('rk.KARCIS', 'rk.IDUNIT', 'rk.KLINIK', 'rk.TGL', 'rk.KDDOK', 'rk.KBUKU')
                ->where('rk.KARCIS', $arrParam['karcis'])
                ->where('rk.IDUNIT', $id_unit)
                ->orderBy('rk.TGL', 'DESC')
                ->first();

            $dataPeserta = DB::table('RIRJ_MASTERPX')
                ->where('KBUKU', $dataKarcis->KBUKU)
                ->first();

            SendObservationToSATUSEHAT::dispatch($payloadSystolic, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'sistolik');
            SendObservationToSATUSEHAT::dispatch($payloadDiastolic, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'diastolik');
            SendObservationToSATUSEHAT::dispatch($payloadDJ, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'denyut_jantung');
            SendObservationToSATUSEHAT::dispatch($payloadTinggi, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'tinggi_badan');
            SendObservationToSATUSEHAT::dispatch($payloadBerat, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'berat_badan');

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
