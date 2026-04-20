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

    public function getDashboardChart()
    {
        $id_unit = Session::get('id_unit', '001');

        // 1. QUERY TREND PENGIRIMAN (Sukses vs Gagal)
        $queryLogTrend = "
            SELECT
                CAST(created_at AS DATE) as tanggal,
                SUM(CASE WHEN flag_success = 1 THEN 1 ELSE 0 END) as total_sukses,
                SUM(CASE WHEN flag_success = 0 THEN 1 ELSE 0 END) as total_gagal
            FROM SATUSEHAT.dbo.SATUSEHAT_LOG_TRANSACTION
            WHERE created_at >= DATEADD(day, -7, GETDATE())
            GROUP BY CAST(created_at AS DATE)
        ";
        $logTrend = DB::select($queryLogTrend);

        // 2. QUERY KUNJUNGAN PASIEN
        // Kita panggil CTE function lu buat ngitung Kunjungan per Tanggal
        $queryKunjungan = "
            WITH MasterKunjungan AS (
                SELECT CAST(TANGGAL AS DATE) as tanggal, ID_SATUSEHAT_ENCOUNTER
                FROM dbo.fn_getDataKunjungan('$id_unit', 'RAWAT_JALAN')
                UNION ALL
                SELECT CAST(TANGGAL AS DATE) as tanggal, ID_SATUSEHAT_ENCOUNTER
                FROM dbo.fn_getDataKunjungan('$id_unit', 'RAWAT_INAP')
            )
            SELECT tanggal, COUNT(ID_SATUSEHAT_ENCOUNTER) as total_kunjungan
            FROM MasterKunjungan
            WHERE tanggal >= DATEADD(day, -7, GETDATE())
            GROUP BY tanggal
        ";
        $kunjunganTrend = DB::select($queryKunjungan);

        // 3. QUERY ENDPOINT (19 Endpoint)
        // Pake trik SUBSTRING & CHARINDEX buat motong "Observation/123" jadi "Observation" aja
        $queryEndpoint = "
            SELECT
                CASE
                    WHEN CHARINDEX('/', service) > 0 THEN SUBSTRING(service, 1, CHARINDEX('/', service) - 1)
                    ELSE service
                END as endpoint_name,
                COUNT(id) as total
            FROM SATUSEHAT.dbo.SATUSEHAT_LOG_TRANSACTION
            WHERE created_at >= DATEADD(day, -7, GETDATE())
            GROUP BY
                CASE
                    WHEN CHARINDEX('/', service) > 0 THEN SUBSTRING(service, 1, CHARINDEX('/', service) - 1)
                    ELSE service
                END
            ORDER BY total DESC
        ";
        $endpointData = DB::select($queryEndpoint);

        // 4. QUERY RJ VS RI (Yang udah kita bahas sebelumnya pakai CTE)
        $queryRJRI = "
            WITH MasterKunjungan AS (
                SELECT ID_SATUSEHAT_ENCOUNTER, 'RAWAT_JALAN' AS JENIS_LAYANAN FROM dbo.fn_getDataKunjungan('$id_unit', 'RAWAT_JALAN')
                UNION ALL
                SELECT ID_SATUSEHAT_ENCOUNTER, 'RAWAT_INAP' AS JENIS_LAYANAN FROM dbo.fn_getDataKunjungan('$id_unit', 'RAWAT_INAP')
            )
            SELECT
                CAST(L.created_at AS DATE) as tanggal,
                K.JENIS_LAYANAN,
                COUNT(L.id) as total_pengiriman
            FROM SATUSEHAT.dbo.SATUSEHAT_LOG_TRANSACTION L
            INNER JOIN MasterKunjungan K
                ON K.ID_SATUSEHAT_ENCOUNTER = SUBSTRING(L.request, CHARINDEX('Encounter/', L.request) + 10, 36)
            WHERE L.created_at >= DATEADD(day, -7, GETDATE()) AND L.flag_success = 1 AND L.request LIKE '%Encounter/%'
            GROUP BY CAST(L.created_at AS DATE), K.JENIS_LAYANAN
        ";
        $rjriData = DB::select($queryRJRI);

        // --- MAPPING DATA KE FORMAT CHART.JS ---

        // Bikin array 7 hari terakhir biar sumbu X chart-nya rapi & gak bolong
        $dates = [];
        $chartUtama = ['labels' => [], 'kunjungan' => [], 'sukses' => [], 'gagal' => []];
        $chartRjRi  = ['labels' => [], 'rj' => [], 'ri' => [], 'total_rj' => 0, 'total_ri' => 0];

        for ($i = 6; $i >= 0; $i--) {
            $dateStr = date('Y-m-d', strtotime("-$i days"));
            $dateLabel = date('d M', strtotime("-$i days"));

            $chartUtama['labels'][] = $dateLabel;
            $chartRjRi['labels'][] = $dateLabel;

            // Cari data log trend
            $logMatch = collect($logTrend)->firstWhere('tanggal', $dateStr);
            $chartUtama['sukses'][] = $logMatch ? $logMatch->total_sukses : 0;
            $chartUtama['gagal'][] = $logMatch ? $logMatch->total_gagal : 0;

            // Cari data kunjungan
            $kunjMatch = collect($kunjunganTrend)->firstWhere('tanggal', $dateStr);
            $chartUtama['kunjungan'][] = $kunjMatch ? $kunjMatch->total_kunjungan : 0;

            // Cari data RJ RI per tanggal
            $rjMatch = collect($rjriData)->where('tanggal', $dateStr)->where('JENIS_LAYANAN', 'RAWAT_JALAN')->first();
            $riMatch = collect($rjriData)->where('tanggal', $dateStr)->where('JENIS_LAYANAN', 'RAWAT_INAP')->first();

            $valRj = $rjMatch ? $rjMatch->total_pengiriman : 0;
            $valRi = $riMatch ? $riMatch->total_pengiriman : 0;

            $chartRjRi['rj'][] = $valRj;
            $chartRjRi['ri'][] = $valRi;
            $chartRjRi['total_rj'] += $valRj;
            $chartRjRi['total_ri'] += $valRi;
        }

        // Format Data Endpoint
        $chartEndpoint = [
            'labels' => collect($endpointData)->pluck('endpoint_name'),
            'data' => collect($endpointData)->pluck('total')
        ];

        return response()->json([
            'utama' => $chartUtama,
            'endpoint' => $chartEndpoint,
            'rj_ri' => $chartRjRi
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
