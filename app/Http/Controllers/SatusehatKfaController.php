<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\HomeController;
use DB;

class SatusehatKfaController extends Controller
{
    public function search(Request $request)
    {
        $homeController = app(HomeController::class);
        $homeController->cek_validitas_token();

        $keyword = $request->query('keyword');
        $templateCode = $request->query('template_code');
        $page = $request->query('page', 1);
        $size = $request->query('size', 1000);

        $token = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')
            ->orderByDesc('id')
            ->value('access_token');

        $baseUrl = 'https://api-satusehat-stg.dto.kemkes.go.id/kfa-v2/products/all';

        try {
            // Tentukan parameter request ke API KFA
            $params = [
                'page' => $page,
                'size' => $size,
                'product_type' => 'farmasi',
            ];

            if (!empty($templateCode)) {
                $params['template_code'] = $templateCode;
            } elseif (!empty($keyword)) {
                $params['keyword'] = $keyword;
            }

            $response = Http::withToken($token)
                ->accept('application/json')
                ->withoutVerifying()
                ->get($baseUrl, $params);

            if (!$response->successful()) {
                return response()->json([
                    'error' => true,
                    'status' => $response->status(),
                    'message' => 'Gagal mengambil data dari API KFA',
                ], $response->status());
            }

            $data = $response->json();
            $items = $data['items']['data'] ?? [];

            $keyword = strtolower($keyword ?? '');

            $templates = collect($items)
                ->map(function ($item) {
                    $template = $item['product_template'] ?? null;
                    if (!$template)
                        return null;

                    // Hitung jumlah zat aktif (bisa null kalau field nggak ada)
                    $activeIngredients = $item['active_ingredients'] ?? [];
                    $isCompound = count($activeIngredients) > 1;

                    return [
                        'kfa_code' => $template['kfa_code'] ?? '-',
                        'name' => $template['name'] ?? '-',
                        'display_name' => $template['display_name'] ?? '-',
                        'state' => $template['state'] ?? '-',
                        'updated_at' => $template['updated_at'] ?? null,
                        'is_compound' => $isCompound, // <— tambahin flag ini
                        'active_ingredients_count' => count($activeIngredients), // optional, buat debug
                    ];
                })
                ->filter()
                ->unique(function ($item) {
                    return $item['name'] . '|' . $item['display_name'] . '|' . $item['kfa_code'];
                })
                ->sortByDesc(function ($item) use ($keyword) {
                    similar_text(strtolower($item['display_name']), $keyword, $percent);
                    return $percent;
                })
                ->values()
                ->all();

            return response()->json($templates);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function setMedication(Request $request)
    {
        try {
            // ambil kode dari request POST
            $kode = $request->input('kode_barang');

            if (empty($kode)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kode barang (kode_barang) wajib dikirim.'
                ], 400);
            }

            // query data master obat
            $data = DB::select("
            SELECT 
                KDBRG_CENTRA, 
                NAMABRG, 
                KD_BRG_KFA, 
                NAMABRG_KFA, 
                IS_COMPOUND  
            FROM SIRS_PHCM.dbo.M_TRANS_KFA
            WHERE KDBRG_CENTRA = ?
        ", [$kode]);

            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Data obat dengan kode $kode tidak ditemukan."
                ], 404);
            }

            $obat = $data[0];
            $orgId = '266bf013-b70b-4dc2-b934-40858a5658cc'; // hardcode sementara

            // tentukan tipe racikan
            $jenisCode = ($obat->IS_COMPOUND == 1) ? 'C' : 'NC';
            $jenisName = ($obat->IS_COMPOUND == 1) ? 'Compound' : 'Non-compound';

            // bentuk payload
            $payload = [
                "resourceType" => "Medication",
                "meta" => [
                    "profile" => [
                        "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication"
                    ]
                ],
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/medication/" . $orgId,
                        "use" => "official",
                        "value" => $obat->KDBRG_CENTRA
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://sys-ids.kemkes.go.id/kfa",
                            "code" => $obat->KD_BRG_KFA,
                            "display" => $obat->NAMABRG_KFA
                        ]
                    ],
                    "text" => $obat->NAMABRG
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
            ];

            // ambil token terakhir dari DB
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

            // kirim ke endpoint SATUSEHAT (staging)
            $client = new \GuzzleHttp\Client();

            $response = $client->post(
                'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1/Medication',
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

            // jika sukses dan ada id dari FHIR
            if (isset($responseBody['id'])) {
                DB::table('SIRS_PHCM.dbo.M_TRANS_KFA')
                    ->where('KDBRG_CENTRA', $obat->KDBRG_CENTRA)
                    ->update(['FHIR_ID' => $responseBody['id']]);

                DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert([
                    'LOG_TYPE' => 'Medication',
                    'LOCAL_ID' => $obat->KDBRG_CENTRA,
                    'KFA_CODE' => $obat->KD_BRG_KFA,
                    'NAMA_OBAT' => $obat->NAMABRG_KFA,
                    'FHIR_ID' => $responseBody['id'],
                    'STATUS' => 'success',
                    'HTTP_STATUS' => $httpStatus,
                    'RESPONSE_MESSAGE' => json_encode($responseBody),
                    'CREATED_AT' => now()
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Data Medication berhasil dikirim ke SATUSEHAT.',
                    'uuid' => $responseBody['id']
                ]);
            }

            // kalau respon gak ada id
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert([
                'LOG_TYPE' => 'Medication',
                'LOCAL_ID' => $obat->KDBRG_CENTRA,
                'KFA_CODE' => $obat->KD_BRG_KFA,
                'NAMA_OBAT' => $obat->NAMABRG_KFA,
                'STATUS' => 'failed',
                'HTTP_STATUS' => $httpStatus,
                'RESPONSE_MESSAGE' => json_encode($responseBody),
                'CREATED_AT' => now()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada ID pada response.',
                'response' => $responseBody
            ], $httpStatus);

        } catch (\Exception $e) {
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert([
                'LOG_TYPE' => 'Medication',
                'LOCAL_ID' => $request->input('kode_barang'),
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
