<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;


class MedicationRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->view('pages.satusehat.medicationrequest.index');
    }

    public function datatable(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$startDate || !$endDate) {
            $endDate = now();
            $startDate = now()->subDays(30);
        }

        $query = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as A')
            ->join('SIRS_PHCM.dbo.IF_HTRANS as B', DB::raw('CONVERT(BIGINT, A.nota)'), '=', 'B.NOTA')
            ->join('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES as N', 'A.id_satusehat_dokter', '=', 'N.idnakes')
            ->join('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN as P', 'A.id_satusehat_px', '=', 'P.idpx')
            ->whereBetween('B.TGL', [$startDate, $endDate])
            ->select(
                'A.id',
                'A.karcis',
                'A.nota',
                'A.idunit',
                'A.tgl',
                'A.id_satusehat_encounter',
                'A.kbuku',
                'A.no_peserta',
                'A.id_satusehat_px',
                'A.kddok',
                'A.id_satusehat_dokter',
                'A.kdklinik',
                'A.id_satusehat_klinik_location',
                'A.sinkron_date',
                'A.jam_datang',
                'A.jam_progress',
                'A.jam_selesai',
                'B.ID_TRANS',
                DB::raw('N.nama as DOKTER'),
                DB::raw('P.nama as PASIEN')
            );
        // ⛔️ HAPUS ->orderByDesc('A.id')

        return DataTables::of($query)
            ->order(function ($query) {
                $query->orderBy('A.id', 'desc'); // ✅ aman di sini
            })
            ->make(true);
    }

public function getDetailObat(Request $request)
    {
        $idTrans = $request->id; // ID_TRANS dikirim dari tombol lihatObat(id)

        try {
            $data = DB::select("
                SELECT
                    T.ID_TRANS,
                    T.[NO],
                    T.NAMABRG AS NAMA_OBAT,
                    T.SIGNA2 AS SIGNA,
                    T.KETQTY AS KET,
                    T.JUMLAH,
                    H.TGL AS TGL_ENTRY,
                    T.ID_TRANS AS IDTRANS,
                    K.KD_BRG_KFA,
                    K.NAMABRG_KFA,
                    T.KDBRG_CENTRA
                FROM SIRS_PHCM..IF_HTRANS H
                JOIN SIRS_PHCM..IF_TRANS T
                    ON H.ID_TRANS = T.ID_TRANS
                LEFT JOIN SIRS_PHCM.dbo.M_TRANS_KFA K
                    ON T.KDBRG_CENTRA = K.KDBRG_CENTRA
                WHERE ISNUMERIC(NOTA) = 1
                  AND H.ID_TRANS = :idTrans
                  AND H.ACTIVE = 1
                  AND H.IDUNIT = 001
            ", ['idTrans' => $idTrans]);

            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data obat tidak ditemukan.'
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

    private function checkDateFormat($date)
    {
        try {
            if ($date instanceof \Carbon\Carbon) {
                return true;
            }
            \Carbon\Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
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
