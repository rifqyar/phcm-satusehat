<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use DB;

class SatusehatKfaController extends Controller
{
    public function search(Request $request)
    {
        $keyword = $request->query('keyword', '');
        $page = $request->query('page', 1);
        $size = $request->query('size', 20);

        // Hardcode token dev dulu (replace later dari .env)
        $token = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')
            ->orderByDesc('id')
            ->value('access_token');


        $baseUrl = 'https://api-satusehat-stg.dto.kemkes.go.id/kfa-v2/products/all';

        // Build query string
        $queryParams = http_build_query([
            'page' => $page,
            'size' => $size,
            'product_type' => 'farmasi',
            'keyword' => $keyword,
        ]);

        $url = $baseUrl . '?' . $queryParams;

        try {
            $ch = curl_init();

            $headers = [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
            ];

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false, 
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);

            $body = curl_exec($ch);
            $curlErr = curl_error($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($body === false && $curlErr) {
                // cURL level error
                return response()->json([
                    'error' => true,
                    'message' => 'cURL error: ' . $curlErr,
                ], 500);
            }

            // Forward response body & status code langsung
            return response($body, $status)
                ->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
