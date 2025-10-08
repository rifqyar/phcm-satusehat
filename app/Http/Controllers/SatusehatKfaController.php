<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SatusehatKfaController extends Controller
{
    public function search(Request $request)
    {
        $keyword = $request->query('keyword', '');
        $page = $request->query('page', 1);
        $size = $request->query('size', 20);

        // Hardcode token dev dulu (replace later dari .env)
        $token = env('SATUSEHAT_TOKEN', 'RpepjKpuMDXoB2wmMCatGTz7OQDi');

        $baseUrl = 'https://api-satusehat-stg.dto.kemkes.go.id/kfa-v2/products/all';

        try {
            $response = Http::withToken($token)
                ->accept('application/json')
                ->get($baseUrl, [
                    'page' => $page,
                    'size' => $size,
                    'product_type' => 'farmasi',
                    'keyword' => $keyword,
                ]);

            // forward response body & status code langsung
            return response($response->body(), $response->status())
                ->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
