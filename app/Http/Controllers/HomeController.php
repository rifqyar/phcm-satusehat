<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
                'url' => '#',
                'icon' => 'mdi mdi-pill',
            ],
            [
                'title' => 'Diagnosis',
                'url' => '#',
                'icon' => 'mdi mdi-radiology',
            ],
            [
                'title' => 'Observasi',
                'url' => '#',
                'icon' => 'mdi mdi-flask',
            ],
            [
                'title' => 'Tindakan',
                'url' => '#',
                'icon' => 'mdi mdi-cube-send',
            ],
            [
                'title' => 'Resume Medis',
                'url' => '#',
                'icon' => 'mdi mdi-cube-send',
            ],
            [
                'title' => 'Imunisasi',
                'url' => '#',
                'icon' => 'mdi mdi-cube-send',
            ],
            [
                'title' => 'Resep Obat',
                'url' => '#',
                'icon' => 'mdi mdi-cube-send',
            ],
            [
                'title' => 'Tebus Obat',
                'url' => '#',
                'icon' => 'mdi mdi-cube-send',
            ],
            [
                'title' => 'Alergi Intoleran',
                'url' => '#',
                'icon' => 'mdi mdi-cube-send',
            ],
            [
                'title' => 'Radiologi',
                'url' => '#',
                'icon' => 'mdi mdi-cube-send',
            ],
            [
                'title' => 'Permintaan Pemeriksaan (Penunjang Medis)',
                'url' => '#',
                'icon' => 'mdi mdi-cube-send',
            ],
            [
                'title' => 'Spesimen',
                'url' => '#',
                'icon' => 'mdi mdi-cube-send',
            ],
            [
                'title' => 'Laporan Pemeriksaan',
                'url' => '#',
                'icon' => 'mdi mdi-cube-send',
            ],
            [
                'title' => 'Rencana Perawatan',
                'url' => '#',
                'icon' => 'mdi mdi-cube-send',
            ],
            [
                'title' => 'Catatan Pengobatan',
                'url' => '#',
                'icon' => 'mdi mdi-chart-bar',
            ],
            [
                'title' => 'Respon Kuesioner',
                'url' => '#',
                'icon' => 'mdi mdi-chart-bar',
            ],
            [
                'title' => 'Data Obat',
                'url' => '#',
                'icon' => 'mdi mdi-chart-bar',
            ],
            [
                'title' => 'Episode Perawatan',
                'url' => '#',
                'icon' => 'mdi mdi-chart-bar',
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
