<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\DataTables;

class MasterSpecimenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $successMessage = session('success');
        $errorMessage = session('error');

        $klinikLab = '0017';
        $idUnit = Session::get('id_unit_simrs', '001');

        $groupsQuery = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_MGRUP_TIND as a')
            ->join('SIRS_PHCM.dbo.RJ_DGRUP_TIND as b', 'a.ID_GRUP_TIND', '=', 'b.ID_GRUP_TIND')
            ->select(
                'a.ID_GRUP_TIND',
                'a.KDKLINIK',
                'a.NM_GRUP_TIND',
                DB::raw("ISNULL(a.IDUNIT,'$idUnit') as IDUNIT")
            )
            ->where('a.KDKLINIK', $klinikLab)
            ->distinct()
            ->orderBy('a.NM_GRUP_TIND');

        $groups = $groupsQuery->get();

        $groupIds = $groups->pluck('ID_GRUP_TIND');

        $totalTindakan = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_DGRUP_TIND as a')
            ->leftJoin('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as b', 'a.KD_TIND', '=', 'b.KD_TIND')
            ->leftJoin('SIRS_PHCM.dbo.RJ_MGRUP_TIND as c', 'a.ID_GRUP_TIND', '=', 'c.ID_GRUP_TIND')
            ->whereIn('a.ID_GRUP_TIND', $groupIds)
            ->select(
                'a.KD_TIND as ID_TINDAKAN',
                'b.NM_TIND as NAMA_TINDAKAN',
            )
            ->groupBy(
                'a.KD_TIND',
                'b.NM_TIND'
            )
            ->get();
        $totalTindakan = count($totalTindakan);

        $totalMapping = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING')
            ->distinct('KODE_TINDAKAN')
            ->count('KODE_TINDAKAN');

        $totalUnmapped = $totalTindakan - $totalMapping;

        return view('pages.specimen.master_specimen', compact('successMessage', 'errorMessage', 'totalTindakan', 'totalMapping', 'totalUnmapped'));
    }

    public function datatable(Request $request)
    {
        $klinikLab = '0017';
        $idUnit = Session::get('id_unit_simrs', '001');

        $groupsQuery = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_MGRUP_TIND as a')
            ->join('SIRS_PHCM.dbo.RJ_DGRUP_TIND as b', 'a.ID_GRUP_TIND', '=', 'b.ID_GRUP_TIND')
            ->select(
                'a.ID_GRUP_TIND',
                'a.KDKLINIK',
                'a.NM_GRUP_TIND',
                DB::raw("ISNULL(a.IDUNIT,'$idUnit') as IDUNIT")
            )
            ->where('a.KDKLINIK', $klinikLab)
            ->distinct()
            ->orderBy('a.NM_GRUP_TIND');

        $groups = $groupsQuery->get();

        $groupIds = $groups->pluck('ID_GRUP_TIND');
        // dd($groupIds);

        $tindakanQuery = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_DGRUP_TIND as a')
            ->leftJoin('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as b', 'a.KD_TIND', '=', 'b.KD_TIND')
            ->leftJoin('SIRS_PHCM.dbo.RJ_MGRUP_TIND as c', 'a.ID_GRUP_TIND', '=', 'c.ID_GRUP_TIND')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING as ss', 'a.KD_TIND', '=', 'ss.KODE_TINDAKAN')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SPECIMEN as sp', 'ss.KODE_SPECIMEN', '=', 'sp.CODE')
            ->select(
                'a.ID_GRUP_TIND as ID_GRUP',
                'c.NM_GRUP_TIND as NAMA_GRUP',
                'a.KDKLINIK',
                'a.KD_TIND as ID_TINDAKAN',
                DB::raw('ISNULL(a.URUTAN, 99) as URUTAN'),
                'b.NM_TIND as NAMA_TINDAKAN',
                DB::raw("(
                    SELECT CONCAT('[', STRING_AGG(
                        CONCAT('{\"code\":\"', sp.code, '\",\"display\":\"', sp.display, '\"}')
                    , ','), ']')
                    FROM SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING ss2
                    JOIN SATUSEHAT.dbo.SATUSEHAT_M_SPECIMEN sp ON ss2.KODE_SPECIMEN = sp.CODE
                    WHERE ss2.KODE_TINDAKAN = a.KD_TIND
                ) as SPECIMEN")
            )
            ->whereIn('a.ID_GRUP_TIND', $groupIds)
            ->groupBy(
                'a.ID_GRUP_TIND',
                'c.NM_GRUP_TIND',
                'a.KDKLINIK',
                'a.KD_TIND',
                'a.URUTAN',
                'b.NM_TIND'
            )
            ->orderByRaw('ISNULL(a.URUTAN, 99)');
        // dd($tindakanQuery->toSql());

        // search filter
        if ($request->filled('cari')) {
            $search = $request->cari;
            if ($search == 'mapped') {
                $tindakanQuery->whereNotNull('ss.KODE_TINDAKAN');
            } else if ($search == 'unmapped') {
                $tindakanQuery->whereNull('ss.KODE_TINDAKAN');
            } else if ($search == 'all') {
                $tindakanQuery = $tindakanQuery;
            }
        }

        return DataTables::of($tindakanQuery->get())
            ->addIndexColumn()
            ->addColumn('status_mapping', function ($row) {
                if (count(json_decode($row->SPECIMEN)) > 0) {
                    return '<span class="badge badge-pill badge-success p-2 w-100"><i class="fas fa-link mr-2"></i>Sudah Mapping</span>';
                } else {
                    return '<span class="badge badge-pill badge-secondary p-2 w-100"><i class="fas fa-unlink mr-2"></i>Belum Mapping</span>';
                }
            })
            ->addColumn('action', function ($row) {
                if (count(json_decode($row->SPECIMEN)) > 0) {
                    $btn = "<a href='" . route('master_specimen.edit', $row->ID_TINDAKAN) . "'
                        class='badge badge-pill badge-warning p-2 w-100'>Edit Specimen</a>";
                } else {
                    $btn = "<a href='" . route('master_specimen.create') . "'
                        class='badge badge-pill badge-primary p-2 w-100'>Tambah Specimen</a>";
                }
                return $btn;
            })
            ->addColumn('SPECIMEN', function ($row) {
                $specimens = json_decode($row->SPECIMEN);
                if (is_array($specimens) && count($specimens) > 0) {
                    $labels = array_map(function ($specimen) {
                        return "<span class='badge badge-info badge-pill p-2 m-1'>{$specimen->display}</span><br>";
                    }, $specimens);
                    return implode(' ', $labels);
                } else {
                    return '<span class="text-muted">-</span>';
                }
            })
            ->rawColumns(['action', 'status_mapping', 'SPECIMEN'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $klinikLab = '0017';
        $idUnit = Session::get('id_unit_simrs', '001');

        // Step 1: Get all lab groups
        $groupsQuery = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_MGRUP_TIND as a')
            ->join('SIRS_PHCM.dbo.RJ_DGRUP_TIND as b', 'a.ID_GRUP_TIND', '=', 'b.ID_GRUP_TIND')
            ->select(
                'a.ID_GRUP_TIND',
                'a.KDKLINIK',
                'a.NM_GRUP_TIND',
                DB::raw("ISNULL(a.IDUNIT,'$idUnit') as IDUNIT")
            )
            ->where('a.KDKLINIK', $klinikLab)
            ->distinct()
            ->orderBy('a.NM_GRUP_TIND');

        $groups = $groupsQuery->get();

        // Step 2: For each group, get the tindakan details
        $groupIds = $groups->pluck('ID_GRUP_TIND');

        $tindakanQuery = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_DGRUP_TIND as a')
            ->leftJoin('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as b', 'a.KD_TIND', '=', 'b.KD_TIND')
            ->leftJoin('SIRS_PHCM.dbo.RJ_MGRUP_TIND as c', 'a.ID_GRUP_TIND', '=', 'c.ID_GRUP_TIND')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING as ss', 'a.KD_TIND', '=', 'ss.KODE_TINDAKAN')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SPECIMEN as sp', 'ss.KODE_SPECIMEN', '=', 'sp.CODE')
            ->select(
                'a.ID_GRUP_TIND as ID_GRUP',
                'c.NM_GRUP_TIND as NAMA_GRUP',
                'a.KDKLINIK',
                'a.KD_TIND as ID_TINDAKAN',
                DB::raw('ISNULL(a.URUTAN, 99) as URUTAN'),
                'b.NM_TIND as NAMA_TINDAKAN',
                DB::raw("(
                        SELECT CONCAT('[', STRING_AGG(
                            CONCAT('{\"code\":\"', sp.code, '\",\"display\":\"', sp.display, '\"}')
                        , ','), ']')
                        FROM SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING ss2
                        JOIN SATUSEHAT.dbo.SATUSEHAT_M_SPECIMEN sp ON ss2.KODE_SPECIMEN = sp.CODE
                        WHERE ss2.KODE_TINDAKAN = a.KD_TIND
                    ) as SPECIMEN")
            )
            ->whereIn('a.ID_GRUP_TIND', $groupIds)
            ->whereNull('ss.KODE_TINDAKAN')
            ->groupBy(
                'a.ID_GRUP_TIND',
                'c.NM_GRUP_TIND',
                'a.KDKLINIK',
                'a.KD_TIND',
                'a.URUTAN',
                'b.NM_TIND'
            )
            ->orderByRaw('ISNULL(a.URUTAN, 99)');

        $tindakanData = $tindakanQuery->get();

        $specimens = DB::connection('dbsatusehat')
            ->table('SATUSEHAT.dbo.SATUSEHAT_M_SPECIMEN')
            ->select('code', 'display', 'codesystem')
            ->orderBy('display')
            ->get();

        return view('pages.specimen.create', compact('tindakanData', 'groups', 'specimens'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tindakan' => 'required|string|max:80',
            'specimen' => 'required|array',
            'specimen.*' => 'string|max:250',
        ], [
            'tindakan.required' => 'Kode tindakan wajib diisi.',
            'specimen.required' => 'Minimal pilih satu specimen.',
        ]);

        DB::connection('sqlsrv')->transaction(function () use ($validated) {
            $dataToInsert = collect($validated['specimen'])->map(function ($kodeSpecimen) use ($validated) {
                return [
                    'KODE_TINDAKAN' => $validated['tindakan'],
                    'KODE_SPECIMEN' => $kodeSpecimen,
                    'ENV' => 'Dev'
                ];
            })->toArray();

            DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING')
                ->insert($dataToInsert);
        });

        // ✅ 3. Redirect ke halaman index dengan pesan sukses
        return redirect()
            ->route('master_specimen.index')
            ->with('success', 'Mapping specimen berhasil ditambahkan.');
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
        $tindakan = DB::connection('dbsirs')
            ->table('RIRJ_MTINDAKAN')
            ->where('KD_TIND', $id)
            ->first();

        if (!$tindakan) {
            return response()->json(['message' => 'Data tindakan tidak ditemukan.'], 404);
        }

        $specimens = DB::connection('dbsatusehat')
            ->table('SATUSEHAT.dbo.SATUSEHAT_M_SPECIMEN')
            ->select('code', 'display', 'codesystem')
            ->orderBy('display')
            ->get();

        $mappings = DB::connection('dbsatusehat')
            ->table('SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING as sm')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SPECIMEN as sp', 'sm.KODE_SPECIMEN', '=', 'sp.CODE')
            ->select(
                'sm.id',
                'sm.KODE_TINDAKAN',
                'sm.KODE_SPECIMEN',
                'sp.display as SPECIMEN_DISPLAY',
                'sp.code',
                'sp.codesystem',
            )
            ->where('sm.KODE_TINDAKAN', $id)
            ->get();

        return view('pages.specimen.edit', compact('tindakan', 'specimens', 'mappings'));
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
        $validated = $request->validate([
            'specimen' => 'array',
            'specimen.*' => 'string'
        ]);

        DB::connection('dbsirs')->transaction(function () use ($id, $validated) {
            DB::connection('dbsirs')
                ->table('SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING')
                ->where('KODE_TINDAKAN', $id)
                ->delete();

            if (!empty($validated['specimen'])) {
                $dataToInsert = collect($validated['specimen'])->map(function ($specimenCode) use ($id) {
                    return [
                        'KODE_TINDAKAN' => $id,
                        'KODE_SPECIMEN' => $specimenCode,
                        'ENV' => 'Dev'
                    ];
                })->toArray();

                DB::connection('dbsirs')
                    ->table('SATUSEHAT.dbo.SATUSEHAT_SPECIMEN_MAPPING')
                    ->insert($dataToInsert);
            }
        });

        return redirect()
            ->route('master_specimen.index')
            ->with('success', 'Mapping specimen berhasil diperbarui.');
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
