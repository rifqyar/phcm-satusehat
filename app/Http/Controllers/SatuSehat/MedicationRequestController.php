<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use App\Jobs\SendMedicationRequest;
use Illuminate\Support\Facades\Session;
use App\Models\SATUSEHAT\SS_Kode_API;


class MedicationRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->view('pages.satusehat.medicationrequest.index');
    }

    public function datatable(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $jenis = $request->input('jenis'); // ri / rj
        $ketLayanan = $jenis === 'ri' ? 'INAP' : 'JALAN';
        $id_unit = Session::get('id_unit', '001');


        if (!$startDate || !$endDate) {
            $endDate = now();
            $startDate = now()->subDays(30);
        }

        // Tentukan tabel kunjungan berdasarkan input jenis
        $kunjunganTable = $jenis === 'ri'
            ? 'SIRS_PHCM.dbo.v_kunjungan_ri'
            : 'SIRS_PHCM.dbo.v_kunjungan_rj';

        $query = DB::table('SIRS_PHCM.dbo.IF_HTRANS_OL as a')
            ->distinct()
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as b', 'a.KARCIS', '=', 'b.karcis')
            ->leftJoin("$kunjunganTable as c", 'a.KARCIS', '=', 'c.ID_TRANSAKSI')
            ->leftJoin(DB::raw("
                (
                    SELECT
                        ol.ID_TRANS,
                        CASE
                            WHEN COUNT(CASE WHEN kfa.KD_BRG_KFA IS NULL THEN 1 END) > 0
                            THEN '000'
                            ELSE '100'
                        END AS STATUS_MAPPING
                    FROM SIRS_PHCM.dbo.IF_TRANS_OL ol
                    LEFT JOIN SIRS_PHCM.dbo.M_TRANS_KFA kfa
                        ON ol.KDBRG_CENTRA = kfa.KDBRG_CENTRA
                    GROUP BY ol.ID_TRANS
                ) as d
            "), 'd.ID_TRANS', '=', 'a.ID_TRANS')
            ->leftJoin(DB::raw("
                (
                    SELECT
                        LOCAL_ID,
                        MAX(ID) AS MAX_ID
                    FROM SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION
                    WHERE LOG_TYPE = 'MedicationRequest'
                    AND STATUS = 'success'
                    GROUP BY LOCAL_ID
                ) AS log_latest
            "), 'log_latest.LOCAL_ID', '=', 'a.ID_TRANS')
            ->leftJoin(
                'SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION as SSM',
                'SSM.ID',
                '=',
                DB::raw('log_latest.MAX_ID')
            )
            ->where('a.IDUNIT', $id_unit)
            ->where('a.ACTIVE', '1')
            ->whereBetween(DB::raw('CAST(c.TANGGAL AS date)'), [$startDate, $endDate])
            ->where('a.KET_LAYANAN', $ketLayanan)
            ->select(
                'b.id',
                'b.id_satusehat_encounter',
                'a.ID_TRANS',
                DB::raw('CAST(c.TANGGAL AS date) AS TGL_KARCIS'),
                'a.KARCIS',
                DB::raw('c.NAMA_PASIEN AS PASIEN'),
                DB::raw('c.DOKTER AS DOKTER'),
                DB::raw("
                    CASE
                        WHEN SSM.ID IS NOT NULL THEN '200'
                        ELSE d.STATUS_MAPPING
                    END AS STATUS_MAPPING
                "),
                DB::raw('SSM.STATUS AS LOG_STATUS'),
                DB::raw('SSM.CREATED_AT AS LOG_CREATED_AT')
            );

        // 🔢 Summary count
        $recordsTotal = (clone $query)->count();

        $sentCount = (clone $query)
            ->whereRaw("
            CASE
                WHEN SSM.ID IS NOT NULL THEN '200'
                ELSE d.STATUS_MAPPING
            END = '200'
        ")
            ->count();

        $unsentCount = $recordsTotal - $sentCount;

        // 🚀 DataTables server-side
        $dataTable = DataTables::of($query)
            ->filterColumn('KARCIS', function ($query, $keyword) {
                $query->where('a.KARCIS', 'like', "%{$keyword}%");
            })
            ->filterColumn('ID_TRANS', function ($query, $keyword) {
                $query->where('a.ID_TRANS', 'like', "%{$keyword}%");
            })
            ->filterColumn('DOKTER', function ($query, $keyword) {
                $query->where('c.DOKTER', 'like', "%{$keyword}%");
            })
            ->filterColumn('PASIEN', function ($query, $keyword) {
                $query->where('c.NAMA_PASIEN', 'like', "%{$keyword}%");
            })
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value']) {
                    $query->where(function ($q) use ($search) {
                        $q->where('a.KARCIS', 'like', "%{$search}%")
                            ->orWhere('a.ID_TRANS', 'like', "%{$search}%")
                            ->orWhere('c.DOKTER', 'like', "%{$search}%")
                            ->orWhere('c.NAMA_PASIEN', 'like', "%{$search}%");
                    });
                }
            })
            ->order(function ($query) {
                $query->orderBy('a.ID_TRANS', 'desc');
            })
            ->make(true);

        // Tambahkan summary
        $json = $dataTable->getData(true);
        $json['summary'] = [
            'all' => $recordsTotal,
            'sent' => $sentCount,
            'unsent' => $unsentCount,
        ];

        return response()->json($json);
    }

    public function getDetailObat(Request $request)
    {
        $idTrans = $request->id; // ID_TRANS dikirim dari tombol lihatObat(id)

        try {
            $data = DB::select("
                    SELECT
                        T.ID_TRANS,
                        T.[NO],
                        T.NAMABRG AS NAMA_OBAT,
                        T.SIGNA2 AS SIGNA,
                        T.KDBRG,
                        T.KETQTY AS KET,
                        T.JUMLAH,
                        H.TGL AS TGL_ENTRY,
                        T.ID_TRANS AS IDTRANS,
                        K.KD_BRG_KFA,
                        K.NAMABRG_KFA,
                        T.KDBRG_CENTRA,
                        LM.ID
                    FROM SIRS_PHCM.dbo.IF_HTRANS_OL H
                    JOIN SIRS_PHCM.dbo.IF_TRANS_OL T
                        ON H.ID_TRANS = T.ID_TRANS
                    LEFT JOIN SIRS_PHCM.dbo.M_TRANS_KFA K
                        ON T.KDBRG_CENTRA = K.KDBRG_CENTRA
                    LEFT JOIN SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION LM on T.ID_TRANS = LM.LOCAL_ID and K.KD_BRG_KFA = LM.KFA_CODE and status = 'success'
                    WHERE
                    H.ID_TRANS = :idTrans
                    AND H.ACTIVE = 1
                    AND H.IDUNIT in (001,002)", ['idTrans' => $idTrans]);

            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data obat tidak ditemukan.'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
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

    //dev gak dipake
    public function sendMedicationRequest(Request $request)
    {
        try {
            // --- ambil parameter dari request ---
            $idTrans = $request->input('id_trans');

            if (empty($idTrans)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Parameter id_trans wajib dikirim.'
                ], 400);
            }

            $data = DB::select("
            SELECT
                H.ID_TRANS,
                MT.FHIR_ID AS medicationReference,
                MT.NAMABRG_KFA,
                MT.KD_BRG_KFA,
                MT.IS_COMPOUND,
                B.id_satusehat_encounter,
                P.idpx AS ID_PASIEN,
                P.nama AS PASIEN,
                N.idnakes AS ID_NAKES,
                N.nama AS NAKES
            FROM SIRS_PHCM.dbo.RJ_KARCIS A
            INNER JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA AS B ON A.KARCIS = B.karcis
            INNER JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES AS N ON B.id_satusehat_dokter = N.idnakes
            INNER JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN AS P ON B.id_satusehat_px = P.idpx
            INNER JOIN SIRS_PHCM.dbo.IF_HTRANS_OL H ON A.KARCIS = H.KARCIS
            INNER JOIN SIRS_PHCM.dbo.IF_TRANS_OL T ON H.ID_TRANS = T.ID_TRANS
            INNER JOIN SIRS_PHCM.dbo.M_TRANS_KFA MT ON T.KDBRG_CENTRA = MT.KDBRG_CENTRA
            WHERE H.ID_TRANS = ?
        ", [$idTrans]);

            if (empty($data)) {

                // contoh: ambil KDBRG_CENTRA dari request
                $kodeBarang = $request->input('kode_barang');

                if (!$kodeBarang) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Data transaksi tidak ditemukan & kode_barang tidak dikirim."
                    ], 400);
                }

                // panggil setMedication internal
                $medResult = app(\App\Http\Controllers\SatusehatKfaController::class)
                    ->processMedication($kodeBarang);

                return response()->json([
                    'status' => 'warning',
                    'message' => 'Transaksi tidak ditemukan. Medication dikirim sebagai master.',
                    'medication' => $medResult
                ]);
            }


            // --- ambil token aktif dari tabel auth ---
            $tokenData = DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')
                ->select('issued_at', 'expired_in', 'access_token')
                ->orderBy('id', 'desc')
                ->first();

            if (!$tokenData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access token tidak ditemukan di tabel RIRJ_SATUSEHAT_AUTH.'
                ], 400);
            }

            $id_unit = Session::get('id_unit_simrs', '001');
            if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
                $orgId = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
            } else {
                $orgId = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
            }

            $accessToken = $tokenData->access_token;
            $client = new \GuzzleHttp\Client();

            $results = [];

            // --- loop setiap obat dari transaksi ---
            foreach ($data as $index => $item) {
                $uniqueId = date('YmdHis') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

                $jenisCode = ($item->IS_COMPOUND == 1) ? 'C' : 'NC';
                $jenisName = ($item->IS_COMPOUND == 1) ? 'Compound' : 'Non-compound';

                // --- bentuk payload ---
                $payload = [
                    "resourceType" => "MedicationRequest",
                    "identifier" => [
                        [
                            "system" => "http://sys-ids.kemkes.go.id/prescription",
                            "use" => "official",
                            "value" => $uniqueId
                        ]
                    ],
                    "contained" => [
                        [
                            "resourceType" => "Medication",
                            "meta" => [
                                "profile" => [
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication"
                                ]
                            ],
                            "id" => $uniqueId,
                            "identifier" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/medication",
                                    "use" => "official",
                                    "value" => $item->KD_BRG_KFA
                                ]
                            ],
                            "code" => [
                                "coding" => [
                                    [
                                        "system" => "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => $item->KD_BRG_KFA,
                                        "display" => $item->NAMABRG_KFA
                                    ]
                                ]
                            ],
                            "status" => "active",
                            "manufacturer" => [
                                "reference" => "Organization/" . $orgId
                            ],
                            "extension" => [
                                [
                                    "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                    "valueCodeableConcept" => [
                                        "coding" => [
                                            [
                                                "system" => "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                                "code" => $jenisCode,
                                                "display" => $jenisName
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "status" => "completed",
                    "intent" => "order",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                                    "code" => "community",
                                    "display" => "Community"
                                ]
                            ]
                        ]
                    ],
                    "priority" => "routine",
                    "medicationReference" => [
                        "reference" => "#" . $uniqueId
                    ],
                    "subject" => [
                        "reference" => "Patient/" . $item->ID_PASIEN,
                        "display" => $item->PASIEN
                    ],
                    "encounter" => [
                        "reference" => "Encounter/" . $item->id_satusehat_encounter
                    ],
                    "authoredOn" => now()->format('Y-m-d\TH:i:sP'),
                    "requester" => [
                        "reference" => "Practitioner/" . $item->ID_NAKES,
                        "display" => $item->NAKES
                    ]
                ];

                // --- kirim ke endpoint FHIR ---
                $response = $client->post(
                    'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1/MedicationRequest',
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type' => 'application/json'
                        ],
                        'body' => json_encode($payload),
                        'verify' => false
                    ]
                );

                $responseBody = json_decode($response->getBody(), true);
                $httpStatus = $response->getStatusCode();

                $logData = [
                    'LOG_TYPE' => 'MedicationRequest',
                    'LOCAL_ID' => $idTrans,
                    'KFA_CODE' => $item->KD_BRG_KFA,
                    'NAMA_OBAT' => $item->NAMABRG_KFA,
                    'FHIR_MEDICATION_REQUEST_ID' => $responseBody['id'] ?? null,
                    'FHIR_ID' => $responseBody['id'] ?? null,
                    'FHIR_MEDICATION_ID' => $item->medicationReference ?? null,
                    'PATIENT_ID' => $item->ID_PASIEN ?? null,
                    'ENCOUNTER_ID' => $item->id_satusehat_encounter ?? null,
                    'STATUS' => isset($responseBody['id']) ? 'success' : 'failed',
                    'HTTP_STATUS' => $httpStatus,
                    'RESPONSE_MESSAGE' => json_encode($responseBody),
                    'CREATED_AT' => now()
                ];

                // cek apakah data sudah pernah tercatat (berdasarkan kombinasi unik)
                $existing = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
                    ->where('LOCAL_ID', $idTrans)
                    ->where('KFA_CODE', $item->KD_BRG_KFA)
                    ->where('LOG_TYPE', 'MedicationRequest')
                    ->first();

                if ($existing) {
                    // update existing record
                    DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
                        ->where('ID', $existing->ID)
                        ->update([
                            'FHIR_ID' => $logData['FHIR_ID'],
                            'FHIR_MEDICATION_ID' => $logData['FHIR_MEDICATION_ID'],
                            'PATIENT_ID' => $logData['PATIENT_ID'],
                            'ENCOUNTER_ID' => $logData['ENCOUNTER_ID'],
                            'STATUS' => $logData['STATUS'],
                            'HTTP_STATUS' => $logData['HTTP_STATUS'],
                            'RESPONSE_MESSAGE' => $logData['RESPONSE_MESSAGE'],
                            'UPDATED_AT' => now()
                        ]);
                } else {
                    // insert baru
                    DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert($logData);
                }



                $results[] = [
                    'medication' => $item->NAMABRG_KFA,
                    'status' => $httpStatus,
                    'response' => $responseBody
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Semua MedicationRequest telah diproses.',
                'results' => $results
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        } catch (\Exception $e) {
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert([
                'LOG_TYPE' => 'MedicationRequest',
                'LOCAL_ID' => $request->input('id_trans'),
                'STATUS' => 'failed',
                'HTTP_STATUS' => 500,
                'RESPONSE_MESSAGE' => $e->getMessage(),
                'CREATED_AT' => now()
            ]);


            return response()->json([
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }

    //revisi
    public function prepMedicationRequest(Request $request)
    {
        try {
            // --- ambil parameter dari request ---
            $idTrans = $request->input('id_trans');

            if (empty($idTrans)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Parameter id_trans wajib dikirim.'
                ], 400);
            }

            $data = DB::table('SIRS_PHCM.dbo.IF_TRANS_OL as A')
                ->leftJoin('SIRS_PHCM.dbo.M_TRANS_KFA as B', 'A.KDBRG', '=', 'B.KDBRG_CENTRA')
                ->select(
                    'A.ID_TRANS',
                    'A.KDBRG',
                    'A.NAMABRG',
                    'B.FHIR_ID',
                    'B.KD_BRG_KFA'
                )
                ->where('A.ID_TRANS', $idTrans)
                ->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data transaksi obat ' . $idTrans . ' tidak ditemukan.'
                ], 404);
            }

            // cari row yang belum dimapping KD_BRG_KFA (jika ada -> stop, tampilkan list KDBRG)
            $missingMapping = [];
            foreach ($data as $row) {
                if (empty($row->KD_BRG_KFA)) {
                    $missingMapping[] = $row->KDBRG;
                }
            }

            if (!empty($missingMapping)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kode KFA (KD_BRG_KFA) belum dimapping untuk transaksi ' . $idTrans,
                    'missing_kdbrg' => $missingMapping
                ], 400);
            }

            // proses setiap row: cek sudah dikirim, jika ok/duplicate -> create payload
            $summary = []; // akan berisi hasil per KDBRG

            foreach ($data as $row) {
                $kdbrg = $row->KDBRG;
                $result_kirim_medication = $this->cekSudahKirimMedication($kdbrg);


                // default hasil untuk row ini
                $rowResult = [
                    'KDBRG' => $kdbrg,
                    'NAMABRG' => $row->NAMABRG,
                    'KD_BRG_KFA' => $row->KD_BRG_KFA,
                    'status' => 'skipped',
                    'message' => null,
                    'created' => false
                ];

                // kalo success -> lanjut buat payload
                if (isset($result_kirim_medication['status']) && $result_kirim_medication['status'] === 'success') {
                    $payloadResult = $this->createMedicationRequestPayload($idTrans, $kdbrg);
                    $rowResult['status'] = $payloadResult['status'] ?? 'error';
                    $rowResult['message'] = $payloadResult['message'] ?? null;
                    $rowResult['created'] = ($payloadResult['status'] ?? '') === 'success';
                    if (($payloadResult['status'] ?? '') === 'success') {
                        // KIRIM PAYLOAD KE JOB MEDICATION REQUEST
                        SendMedicationRequest::dispatch(
                            $payloadResult['payload'],
                            [
                                'idTrans' => $idTrans,
                                'item' => [
                                    'KD_BRG_KFA' => $row->KD_BRG_KFA,
                                    'NAMABRG_KFA' => $row->NAMABRG,
                                    'medicationReference' => $result_kirim_medication['medicationReference'] ?? null,
                                    'ID_PASIEN' => $payloadResult['payload']['subject']['reference'] ?? null,
                                    'id_satusehat_encounter' => $payloadResult['payload']['encounter']['reference'] ?? null,
                                ]
                            ]
                        )->onQueue('MedicationRequest');
                    }
                } else {
                    // decode message (bisa jadi JSON string dari FHIR)
                    $decoded = null;
                    if (isset($result_kirim_medication['message'])) {
                        $decoded = @json_decode($result_kirim_medication['message'], true);
                    }

                    if ($decoded && isset($decoded['issue'][0]['code']) && $decoded['issue'][0]['code'] === 'duplicate') {
                        // duplicat OK => lanjut buat payload
                        // echo $idTrans.$kdbrg; die();
                        $payloadResult = $this->createMedicationRequestPayload($idTrans, $kdbrg);

                        // echo json_encode($payloadResult); die();
                        $rowResult['status'] = $payloadResult['status'] ?? 'error';
                        $rowResult['message'] = $payloadResult['message'] ?? null;
                        $rowResult['idTrans'] = $payloadResult['idTrans'] ?? null;
                        $rowResult['KDBRG'] = $payloadResult['KDBRG'] ?? null;
                        $rowResult['created'] = ($payloadResult['status'] ?? '') === 'success';
                        $rowResult['payload'] = $payloadResult['payload'] ?? null;
                        $rowResult['note'] = 'previously duplicate'; // simpan catatan

                        // ==========================================
                        //  DISPATCH JOB di sini (duplicate case)
                        // ==========================================
                        if (($payloadResult['status'] ?? '') === 'success') {

                            SendMedicationRequest::dispatch(
                                $payloadResult['payload'],
                                [
                                    'idTrans' => $idTrans,
                                    'item' => [
                                        'KD_BRG_KFA' => $row->KD_BRG_KFA,
                                        'NAMABRG_KFA' => $row->NAMABRG,
                                        'medicationReference' => $row->FHIR_ID ?? null,
                                        'ID_PASIEN' => $payloadResult['payload']['subject']['reference'] ?? null,
                                        'id_satusehat_encounter' => $payloadResult['payload']['encounter']['reference'] ?? null,
                                    ],
                                ]
                            )->onQueue('MedicationRequest');
                        }

                    } else {
                        // error lain -> jangan lanjut untuk row ini
                        $rowResult['status'] = 'error';
                        $rowResult['message'] = $result_kirim_medication['message'] ?? 'Gagal Kirim Medication';
                    }

                }

                $summary[] = $rowResult;
            }

            // hitung ringkasan
            $createdCount = 0;
            $errorCount = 0;
            foreach ($summary as $s) {
                if (isset($s['created']) && $s['created'])
                    $createdCount++;
                if ($s['status'] === 'error')
                    $errorCount++;
            }

            return response()->json([
                'status' => ($errorCount === 0) ? 'success' : (($createdCount > 0) ? 'partial' : 'error'),
                'message' => ($errorCount === 0) ? 'Semua row diproses' : 'Sebagian row diproses, ada error pada beberapa row',
                'summary' => [
                    'total_rows' => count($summary),
                    'created' => $createdCount,
                    'errors' => $errorCount
                ],
                'results' => $summary
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }
    // biar singkat ngecek kirim medication sekalian biar bisa dipanggil di fungsi lain
    function cekSudahKirimMedication($kdbrg)
    {
        $kdbrg_centra = $kdbrg;
        $medResult = app(\App\Http\Controllers\SatusehatKfaController::class)
            ->processMedication($kdbrg_centra);

        return $medResult;
    }

    // create payload sekalian kirim melalui job
    function createMedicationRequestPayload($idTrans, $kdbrg)
    {
        try {
            $data = DB::select("
            SELECT
                H.ID_TRANS,
                MT.FHIR_ID AS medicationReference,
                T.ID as 'fl_racik',
                MT.NAMABRG_KFA,
                MT.KD_BRG_KFA,
                MT.IS_COMPOUND,
                B.id_satusehat_encounter,
                P.idpx AS ID_PASIEN,
                P.nama AS PASIEN,
                N.idnakes AS ID_NAKES,
                N.nama AS NAKES
            FROM SIRS_PHCM.dbo.RJ_KARCIS A
            INNER JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA AS B ON A.KARCIS = B.karcis
            INNER JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES AS N ON B.id_satusehat_dokter = N.idnakes
            INNER JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN AS P ON B.id_satusehat_px = P.idpx
            INNER JOIN SIRS_PHCM.dbo.IF_HTRANS_OL H ON A.KARCIS = H.KARCIS
            INNER JOIN SIRS_PHCM.dbo.IF_TRANS_OL T ON H.ID_TRANS = T.ID_TRANS
            INNER JOIN SIRS_PHCM.dbo.M_TRANS_KFA MT ON T.KDBRG_CENTRA = MT.KDBRG_CENTRA
            WHERE H.ID_TRANS = ? and T.KDBRG_CENTRA = ?
        ", [$idTrans, $kdbrg]);


        $id_unit = Session::get('id_unit', '001');
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $orgId = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $orgId = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

            foreach ($data as $index => $item) {
                $uniqueId = date('YmdHis') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

                $jenisCode = ($item->fl_racik == 1) ? 'C' : 'NC';
                $jenisName = ($item->fl_racik == 1) ? 'Compound' : 'Non-compound';

                // --- bentuk payload ---
                $payload = [
                    "resourceType" => "MedicationRequest",
                    "identifier" => [
                        [
                            "system" => "http://sys-ids.kemkes.go.id/prescription",
                            "use" => "official",
                            "value" => $uniqueId
                        ]
                    ],
                    "contained" => [
                        [
                            "resourceType" => "Medication",
                            "meta" => [
                                "profile" => [
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication"
                                ]
                            ],
                            "id" => $uniqueId,
                            "identifier" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/medication",
                                    "use" => "official",
                                    "value" => $item->KD_BRG_KFA
                                ]
                            ],
                            "code" => [
                                "coding" => [
                                    [
                                        "system" => "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => $item->KD_BRG_KFA,
                                        "display" => $item->NAMABRG_KFA
                                    ]
                                ]
                            ],
                            "status" => "active",
                            "manufacturer" => [
                                "reference" => "Organization/" . $orgId
                            ],
                            "extension" => [
                                [
                                    "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                    "valueCodeableConcept" => [
                                        "coding" => [
                                            [
                                                "system" => "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                                "code" => $jenisCode,
                                                "display" => $jenisName
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "status" => "completed",
                    "intent" => "order",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                                    "code" => "community",
                                    "display" => "Community"
                                ]
                            ]
                        ]
                    ],
                    "priority" => "routine",
                    "medicationReference" => [
                        "reference" => "Medication/" . $item->medicationReference
                    ],
                    "subject" => [
                        "reference" => "Patient/" . $item->ID_PASIEN,
                        "display" => $item->PASIEN
                    ],
                    "encounter" => [
                        "reference" => "Encounter/" . $item->id_satusehat_encounter
                    ],
                    "authoredOn" => now()->format('Y-m-d\TH:i:sP'),
                    "requester" => [
                        "reference" => "Practitioner/" . $item->ID_NAKES,
                        "display" => $item->NAKES
                    ]
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Payload dibuat untuk KDBRG ' . $kdbrg,
                'idTrans' => $idTrans,
                'KDBRG' => $kdbrg,
                'payload' => $payload
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'createMedicationRequestPayload exception: ' . $e->getMessage()
            ];
        }
    }

}
