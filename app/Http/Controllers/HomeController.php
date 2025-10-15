<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $satuSehatMenu = [
            [
                'title' => 'Encounter / Kunjungan Pasien',
                'url' => 'satusehat.encounter.index',
            ],
            [
                'title' => 'Diagnosis',
                'url' => '#',
            ],
            [
                'title' => 'Observasi',
                'url' => '#',
            ],
            [
                'title' => 'Tindakan',
                'url' => '#',
            ],
            [
                'title' => 'Resume Medis',
                'url' => '#',
            ],
            [
                'title' => 'Imunisasi',
                'url' => '#',
            ],
            [
                'title' => 'Resep Obat',
                'url' => '#',
            ],
            [
                'title' => 'Tebus Obat',
                'url' => '#',
            ],
            [
                'title' => 'Alergi Intoleran',
                'url' => '#',
            ],
            [
                'title' => 'Radiologi',
                'url' => '#',
            ],
            [
                'title' => 'Permintaan Pemeriksaan (Penunjang Medis)',
                'url' => 'satusehat.service-request.index',
            ],
            [
                'title' => 'Spesimen',
                'url' => '#',
            ],
            [
                'title' => 'Laporan Pemeriksaan',
                'url' => '#',
            ],
            [
                'title' => 'Rencana Perawatan',
                'url' => '#',
            ],
            [
                'title' => 'Catatan Pengobatan',
                'url' => '#',
            ],
            [
                'title' => 'Respon Kuesioner',
                'url' => '#',
            ],
            [
                'title' => 'Data Obat',
                'url' => '#',
            ],
            [
                'title' => 'Episode Perawatan',
                'url' => '#',
            ]
        ];
        $cek = $this->cek_validitas_token();
        // dd($cek);
        $satuSehatMenu = json_decode(json_encode($satuSehatMenu));
        return view('home', compact('satuSehatMenu'));
    }

    function cek_validitas_token()
    {
        // Ambil record terakhir
        $data = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')
            ->select('issued_at', 'expired_in', 'access_token')
            ->orderBy('id', 'desc')
            ->first();

        if (!$data) {
            return 'Tidak ada data token';
        }

        // Waktu sekarang pakai zona waktu Jakarta
        $now = Carbon::now('Asia/Jakarta');
        $expiredAt = Carbon::parse($data->expired_in, 'Asia/Jakarta');

        // Kurangi 1 jam dari waktu expired aktual
        $earlyExpire = $expiredAt->copy()->subHour();

        if ($now->lessThan($earlyExpire)) {
            // Masih valid (dengan buffer 1 jam)
            return [
                'expired_in' => $expiredAt->toDateTimeString(),
                'expired_buffer' => $earlyExpire->toDateTimeString(),
                'now' => $now->toDateTimeString(),
                'status' => 'MASIH VALID (BUFFER 1 JAM)',
            ];
        }


        // ==============================
        // Token expired → minta token baru
        // ==============================

        $clientId = env('SATUSEHAT_CLIENT_ID');
        $clientSecret = env('SATUSEHAT_CLIENT_SECRET');

        $url = 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';

        $response = Http::asForm()
            ->withOptions(['verify' => false]) // ignore ssl
            ->post($url, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);


        if ($response->failed()) {
            return [
                'status' => 'GAGAL REFRESH TOKEN',
                'response' => $response->body(),
            ];
        }

        $json = $response->json();
        $accessToken = $json['access_token'] ?? null;
        $expiresIn = $json['expires_in'] ?? 3600; // detik
        $developerEmail = $json['developer.email'] ?? null;
        $clientIdResp = $json['client_id'] ?? $clientId;

        // Hitung expired_in baru
        $expiredAtNew = $now->copy()->addSeconds($expiresIn);

        DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')
            ->insert([
                'idunit' => '001',
                'issued_at' => $now->toDateTimeString(),
                'expired_in' => $expiredAtNew->toDateTimeString(),
                'access_token' => $accessToken,
                'developer_email' => $developerEmail,
                'client_id' => $clientIdResp,
            ]);

        return [
            'status' => 'REFRESHED',
            'issued_at' => $now->toDateTimeString(),
            'expired_in' => $expiredAtNew->toDateTimeString(),
            'access_token' => $accessToken,
            'developer_email' => $developerEmail,
            'client_id' => $clientIdResp,
        ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
