<?php

namespace App\Http\Controllers\SatuSehat;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Jobs\SendMedicationdispense;
use App\Jobs\SendMedicationRequest as JobsSendMedicationRequest;

class MedicationDispenseController extends Controller
{
    public function index(Request $request)
    {
        return response()->view('pages.satusehat.medicationdispense.index');
    }

    public function datatable(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $jenis = $request->input('jenis'); // ri / rj

        if (!$startDate || !$endDate) {
            $endDate = now();
            $startDate = now()->subDays(30);
        }

        // pilih tabel kunjungan
        $kunjunganTable = $jenis === 'ri' ? 'SIRS_PHCM.dbo.v_kunjungan_ri' : 'SIRS_PHCM.dbo.v_kunjungan_rj';

        // pilih KET_LAYANAN sesuai jenis
        $ketLayanan = $jenis === 'ri' ? 'INAP' : 'JALAN';

        $query = DB::table('SIRS_PHCM.dbo.IF_HTRANS_OL as a')
            ->distinct()
            ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as b', 'a.KARCIS', '=', 'b.karcis')

            // kunjungan dinamis
            ->leftJoin("$kunjunganTable as c", 'a.KARCIS', '=', 'c.ID_TRANSAKSI')

            // join ke IF_HTRANS (untuk ambil ID_TRANS final)
            ->join('SIRS_PHCM.dbo.IF_HTRANS as aj', 'a.ID_TRANS', '=', 'aj.ID_TRANS_OL')

            // mapping status KFA
            ->leftJoin(
                DB::raw("
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
            ) AS d
        "),
                'd.ID_TRANS',
                '=',
                'a.ID_TRANS',
            )

            // LOG MedicationRequest (success)
            ->leftJoin(
                DB::raw("
            (
                SELECT
                    LOCAL_ID,
                    MAX(ID) AS MAX_ID
                FROM SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION
                WHERE LOG_TYPE = 'MedicationRequest'
                  AND STATUS = 'success'
                GROUP BY LOCAL_ID
            ) AS log_req
        "),
                'log_req.LOCAL_ID',
                '=',
                'a.ID_TRANS',
            )

            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION as SSM_REQ', 'SSM_REQ.ID', '=', DB::raw('log_req.MAX_ID'))

            // LOG MedicationDispense (success)
            ->leftJoin(
                DB::raw("
            (
                SELECT
                    LOCAL_ID,
                    MAX(ID) AS MAX_ID
                FROM SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION
                WHERE LOG_TYPE = 'MedicationDispense'
                  AND STATUS = 'success'
                GROUP BY LOCAL_ID
            ) AS log_disp
        "),
                'log_disp.LOCAL_ID',
                '=',
                'a.ID_TRANS',
            )

            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION as SSM_DISP', 'SSM_DISP.ID', '=', DB::raw('log_disp.MAX_ID'))

            // FILTER tanggal
            ->whereBetween(DB::raw('CAST(c.TANGGAL AS date)'), [$startDate, $endDate])

            // FILTER JALAN / INAP
            ->where('a.KET_LAYANAN', $ketLayanan)

            ->select(
                'b.id',
                'b.id_satusehat_encounter',
                'aj.ID_TRANS',
                DB::raw('CAST(c.TANGGAL AS date) AS TGL_KARCIS'),
                'a.KARCIS',
                DB::raw('c.NAMA_PASIEN AS PASIEN'),
                DB::raw('c.DOKTER AS DOKTER'),
                DB::raw("
                CASE
                    WHEN SSM_DISP.ID IS NOT NULL THEN '200'
                    WHEN SSM_REQ.ID  IS NOT NULL THEN '100'
                    ELSE d.STATUS_MAPPING
                END AS STATUS_MAPPING
            "),
                DB::raw('SSM_DISP.STATUS AS DISP_STATUS'),
                DB::raw('SSM_DISP.CREATED_AT AS DISP_CREATED_AT'),
            );

        // COUNT
        $recordsTotal = (clone $query)->count();

        $dataTable = DataTables::of($query)
            ->filterColumn('KARCIS', function ($q, $k) {
                $q->where('a.KARCIS', 'like', "%{$k}%");
            })
            ->filterColumn('PASIEN', function ($q, $k) {
                $q->where('c.NAMA_PASIEN', 'like', "%{$k}%");
            })
            ->filterColumn('DOKTER', function ($q, $k) {
                $q->where('c.DOKTER', 'like', "%{$k}%");
            })
            ->filter(function ($query) use ($request) {
                $search = $request->get('search');
                if (isset($search['value']) && $search['value'] !== '') {
                    $keyword = $search['value'];
                    $query->where(function ($q) use ($keyword) {
                        $q->where('a.KARCIS', 'like', "%{$keyword}%")
                            ->orWhere('c.NAMA_PASIEN', 'like', "%{$keyword}%")
                            ->orWhere('c.DOKTER', 'like', "%{$keyword}%")
                            ->orWhere('aj.ID_TRANS', 'like', "%{$keyword}%");
                    });
                }
            })
            ->order(function ($q) {
                $q->orderBy('aj.ID_TRANS', 'desc');
            })
            ->make(true);

        $json = $dataTable->getData(true);
        $json['summary'] = ['all' => $recordsTotal];

        return response()->json($json);
    }
    public function getDetailObat(Request $request)
    {
        $idTrans = $request->id; // ID_TRANS dikirim dari tombol lihatObat(id)

        try {
            $data = DB::select(
                "
             SELECT
                i.ID_TRANS,
                i.NAMABRG AS NAMA_OBAT,
                i.KDBRG_CENTRA,
                m.KD_BRG_KFA,
                m.NAMABRG_KFA,
                ih.ID_TRANS_OL,
                iho.ID_TRANS as 'ID_TRANS_DOKTER',
                COALESCE(slm11.STATUS, slm1.STATUS) AS STATUS_KIRIM_MEDICATION_REQUEST,
                slm1.STATUS as 'STATUS_KIRIM_MEDICATION_REQUEST_N',
                slm11.STATUS as 'STATUS_KIRIM_MEDICATION_REQUEST_F_DISPENSE',
                slm2.STATUS as 'STATUS_KIRIM_MEDICATION_DISPENSE'
            FROM SIRS_PHCM.dbo.IF_TRANS i
            join SIRS_PHCM.dbo.IF_HTRANS ih ON I.ID_TRANS = ih.ID_TRANS
            left join SIRS_PHCM.dbo.IF_HTRANS_OL iho on ih.ID_TRANS_OL = iho.ID_TRANS
            LEFT JOIN SIRS_PHCM.dbo.M_TRANS_KFA m
                ON i.KDBRG_CENTRA = m.KDBRG_CENTRA
            left join SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION slm1 on iho.ID_TRANS = slm1.LOCAL_ID and slm1.LOG_TYPE = 'MedicationRequest' and slm1.KFA_CODE = m.KD_BRG_KFA and slm1.STATUS = 'success'
            left join SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION slm11 on ih.ID_TRANS = slm11.LOCAL_ID and slm11.LOG_TYPE = 'MedicationRequestFromDispense' and slm11.KFA_CODE = m.KD_BRG_KFA and slm11.STATUS = 'success'
            left join SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION slm2 on ih.ID_TRANS = slm2.LOCAL_ID and slm2.LOG_TYPE = 'MedicationDispense'and slm2.KFA_CODE = m.KD_BRG_KFA and slm2.STATUS = 'success'
           WHERE i.ID_TRANS = :idTrans
        ",
                ['idTrans' => $idTrans],
            );

            if (empty($data)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Data obat tidak ditemukan untuk transaksi tersebut.',
                    ],
                    404,
                );
            }

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }
    //cek obat ini ada di medication request apa nggak
    public function cekBelumMedicationRequest(Request $req)
    {
        $idTrans = $req->id_trans;

        try {
            // ambil seluruh obat dari transaksi
            $items = DB::connection('sqlsrv')->table('dbo.IF_TRANS_OL')->select('NAMABRG', 'KDBRG_CENTRA', 'ID_TRANS')->where('ID_TRANS', $idTrans)->get();

            // jika tidak ada item sama sekali
            if ($items->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'data' => [],
                ]);
            }

            // ambil daftar item yang SUDAH menjadi Medication Request sebelumnya
            $existing = DB::connection('sqlsrv')->table('dbo.IF_HTRANS_OL')->join('dbo.IF_TRANS_OL', 'IF_HTRANS_OL.ID_TRANS', '=', 'IF_TRANS_OL.ID_TRANS')->where('IF_HTRANS_OL.ID_TRANS', $idTrans)->pluck('IF_TRANS_OL.KDBRG_CENTRA')->toArray();

            // bentuk payload final
            $result = [];
            foreach ($items as $item) {
                $result[] = [
                    'id_trans' => $item->ID_TRANS,
                    'kfa_code' => $item->KDBRG_CENTRA,
                    'nama_obat' => $item->NAMABRG,
                    'sudah_jadi_request' => in_array($item->KDBRG_CENTRA, $existing),
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function sendMedicationDispense(Request $request)
    {
        try {
            // --- ambil parameter dari request ---
            $idTrans = $request->input('id_trans');

            if (empty($idTrans)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Parameter id_trans wajib dikirim.',
                    ],
                    400,
                );
            }

            // --- ambil data gabungan resep farmasi + dokter + pasien + FHIR log ---
            $data = DB::select(
                "
            SELECT distinct
                i.ID_TRANS AS ID_RESEP_FARMASI,
                i3.ID_TRANS AS RESEP_DOKTER,
                i2.MR_LINE as urutan,
                FORMAT(i2.INPUTDATE, 'yyyy-MM-ddTHH:mm:sszzz') as inputdate,
                i2.ID as isRacikan,
                i3.KARCIS,
                m.FHIR_ID AS medicationReference_reference,
                m.NAMABRG AS medicationReference_display,
                m.KD_BRG_KFA,
                r.id_satusehat_encounter,
                s.FHIR_MEDICATION_REQUEST_ID,
                r2.idpx,
                r2.nama AS pasien_nama,
                r3.idnakes,
                r3.nama AS nakes_nama
            FROM SIRS_PHCM.dbo.IF_HTRANS i
            JOIN SIRS_PHCM.dbo.IF_TRANS i2 ON i.ID_TRANS = i2.ID_TRANS
            JOIN SIRS_PHCM.dbo.IF_HTRANS_OL i3 ON i.ID_TRANS_OL = i3.ID_TRANS
            JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA r ON i3.KARCIS  = r.karcis
            JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN r2 ON r.id_satusehat_px = r2.idpx
            JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES r3 ON r.id_satusehat_dokter = r3.idnakes
            LEFT JOIN SIRS_PHCM.dbo.M_TRANS_KFA m ON i2.KDBRG_CENTRA = m.KDBRG_CENTRA
            LEFT JOIN SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION s
                ON m.KD_BRG_KFA = s.KFA_CODE
                AND i3.ID_TRANS = s.LOCAL_ID
            WHERE i.ID_TRANS = ?
        ",
                [$idTrans],
            );

            if (empty($data)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => "Data tidak ditemukan untuk ID_TRANS $idTrans.",
                    ],
                    404,
                );
            }

            // --- ambil token aktif dari tabel auth ---
            $tokenData = DB::connection('sqlsrv')->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')->select('issued_at', 'expired_in', 'access_token')->orderBy('id', 'desc')->first();

            if (!$tokenData) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Access token tidak ditemukan di tabel RIRJ_SATUSEHAT_AUTH.',
                    ],
                    400,
                );
            }

            $accessToken = $tokenData->access_token;
            $orgId = '266bf013-b70b-4dc2-b934-40858a5658cc'; // organization ID (sandbox)
            $client = new \GuzzleHttp\Client();
            $results = [];

            // --- loop setiap obat di transaksi farmasi ---
            foreach ($data as $index => $item) {
                $uniqueId = str_replace('/', '-', $item->ID_RESEP_FARMASI);
                $identifierValue = 'DISP-' . $uniqueId;
                $jenisCode = $item->isRacikan == 1 ? 'C' : 'NC';
                $jenisName = $item->isRacikan == 1 ? 'Compound' : 'Non-compound';

                // --- handle authorizingPrescription wajib ---
                if (empty($item->FHIR_MEDICATION_REQUEST_ID)) {
                    // skip kalau data gak lengkap
                    $results[] = [
                        'medication' => $item->medicationReference_display ?? '-',
                        'status' => 'skipped',
                        'reason' => 'FHIR_MEDICATION_REQUEST_ID tidak ditemukan',
                        'response' => null,
                    ];
                    continue;
                }

                $authorizingPrescription = [
                    [
                        'reference' => 'MedicationRequest/' . $item->FHIR_MEDICATION_REQUEST_ID,
                    ],
                ];

                // --- handle encounter (optional) ---
                $context = null;
                if (!empty($item->ENCOUNTER_ID)) {
                    $context = [
                        'reference' => 'Encounter/' . $item->ENCOUNTER_ID,
                    ];
                }

                // --- bentuk payload minimum MedicationDispense ---
                $payload = [
                    'resourceType' => 'MedicationDispense',
                    'contained' => [
                        [
                            'resourceType' => 'Medication',
                            'meta' => [
                                'profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Medication'],
                            ],
                            'id' => $item->ID_RESEP_FARMASI . '-' . $item->urutan,
                            'identifier' => [
                                [
                                    'system' => 'http://sys-ids.kemkes.go.id/medication/' . $orgId,
                                    'use' => 'official',
                                ],
                            ],
                            'code' => [
                                'coding' => [
                                    [
                                        'system' => 'http://sys-ids.kemkes.go.id/kfa',
                                        'code' => $item->KD_BRG_KFA,
                                        'display' => $item->medicationReference_display,
                                    ],
                                ],
                            ],
                            'status' => 'active',
                            'extension' => [
                                [
                                    'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType',
                                    'valueCodeableConcept' => [
                                        'coding' => [
                                            [
                                                'system' => 'http://terminology.kemkes.go.id/CodeSystem/medication-type',
                                                'code' => $jenisCode,
                                                'display' => $jenisName,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'identifier' => [
                        [
                            'system' => 'http://sys-ids.kemkes.go.id/prescription/' . $orgId,
                            'use' => 'official',
                        ],
                    ],
                    'status' => 'completed',
                    'category' => [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category',
                                'code' => 'community',
                                'display' => 'Community',
                            ],
                        ],
                    ],
                    'medicationReference' => [
                        'reference' => '#' . $item->ID_RESEP_FARMASI . '-' . $item->urutan,
                    ],
                    'subject' => [
                        'reference' => 'Patient/' . $item->idpx,
                        'display' => $item->pasien_nama,
                    ],
                    'context' => [
                        'reference' => 'Encounter/' . $item->id_satusehat_encounter,
                    ],
                    'whenPrepared' => '2023-11-13T05:35:00+00:00',
                    'performer' => [
                        [
                            'actor' => [
                                'reference' => 'Practitioner/' . $item->idnakes,
                                'display' => $item->nakes_nama,
                            ],
                        ],
                    ],
                    'authorizingPrescription' => $authorizingPrescription,
                    'receiver' => [
                        [
                            'reference' => 'Patient/' . $item->idpx,
                            'display' => $item->pasien_nama,
                        ],
                    ],
                    'substitution' => [
                        'wasSubstituted' => false,
                    ],
                ];

                if ($context) {
                    $payload['context'] = $context;
                }

                // --- kirim ke API MedicationDispense ---
                $response = $client->post('https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1/MedicationDispense', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($payload),
                    'verify' => false,
                ]);
                $responseBody = json_decode($response->getBody(), true);
                $httpStatus = $response->getStatusCode();

                // --- catat log hasil pengiriman ---
                $logData = [
                    'LOG_TYPE' => 'MedicationDispense',
                    'LOCAL_ID' => $item->ID_RESEP_FARMASI,
                    'FHIR_MEDICATION_ID' => $item->medicationReference_reference ?? null,
                    'NAMA_OBAT' => $item->medicationReference_display ?? '-',
                    'KFA_CODE' => $item->KD_BRG_KFA ?? '-',
                    'FHIR_MEDICATION_DISPENSE_ID' => $responseBody['id'] ?? null,
                    'FHIR_MEDICATION_REQUEST_ID' => $item->FHIR_MEDICATION_REQUEST_ID ?? null,
                    'PATIENT_ID' => $item->idpx ?? null,
                    'ENCOUNTER_ID' => $item->ENCOUNTER_ID ?? null,
                    'STATUS' => isset($responseBody['id']) ? 'success' : 'failed',
                    'HTTP_STATUS' => $httpStatus,
                    'RESPONSE_MESSAGE' => json_encode($responseBody),
                    'CREATED_AT' => now(),
                    'PAYLOAD' => json_encode($payload),
                ];

                $existing = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->where('LOCAL_ID', $item->ID_RESEP_FARMASI)->where('FHIR_MEDICATION_ID', $item->medicationReference_reference)->where('LOG_TYPE', 'MedicationDispense')->first();

                if ($existing) {
                    DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
                        ->where('ID', $existing->ID)
                        ->update([
                            'FHIR_MEDICATION_DISPENSE_ID' => $logData['FHIR_MEDICATION_DISPENSE_ID'],
                            'FHIR_MEDICATION_REQUEST_ID' => $logData['FHIR_MEDICATION_REQUEST_ID'],
                            'FHIR_MEDICATION_ID' => $item->medicationReference_reference ?? null,
                            'PATIENT_ID' => $logData['PATIENT_ID'],
                            'ENCOUNTER_ID' => $logData['ENCOUNTER_ID'],
                            'KFA_CODE' => $item->KD_BRG_KFA ?? '-',
                            'STATUS' => $logData['STATUS'],
                            'HTTP_STATUS' => $logData['HTTP_STATUS'],
                            'RESPONSE_MESSAGE' => $logData['RESPONSE_MESSAGE'],
                            'UPDATED_AT' => now(),
                            'PAYLOAD' => json_encode($payload),
                        ]);
                } else {
                    DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert($logData);
                }

                $results[] = [
                    'medication' => $item->medicationReference_display,
                    'status' => $httpStatus,
                    'response' => $responseBody,
                ];
            }

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Semua MedicationDispense telah diproses.',
                    'results' => $results,
                ],
                200,
                [],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            );
        } catch (\Exception $e) {
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert([
                'LOG_TYPE' => 'MedicationDispense',
                'LOCAL_ID' => $request->input('id_trans'),
                'FHIR_MEDICATION_ID' => $item->medicationReference_reference ?? null,
                'LOCAL_ID' => $item->ID_RESEP_FARMASI,
                'FHIR_MEDICATION_ID' => $item->medicationReference_reference ?? null,
                'NAMA_OBAT' => $item->medicationReference_display ?? '-',
                'KFA_CODE' => $item->KD_BRG_KFA ?? '-',
                'STATUS' => 'failed',
                'HTTP_STATUS' => 500,
                'RESPONSE_MESSAGE' => $e->getMessage(),
                'CREATED_AT' => now(),
            ]);

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Exception: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
    // fungsi baru kirim lewat jobs
    public function prepMedicationDispense(Request $request)
    {
        try {
            // --- ambil parameter ---
            $idTrans = $request->input('id_trans');
            if (empty($idTrans)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Parameter id_trans wajib dikirim.',
                    ],
                    400,
                );
            }

            // --- ambil data gabungan resep farmasi + dokter + pasien + FHIR log ---
            $data = DB::select(
                "
            SELECT DISTINCT
                i.ID_TRANS AS ID_RESEP_FARMASI,
                i3.ID_TRANS AS RESEP_DOKTER,
                i2.MR_LINE as urutan,
                i2.ID as isRacikan,
                m.FHIR_ID AS medicationReference_reference,
                m.NAMABRG AS medicationReference_display,
                m.KD_BRG_KFA,
                r.id_satusehat_encounter,
                COALESCE(s.FHIR_MEDICATION_REQUEST_ID,slm.FHIR_MEDICATION_REQUEST_ID) AS FHIR_MEDICATION_REQUEST_ID,
                r2.idpx,
                r2.nama AS pasien_nama,
                r3.idnakes,
                r3.nama AS nakes_nama
            FROM SIRS_PHCM.dbo.IF_HTRANS i
            JOIN SIRS_PHCM.dbo.IF_TRANS i2 ON i.ID_TRANS = i2.ID_TRANS
            JOIN SIRS_PHCM.dbo.IF_HTRANS_OL i3 ON i.ID_TRANS_OL = i3.ID_TRANS
            JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA r ON i3.KARCIS  = r.karcis
            JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN r2 ON r.id_satusehat_px = r2.idpx
            JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES r3 ON r.id_satusehat_dokter = r3.idnakes
            LEFT JOIN SIRS_PHCM.dbo.M_TRANS_KFA m ON i2.KDBRG_CENTRA = m.KDBRG_CENTRA
            LEFT JOIN SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION s
                ON m.KD_BRG_KFA = s.KFA_CODE
                AND i3.ID_TRANS = s.LOCAL_ID
                and s.LOG_TYPE = 'MedicationRequest'
			LEFT JOIN SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION slm 
				on m.KD_BRG_KFA = slm.KFA_CODE and i.ID_TRANS = slm.LOCAL_ID and slm.LOG_TYPE = 'MedicationRequestFromDispense'
            WHERE i.ID_TRANS = ?
        ",
                [$idTrans],
            );

            if (empty($data)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => "Data tidak ditemukan untuk ID_TRANS $idTrans.",
                    ],
                    404,
                );
            }

            $summary = [];

            foreach ($data as $item) {
                // Wajib ada MedicationRequest untuk bisa membuat MedicationDispense
                if (empty($item->FHIR_MEDICATION_REQUEST_ID)) {
                    $summary[] = [
                        'medication' => $item->medicationReference_display,
                        'status' => 'skipped',
                        'reason' => 'MedicationRequest belum terkirim',
                    ];
                    continue;
                }

                // --- build payload MedicationDispense ---
                $payload = $this->createMedicationDispensePayload($item);

                // --- push to queue job ---
                SendMedicationDispense::dispatch($payload, [
                    'idTrans' => $idTrans,
                    'item' => $item, // penting! job membutuhkan object ini
                ]);

                $summary[] = [
                    'medication' => $item->medicationReference_display,
                    'status' => 'queued',
                    'payload' => $payload,
                ];
            }

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Payload dibuat. Semua pengiriman MedicationDispense diproses melalui queue.',
                    'results' => $summary,
                ],
                200,
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Exception: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    //buat payload dispense
    private function createMedicationDispensePayload($item)
    {
        $orgId = '266bf013-b70b-4dc2-b934-40858a5658cc'; // ID organisasi
        $jenisCode = $item->isRacikan == 1 ? 'C' : 'NC';
        $jenisName = $item->isRacikan == 1 ? 'Compound' : 'Non-compound';

        $medId = $item->ID_RESEP_FARMASI . '-' . $item->urutan;
        $authorizingPrescription = [
            [
                'reference' => 'MedicationRequest/' . $item->FHIR_MEDICATION_REQUEST_ID,
            ],
        ];

        return [
            'resourceType' => 'MedicationDispense',
            'contained' => [
                [
                    'resourceType' => 'Medication',
                    'meta' => [
                        'profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Medication'],
                    ],
                    'id' => $item->ID_RESEP_FARMASI . '-' . $item->urutan,
                    'identifier' => [
                        [
                            'system' => 'http://sys-ids.kemkes.go.id/medication/' . $orgId,
                            'use' => 'official',
                        ],
                    ],
                    'code' => [
                        'coding' => [
                            [
                                'system' => 'http://sys-ids.kemkes.go.id/kfa',
                                'code' => $item->KD_BRG_KFA,
                                'display' => $item->medicationReference_display,
                            ],
                        ],
                    ],
                    'status' => 'active',
                    'extension' => [
                        [
                            'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType',
                            'valueCodeableConcept' => [
                                'coding' => [
                                    [
                                        'system' => 'http://terminology.kemkes.go.id/CodeSystem/medication-type',
                                        'code' => $jenisCode,
                                        'display' => $jenisName,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'identifier' => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/prescription/' . $orgId,
                    'use' => 'official',
                ],
            ],
            'status' => 'completed',
            'category' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category',
                        'code' => 'community',
                        'display' => 'Community',
                    ],
                ],
            ],
            'medicationReference' => [
                'reference' => '#' . $item->ID_RESEP_FARMASI . '-' . $item->urutan,
            ],
            'subject' => [
                'reference' => 'Patient/' . $item->idpx,
                'display' => $item->pasien_nama,
            ],
            'context' => [
                'reference' => 'Encounter/' . $item->id_satusehat_encounter,
            ],
            'whenPrepared' => '2023-11-13T05:35:00+00:00',
            'performer' => [
                [
                    'actor' => [
                        'reference' => 'Practitioner/' . $item->idnakes,
                        'display' => $item->nakes_nama,
                    ],
                ],
            ],
            'authorizingPrescription' => $authorizingPrescription,
            'receiver' => [
                [
                    'reference' => 'Patient/' . $item->idpx,
                    'display' => $item->pasien_nama,
                ],
            ],
            'substitution' => [
                'wasSubstituted' => false,
            ],
        ];
    }

    //buat dispense kirim med request
    public function createMedicationRequestPayloadfromDispense(Request $request)
    {
        try {
            $idTrans = $request->idTrans;
            $kdbrg = $request->kdbrg;

            $data = DB::connection('sqlsrv')->select(
                "
            SELECT
                H1.ID_TRANS,
                MT.FHIR_ID AS medicationReference,
                MT.NAMABRG_KFA,
                MT.KD_BRG_KFA,
                MT.KDBRG_CENTRA,
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
            INNER JOIN SIRS_PHCM.dbo.IF_HTRANS H1 ON H.ID_TRANS = H1.ID_TRANS_OL
            INNER JOIN SIRS_PHCM.dbo.IF_TRANS H2 ON H1.ID_TRANS = H2.ID_TRANS
            INNER JOIN SIRS_PHCM.dbo.M_TRANS_KFA MT ON H2.KDBRG_CENTRA = MT.KDBRG_CENTRA
            WHERE H1.ID_TRANS = ? AND H2.KDBRG_CENTRA = ?
        ",
                [$idTrans, $kdbrg],
            );

            if (empty($data)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Data transaksi atau obat tidak ditemukan',
                    ],
                    404,
                );
            }

            $item = $data[0];
            $orgId = '266bf013-b70b-4dc2-b934-40858a5658cc';

            // pastikan FHIR_ID sudah ada
            if (empty($item->medicationReference)) {
                app(\App\Http\Controllers\SatusehatKfaController::class)->processMedication($item->KDBRG_CENTRA);

                $item->medicationReference = DB::connection('sqlsrv')->table('SIRS_PHCM.dbo.M_TRANS_KFA')->where('KDBRG_CENTRA', $item->KDBRG_CENTRA)->value('FHIR_ID');

                if (empty($item->medicationReference)) {
                    return response()->json(
                        [
                            'status' => 'error',
                            'message' => 'Kode FHIR tidak dapat dari SATUSEHAT',
                        ],
                        500,
                    );
                }
            }

            $uniqueId = now()->format('YmdHis') . '-001';
            $jenisCode = $item->IS_COMPOUND == 1 ? 'C' : 'NC';
            $jenisName = $item->IS_COMPOUND == 1 ? 'Compound' : 'Non-compound';

            $payload = [
                'resourceType' => 'MedicationRequest',
                'identifier' => [
                    [
                        'system' => 'http://sys-ids.kemkes.go.id/prescription',
                        'use' => 'official',
                        'value' => $uniqueId,
                    ],
                ],
                'contained' => [
                    [
                        'resourceType' => 'Medication',
                        'meta' => [
                            'profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Medication'],
                        ],
                        'id' => $uniqueId,
                        'identifier' => [
                            [
                                'system' => 'http://sys-ids.kemkes.go.id/medication',
                                'use' => 'official',
                                'value' => $item->KD_BRG_KFA,
                            ],
                        ],
                        'code' => [
                            'coding' => [
                                [
                                    'system' => 'http://sys-ids.kemkes.go.id/kfa',
                                    'code' => $item->KD_BRG_KFA,
                                    'display' => $item->NAMABRG_KFA,
                                ],
                            ],
                        ],
                        'status' => 'active',
                        'manufacturer' => [
                            'reference' => 'Organization/' . $orgId,
                        ],
                        'extension' => [
                            [
                                'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType',
                                'valueCodeableConcept' => [
                                    'coding' => [
                                        [
                                            'system' => 'http://terminology.kemkes.go.id/CodeSystem/medication-type',
                                            'code' => $jenisCode,
                                            'display' => $jenisName,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'status' => 'completed',
                'intent' => 'order',
                'category' => [
                    [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/medicationrequest-category',
                                'code' => 'community',
                                'display' => 'Community',
                            ],
                        ],
                    ],
                ],
                'priority' => 'routine',
                'medicationReference' => [
                    'reference' => 'Medication/' . $item->medicationReference,
                ],
                'subject' => [
                    'reference' => 'Patient/' . $item->ID_PASIEN,
                    'display' => $item->PASIEN,
                ],
                'encounter' => [
                    'reference' => 'Encounter/' . $item->id_satusehat_encounter,
                ],
                'authoredOn' => now()->format('Y-m-d\TH:i:sP'),
                'requester' => [
                    'reference' => 'Practitioner/' . $item->ID_NAKES,
                    'display' => $item->NAKES,
                ],
            ];

            JobsSendMedicationRequest::dispatch($payload, [
                'idTrans' => $idTrans,
                'item' => [
                    'KD_BRG_KFA' => $item->KD_BRG_KFA,
                    'NAMABRG_KFA' => $item->NAMABRG_KFA,
                    'medicationReference' => $item->medicationReference,
                    'ID_PASIEN' => $item->ID_PASIEN,
                    'id_satusehat_encounter' => $item->id_satusehat_encounter,
                    'FROM' => 'MedicationRequestFromDispense',
                ],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'MedicationRequest dari Dispense dikirim ke antrian',
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'createMedicationRequestPayloadfromDispense exception: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    // biar singkat ngecek kirim medication sekalian biar bisa dipanggil di fungsi lain
    function cekSudahKirimMedication($kdbrg)
    {
        $kdbrg_centra = $kdbrg;
        $medResult = app(\App\Http\Controllers\SatusehatKfaController::class)->processMedication($kdbrg_centra);

        return $medResult;
    }
}
