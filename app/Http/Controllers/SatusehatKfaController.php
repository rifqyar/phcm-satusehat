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



            // Langsung return array data bersih
            return response()->json($templates);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
