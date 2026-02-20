<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\SATUSEHAT\SATUSEHAT_CARE_PLAN;
use App\Models\SATUSEHAT\SS_Kode_API;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Faker\Factory as Faker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\DataTables;

class CarePlanController extends Controller
{
    use SATUSEHATTraits, LogTraits;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Initialize summary counters
        $result = [
            'total_semua' => 0,
            'total_rawat_jalan' => 0,
            'total_rawat_inap' => 0,
            'total_sudah_integrasi' => 0,
            'total_belum_integrasi' => 0,
        ];

        return view('pages.satusehat.care-plan.index', compact('result'));
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

        // ================= DATATABLES PAGINATION =================
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $draw   = (int) $request->input('draw', 1);

        $pageNumber = ($start / $length) + 1;
        $pageSize   = $length;

        $data = DB::select("
            EXEC dbo.sp_getCarePlan ?, ?, ?, ?, ?, ?, ?, ?
        ", [
            $id_unit,
            $tgl_awal_db,
            $tgl_akhir_db,
            $request->input('cari') ?? 'unmapped',
            null,
            $request->search['value'],
            $pageNumber,
            $pageSize
        ]);

        $dataAll = DB::select("
            EXEC dbo.sp_getCarePlan ?, ?, ?, ?, ?, ?, ?, ?
        ", [
            $id_unit,
            $tgl_awal_db,
            $tgl_akhir_db,
            'all',
            null,
            null,
            1,
            1
        ]);

        if (count($dataAll) == 0) {
            return response()->json([
                "draw" => $draw,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
            ]);
        }

        $recordsTotal    = $data[0]->total_semua ?? 0;
        $recordsFiltered = $data[0]->recordsFiltered ?? $recordsTotal;
        $totalData = [
            'total_semua' => $dataAll[0]->total_semua ?? 0,
            'total_sudah_integrasi' => $dataAll[0]->total_sudah_integrasi ?? 0,
            'total_belum_integrasi' => $dataAll[0]->total_belum_integrasi ?? 0,
            'total_rawat_jalan' => $dataAll[0]->total_rawat_jalan ?? 0,
            'total_rawat_inap' => $dataAll[0]->total_rawat_inap ?? 0,
        ];

        $dataKunjungan = [];
        $index = $start + 1;
        foreach ($data as $row) {
            $jenis = $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
            $id_transaksi = LZString::compressToEncodedURIComponent($row->ID_TRANSAKSI);
            $KbBuku = LZString::compressToEncodedURIComponent($row->KBUKU);
            $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
            $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
            $idEncounter = LZString::compressToEncodedURIComponent($row->ID_SATUSEHAT_ENCOUNTER);
            $paramSatuSehat = "sudah_integrasi=$row->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter&jenis_perawatan=" . LZString::compressToEncodedURIComponent($row->JENIS_PERAWATAN);
            $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

            $param = LZString::compressToEncodedURIComponent("karcis=$id_transaksi&kbuku=$KbBuku&jenis_perawatan=" . LZString::compressToEncodedURIComponent($row->JENIS_PERAWATAN));

            $dataKunjungan[] = [
                'DT_RowIndex' => $index++,
                'ID_TRANSAKSI' => $row->ID_TRANSAKSI,
                'NO_PESERTA' => $row->NO_PESERTA,
                'KBUKU' => $row->KBUKU,
                'checkbox' => $this->renderCheckbox($row, $paramSatuSehat),
                'JENIS_PERAWATAN' => $jenis,
                'TANGGAL' => date('Y-m-d', strtotime($row->TANGGAL)),
                'NAMA_PASIEN' => $row->NAMA_PASIEN,
                'DOKTER' => $row->DOKTER,
                'status_integrasi' => $row->sudah_integrasi > 0
                    ? '<span class="badge badge-success">Sudah Integrasi</span>'
                    : '<span class="badge badge-danger">Belum Integrasi</span>',
                'action' => $this->renderAction($row, $paramSatuSehat, $param),
            ];
        }

        return response()->json([
            "draw" => intval($request->draw),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $dataKunjungan,
            "summary" => $totalData
        ]);
    }

    private function renderCheckbox($row, $paramSatuSehat)
    {
        $checkBox = '';
        if ($row->ID_PASIEN_SS != null && $row->ID_NAKES_SS != null && $row->ID_SATUSEHAT_ENCOUNTER != null) {
            $checkBox = "
                <input type='checkbox' class='select-row chk-col-purple' value='$row->ID_TRANSAKSI' data-resend='$row->sudah_integrasi' data-param='$paramSatuSehat' id='$row->ID_TRANSAKSI' />
                <label for='$row->ID_TRANSAKSI' style='margin-bottom: 0px !important; line-height: 25px !important; font-weight: 500'> &nbsp; </label>
            ";
        }

        return $checkBox;
    }

    private function renderAction($row, $paramSatuSehat, $param)
    {
        $btn = '';

        $dataErm = null;
        if ($row->JENIS_PERAWATAN == 'RAWAT_INAP') {
            $dataErm = DB::table('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD')
                ->where('noreg', $row->ID_TRANSAKSI)
                ->first();
        }

        if ($row->ID_PASIEN_SS == null) {
            $btn = '<i class="text-muted">Pasien Belum Mapping</i>';
        } else if ($row->ID_NAKES_SS == null) {
            $btn .= '<i class="text-muted">Nakes Belum Mapping</i>';
        } else if ($row->ID_SATUSEHAT_ENCOUNTER == null) {
            $btn .= '<i class="text-muted">Encounter Belum Kirim</i>';
        } else if ($dataErm == null && $row->JENIS_PERAWATAN == 'RAWAT_INAP') {
            $btn .= '<i class="text-muted">Assesmne Awal Pasien Masuk Belum Diisi</i>';
        } else {
            if ($row->sudah_integrasi == '0') {
                $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
            } else {
                $btn = '<a href="javascript:void(0)" onclick="resendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
            }
            $btn .= '<br>';
            $btn .= '<a href="javascript:void(0)" onclick="lihatDetail(`' . $param . '`, `' . $paramSatuSehat . '`)" class="mt-2 btn btn-sm btn-info w-100"><i class="fas fa-info-circle mr-2"></i>Lihat Detail</a>';
        }
        return $btn;
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

        $id_unit = Session::get('id_unit', '001');
        $data = collect(DB::select("
            EXEC dbo.sp_getCarePlan ?, ?, ?, ?, ?
        ", [
            $id_unit,
            null,
            null,
            'all',
            $arrParam['karcis'] ?? '',
        ]))->first();

        // Dummy detail data
        $dataPasien = [
            'NAMA' => $data->NAMA_PASIEN,
            'KBUKU' => $data->KBUKU,
            'NO_PESERTA' => $data->NO_PESERTA,
            'KARCIS' => $data->ID_TRANSAKSI,
            'DOKTER' => $data->DOKTER,
            'statusIntegrated' => $data->sudah_integrasi == '1' ? true : false,
        ];

        $dataErm = [
            'PLAN_TERAPI' => $data->PLAN_TERAPI,
            'DIAGNOSA' => $data->ICD10 . ' - ' . $data->DISPLAY_ICD10,
        ];

        return response()->json([
            'dataPasien' => $dataPasien,
            'dataErm' => $dataErm,
        ]);
    }

    public function send(Request $request, $resend = false)
    {
        $param = $request->param;
        $params = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('&', $params);

        $arrParam = [];
        for ($i = 0; $i < count($parts); $i++) {
            $partsParam = explode('=', $parts[$i]);
            $key = $partsParam[0];
            $val = $partsParam[1];
            $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
        }

        $id_unit = Session::get('id_unit', $arrParam['id_unit'] ?? null);
        $data = DB::selectOne("
            EXEC dbo.sp_getCarePlan ?, ?, ?, ?, ?, ?, ?, ?
        ", [
            $id_unit,
            null,
            null,
            'all',
            $arrParam['karcis'] ?? '',
            null,
            1,
            1
        ]);

        $baseurl = '';
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

        try {
            $satusehatPayload = $this->buildSatusehatParam($arrParam, $data, $organisasi);

            if ($resend) {
                $currData = SATUSEHAT_CARE_PLAN::where('KARCIS', $arrParam['karcis'])
                    ->where('NO_PESERTA', $data->NO_PESERTA)
                    ->where('ID_UNIT', $id_unit)
                    ->select('ID_SATUSEHAT_CAREPLAN')
                    ->first();
                $satusehatPayload['id'] = $currData->ID_SATUSEHAT_CAREPLAN;
            }

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }
            $token = $login['response']['token'];

            $url = $resend ? 'CarePlan/' . $currData->ID_SATUSEHAT_CAREPLAN : 'CarePlan';
            $dataCarePlan = $this->consumeSATUSEHATAPI($resend ? 'PUT' : 'POST', $baseurl, $url, $satusehatPayload, true, $token);
            $result = json_decode($dataCarePlan->getBody()->getContents(), true);

            if ($dataCarePlan->getStatusCode() >= 400) {
                $response = json_decode($dataCarePlan->getBody(), true);

                $this->logError('CarePlan', 'Gagal kirim data CarePlan', [
                    'payload' => $satusehatPayload,
                    'response' => $response,
                    'user_id' => Session::get('nama', 'system') //Session::get('id')
                ]);

                $this->logDb(json_encode($response), 'CarePlan', json_encode($satusehatPayload), 'system'); //Session::get('id')

                $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Kirim Data CarePlan';
                throw new Exception($msg, $dataCarePlan->getStatusCode());
            } else {
                DB::beginTransaction();
                try {
                    $CarePlan_satusehat = SATUSEHAT_CARE_PLAN::firstOrCreate(
                        [
                            'KARCIS' => $data->ID_TRANSAKSI,
                            'NO_PESERTA' => $data->NO_PESERTA,
                            'ID_UNIT' => $id_unit,
                            'ID_ERM' => $data->ID_ERM,
                        ],
                        [
                            'ID_SATUSEHAT_CAREPLAN' => $result['id'],
                            'JENIS_PERAWATAN' => $data->JENIS_PERAWATAN,
                            'ID_SATUSEHAT_ENCOUNTER' => $data->ID_SATUSEHAT_ENCOUNTER,
                            'CRTUSR' => Session::get('nama', 'system'),
                            'CRTDT' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        ]
                    );

                    $this->logInfo('CarePlan', 'Sukses kirim data CarePlan', [
                        'payload' => $satusehatPayload,
                        'response' => $result,
                        'user_id' => Session::get('nama', 'system') //Session::get('id')
                    ]);
                    $this->logDb(json_encode($result), 'CarePlan', json_encode($satusehatPayload), 'system'); //Session::get('id')

                    DB::commit();
                    return response()->json([
                        'status' => JsonResponse::HTTP_OK,
                        'message' => 'Berhasil Kirim Data CarePlan',
                        'redirect' => [
                            'need' => false,
                            'to' => null,
                        ]
                    ], 200);
                } catch (Exception $th) {
                    DB::rollBack();
                    throw new Exception($th->getMessage(), 500);
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
            ], 500);
        }
    }

    private function buildSatusehatParam($arrParam, $data, $organisasi): array
    {
        $category = [];

        if ($data->JENIS_PERAWATAN == 'RAWAT_INAP') {
            $category["coding"] = [[
                "system" => "http://snomed.info/sct",
                "code" => "736353004",
                "display" => "Inpatient care plan"
            ]];
            $data->PLAN_TERAPI = strip_tags($data->PLAN_TERAPI);
        } else if ($data->JENIS_PERAWATAN == 'RAWAT_JALAN') {
            $category["coding"] = [[
                "system" => "http://snomed.info/sct",
                "code" => "736271009",
                "display" => "Outpatient care plan"
            ]];
        }

        $identifier = now()->timestamp;
        $payload = [
            "status" => "active",
            "resourceType" => "CarePlan",
            "intent" => "plan",
            "category" => [
                $category
            ],
            "identifier" => [[
                "system" => "http://sys-ids.kemkes.go.id/careplan/{$organisasi}",
                "value" => "$identifier"
            ]],
            "subject" => [
                "reference" => "Patient/$data->ID_PASIEN_SS",
                "display" => $data->NAMA_PASIEN,
            ],
            "encounter" => [
                "reference" => "Encounter/{$data->ID_SATUSEHAT_ENCOUNTER}",
                "display" => "Kunjungan $data->NAMA_PASIEN pada " . date('Y-m-d', strtotime($data->TANGGAL)),
            ],
            'created' => Carbon::now()->toIso8601String(),
            "author" => [
                "reference" => "Practitioner/$data->ID_NAKES_SS",
                "display" => $data->DOKTER,
            ],
            "title" => "Instruksi Medik dan Keperawatan Pasien",
            "description" => "Rencana Perawatan untuk pasien {$data->NAMA_PASIEN}, $data->PLAN_TERAPI",
        ];

        return $payload;
    }

    public function resend(Request $request)
    {
        return $this->send(new Request($request->all()), true);
    }

    public function bulkSend(Request $request)
    {
        $selectedIds = $request->input('selected_ids', []);
        $responses = [];

        foreach ($selectedIds as $data) {
            $response = $this->send(new Request(['param' => $data['param']]), $data['resend']);
            $responses[] = json_decode($response->getContent(), true);
        }

        return response()->json([
            'status' => JsonResponse::HTTP_OK,
            'message' => 'Proses Bulk Send Selesai',
            'responses' => $responses,
        ], 200);
    }
}
