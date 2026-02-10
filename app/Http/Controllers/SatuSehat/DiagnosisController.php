<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\SATUSEHAT\SS_Kode_API;
use App\Models\GlobalParameter;


class DiagnosisController extends Controller
{
    public function index()
    {
        return response()->view('pages.satusehat.diagnosis.index');
    }

    public function datatable(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = Carbon::parse($request->input('end_date'))->addDay()->startOfDay();
        $id_unit   = Session::get('id_unit', '001');

        if (!$startDate || !$endDate) {
            $endDate   = now()->toDateString();
            $startDate = now()->subDays(30)->toDateString();
        }


        $baseSql = "
            SELECT
                SE.id_satusehat_encounter,
                SP.nama AS PASIEN,
                SN.nama AS DOKTER,
                irja.KODE_DIAGNOSA_UTAMA,
                SD.id_satusehat_condition,
                b.KARCIS,
                b.TGL,
                a.NOTA,
                d.REKENING AS KLINIK,
                SE.jam_datang,
                SE.jam_progress,
                SE.jam_selesai
            FROM SIRS_PHCM..RJ_KARCIS b
            JOIN SIRS_PHCM..RJ_KARCIS_BAYAR a
                ON a.KARCIS = b.KARCIS

            LEFT JOIN (
                SELECT KARCIS, KODE_DIAGNOSA_UTAMA
                FROM (
                    SELECT
                        KARCIS,
                        KODE_DIAGNOSA_UTAMA,
                        ROW_NUMBER() OVER (
                            PARTITION BY KARCIS
                            ORDER BY CRTDT DESC
                        ) AS rn
                    FROM E_RM_PHCM.dbo.ERM_RM_IRJA
                    WHERE KODE_DIAGNOSA_UTAMA IS NOT NULL
                ) x
                WHERE rn = 1
            ) irja
                ON irja.KARCIS = b.KARCIS

            LEFT JOIN SIRS_PHCM..RJ_MKLINIK d
                ON d.KDKLINIK = b.KLINIK
            LEFT JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA SE
                ON b.KARCIS = SE.karcis
            JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN SP
                ON SE.id_satusehat_px = SP.idpx
            JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES SN
            	ON SE.id_satusehat_dokter = SN.idnakes
            LEFT JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_DIAGNOSA SD
                ON b.KARCIS = SD.karcis
            WHERE b.TGL BETWEEN ? AND ?
            AND b.IDUNIT = ?
            AND a.IDUNIT = ?
            AND ISNULL(a.STBTL, 0) = 0
            ";


        $query = DB::table(DB::raw("($baseSql) AS x"))
            ->setBindings([$startDate, $endDate, $id_unit, $id_unit]);

        // filter status (optional)
        if ($status = $request->input('status')) {
            if ($status === 'sent') {
                $query->whereNotNull('x.id_satusehat_condition');
            } elseif ($status === 'unsent') {
                $query->whereNull('x.id_satusehat_condition');
            }
        }



        $dataTable = DataTables::of($query)
            ->order(function ($q) {
                $q->orderBy('x.KARCIS', 'desc');
            })
            ->make(true);


        $summary = DB::connection('sqlsrv')->selectOne("
            SELECT
                COUNT(DISTINCT b.KARCIS) AS total,
                COUNT(DISTINCT CASE
                    WHEN SD.id_satusehat_condition IS NOT NULL
                    THEN b.KARCIS
                END) AS sent
            FROM SIRS_PHCM..RJ_KARCIS b
            JOIN SIRS_PHCM..RJ_KARCIS_BAYAR a
                ON a.KARCIS = b.KARCIS

            LEFT JOIN (
                SELECT KARCIS
                FROM (
                    SELECT
                        KARCIS,
                        ROW_NUMBER() OVER (
                            PARTITION BY KARCIS
                            ORDER BY CRTDT DESC
                        ) AS rn
                    FROM E_RM_PHCM.dbo.ERM_RM_IRJA
                    WHERE KODE_DIAGNOSA_UTAMA IS NOT NULL
                ) x
                WHERE rn = 1
            ) irja
                ON irja.KARCIS = b.KARCIS

            JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA SE
                ON b.KARCIS = SE.karcis
            LEFT JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_DIAGNOSA SD
                ON b.KARCIS = SD.karcis

            WHERE b.TGL BETWEEN ? AND ?
            AND b.IDUNIT = ?
            AND a.IDUNIT = ?
            AND ISNULL(a.STBTL, 0) = 0
            ", [$startDate, $endDate, $id_unit, $id_unit]);


        $recordsTotal = (int) ($summary->total ?? 0);
        $sentCount    = (int) ($summary->sent ?? 0);

        $json = $dataTable->getData(true);
        $json['summary'] = [
            'all'    => $recordsTotal,
            'sent'   => $sentCount,
            'unsent' => $recordsTotal - $sentCount,
        ];

        return response()->json($json);
    }
    public function getDetailDiagnosis(Request $request)
    {
        $karcis  = $request->id;
        $id_unit = Session::get('id_unit', '001');

        if (!$karcis) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter KARCIS tidak ditemukan.'
            ], 400);
        }

        $sql = "
        SELECT
            karcis,
            KODE_DIAGNOSA_UTAMA,
            DIAG_UTAMA,
            ANAMNESE,
            recorded_date,
            id_satusehat_encounter,
            id_satusehat_px,
            nama_pasien,
            tglLahir
        FROM (
            SELECT
                SE.karcis,
                A.KODE_DIAGNOSA_UTAMA,
                A.DIAG_UTAMA,
                A.ANAMNESE,
                A.CRTDT AS recorded_date,
                SE.id_satusehat_encounter,
                SE.id_satusehat_px,
                SP.nama AS nama_pasien,
                SP.tglLahir,
                ROW_NUMBER() OVER (
                    PARTITION BY A.KARCIS
                    ORDER BY
                        CASE 
                            WHEN A.KODE_DIAGNOSA_UTAMA IS NOT NULL THEN 0
                            ELSE 1
                        END,
                        A.CRTDT DESC
                ) AS rn
            FROM E_RM_PHCM.dbo.ERM_RM_IRJA A
            LEFT JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA SE
                ON A.KARCIS = SE.karcis
            AND SE.idunit = ?
            LEFT JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN SP
                ON SE.id_satusehat_px = SP.idpx
            WHERE A.KARCIS = ?
        ) x
        WHERE rn = 1";

        $row = DB::connection('sqlsrv')->selectOne($sql, [$id_unit, $karcis]);

        if (!$row) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data diagnosis tidak ditemukan.'
            ], 404);
        }

        $data = [
            'patient_id'   => $row->id_satusehat_px,
            'patient_name' => $row->nama_pasien,
            'birth_date'   => $row->tglLahir
                ? Carbon::parse($row->tglLahir)->format('d-m-Y')
                : null,

            'encounter_id' => $row->id_satusehat_encounter,
            'diagnosis_id' => $row->KODE_DIAGNOSA_UTAMA,

            'code' => [
                'icd10'       => $row->KODE_DIAGNOSA_UTAMA,
                'description' => $row->DIAG_UTAMA,
            ],

            'clinical_status'     => 'active',
            'verification_status' => 'confirmed',

            'recorded_date' => Carbon::parse($row->recorded_date)
                ->setTimezone('Asia/Jakarta')
                ->toIso8601String(),

            'note' => $row->ANAMNESE,
        ];

        return response()->json([
            'status' => 'success',
            'data'   => $data
        ]);
    }

    public function prepSendDiagnosis(Request $request)
    {
        $karcis  = $request->karcis;
        $id_unit = Session::get('id_unit', '001');
        $user    = auth()->user()->name ?? 'system';

        if (!$karcis) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter karcis wajib diisi'
            ], 400);
        }

        $sql = "
        SELECT
            UPPER(LTRIM(RTRIM(A.KODE_DIAGNOSA_UTAMA))) AS KODE_DIAGNOSA_UTAMA,
            A.DIAG_UTAMA,
            SE.id_satusehat_encounter,
            SE.nota,
            SE.karcis,
            SE.jam_datang,
            SP.idpx,
            SP.nama AS nama_pasien,
            ICD.KATA1
        FROM (
            SELECT *
            FROM (
                SELECT
                    A.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY A.KARCIS
                        ORDER BY
                            CASE 
                                WHEN A.KODE_DIAGNOSA_UTAMA IS NOT NULL THEN 0
                                ELSE 1
                            END,
                            A.CRTDT DESC
                    ) AS rn
                FROM E_RM_PHCM.dbo.ERM_RM_IRJA A
                WHERE A.KARCIS = ?
                AND A.IDUNIT = ?
            ) x
            WHERE rn = 1
        ) A
        JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA SE
            ON A.KARCIS = SE.karcis
        AND SE.idunit = ?
        JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN SP
            ON SE.id_satusehat_px = SP.idpx
        LEFT JOIN SIRS_PHCM.dbo.RIRJ_ICD ICD
            ON A.KODE_DIAGNOSA_UTAMA = ICD.DIAGNOSA";

        $row = DB::connection('sqlsrv')->selectOne(
            $sql,
            [$karcis, $id_unit, $id_unit]
        );

        if (!$row || !$row->KODE_DIAGNOSA_UTAMA) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Diagnosa utama belum tersedia'
            ], 422);
        }

        $payload = $this->buildConditionPayload($row);

        // cek payload sebelum kirim
        // return response()->json([
        //     'status'  => 'debug',
        //     'payload' => $payload
        // ]);

        $meta = [
            'karcis' => $karcis,
            'nota'   => $row->nota,
            'idunit' => $id_unit,
            'tgl'    => now()->toDateString(),
            'rank'   => 1,
            'user'   => $user,
        ];

        $result = $this->kirimConditionToSatuSehat($payload, $meta);

        return response()->json($result);
    }
    private function buildConditionPayload($row)
    {
        return [
            'resourceType' => 'Condition',

            'clinicalStatus' => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                        'code'    => 'active',
                        'display' => 'Active'
                    ]
                ]
            ],

            'category' => [
                [
                    'coding' => [
                        [
                            'system'  => 'http://terminology.hl7.org/CodeSystem/condition-category',
                            'code'    => 'encounter-diagnosis',
                            'display' => 'Encounter Diagnosis'
                        ]
                    ]
                ]
            ],

            'code' => [
                'coding' => [
                    [
                        'system'  => 'http://hl7.org/fhir/sid/icd-10',
                        'code'    => $row->KODE_DIAGNOSA_UTAMA,
                        'display' => $row->KATA1 ?? $row->DIAG_UTAMA
                    ]
                ]
            ],

            'subject' => [
                'reference' => 'Patient/' . $row->idpx,
                'display'   => $row->nama_pasien
            ],

            'encounter' => [
                'reference' => 'Encounter/' . $row->id_satusehat_encounter,
                'display'   => 'Kunjungan ' . $row->nama_pasien .
                    ' pada ' . Carbon::parse($row->jam_datang)
                    ->setTimezone('Asia/Jakarta')
                    ->translatedFormat('l, d F Y')
            ],

            // opsional tapi bagus
            'onsetDateTime' => Carbon::parse($row->jam_datang)
                ->setTimezone('Asia/Jakarta')
                ->toIso8601String(),

            'recordedDate' => Carbon::now('Asia/Jakarta')->toIso8601String()
        ];
    }

    public function kirimConditionToSatuSehat(array $payload, array $meta)
    {

        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return [
                'status'  => false,
                'message' => 'Access token tidak tersedia',
                'meta'    => $meta
            ];
        }

        $baseurl = strtoupper(env('SATUSEHAT', 'PRODUCTION')) === 'DEVELOPMENT'
            ? GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->value('valStr')
            : GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->value('valStr');

        $endpoint = 'Condition';
        $url      = rtrim($baseurl, '/') . '/' . $endpoint;

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

            $httpStatus   = $response->getStatusCode();
            $responseBody = json_decode((string) $response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from SATUSEHAT');
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {

            $httpStatus = $e->hasResponse()
                ? $e->getResponse()->getStatusCode()
                : null;

            $rawBody = $e->hasResponse()
                ? (string) $e->getResponse()->getBody()
                : null;

            $responseBody = $rawBody
                ? json_decode($rawBody, true)
                : null;

            return [
                'status'  => false,
                'message' => 'Gagal mengirim data ke satu sehat',
                'meta'    => $meta,
                'http'    => [
                    'status' => $httpStatus,
                    'error'  => 'RequestException'
                ],
                'response_raw' => $rawBody,
                'response'     => $responseBody
            ];
        }

        $conditionId = $responseBody['id'] ?? null;
        $status      = $conditionId ? 'success' : 'failed';


        try {
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_TRANSACTION')->insert([
                'service'    => 'Condition',
                'request'    => json_encode(
                    $payload,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ),
                'response'   => json_encode(
                    $responseBody,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ),
                'created_by' => $meta['user'] ?? 'system',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // logging gagal TIDAK boleh menggagalkan proses utama
            Log::error('Gagal insert SATUSEHAT_LOG_TRANSACTION', [
                'service' => 'Condition',
                'error'   => $e->getMessage()
            ]);
        }

        $logData = [
            'karcis'  => $meta['karcis'],
            'nota'    => $meta['nota'],
            'idunit'  => $meta['idunit'],
            'tgl'     => $meta['tgl'],
            'rank'    => $meta['rank'] ?? 1,

            'code'    => $payload['code']['coding'][0]['code'] ?? null,
            'display' => $payload['code']['coding'][0]['display'] ?? null,

            'id_satusehat_condition' => $conditionId,
            'status_sinkron'         => $status === 'success' ? 1 : 0,
            'crtusr'                 => $meta['user'] ?? 'system',
            'crtdt'                  => now(),
            'sinkron_date'           => $status === 'success' ? now() : null,
        ];


        $existing = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_DIAGNOSA')
            ->where('karcis', $logData['karcis'])
            ->where('rank', $logData['rank'])
            ->where('idunit', $logData['idunit'])
            ->first();

        if ($existing) {
            DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_DIAGNOSA')
                ->where('id', $existing->id)
                ->update($logData);
        } else {
            DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_DIAGNOSA')
                ->insert($logData);
        }

        return [
            'status'  => $status === 'success',
            'message' => $status === 'success'
                ? 'Condition (Diagnosis) berhasil dikirim'
                : 'Condition (Diagnosis) gagal dikirim',
            'fhir_id' => $conditionId,
            'http'    => [
                'status' => $httpStatus,
                'url'    => $url
            ],
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
