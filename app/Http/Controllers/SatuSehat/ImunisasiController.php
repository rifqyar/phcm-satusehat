<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\GlobalParameter;

class ImunisasiController extends Controller
{
    public function index()
    {
        return response()->view('pages.satusehat.imunisasi.index');
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
        $idUnit   = session('id_unit', '001');
        $tglAwal  = $request->input('tgl_awal');
        $tglAkhir = $request->input('tgl_akhir');

        $whereTanggal = '';
        $bindings = [$idUnit];

        if ($tglAwal && $tglAkhir) {
            $whereTanggal = ' AND A.TANGGAL BETWEEN ? AND ? ';
            $bindings[] = $tglAwal;
            $bindings[] = $tglAkhir;
        }

        $sql = "
        SELECT 
            A.ID_IMUNISASI_PX,
            B.id_satusehat_encounter,
            B.karcis,
            C.nama AS NAMA_PASIEN,
            A.TANGGAL,
            A.JENIS_VAKSIN,
            A.DOSIS,
            A.KODE_CENTRA,
            A.KODE_VAKSIN,
            A.DISPLAY_VAKSIN,
            A.SATUSEHAT_STATUS,
            A.CRTDT
        FROM E_RM_PHCM.dbo.ERM_IMUNISASI_PX A
        JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA B
            ON A.KARCIS = B.karcis
        JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN C
            ON B.id_satusehat_px = C.idpx
        WHERE A.IDUNIT = ?
        $whereTanggal
        ORDER BY A.CRTDT DESC";

        $data = DB::select($sql, $bindings);

        return response()->json([
            'data' => $data,
            'summary' => [
                'all'     => count($data),
                'pending' => collect($data)->where('SATUSEHAT_STATUS', 'PENDING')->count(),
                'success' => collect($data)->where('SATUSEHAT_STATUS', 'SUCCESS')->count(),
                'failed'  => collect($data)->where('SATUSEHAT_STATUS', 'FAILED')->count(),
            ]
        ]);
    }

