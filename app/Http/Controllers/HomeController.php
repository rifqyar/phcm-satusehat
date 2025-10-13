<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
                'url' => '#',
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
        $satuSehatMenu = json_decode(json_encode($satuSehatMenu));
        return view('home', compact('satuSehatMenu'));
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
