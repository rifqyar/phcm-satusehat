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
            ->selectRaw('
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
                CASE WHEN MAX(rsp.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_integrasi,
                CASE WHEN MAX(eri.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
            ')
            ->groupBy('vkr.ID_TRANSAKSI')
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

        return view('pages.satusehat.procedure.index', compact('result'));
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
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE as rsp', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'rsp.KARCIS')
                    ->on('vkr.KBUKU', '=', 'rsp.KBUKU');
            })
            ->where('eri.AKTIF', 1)
            ->whereBetween('vkr.TANGGAL', [$tgl_awal_db, $tgl_akhir_db])
            ->selectRaw('
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
                CASE WHEN MAX(rsp.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_integrasi,
                CASE WHEN MAX(eri.KARCIS) IS NOT NULL THEN 1 ELSE 0 END as sudah_proses_dokter
            ')
            ->groupBy('vkr.ID_TRANSAKSI');

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
            ])
            ->leftJoin('E_RM_PHCM.dbo.ERM_RM_IRJA as eri', function ($join) {
                $join->on('vkr.ID_TRANSAKSI', '=', 'eri.KARCIS');
            })
            ->where('vkr.KBUKU', $arrParam['kbuku'])
            ->where('vkr.ID_TRANSAKSI', $arrParam['karcis'])
            ->orderByDesc('vkr.TANGGAL')
            ->first();

        // GET DATA ELAB
        $dataLab = DB::table('E_RM_PHCM.dbo.ERM_RM_IRJA as eri')
            ->select([
                'eri.KARCIS',
                'eri.ANAMNESE',
                'ere.ARRAY_TINDAKAN',
                'ere.TANGGAL_ENTRI'
            ])
            ->leftJoin('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as ere', 'eri.KARCIS', 'ere.KARCIS_ASAL')
            ->where('eri.AKTIF', 1)
            ->where('ere.KLINIK_TUJUAN', '0017')
            ->where('eri.KARCIS', $arrParam['karcis'])
            ->get();

        // GET DATA ERAD
        $dataRad = DB::table('E_RM_PHCM.dbo.ERM_RM_IRJA as eri')
            ->select([
                'eri.KARCIS',
                'eri.ANAMNESE',
                'ere.ARRAY_TINDAKAN',
                'ere.TANGGAL_ENTRI'
            ])
            ->leftJoin('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as ere', 'eri.KARCIS', 'ere.KARCIS_ASAL')
            ->where('eri.AKTIF', 1)
            ->where('ere.KLINIK_TUJUAN', '0015')
            ->where('eri.KARCIS', $arrParam['karcis'])
            ->get();

        // Pluck array tindakan untuk parameter where in
        $kdTindakanLab = $dataLab
            ->pluck('ARRAY_TINDAKAN')
            ->filter()
            ->flatMap(function ($item) {
                return explode(',', $item);
            })
            ->map('trim')
            ->filter()
            ->unique()
            ->values();

        $kdTindakanRad = $dataRad
            ->pluck('ARRAY_TINDAKAN')
            ->filter()
            ->flatMap(function ($item) {
                return explode(',', $item);
            })
            ->map('trim')
            ->filter()
            ->unique()
            ->values();

        // Ambil data nama tindakan lab & radiologi
        $tindakanLab = DB::table('RIRJ_MTINDAKAN')->whereIn('KD_TIND', $kdTindakanLab)->get();
        $tindakanRad = DB::table('RIRJ_MTINDAKAN')->whereIn('KD_TIND', $kdTindakanRad)->get();

        $dataTindOp = DB::table('E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI as erflo')
            ->where('erflo.KARCIS', $arrParam['karcis'])
            ->get();

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
                'tindakanOp' => $dataTindOp
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
        $dataICD9 = DB::table('RIRJ_ICD9CM')
            ->where(DB::raw('UPPER(KODE)'), 'like', "%$param%")
            ->orWhere(DB::raw('UPPER(KODE_SUB)'), 'like', "%$param%")
            ->orWhere(DB::raw('UPPER(DIAGNOSA)'), 'like', "%$param%")
            ->limit(50)
            ->get();

        return response()->json($dataICD9);
    }

    public function sendSatuSehat(Request $request)
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
        $id_unit      = '001'; // session('id_klinik');

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

        $dataErm = DB::table('E_RM_PHCM.dbo.ERM_RM_IRJA as eri')
            ->select([
                'eri.NOMOR',
                'eri.KODE_DIAGNOSA_UTAMA',
                'eri.DIAG_UTAMA',
                'eri.CRTDT'
            ])
            ->where('karcis', $arrParam['karcis'])
            ->first();

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

        // try {
            $payloadPemeriksaanFisik = $this->definePayloadAnamnese($arrParam, $patient, $request, $dataErm);
            $payloadLab = $this->definePayloadLab($arrParam, $patient, $request, $dataErm);
            $payloadRad = $this->definePayloadRad($arrParam, $patient, $request, $dataErm);
            $payloadOP = $this->definePayloadOp($arrParam, $patient, $request, $dataErm);

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }

            $token = $login['response']['token'];

            $url = 'Procedure';
            $procedureAnamnese = $this->consumeSATUSEHATAPI('POST', $baseurl, $url, $payloadPemeriksaanFisik['payload'], true, $token);

            $result = json_decode($procedureAnamnese->getBody()->getContents(), true);
            if ($procedureAnamnese->getStatusCode() >= 400) {
                $response = json_decode($procedureAnamnese->getBody(), true);

                $this->logError($url, 'Gagal kirim data Procedure / Tindakan Pemeriksaan Fisik', [
                    'payload' => $payloadPemeriksaanFisik['payload'],
                    'response' => $response,
                    'user_id' => 'system'
                ]);

                $this->logDb(json_encode($response), $url, json_encode($payloadPemeriksaanFisik['payload']), 'system'); //Session::get('id')

                $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Kirim Data Encounter';
                throw new Exception($msg, $procedureAnamnese->getStatusCode());
            } else {
                DB::beginTransaction();
                // try {
                    $dataKarcis = DB::table('RJ_KARCIS as rk')
                        ->select('rk.KARCIS', 'rk.IDUNIT', 'rk.KLINIK', 'rk.TGL', 'rk.KDDOK', 'rk.KBUKU')
                        ->where('rk.KARCIS', $arrParam['karcis'])
                        ->where('rk.IDUNIT', $id_unit)
                        ->orderBy('rk.TGL', 'DESC')
                        ->first();

                    $dataPeserta = DB::table('RIRJ_MASTERPX')
                        ->where('KBUKU', $dataKarcis->KBUKU)
                        ->first();

                    $procedureSatuSehat = new SATUSEHAT_PROCEDURE();
                    $procedureSatuSehat->karcis = (int)$dataKarcis->KARCIS;
                    $procedureSatuSehat->kbuku = $dataKarcis->KBUKU;
                    $procedureSatuSehat->no_peserta = $dataPeserta->NO_PESERTA;
                    $procedureSatuSehat->id_satusehat_encounter = $arrParam['encounter_id'];
                    $procedureSatuSehat->id_satusehat_procedure = $result['id'];
                    $procedureSatuSehat->crtdt = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $procedureSatuSehat->crtuser = 'system';
                    $procedureSatuSehat->status_sincron = 1;
                    $procedureSatuSehat->sincron_date = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $procedureSatuSehat->ID_JENIS_TINDAKAN = $payloadPemeriksaanFisik['id_tindakan'];
                    $procedureSatuSehat->KD_ICD9 = $payloadPemeriksaanFisik['kodeICD'];
                    $procedureSatuSehat->DISP_ICD9 = $payloadPemeriksaanFisik['textICD'];
                    $procedureSatuSehat->JENIS_TINDAKAN = 'anamnese';
                    $procedureSatuSehat->KDDOK = $payloadPemeriksaanFisik['kddok'];
                    $procedureSatuSehat->save();

                    $this->logInfo($url, 'Sukses kirim data Procedure / Tindakan Pemeriksaan Fisik', [
                        'payload' => $payloadPemeriksaanFisik,
                        'response' => $result,
                        'user_id' => 'system' //Session::get('id')
                    ]);
                    $this->logDb(json_encode($result), $url, json_encode($payloadPemeriksaanFisik), 'system'); //Session::get('id')

                    SendProcedureToSATUSEHAT::dispatch($payloadLab, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'lab');
                    SendProcedureToSATUSEHAT::dispatch($payloadRad, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'rad');
                    SendProcedureToSATUSEHAT::dispatch($payloadOP, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, 'op');

                    DB::commit();
                    return response()->json([
                        'status' => JsonResponse::HTTP_OK,
                        'message' => 'Berhasil Kirim Data Procedure / Tindakan Pemeriksaan Fisik',
                        'redirect' => [
                            'need' => false,
                            'to' => null,
                        ]
                    ], 200);
                // } catch (Exception $th) {
                //     throw new Exception($th->getMessage(), $th->getCode());
                // }
            }
        // } catch (Exception $th) {
        //     return response()->json([
        //         'status' => [
        //             'msg' => $th->getMessage() != '' ? $th->getMessage() : 'Err',
        //             'code' => $th->getCode() != '' ? $th->getCode() : 500,
        //         ],
        //         'data' => null,
        //         'err_detail' => $th,
        //         'message' => $th->getMessage() != '' ? $th->getMessage() : 'Terjadi Kesalahan Saat Kirim Data, Harap Coba lagi!'
        //     ], $th->getCode() != '' ? $th->getCode() : 500);
        // }
    }

    private function definePayloadAnamnese($param, $patient, $request, $dataErm)
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

        $reasonCode = [
            [
                "coding" => [
                    [
                        "system" => "http://hl7.org/fhir/sid/icd-10",
                        "code" => "$dataErm->KODE_DIAGNOSA_UTAMA",
                        "display" => "$dataErm->DIAG_UTAMA",
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

        return [
            "payload" => $payload,
            "kddok" => $nakes->kddok,
            "id_tindakan" => $dataErm->NOMOR,
            "kodeICD" => $kodeICD,
            "textICD" => $textICD,
        ];
    }

    private function definePayloadLab($param, $patient, $request, $dataErm)
    {
        $dataLab = DB::table('E_RM_PHCM.dbo.ERM_RM_IRJA as eri')
            ->select([
                'ere.ID_RIWAYAT_ELAB',
                'eri.KARCIS',
                'eri.ANAMNESE',
                'ere.ARRAY_TINDAKAN',
                'ere.TANGGAL_ENTRI',
                'ere.KDDOK',
                'ere.KET_TINDAKAN'
            ])
            ->leftJoin('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as ere', 'eri.KARCIS', 'ere.KARCIS_ASAL')
            ->where('eri.AKTIF', 1)
            ->where('ere.KLINIK_TUJUAN', '0017')
            ->where('eri.KARCIS', $param['karcis'])
            ->first();

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
        $icd9 = json_decode($request->icd9_lab, true);
        $texticd9 = json_decode($request->text_icd9_lab, true);

        for ($i = 0; $i < count($icd9); $i++) {
            array_push($code, [
                "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                "code" => "$icd9[$i]",
                "display" => "$texticd9[$i]",
            ]);
        }

        $nakes = SS_Nakes::where('kddok', $dataLab->KDDOK)->first();
        $performer = [
            [
                "actor" => [
                    "reference" => "Practitioner/$nakes->idnakes",
                    "display" => "$nakes->nama",
                ],
            ]
        ];

        $reasonCode = [
            [
                "coding" => [
                    [
                        "system" => "http://hl7.org/fhir/sid/icd-10",
                        "code" => "$dataErm->KODE_DIAGNOSA_UTAMA",
                        "display" => "$dataErm->DIAG_UTAMA",
                    ]
                ],
            ]
        ];

        Carbon::setLocale('id');
        $tglText = Carbon::parse($dataLab->TANGGAL_ENTRI)->translatedFormat('l, d F Y');
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
                "display" => "Tindakan Pemeriksaan Lab pasien A/n $patient->nama pada $tglText"
            ],
            "performer" => $performer,
            "reasonCode" => $reasonCode
        ];

        return [
            "payload" => $payload,
            "kddok" => $nakes->kddok,
            "id_tindakan" => $dataLab->ID_RIWAYAT_ELAB,
            "kodeICD" => implode(',', $icd9),
            "textICD" => implode(',', $texticd9),
        ];
    }

    private function definePayloadRad($param, $patient, $request, $dataErm)
    {
        $dataRad = DB::table('E_RM_PHCM.dbo.ERM_RM_IRJA as eri')
            ->select([
                'ere.ID_RIWAYAT_ELAB',
                'eri.KARCIS',
                'eri.ANAMNESE',
                'ere.ARRAY_TINDAKAN',
                'ere.TANGGAL_ENTRI',
                'ere.KDDOK',
                'ere.KET_TINDAKAN'
            ])
            ->leftJoin('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as ere', 'eri.KARCIS', 'ere.KARCIS_ASAL')
            ->where('eri.AKTIF', 1)
            ->where('ere.KLINIK_TUJUAN', '0015')
            ->where('eri.KARCIS', $param['karcis'])
            ->first();

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
        $icd9 = json_decode($request->icd9_rad, true);
        $texticd9 = json_decode($request->text_icd9_rad, true);

        for ($i = 0; $i < count($icd9); $i++) {
            array_push($code, [
                "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                "code" => "$icd9[$i]",
                "display" => "$texticd9[$i]",
            ]);
        }

        $nakes = SS_Nakes::where('kddok', $dataRad->KDDOK)->first();
        $performer = [
            [
                "actor" => [
                    "reference" => "Practitioner/$nakes->idnakes",
                    "display" => "$nakes->nama",
                ],
            ]
        ];

        $reasonCode = [
            [
                "coding" => [
                    [
                        "system" => "http://hl7.org/fhir/sid/icd-10",
                        "code" => "$dataErm->KODE_DIAGNOSA_UTAMA",
                        "display" => "$dataErm->DIAG_UTAMA",
                    ]
                ],
            ]
        ];

        Carbon::setLocale('id');
        $tglText = Carbon::parse($dataRad->TANGGAL_ENTRI)->translatedFormat('l, d F Y');
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
                "display" => "Tindakan Pemeriksaan Radiologi pasien A/n $patient->nama pada $tglText"
            ],
            "performer" => $performer,
            "reasonCode" => $reasonCode
        ];


        return [
            "payload" => $payload,
            "kddok" => $nakes->kddok,
            "id_tindakan" => $dataRad->ID_RIWAYAT_ELAB,
            "kodeICD" => implode(',', $icd9),
            "textICD" => implode(',', $texticd9),
        ];
    }

    private function definePayloadOp($param, $patient, $request, $dataErm)
    {
        $dataTindOp = DB::table('E_RM_PHCM.dbo.ERM_RI_F_LAP_OPERASI as erflo')
            ->where('erflo.KARCIS', $param['karcis'])
            ->first();

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

        $reasonCode = [
            [
                "coding" => [
                    [
                        "system" => "http://hl7.org/fhir/sid/icd-10",
                        "code" => "$dataErm->KODE_DIAGNOSA_UTAMA",
                        "display" => "$dataErm->DIAG_UTAMA",
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

        return [
            "payload" => $payload,
            "kddok" => $nakes->kddok,
            "id_tindakan" => $dataTindOp->id_lap_operasi,
            "kodeICD" => implode(',', $icd9),
            "textICD" => implode(',', $texticd9),
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
