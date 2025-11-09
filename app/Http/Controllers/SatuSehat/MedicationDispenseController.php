<?php

namespace App\Http\Controllers\SatuSehat;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

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

        if (!$startDate || !$endDate) {
            $endDate = now();
            $startDate = now()->subDays(30);
        }

        $query = DB::table(DB::raw("
        (
            SELECT DISTINCT
                a.KLINIK AS KodeKlinik,
                a.NOURUT AS NomorUrut,
                a.KARCIS AS NomorKarcis,
                CONVERT(varchar(10), a.TGL, 105) AS TanggalKarcis,
                a.KBUKU AS KodeBuku,
                a.NO_PESERTA AS NomorPeserta,
                b.NAMA AS NamaPasien,
                c.NMDEBT AS NamaDebitur,
                b.TGL_LHR AS TanggalLahir,
                a.TGL AS TanggalKunjungan,
                CASE 
                    WHEN k.NO_KUNJUNG IS NULL THEN 'BELUM'
                    ELSE 'SELESAI'
                END AS StatusRekamMedis,
                CASE 
                    WHEN m.NO_KUNJUNG IS NULL THEN 'TUTUP'
                    ELSE 'BUKA'
                END AS StatusPermintaanIsian,
                aa.NOTA,
                ab.ID_TRANS,
                ac.nmDok AS NamaDokter,
                r.id_satusehat_encounter,
                CASE 
                    WHEN log_disp.ID IS NOT NULL THEN '200'
                    WHEN log_req.ID IS NOT NULL THEN '100'
                    ELSE '000'
                END AS STATUS_MAPPING,
                log_disp.CREATED_AT AS WaktuKirimTerakhir
            FROM SIRS_PHCM.dbo.RJ_KARCIS a
            JOIN SIRS_PHCM.dbo.RIRJ_MASTERPX b
                ON a.NO_PESERTA = b.NO_PESERTA
            JOIN SIRS_PHCM.dbo.RIRJ_MDEBITUR c
                ON a.KDEBT = c.KDDEBT
            LEFT JOIN SIRS_PHCM.dbo.RJ_KARCIS_BAYAR aa
                ON a.KARCIS = aa.KARCIS
            LEFT JOIN SIRS_PHCM.dbo.IF_HTRANS ab
                ON aa.NOTA = ab.NOTA
            LEFT JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA r 
                ON ab.NOTA = r.nota
            LEFT JOIN SIRS_PHCM.dbo.DR_MDOKTER ac
                ON a.KDDOK = ac.kdDok
            LEFT JOIN E_RM_PHCM.dbo.ERM_NOMOR_KUNJUNG j
                ON a.KARCIS = j.KARCIS AND a.IDUNIT = j.IDUNIT
            LEFT JOIN E_RM_PHCM.dbo.ERM_RM_IRJA k
                ON j.NO_KUNJUNG = k.NO_KUNJUNG AND k.AKTIF = '1'
            LEFT JOIN E_RM_PHCM.dbo.ERM_PERMINTAAN_ISIAN m
                ON j.NO_KUNJUNG = m.NO_KUNJUNG AND m.AKTIF = 'true'

            -- 🧩 Cek apakah encounter sudah punya MedicationRequest
            LEFT JOIN (
                SELECT 
                    ENCOUNTER_ID,
                    MAX(ID) AS ID,
                    MAX(CREATED_AT) AS CREATED_AT
                FROM SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION
                WHERE LOG_TYPE = 'MedicationRequest'
                    AND STATUS = 'success'
                GROUP BY ENCOUNTER_ID
            ) AS log_req ON log_req.ENCOUNTER_ID = r.id_satusehat_encounter

            -- 🧩 Cek apakah encounter sudah punya MedicationDispense
            LEFT JOIN (
                SELECT 
                    ENCOUNTER_ID,
                    MAX(ID) AS ID,
                    MAX(CREATED_AT) AS CREATED_AT
                FROM SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION
                WHERE LOG_TYPE = 'MedicationDispense'
                    AND STATUS = 'success'
                GROUP BY ENCOUNTER_ID
            ) AS log_disp ON log_disp.ENCOUNTER_ID = r.id_satusehat_encounter

            WHERE
                ISNULL(a.SELESAI, 0) NOT IN ('9','10')
                AND ISNULL(a.STBTL, 0) = 0
                AND ab.ID_TRANS IS NOT NULL
                AND r.id_satusehat_encounter IS NOT NULL
        ) AS src
    "))
            ->whereBetween(DB::raw('CONVERT(date, src.TanggalKunjungan)'), [$startDate, $endDate])
            ->select(
                'src.NomorKarcis',
                'src.NamaPasien',
                'src.NamaDokter',
                DB::raw("CONVERT(varchar(10), src.TanggalKunjungan, 105) AS TanggalKunjungan"),
                'src.ID_TRANS',
                'src.id_satusehat_encounter',
                'src.STATUS_MAPPING',
                DB::raw("CONVERT(varchar(19), src.WaktuKirimTerakhir, 120) AS WaktuKirimTerakhir")
            );

        $allData = $query->get();
        $recordsTotal = $allData->count();

        $dataTable = DataTables::of($query)
            ->order(function ($query) {
                $query->orderBy('src.TanggalKunjungan', 'desc');
            })
            ->make(true);

        $json = $dataTable->getData(true);
        $json['summary'] = [
            'all' => $recordsTotal,
        ];

        return response()->json($json);
    }


    public function getDetailObat(Request $request)
    {
        $idTrans = $request->id; // ID_TRANS dikirim dari tombol lihatObat(id)

        try {
            $data = DB::select("
            SELECT 
                i.ID_TRANS,
                i.NAMABRG AS NAMA_OBAT,
                i.KDBRG_CENTRA,
                m.KD_BRG_KFA,
                m.NAMABRG_KFA
            FROM SIRS_PHCM.dbo.IF_TRANS i
            LEFT JOIN SIRS_PHCM.dbo.M_TRANS_KFA m 
                ON i.KDBRG_CENTRA = m.KDBRG_CENTRA
            WHERE i.ID_TRANS = :idTrans
        ", ['idTrans' => $idTrans]);

            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data obat tidak ditemukan untuk transaksi tersebut.'
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
    public function sendMedicationDispense(Request $request)
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

            // --- ambil data gabungan resep farmasi + dokter + pasien + FHIR log ---
            $data = DB::select("
            SELECT 
                i.ID_TRANS AS ID_RESEP_FARMASI,
                i3.ID_TRANS AS RESEP_DOKTER,
                r4.KARCIS,
                m.FHIR_ID AS medicationReference_reference,
                m.NAMABRG AS medicationReference_display,
                s.ENCOUNTER_ID,
                s.FHIR_MEDICATION_REQUEST_ID,
                r2.idpx,
                r2.nama AS pasien_nama,
                r3.idnakes,
                r3.nama AS nakes_nama
            FROM SIRS_PHCM.dbo.IF_HTRANS i
            JOIN SIRS_PHCM.dbo.IF_TRANS i2 ON i.ID_TRANS = i2.ID_TRANS
            JOIN SIRS_PHCM.dbo.RJ_KARCIS_BAYAR r4 ON i.NOTA = r4.NOTA
            JOIN SIRS_PHCM.dbo.IF_HTRANS_OL i3 ON r4.KARCIS = i3.KARCIS
            JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA r ON i.NOTA = r.nota
            JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN r2 ON r.id_satusehat_px = r2.idpx
            JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES r3 ON r.id_satusehat_dokter = r3.idnakes
            LEFT JOIN SIRS_PHCM.dbo.M_TRANS_KFA m ON i2.KDBRG_CENTRA = m.KDBRG_CENTRA
            LEFT JOIN SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION s 
                ON m.KD_BRG_KFA = s.KFA_CODE 
                AND i3.ID_TRANS = s.LOCAL_ID
            WHERE i.ID_TRANS = ?
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

            // --- loop setiap obat di transaksi farmasi ---
            foreach ($data as $index => $item) {
                $uniqueId = str_replace('/', '-', $item->ID_RESEP_FARMASI);
                $identifierValue = 'DISP-' . $uniqueId;

                // --- handle authorizingPrescription wajib ---
                if (empty($item->FHIR_MEDICATION_REQUEST_ID)) {
                    // skip kalau data gak lengkap
                    $results[] = [
                        'medication' => $item->medicationReference_display ?? '-',
                        'status' => 'skipped',
                        'reason' => 'FHIR_MEDICATION_REQUEST_ID tidak ditemukan',
                        'response' => null
                    ];
                    continue;
                }

                $authorizingPrescription = [
                    [
                        "reference" => "MedicationRequest/" . $item->FHIR_MEDICATION_REQUEST_ID
                    ]
                ];


                // --- handle encounter (optional) ---
                $context = null;
                if (!empty($item->ENCOUNTER_ID)) {
                    $context = [
                        "reference" => "Encounter/" . $item->ENCOUNTER_ID
                    ];
                }

                // --- bentuk payload minimum MedicationDispense ---
                $payload = [
                    "resourceType" => "MedicationDispense",
                    "identifier" => [
                        [
                            "system" => "http://sys-ids.kemkes.go.id/prescription/" . $orgId,
                            "use" => "official",
                            "value" => $identifierValue
                        ]
                    ],
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                "code" => "outpatient",
                                "display" => "Outpatient"
                            ]
                        ]
                    ],
                    "medicationReference" => [
                        "reference" => !empty($item->medicationReference_reference)
                            ? "Medication/" . $item->medicationReference_reference
                            : "Medication/UNKNOWN",
                        "display" => $item->medicationReference_display ?? "-"
                    ],
                    "subject" => [
                        "reference" => "Patient/" . $item->idpx,
                        "display" => $item->pasien_nama
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/" . $item->idnakes,
                                "display" => $item->nakes_nama
                            ]
                        ]
                    ],
                    "authorizingPrescription" => $authorizingPrescription
                ];

                if ($context) {
                    $payload["context"] = $context;
                }

                // --- kirim ke API MedicationDispense ---
                $response = $client->post(
                    'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1/MedicationDispense',
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

                // --- catat log hasil pengiriman ---
                $logData = [
                    'LOG_TYPE' => 'MedicationDispense',
                    'LOCAL_ID' => $item->ID_RESEP_FARMASI,
                    'KFA_CODE' => $item->medicationReference_reference ?? null,
                    'NAMA_OBAT' => $item->medicationReference_display ?? '-',
                    'FHIR_MEDICATION_DISPENSE_ID' => $responseBody['id'] ?? null,
                    'FHIR_MEDICATION_REQUEST_ID' => $item->FHIR_MEDICATION_REQUEST_ID ?? null,
                    'PATIENT_ID' => $item->idpx ?? null,
                    'ENCOUNTER_ID' => $item->ENCOUNTER_ID ?? null,
                    'STATUS' => isset($responseBody['id']) ? 'success' : 'failed',
                    'HTTP_STATUS' => $httpStatus,
                    'RESPONSE_MESSAGE' => json_encode($responseBody),
                    'CREATED_AT' => now()
                ];

                $existing = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
                    ->where('LOCAL_ID', $item->ID_RESEP_FARMASI)
                    ->where('KFA_CODE', $item->medicationReference_reference)
                    ->where('LOG_TYPE', 'MedicationDispense')
                    ->first();

                if ($existing) {
                    DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
                        ->where('ID', $existing->ID)
                        ->update([
                            'FHIR_MEDICATION_DISPENSE_ID' => $logData['FHIR_MEDICATION_DISPENSE_ID'],
                            'FHIR_MEDICATION_REQUEST_ID' => $logData['FHIR_MEDICATION_REQUEST_ID'],
                            'PATIENT_ID' => $logData['PATIENT_ID'],
                            'ENCOUNTER_ID' => $logData['ENCOUNTER_ID'],
                            'STATUS' => $logData['STATUS'],
                            'HTTP_STATUS' => $logData['HTTP_STATUS'],
                            'RESPONSE_MESSAGE' => $logData['RESPONSE_MESSAGE'],
                            'UPDATED_AT' => now()
                        ]);
                } else {
                    DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert($logData);
                }

                $results[] = [
                    'medication' => $item->medicationReference_display,
                    'status' => $httpStatus,
                    'response' => $responseBody
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Semua MedicationDispense telah diproses.',
                'results' => $results
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        } catch (\Exception $e) {
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert([
                'LOG_TYPE' => 'MedicationDispense',
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