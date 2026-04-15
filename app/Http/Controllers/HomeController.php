<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $idUnit = Session::get('id_unit', '001');
        return view('home');
    }

    function cek_validitas_token()
    {
        // Ambil record terakhir
        $data = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')
            ->select('issued_at', 'expired_in', 'access_token', 'idunit')
            ->where('idunit', '001')
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
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
            //$organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            // $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $baseurl = 'https://api-satusehat.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
            //$organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }
        // $url = 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';

        $response = Http::asForm()
            ->withOptions(['verify' => false]) // ignore ssl
            ->post($baseurl, [
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

    public function getChartDataRJRI()
    {
        // Menggunakan RAW Query Laravel untuk efisiensi Dashboard
        // Asumsi lu punya tabel Master Kunjungan (misal: dbo.Trx_Kunjungan)
        // yang nyimpen ID_SATUSEHAT_ENCOUNTER dan Jenis Layanan (RJ/RI)

        $query = "
            SELECT
                CAST(L.created_at AS DATE) as tanggal,
                K.JENIS_LAYANAN, -- (Isinya 'RAWAT_INAP' atau 'RAWAT_JALAN')
                COUNT(L.id) as total_pengiriman
            FROM SATUSEHAT.dbo.SATUSEHAT_LOG_TRANSACTION L

            -- Ekstrak 'Encounter/abc-123' dari JSON request log, potong kata 'Encounter/'-nya
            -- Lalu JOIN dengan tabel SIMRS lu yang nyimpen data Kunjungan
            INNER JOIN DBO.NAMA_TABEL_KUNJUNGAN_LU K
                ON K.ID_SATUSEHAT_ENCOUNTER = REPLACE(JSON_VALUE(L.request, '$.encounter.reference'), 'Encounter/', '')

            -- Filter 7 hari terakhir biar enteng
            WHERE L.created_at >= DATEADD(day, -7, GETDATE())
            AND L.request IS NOT NULL -- Pastikan JSON tidak kosong

            GROUP BY CAST(L.created_at AS DATE), K.JENIS_LAYANAN
            ORDER BY tanggal ASC
        ";

        $data = DB::select($query);

        // Format data biar gampang dimakan sama Chart.js di frontend
        $formattedForChart = [
            'tanggal' => [],
            'rawat_jalan' => [],
            'rawat_inap' => []
        ];

        // (Opsional) Mapping logic di Laravel sebelum dilempar ke frontend...
        // ...

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
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
