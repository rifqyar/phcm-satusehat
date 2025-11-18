<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function sendSatuSehatInternal($id_unit, $idTransaksi, $kdPasienSS, $kdNakesSS, $kdLokasiSS)
    {
        $dataErm = DB::table('E_RM_PHCM.dbo.ERM_RM_IRJA as eri')
            ->select([
                'eri.NOMOR',
                'eri.KODE_DIAGNOSA_UTAMA',
                'eri.DIAG_UTAMA',
                'eri.KODE_DIAGNOSA_SEKUNDER',
                'eri.DIAG_SEKUNDER',
                'eri.KODE_DIAGNOSA_KOMPLIKASI',
                'eri.DIAG_KOMPLIKASI',
                'eri.KODE_DIAGNOSA_PENYEBAB',
                'eri.PENYEBAB',
                'eri.ANAMNESE',
            ])
            ->where('karcis', '94131')
            ->where('aktif', 1)
            ->first();

        $diagnosa = [];
        if ($dataErm) {
            if ($dataErm->KODE_DIAGNOSA_UTAMA) {
                $diagnosa[] = [
                    'kode' => $dataErm->KODE_DIAGNOSA_UTAMA,
                    'keterangan' => $dataErm->DIAG_UTAMA,
                ];
            }

            if ($dataErm->KODE_DIAGNOSA_SEKUNDER) {
                $diagnosa[] = [
                    'kode' => $dataErm->KODE_DIAGNOSA_SEKUNDER,
                    'keterangan' => $dataErm->DIAG_SEKUNDER,
                ];
            }

            if ($dataErm->KODE_DIAGNOSA_KOMPLIKASI) {
                $diagnosa[] = [
                    'kode' => $dataErm->KODE_DIAGNOSA_KOMPLIKASI,
                    'keterangan' => $dataErm->DIAG_KOMPLIKASI,
                ];
            }

            if ($dataErm->KODE_DIAGNOSA_PENYEBAB) {
                $diagnosa[] = [
                    'kode' => $dataErm->KODE_DIAGNOSA_PENYEBAB,
                    'keterangan' => $dataErm->PENYEBAB,
                ];
            }
        }

        dd($diagnosa);
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
