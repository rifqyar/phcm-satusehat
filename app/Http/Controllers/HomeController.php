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
