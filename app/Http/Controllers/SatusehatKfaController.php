<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\DB;
use App\Models\GlobalParameter;
use App\Models\SATUSEHAT\SS_Kode_API;
use Illuminate\Support\Facades\Session;

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

            $id_unit = Session::get('id_unit', '001');
            if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
                $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_KFA_STAGING')->select('valStr')->first()->valStr;
                //$organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
            } else {
                $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_KFA')->select('valStr')->first()->valStr;
                //$organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
            }
            $url = 'products/all';
            $baseuri = rtrim($baseurl, '/') . '/' . ltrim($url, '/');
        // $baseUrl = 'https://api-satusehat-stg.dto.kemkes.go.id/kfa-v2/products/all';

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
                ->get($baseuri, $params);

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
        $kode = $request->input('kode_barang');

        if (empty($kode)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode barang wajib dikirim.'
            ], 400);
        }

        $result = $this->processMedication($kode);

        return response()->json($result);
    }


    public function processMedication($kodeBarang)
    {
        try {
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
        ", [$kodeBarang]);

            if (empty($data)) {
                return [
                    'status' => 'error',
                    'message' => "Data obat dengan kode $kodeBarang tidak ditemukan."
                ];
            }

            $obat = $data[0];
            $id_unit = Session::get('id_unit', '001');
            if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
                $orgId = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
            } else {
                $orgId = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
            }

            // tentukan tipe racikan
            $jenisCode = ($obat->IS_COMPOUND == 1) ? 'C' : 'NC';
            $jenisName = ($obat->IS_COMPOUND == 1) ? 'Compound' : 'Non-compound';

            // payload Medication
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

            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                throw new \Exception('Access token tidak tersedia di database.');
            }



            $id_unit = Session::get('id_unit_simrs', '001');
            if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
                $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
                //$organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
            } else {
                $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
                //$organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
            }
            $url = 'Medication';
            $baseuri = rtrim($baseurl, '/') . '/' . ltrim($url, '/');

            $client = new \GuzzleHttp\Client();

            $response = $client->post(
                $baseuri,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode($payload),
                    'verify' => false
                ]
            );

            $body = json_decode($response->getBody(), true);
            $status = $response->getStatusCode();

            // LOGGING
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert([
                'LOG_TYPE' => 'Medication',
                'LOCAL_ID' => $obat->KDBRG_CENTRA,
                'KFA_CODE' => $obat->KD_BRG_KFA,
                'NAMA_OBAT' => $obat->NAMABRG_KFA,
                'FHIR_ID' => $body['id'] ?? null,
                'STATUS' => isset($body['id']) ? 'success' : 'failed',
                'HTTP_STATUS' => $status,
                'RESPONSE_MESSAGE' => json_encode($body),
                'CREATED_AT' => now()
            ]);

            // update master
            if (isset($body['id'])) {
                DB::table('SIRS_PHCM.dbo.M_TRANS_KFA')
                    ->where('KDBRG_CENTRA', $obat->KDBRG_CENTRA)
                    ->update(['FHIR_ID' => $body['id']]);
            }

            return [
                'status' => isset($body['id']) ? 'success' : 'error',
                'message' => isset($body['id']) ? 'OK' : 'Tidak ada ID pada response',
                'data' => $body
            ];

        } catch (\GuzzleHttp\Exception\RequestException $e) {

            $fullError = $e->hasResponse()
                ? $e->getResponse()->getBody()->getContents()
                : $e->getMessage();

            return [
                'status' => 'error',
                'message' => $fullError
            ];

        } catch (\Exception $e) {

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function getAccessToken()
    {
        $tokenData = DB::connection('sqlsrv')->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')->select('issued_at', 'expired_in', 'access_token')->where('idunit', '001')->orderBy('id', 'desc')->first();

        return $tokenData->access_token ?? null;
    }

}
