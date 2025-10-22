<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpecimenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('pages.satusehat.specimen.index');
    }

    public function summary(Request $request)
    {
        $startDate  = $request->input('tgl_awal');
        $endDate    = $request->input('tgl_akhir');

        $klinikLab = '0017';
        $idUnit = '001';

        // Set default date range if empty
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $endDate   = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $connection = DB::connection('sqlsrv');

        // $groupsQuery = DB::connection('sqlsrv')
        //     ->table('SIRS_PHCM.dbo.RJ_MGRUP_TIND as a')
        //     ->join('SIRS_PHCM.dbo.RJ_DGRUP_TIND as b', 'a.ID_GRUP_TIND', '=', 'b.ID_GRUP_TIND')
        //     ->select(
        //         'a.ID_GRUP_TIND',
        //         'a.KDKLINIK',
        //         'a.NM_GRUP_TIND',
        //         DB::raw("ISNULL(a.IDUNIT,'$idUnit') as IDUNIT")
        //     )
        //     ->where('a.KDKLINIK', $klinikLab)
        //     ->distinct()
        //     ->orderBy('a.NM_GRUP_TIND');

        // $groups = $groupsQuery->get();

        // $groupIds = $groups->pluck('ID_GRUP_TIND');

        // $totalTindakan = DB::connection('sqlsrv')
        //     ->table('SIRS_PHCM.dbo.RJ_DGRUP_TIND as a')
        //     ->leftJoin('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as b', 'a.KD_TIND', '=', 'b.KD_TIND')
        //     ->leftJoin('SIRS_PHCM.dbo.RJ_MGRUP_TIND as c', 'a.ID_GRUP_TIND', '=', 'c.ID_GRUP_TIND')
        //     ->whereIn('a.ID_GRUP_TIND', $groupIds)
        //     ->select(
        //         'a.KD_TIND as ID_TINDAKAN',
        //         'b.NM_TIND as NAMA_TINDAKAN',
        //     )
        //     ->groupBy(
        //         'a.KD_TIND',
        //         'b.NM_TIND'
        //     )
        //     ->get();
        // $totalTindakan = count($totalTindakan);

        // Pre-fetch active radiology clinics and format for SQL IN clause
        $activeRadKlinik = $connection->table('SIRS_PHCM.dbo.RJ_KLINIK_RADIOLOGI')
            ->where('AKTIF', 'true')
            ->where('IDUNIT', '001')
            ->pluck('KODE_KLINIK')
            ->map(function ($k) {
                return "'$k'";
            })  // wrap each value in quotes
            ->toArray();

        $activeRadKlinikString = implode(',', $activeRadKlinik);

        // Combined query for lab & rad
        $summary = $connection->table('SIRS_PHCM.dbo.v_kunjungan_rj as a')
            ->join('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as b', function ($join) {
                $join->on('b.karcis', '=', 'a.ID_TRANSAKSI')
                    ->on('b.idunit', '=', 'a.ID_UNIT')
                    ->on('b.kbuku', '=', 'a.KBUKU')
                    ->on('b.no_peserta', '=', 'a.NO_PESERTA');
            })
            ->join('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as c', function ($join) {
                $join->on('c.KARCIS_ASAL', '=', 'b.karcis')
                    ->on('c.IDUNIT', '=', 'b.idunit')
                    ->on('c.KBUKU', '=', 'b.kbuku')
                    ->on('c.NO_PESERTA', '=', 'b.no_peserta');
            })
            // LEFT JOIN log table for mapped check
            ->join('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as d', 'a.KBUKU', '=', 'd.kbuku')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING as e', 'c.ARRAY_TINDAKAN', '=', 'e.KODE_TINDAKAN')
            ->whereBetween('c.TANGGAL_ENTRI', [$startDate, $endDate])
            ->where('c.IDUNIT', '001')
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN c.KLINIK_TUJUAN = '0017' THEN c.ID_RIWAYAT_ELAB END) as total_all_lab,
                COUNT(DISTINCT CASE WHEN c.KLINIK_TUJUAN = '0017' AND d.id_satusehat_servicerequest IS NOT NULL AND d.id_satusehat_servicerequest <> '' THEN c.ID_RIWAYAT_ELAB END) as total_mapped_lab,
            ")
            ->first();
        // dd($summary);

        // Calculate unmapped counts
        $total_unmapped_lab = $summary->total_all_lab - $summary->total_mapped_lab;
        $total_unmapped_rad = $summary->total_all_rad - $summary->total_mapped_rad;

        // Return JSON response
        return response()->json([
            'total_all_lab' => $summary->total_all_lab,
            'total_all_rad' => $summary->total_all_rad,
            'total_all_combined' => $summary->total_all_lab + $summary->total_all_rad,
            'total_mapped_lab' => $summary->total_mapped_lab,
            'total_mapped_rad' => $summary->total_mapped_rad,
            'total_mapped_combined' => $summary->total_mapped_lab + $summary->total_mapped_rad,
            'total_unmapped_lab' => $total_unmapped_lab,
            'total_unmapped_rad' => $total_unmapped_rad,
            'total_unmapped_combined' => $total_unmapped_lab + $total_unmapped_rad,
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