    public function kirimImunisasiSatusehat(Request $request)
    {
        $idImunisasi = $request->id_imunisasi_px;

        if (!$idImunisasi) {
            return response()->json([
                'success' => false,
                'message' => 'ID imunisasi tidak valid'
            ], 400);
        }

        //  ambil data imunisasi
        $data = $this->getDataImunisasi($idImunisasi);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data imunisasi tidak ditemukan'
            ], 404);
        }

        //  build payload FHIR
        $payload = $this->buildPayloadImunisasi($data);

        //  meta (untuk logging & update)
        $meta = [
            'id_imunisasi_px' => $data->ID_IMUNISASI_PX,
            'karcis'          => $data->KARCIS ?? null,
            'idunit'          => $data->IDUNIT ?? null,
            'tgl'             => $data->TANGGAL ?? null,
            'user'            => auth()->user()->username ?? 'system'
        ];

        //  KIRIM KE SATUSEHAT (INI YANG TADI BELUM)
        $result = $this->kirimImunisasiToSatuSehat($payload, $meta);

        // return ke frontend
        return response()->json([
            'success' => $result['status'],
            'message' => $result['message'],
            'fhir_id' => $result['fhir_id'] ?? null,
            'http'    => $result['http'] ?? null,
            'response' => $result['response'] ?? null,
        ]);
    }


    private function getDataImunisasi($idImunisasi)
    {
        $sql = "
        SELECT 
            A.ID_IMUNISASI_PX,
            A.DISPLAY_VAKSIN,
            A.KODE_VAKSIN,
            C.nama AS NAMA_PASIEN,
            C.idpx AS ID_SATUSEHAT_PASIEN,
            B.id_satusehat_encounter,
            A.TANGGAL,
            A.CRTDT,
            A.JENIS_VAKSIN,
            F.CODE_DISPLAY,
            D.idnakes,
            D.NAMA AS NAMA_NAKES,
            A.KODE_CENTRA,
            A.SATUSEHAT_STATUS,
            A.DOSIS,
            E.NAMA_UNIT
        FROM E_RM_PHCM.dbo.ERM_IMUNISASI_PX A
        JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA B
            ON A.KARCIS = B.karcis
        JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN C
            ON B.id_satusehat_px = C.idpx
        JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES D
            ON B.id_satusehat_dokter = D.idnakes
        JOIN SIRS_PHCM.dbo.RIRJ_MKODE_UNIT E
            ON A.IDUNIT = E.ID_UNIT
        LEFT JOIN E_RM_PHCM.dbo.ERM_IMUNISASI_JENIS F
            on A.JENIS_VAKSIN = F.CODE_VALUE 
        WHERE A.ID_IMUNISASI_PX = ?
    ";

        return DB::selectOne($sql, [$idImunisasi]);
    }
    private function buildPayloadImunisasi($row)
    {
        return [
            'resourceType' => 'Immunization',
            'status' => 'completed',

            'vaccineCode' => [
                'coding' => [
                    [
                        'system'  => 'http://sys-ids.kemkes.go.id/kfa',
                        'code'    => $row->KODE_VAKSIN,
                        'display' => $row->DISPLAY_VAKSIN
                    ]
                ]
            ],

            'patient' => [
                'reference' => 'Patient/' . $row->ID_SATUSEHAT_PASIEN,
                'display'   => $row->NAMA_PASIEN
            ],

            'encounter' => [
                'reference' => 'Encounter/' . $row->id_satusehat_encounter
            ],

            'occurrenceDateTime' => date('c', strtotime($row->TANGGAL)),
            'recorded'           => date('c', strtotime($row->CRTDT)),

            'primarySource' => false,
            'reasonCode' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.kemkes.go.id/CodeSystem/immunization-reason',
                            'code'   => $row->JENIS_VAKSIN,
                            'display'=> $row->CODE_DISPLAY,
                        ]
                    ]
                ]
            ],
            'performer' => [
                [
                    'function' => [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0443',
                                'code'   => 'EP'
                            ]
                        ]
                    ],
                    'actor' => [
                        'reference' => 'Practitioner/' . $row->idnakes,
                        'display'   => $row->NAMA_NAKES
                    ]
                ]
            ],

            'location' => [
                'display' => $row->NAMA_UNIT
            ],

            'protocolApplied' => [
                [
                    'doseNumberPositiveInt' => !empty($row->DOSIS) && (int)$row->DOSIS > 0 ? (int)$row->DOSIS : 1
                ]
            ]
        ];
    }

    public function kirimImunisasiToSatuSehat(array $payload, array $meta)
    {
        // =======================
        // ACCESS TOKEN
        // =======================
        $accessToken = $this->getAccessToken();

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
        $baseurl = strtoupper(env('SATUSEHAT', 'PRODUCTION')) === 'DEVELOPMENT'
            ? GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->value('valStr')
            : GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->value('valStr');

        $endpoint = 'Immunization';
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
                'body'    => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'verify'  => false,
                'timeout' => 30,
            ]);

            $httpStatus   = $response->getStatusCode();
            $responseBody = json_decode((string) $response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from SATUSEHAT');
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {

            $httpStatus   = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $responseBody = $e->hasResponse()
                ? json_decode((string) $e->getResponse()->getBody(), true)
                : [];

            // update FAILED
            DB::table('E_RM_PHCM.dbo.ERM_IMUNISASI_PX')
                ->where('ID_IMUNISASI_PX', $meta['id_imunisasi_px'])
                ->update([
                    'SATUSEHAT_STATUS' => 'FAILED',
                    'SATUSEHAT_RESPONSE' => json_encode($responseBody),
                    'SATUSEHAT_SENT_AT' => now(),
                ]);

            return [
                'status'   => false,
                'message'  => 'HTTP request ke SATUSEHAT gagal',
                'http'     => $httpStatus,
                'response' => $responseBody
            ];
        }

        // =======================
        // RESPONSE PARSING
        // =======================
        $immunizationId = $responseBody['id'] ?? null;
        $status         = $immunizationId ? 'SUCCESS' : 'FAILED';

        // =======================
        // LOG GLOBAL TRANSACTION
        // =======================
        try {
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_TRANSACTION')->insert([
                'service'    => 'Immunization',
                'request'    => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'response'   => json_encode($responseBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_by' => $meta['user'] ?? 'system',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal insert SATUSEHAT_LOG_TRANSACTION (Immunization)', [
                'error' => $e->getMessage()
            ]);
        }

        // =======================
        // UPDATE ERM_IMUNISASI_PX
        // =======================
        DB::table('E_RM_PHCM.dbo.ERM_IMUNISASI_PX')
            ->where('ID_IMUNISASI_PX', $meta['id_imunisasi_px'])
            ->update([
                'SATUSEHAT_STATUS'        => $status,
                'SATUSEHAT_ID'            => $immunizationId,
                'SATUSEHAT_RESPONSE'      => json_encode($responseBody),
                'SATUSEHAT_SENT_AT'       => now(),
            ]);

        // =======================
        // FINAL RETURN
        // =======================
        return [
            'status'  => $status === 'SUCCESS',
            'message' => $status === 'SUCCESS'
                ? 'Imunisasi berhasil dikirim ke SATUSEHAT'
                : 'Imunisasi gagal dikirim ke SATUSEHAT',
            'fhir_id' => $immunizationId,
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
