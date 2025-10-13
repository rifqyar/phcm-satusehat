<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class EncounterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('pages.satusehat.encounter.index');
    }

    public function datatable(Request $request)
    {
        $tgl_awal   = $request->input('tgl_awal');
        $tgl_akhir  = $request->input('tgl_akhir');
        $nopeserta  = $request->input('nopeserta');
        $id_unit    = session('id_klinik');
        // Jika parameter belum lengkap, kembalikan data kosong
        if (empty($tgl_awal) || empty($tgl_akhir) || empty($nopeserta)) {
            return DataTables::of([])->make(true);
        }

        // Pastikan format tanggal valid
        if (!$this->checkDateFormat($tgl_awal) || !$this->checkDateFormat($tgl_akhir)) {
            return DataTables::of([])->make(true);
        }

        // Konversi tanggal ke format DB (yyyy-mm-dd)
        $tgl_awal_db  = $this->convertDate($tgl_awal);
        $tgl_akhir_db = $this->convertDate($tgl_akhir);

        $dataKunjungan = DB::select(
            'EXEC rj_sp_bacalistkunjungan ?, ?, ?, ?',
            [$nopeserta, $tgl_awal_db, $tgl_akhir_db, $id_unit]
        );

        return DataTables::of($dataKunjungan)
            ->addIndexColumn()
            ->editColumn('TGL', function ($row) {
                return date('d-m-Y', strtotime($row->TGL));
            })
            ->editColumn('BIAYA_NOTA', function ($row) {
                return number_format($row->BIAYA_NOTA, 0);
            })
            ->editColumn('BIAYA_FARMASI', function ($row) {
                return number_format($row->BIAYA_FARMASI, 0);
            })
            ->editColumn('TOTAL_BIAYA', function ($row) {
                return number_format($row->TOTAL_BIAYA, 0);
            })
            ->make(true);
    }

    private function checkDateFormat($date)
    {
        $day   = substr($date, 0, 2);
        $month = substr($date, 3, 2);
        $year  = substr($date, 6, 4);

        return checkdate((int)$month, (int)$day, (int)$year);
    }

    private function convertDate($date)
    {
        return substr($date, 6, 4) . '-' . substr($date, 3, 2) . '-' . substr($date, 0, 2);
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
