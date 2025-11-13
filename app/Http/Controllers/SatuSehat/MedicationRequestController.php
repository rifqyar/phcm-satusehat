<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;


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

    if (!$startDate || !$endDate) {
        $endDate = now();
        $startDate = now()->subDays(30);
    }

    // 🧱 Base query
    $query = DB::table('SIRS_PHCM.dbo.IF_HTRANS_OL as a')
        ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as b', 'a.KARCIS', '=', 'b.karcis')
        ->leftJoin('SIRS_PHCM.dbo.v_kunjungan_rj as c', 'a.KARCIS', '=', 'c.ID_TRANSAKSI')
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
                WHERE LOG_TYPE = 'MedicationRequest' AND STATUS = 'success'
                GROUP BY LOCAL_ID
            ) AS log_latest
        "), 'log_latest.LOCAL_ID', '=', 'a.ID_TRANS')
        ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION as SSM', 'SSM.ID', '=', DB::raw('log_latest.MAX_ID'))
        ->whereBetween(DB::raw('CAST(c.TANGGAL AS date)'), [$startDate, $endDate])
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

    // 🔢 Summary count (tanpa ambil seluruh data ke memory)
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
        // ✅ Filter kolom agar bisa search per-field
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

        // 🔍 Search global (input search di datatable)
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

    // ✨ Tambahkan summary ke dalam response
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
                        T.KETQTY AS KET,
                        T.JUMLAH,
                        H.TGL AS TGL_ENTRY,
                        T.ID_TRANS AS IDTRANS,
                        K.KD_BRG_KFA,
                        K.NAMABRG_KFA,
                        T.KDBRG_CENTRA
                    FROM SIRS_PHCM.dbo.IF_HTRANS_OL H
                    JOIN SIRS_PHCM.dbo.IF_TRANS_OL T
                        ON H.ID_TRANS = T.ID_TRANS
                    LEFT JOIN SIRS_PHCM.dbo.M_TRANS_KFA K
                        ON T.KDBRG_CENTRA = K.KDBRG_CENTRA
                    WHERE
                    H.ID_TRANS = :idTrans
                    AND H.ACTIVE = 1
                    AND H.IDUNIT = 001
            ", ['idTrans' => $idTrans]);

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
                return response()->json([
                    'status' => 'error',
                    'message' => "Data tidak ditemukan untuk ID_TRANS $idTrans."
                ], 404);
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

            $accessToken = $tokenData->access_token;
            $orgId = '266bf013-b70b-4dc2-b934-40858a5658cc'; // organization ID (sandbox)
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


}
