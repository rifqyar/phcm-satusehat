<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Session;
use App\Models\SATUSEHAT\SS_Kode_API;
use App\Models\GlobalParameter;

class MedStatementController extends Controller
{
    public function index()
    {
        return response()->view('pages.satusehat.medstatement.index');
    }

    public function getDetailDiagnosis(Request $request)
    {
        // Optional: ambil ID dari request (kalau frontend kirim)
        $idTrans = $request->id;

        // Static JSON Diagnosis
        $mockDiagnosis = [
            'diagnosis_id' => 'DX-001',
            'patient_id' => 'PAT-12345',
            'encounter_id' => 'ENC-20251210-01',
            'code' => [
                'icd10' => 'J45.9',
                'description' => 'Asthma, unspecified',
            ],
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'severity' => 'moderate',
            'onset_date' => '2025-12-10',
            'recorded_date' => '2025-12-10T09:30:00+07:00',
            'note' => 'Pasien mengeluhkan sesak napas dan wheezing sejak 2 hari.',
        ];

        return response()->json([
            'status' => 'success',
            'data' => $mockDiagnosis,
        ]);
    }

    public function datatabel(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
        $status = $request->get('status', 'all');


        $sql = "
            SELECT 
                AA.KARCIS,
                A.ID_TRANS,
                A.NMPX,
                A.TGL,
                B.CREATED_AT AS WAKTU_KIRIM_DISPENSE,
                CASE 
                    WHEN EXISTS (
                        SELECT 1
                        FROM SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION MS
                        WHERE MS.LOG_TYPE = 'MedicationStatement'
                        AND MS.LOCAL_ID = A.ID_TRANS
                        AND MS.STATUS = 'success'
                    )
                    THEN 'Integrasi'
                    ELSE 'Belum Integrasi'
                END AS STATUS_KIRIM_STATEMENT
            FROM IF_HTRANS A
            JOIN IF_HTRANS_OL AA 
                ON A.ID_TRANS_OL = AA.ID_TRANS
            JOIN (
                SELECT *
                FROM (
                    SELECT *,
                        ROW_NUMBER() OVER (
                            PARTITION BY LOCAL_ID
                            ORDER BY CREATED_AT DESC
                        ) AS rn
                    FROM SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION
                    WHERE LOG_TYPE = 'MedicationDispense'
                    AND STATUS = 'success'
                ) x
                WHERE rn = 1
            ) B
                ON A.ID_TRANS = B.LOCAL_ID
        WHERE B.CREATED_AT BETWEEN ? AND ?";

        $query = DB::table(
            DB::raw("(
                $sql
            ) AS dashboard_med_statement")
        )->setBindings([$startDate, $endDate]);

        // filer status yang diklik via card ntar
        if ($status === 'integrated') {
            $query->where('STATUS_KIRIM_STATEMENT', 'Integrasi');
        }
        elseif ($status === 'not_integrated') {
            $query->where('STATUS_KIRIM_STATEMENT', 'Belum Integrasi');
        }


        $recordsTotal = DB::selectOne(
            "SELECT COUNT(1) AS total FROM ($sql) x",
            [$startDate, $endDate]
        )->total;

        $dataTable = DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value']) {
                    $query->where(function ($q) use ($search) {
                        $q->where('KARCIS', 'like', "%{$search}%")
                            ->orWhere('ID_TRANS', 'like', "%{$search}%")
                            ->orWhere('NMPX', 'like', "%{$search}%")
                            ->orWhere('STATUS_KIRIM_STATEMENT', 'like', "%{$search}%");
                    });
                }
            })
            ->order(function ($query) {
                $query->orderBy('WAKTU_KIRIM_DISPENSE', 'desc');
            })
            ->make(true);

        $json = $dataTable->getData(true);

        $summary = DB::selectOne("
                SELECT
                    COUNT(1) AS all_data,
                    SUM(T.is_sudah_kirim) AS sudah_kirim
                FROM (
                    SELECT
                        A.ID_TRANS,
                        CASE 
                            WHEN MS.LOCAL_ID IS NOT NULL THEN 1
                            ELSE 0
                        END AS is_sudah_kirim
                    FROM IF_HTRANS A
                    JOIN IF_HTRANS_OL AA 
                        ON A.ID_TRANS_OL = AA.ID_TRANS
                    JOIN (
                        SELECT *
                        FROM (
                            SELECT *,
                                ROW_NUMBER() OVER (
                                    PARTITION BY LOCAL_ID
                                    ORDER BY CREATED_AT DESC
                                ) AS rn
                            FROM SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION
                            WHERE LOG_TYPE = 'MedicationDispense'
                            AND STATUS = 'success'
                        ) x
                        WHERE rn = 1
                    ) B
                        ON A.ID_TRANS = B.LOCAL_ID
                    LEFT JOIN SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION MS
                        ON MS.LOCAL_ID = A.ID_TRANS
                        AND MS.LOG_TYPE = 'MedicationStatement'
                        AND MS.STATUS = 'success'
                    WHERE B.CREATED_AT BETWEEN ? AND ?
                    GROUP BY A.ID_TRANS, MS.LOCAL_ID
                ) T
            ", [$startDate, $endDate]);



        $json['summary'] = [
            'all'         => (int) $summary->all_data,
            'sudah_kirim' => (int) $summary->sudah_kirim,
            'belum_kirim' => (int) ($summary->all_data - $summary->sudah_kirim),
            'periode' => [
                'start' => $startDate,
                'end'   => $endDate,
            ],
        ];


        return response()->json($json);
    }

    public function detail(Request $request)
    {
        $idTrans = $request->id_trans;

        $sql = "
        SELECT 
            PS.nik,
            PS.nama,
            PS.sex,
            PS.alamat,

            A.ID_TRANS,
            AA.KARCIS,
            A.TGL,

            B.KDBRG_CENTRA,
            C.KD_BRG_KFA,
            B.NAMABRG,
            SIG.URAIAN AS ATURAN_PAKAI,

            D.CREATED_AT AS WK_KIRIM_SATUSEHAT,
            D.STATUS AS STATUS_KIRIM,
            D1.IDENTIFIER_VALUE

        FROM IF_HTRANS A
        JOIN IF_HTRANS_OL AA 
            ON A.ID_TRANS_OL = AA.ID_TRANS
        JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA AAA 
            ON AA.KARCIS = AAA.karcis
        JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN PS 
            ON AAA.id_satusehat_px = PS.idpx
        JOIN IF_TRANS B 
            ON A.ID_TRANS = B.ID_TRANS
        JOIN IF_MSIGNA SIG 
            ON B.SIGNA = SIG.KDSIGNA
        JOIN M_TRANS_KFA C 
            ON B.KDBRG_CENTRA = C.KDBRG_CENTRA
        LEFT JOIN SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION D 
            ON A.ID_TRANS = D.LOCAL_ID
           AND D.LOG_TYPE = 'MedicationDispense'
           and D.STATUS = 'success'
           AND C.KD_BRG_KFA = D.KFA_CODE
        left JOIN SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION D1 ON A.ID_TRANS = D1.LOCAL_ID 
            AND D1.LOG_TYPE = 'MedicationStatement' 
            and D1.STATUS = 'success' 
            and C.KD_BRG_KFA = D1.KFA_CODE
        WHERE A.ID_TRANS = ?";

        $rows = DB::select($sql, [$idTrans]);

        if (empty($rows)) {
            return response()->json([
                'html' => '<div class="alert alert-warning">Data tidak ditemukan</div>'
            ]);
        }

        $header = [
            'nik'     => $rows[0]->nik,
            'nama'    => $rows[0]->nama,
            'sex'     => $rows[0]->sex,
            'alamat'  => $rows[0]->alamat,
            'id_trans' => $rows[0]->ID_TRANS,
            'karcis'  => $rows[0]->KARCIS,
            'tgl'     => $rows[0]->TGL,
        ];

        $items = $rows;

        $html = view('pages.satusehat.medstatement.modal-detail', compact('header', 'items'))->render();

        return response()->json(['html' => $html]);
    }

    // kirim kirim
    // khusus dipanggil dari web routess
    public function fetchMedStatementRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_trans' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid request',
                'errors'  => $validator->errors()
            ], 422);
        }

        $idTrans = $request->id_trans;

        $result = $this->prepMedStatementPayload($idTrans);

        if ($result === false || empty($result['payloads'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal menyiapkan payload'
            ], 500);
        }
        $payloads = $result['payloads'];
        $metas    = $result['meta'];



        // $verify = $this->verifyMedStatementPayload($payload);
        $verify = true; // skip verifikasi untuk payload batch

        if ($verify !== true) {
            return response()->json([
                'status'  => false,
                'message' => 'Payload tidak valid',
                'errors'  => $verify
            ], 422);
        }

        $results = [];
        $success = 0;
        $failed  = 0;

        foreach ($payloads as $i => $payload) {
            $meta = $metas[$i]; // pasangan payload ↔ meta

            $sendResult = $this->kirimPayloadToSatuSehat($payload, $meta);

            $results[] = [
                $sendResult
            ];

            if ($sendResult['status'] === true) {
                $success++;
            } else {
                $failed++;
            }
        }


        return response()->json([
            'status'  => $failed === 0,
            'summary' => [
                'total'   => count($payloads),
                'success' => $success,
                'failed'  => $failed
            ],
            'data' => $results
        ]);
    }

    /**
     * Reusable
     * Dipakai oleh fetch / retry / cron / bulk
     */
    public function prepMedStatementPayload($idTrans)
    {
        $sql = "
                SELECT 
                AAA.id_satusehat_encounter,
                PS.nik,
                PS.nama,
                PS.idpx,
                A.ID_TRANS,
                B.KDBRG_CENTRA,
                C.KD_BRG_KFA,
                B.NAMABRG,
                B.ID AS IS_COMPOUND,
                SIG.URAIAN,
                B.INPUTDATE,
                D.CREATED_AT AS WK_KIRIM_SATUSEHAT,
                D.FHIR_MEDICATION_DISPENSE_ID
            FROM IF_HTRANS A
            JOIN IF_HTRANS_OL AA 
                ON A.ID_TRANS_OL = AA.ID_TRANS
            JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA AAA 
                ON AA.KARCIS = AAA.karcis
            JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN PS 
                ON AAA.id_satusehat_px = PS.idpx 
            JOIN IF_TRANS B 
                ON A.ID_TRANS = B.ID_TRANS
            JOIN IF_MSIGNA SIG 
                ON B.SIGNA = SIG.KDSIGNA
            JOIN M_TRANS_KFA C 
                ON B.KDBRG_CENTRA = C.KDBRG_CENTRA
            JOIN SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION D 
                ON A.ID_TRANS = D.LOCAL_ID
            AND D.LOG_TYPE = 'MedicationDispense'
            AND D.STATUS = 'success'
            AND C.KD_BRG_KFA = D.KFA_CODE
            WHERE A.ID_TRANS = ?";

        $rows = DB::select($sql, [$idTrans]);

        if (empty($rows)) {
            return false;
        }

        $payloads = [];
        $metas    = [];

        foreach ($rows as $row) {

            // ===== ID STABIL & IDEMPOTENT =====
            $medId = $row->ID_TRANS . '-' . $row->KD_BRG_KFA;

            // ===== orgid
            $id_unit = Session::get('id_unit_simrs', '001');
            if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
                $orgId = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
            } else {
                $orgId = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
            }

            // ===== MEDICATION TYPE =====
            $medTypeCode = ((int)$row->IS_COMPOUND === 1) ? 'C' : 'NC';
            $medTypeDisplay = ((int)$row->IS_COMPOUND === 1) ? 'Compound' : 'Non-compound';

            $payloads[] = [
                'resourceType' => 'MedicationStatement',
                'contained' => [
                    [
                        'resourceType' => 'Medication',
                        'id' => $medId,
                        'status' => 'active',
                        'meta' => [
                            'profile' => [
                                'https://fhir.kemkes.go.id/r4/StructureDefinition/Medication'
                            ]
                        ],
                        'code' => [
                            'coding' => [
                                [
                                    'system'  => 'http://sys-ids.kemkes.go.id/kfa',
                                    'code'    => $row->KD_BRG_KFA,
                                    'display' => $row->NAMABRG
                                ]
                            ]
                        ],
                        'extension' => [
                            [
                                'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType',
                                'valueCodeableConcept' => [
                                    'coding' => [
                                        [
                                            'system'  => 'http://terminology.kemkes.go.id/CodeSystem/medication-type',
                                            'code'    => $medTypeCode,
                                            'display' => $medTypeDisplay
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'identifier' => [
                            [
                                'system' => 'http://sys-ids.kemkes.go.id/medication/' . $orgId,
                                'use'    => 'official',
                                'value'  => $medId
                            ]
                        ]
                    ]
                ],

                'status' => 'completed',

                'category' => [
                    'coding' => [
                        [
                            'system'  => 'http://terminology.hl7.org/CodeSystem/medication-statement-category',
                            'code'    => 'community',
                            'display' => 'Community'
                        ]
                    ]
                ],

                'medicationReference' => [
                    'reference' => '#' . $medId,
                    'display'   => $row->NAMABRG
                ],

                'subject' => [
                    'reference' => 'Patient/' . $row->idpx,
                    'display'   => $row->nama
                ],

                'dosage' => [
                    [
                        'text' => $row->URAIAN
                    ]
                ],

                'effectiveDateTime' => Carbon::parse($row->INPUTDATE)->toIso8601String(),
                'dateAsserted'      => Carbon::parse($row->WK_KIRIM_SATUSEHAT)->toIso8601String(),
                'informationSource' => [
                    'reference' => 'Patient/' . $row->idpx,
                    'display'   => $row->nama
                ],

                'context' => [
                    'reference' => 'Encounter/' . $row->id_satusehat_encounter
                ]
            ];
            $metas[] = [
                'ID_TRANS'                    => $row->ID_TRANS,
                'id_satusehat_encounter'      => $row->id_satusehat_encounter,
                'KD_BRG_KFA'                  => $row->KD_BRG_KFA,
                'FHIR_MEDICATION_DISPENSE_ID' => $row->FHIR_MEDICATION_DISPENSE_ID,
                'idpx'                        => $row->idpx,
                'local_medication_statement_id' => $medId
            ];
        }

        return [
            'payloads' => $payloads,
            'meta'     => $metas
        ];
    }


    protected function verifyMedStatementPayload(array $payload)
    {
        $errors = [];

        $requiredFields = [
            'resourceType',
            'status',
            'subject',
            'medicationReference'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                $errors[] = "Field '{$field}' wajib ada";
            }
        }

        // resourceType harus benar
        if (
            isset($payload['resourceType']) &&
            $payload['resourceType'] !== 'MedicationStatement'
        ) {
            $errors[] = 'resourceType harus MedicationStatement';
        }

        // subject.reference wajib
        if (
            !isset($payload['subject']['reference']) ||
            empty($payload['subject']['reference'])
        ) {
            $errors[] = 'subject.reference wajib diisi';
        }

        // medicationReference.reference wajib
        if (
            !isset($payload['medicationReference']['reference']) ||
            empty($payload['medicationReference']['reference'])
        ) {
            $errors[] = 'medicationReference.reference wajib diisi';
        }

        return empty($errors) ? true : $errors;
    }


    public function kirimPayloadToSatuSehat(array $payload, array $meta)
    {
        // =======================
        // ACCESS TOKEN
        // =======================
        $accessToken = $this->getAccessToken();
        // echo json_encode($payload); die();

        if (!$accessToken) {
            return [
                'status'  => false,
                'message' => 'Access token tidak tersedia',
                'meta'    => $meta
            ];
        }

        // =======================
        // BASE URL
        // =======================
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) === 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')
                ->value('valStr');
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')
                ->value('valStr');
        }

        $endpoint = 'MedicationStatement';
        $url      = rtrim($baseurl, '/') . '/' . $endpoint;

        // =======================
        // HTTP REQUEST
        // =======================
        try {
            $client = new \GuzzleHttp\Client();

            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => json_encode($payload),
                'verify'  => false,
                'timeout' => 30,
            ]);

            $httpStatus      = $response->getStatusCode();
            $responseBodyRaw = (string) $response->getBody();
            $responseBody    = json_decode($responseBodyRaw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from SATUSEHAT');
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {

            $httpStatus   = null;
            $responseBody = [];

            if ($e->hasResponse()) {
                $httpStatus = $e->getResponse()->getStatusCode();
                $raw        = (string) $e->getResponse()->getBody();
                $responseBody = json_decode($raw, true) ?? [];
            }

            return [
                'status'  => false,
                'message' => 'HTTP request ke SATUSEHAT gagal',
                // 'payload' => $payload,
                'meta'    => $meta,
                'http'    => [
                    'status' => $httpStatus,
                    'error'  => $e->getMessage()
                ],
                // 'request'  => $payload,
                'response' => $responseBody
            ];
        }

        // =======================
        // RESPONSE PARSING
        // =======================
        $fhirStatementId = $responseBody['id'] ?? null;
        $status          = !empty($fhirStatementId) ? 'success' : 'failed';

        // =======================
        // LOGGING DATA
        // =======================
        $logData = [
            'LOG_TYPE' => 'MedicationStatement',

            // local & idempotent reference
            'LOCAL_ID' => $meta['ID_TRANS'] ?? null,

            // medication reference (contained)
            'FHIR_MEDICATION_ID' => $payload['medicationReference']['reference'] ?? null,
            'NAMA_OBAT'          => $payload['medicationReference']['display'] ?? '-',
            'KFA_CODE'           => $meta['KD_BRG_KFA'] ?? null,

            // FHIR result (NOTE: kolom lama masih bernama DISPENSE)
            'IDENTIFIER_VALUE' => $fhirStatementId,

            // relasi utama
            'PATIENT_ID'   => $meta['idpx'] ?? null,
            'ENCOUNTER_ID' => $meta['id_satusehat_encounter'] ?? null,

            // status & audit
            'STATUS'      => $status,
            'HTTP_STATUS' => $httpStatus,
            'RESPONSE_MESSAGE' => json_encode(
                $responseBody,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
            'CREATED_AT' => now(),
            'PAYLOAD'    => json_encode(
                $payload,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        ];

        // =======================
        // UPSERT LOG DB
        // =======================
        $existing = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
            ->where('LOG_TYPE', 'MedicationStatement')
            ->where('LOCAL_ID', $logData['LOCAL_ID'])
            ->where('KFA_CODE', $logData['KFA_CODE'])
            ->first();

        if ($existing) {
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
                ->where('ID', $existing->ID)
                ->update([
                    'FHIR_MEDICATION_DISPENSE_ID' => $logData['FHIR_MEDICATION_DISPENSE_ID'],
                    'STATUS'          => $logData['STATUS'],
                    'HTTP_STATUS'     => $logData['HTTP_STATUS'],
                    'RESPONSE_MESSAGE' => $logData['RESPONSE_MESSAGE'],
                    'UPDATED_AT'      => now(),
                    'PAYLOAD'         => $logData['PAYLOAD']
                ]);
        } else {
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
                ->insert($logData);
        }

        // =======================
        // FINAL RETURN
        // =======================
        return [
            'status'  => $status === 'success',
            'message' => $status === 'success'
                ? 'MedicationStatement berhasil dikirim'
                : 'MedicationStatement gagal dikirim',
            'fhir_id' => $fhirStatementId,
            'http'    => [
                'status' => $httpStatus,
                'url'    => $url
            ],
            'meta'    => $meta,
            'response' => $responseBody
        ];
    }
    private function getAccessToken()
    {
        $tokenData = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')
            ->select('issued_at', 'expired_in', 'access_token')
            ->where('idunit', '001')
            ->orderBy('id', 'desc')
            ->first();

        return $tokenData->access_token ?? null;
    }
}
