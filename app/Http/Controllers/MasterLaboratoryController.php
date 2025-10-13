<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterLaboratoryController extends Controller
{
    public function index(Request $request)
    {
        $klinikLab = '0017';
        $idUnit = '001';

        $groupsQuery = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_MGRUP_TIND as a')
            ->join('SIRS_PHCM.dbo.RJ_DGRUP_TIND as b', 'a.ID_GRUP_TIND', '=', 'b.ID_GRUP_TIND')
            ->select(
                'a.ID_GRUP_TIND',
                'a.KDKLINIK',
                'a.NM_GRUP_TIND'
            )
            ->where('a.KDKLINIK', $klinikLab)
            ->distinct()
            ->orderBy('a.NM_GRUP_TIND');

        $groups = $groupsQuery->get();

        $groupIds = $groups->pluck('ID_GRUP_TIND');

        $query = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_DGRUP_TIND as a')
            ->leftJoin('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as b', 'a.KD_TIND', '=', 'b.KD_TIND')
            ->leftJoin('SIRS_PHCM.dbo.RJ_MGRUP_TIND as c', 'a.ID_GRUP_TIND', '=', 'c.ID_GRUP_TIND')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as ss', 'a.KD_TIND', '=', 'ss.ID')
            ->select(
                'a.ID_GRUP_TIND as ID_GRUP',
                'c.NM_GRUP_TIND as NAMA_GRUP',
                'a.KD_TIND as ID_TINDAKAN',
                'b.NM_TIND as NAMA_TINDAKAN',
                'ss.code as SATUSEHAT_CODE',
                'ss.codesystem as SATUSEHAT_SYSTEM',
                'ss.display as SATUSEHAT_DISPLAY',
                'ss.CATEGORY'
            )
            ->whereIn('a.ID_GRUP_TIND', $groupIds)
            ->groupBy(
                'a.ID_GRUP_TIND',
                'c.NM_GRUP_TIND',
                'a.KD_TIND',
                'b.NM_TIND',
                'ss.code',
                'ss.codesystem',
                'ss.display',
                'ss.CATEGORY'
            )
            ->orderBy('a.ID_GRUP_TIND', 'asc')
            ->distinct();

        // clone the base query for later reuse
        $queryCount = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_DGRUP_TIND as a')
            ->leftJoin('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as b', 'a.KD_TIND', '=', 'b.KD_TIND')
            ->leftJoin('SIRS_PHCM.dbo.RJ_MGRUP_TIND as c', 'a.ID_GRUP_TIND', '=', 'c.ID_GRUP_TIND')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as ss', 'a.KD_TIND', '=', 'ss.ID')
            ->select(
                'a.ID_GRUP_TIND as ID_GRUP',
                'b.NM_TIND as NAMA_TINDAKAN',
            )
            ->whereIn('a.ID_GRUP_TIND', $groupIds)
            ->groupBy(
                'a.ID_GRUP_TIND',
                'b.NM_TIND',
            )
            ->get();
        $total_all = count($queryCount);

        $queryMapped = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_DGRUP_TIND as a')
            ->leftJoin('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as b', 'a.KD_TIND', '=', 'b.KD_TIND')
            ->leftJoin('SIRS_PHCM.dbo.RJ_MGRUP_TIND as c', 'a.ID_GRUP_TIND', '=', 'c.ID_GRUP_TIND')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as ss', 'a.KD_TIND', '=', 'ss.ID')
            ->select(
                'a.ID_GRUP_TIND as ID_GRUP',
                'b.NM_TIND as NAMA_TINDAKAN',
                'ss.code as SATUSEHAT_CODE',
            )
            ->whereIn('a.ID_GRUP_TIND', $groupIds)
            ->whereNotNull('ss.code')
            ->where('ss.code', '<>', '')
            ->groupBy(
                'a.ID_GRUP_TIND',
                'b.NM_TIND',
                'ss.code',
            )
            ->get();
        $total_mapped = count($queryMapped);
        $total_unmapped = ($total_all - $total_mapped);

        // search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('b.NM_TIND', 'like', "%{$search}%")
                    ->orWhere('c.NM_GRUP_TIND', 'like', "%{$search}%")
                    ->orWhere('ss.code', 'like', "%{$search}%")
                    ->orWhere('ss.display', 'like', "%{$search}%");
            });
        }

        // Mapped filter: mapped/unmapped
        $mapped_filter = $request->get('mapped_filter', 'all');
        if ($request->mapped_filter === 'mapped') {
            $query->whereNotNull('ss.code')->where('ss.code', '<>', '');
        } elseif ($request->mapped_filter === 'unmapped') {
            $query->where(function ($q) {
                $q->whereNull('ss.code')->orWhere('ss.code', '');
            });
        }

        $data = $query->paginate(10)
            ->appends(['search' => request('search'), 'mapped_filter' => request('mapped_filter')]);

        return view('pages.master_laboratory', compact('groups', 'data', 'mapped_filter', 'total_all', 'total_mapped', 'total_unmapped'));
    }

    public function show(Request $request)
    {
        $id = $request->input('id');

        $klinikLab = '0017';
        $idUnit = '001';

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

        $query = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.RJ_DGRUP_TIND as a')
            ->leftJoin('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as b', 'a.KD_TIND', '=', 'b.KD_TIND')
            ->leftJoin('SIRS_PHCM.dbo.RJ_MGRUP_TIND as c', 'a.ID_GRUP_TIND', '=', 'c.ID_GRUP_TIND')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as ss', 'a.KD_TIND', '=', 'ss.ID')
            ->select(
                'c.NM_GRUP_TIND as NAMA_GRUP',
                'a.KD_TIND as ID_TINDAKAN',
                'b.NM_TIND as NAMA_TINDAKAN',
                'ss.code as SATUSEHAT_CODE',
                'ss.display as SATUSEHAT_DISPLAY',
            )
            ->whereIn('a.ID_GRUP_TIND', $groupIds)
            ->where('a.KD_TIND', $id)
            ->first();

        if (!$query) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($query);
    }

    public function saveLoinc(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_tindakan' => 'required|integer',
                'nama_tindakan' => 'required|string|max:255',
                'satusehat_code' => 'required|string|max:100',
                'satusehat_display' => 'required|string|max:255',
            ]);

            $loinc = DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE')
                ->where('ID', $validated['id_tindakan'])
                ->first();

            if ($loinc) {
                // Update
                DB::connection('sqlsrv')
                    ->table('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE')
                    ->where('ID', $validated['id_tindakan'])
                    ->update([
                        'NM_TIND'   => $validated['nama_tindakan'],
                        'code'      => $validated['satusehat_code'],
                        'display'   => $validated['satusehat_display'],
                        'codesystem' => 'http://loinc.org',
                        'CATEGORY'  => 2,       // Laboratory
                    ]);
            } else {
                // Insert
                DB::connection('sqlsrv')
                    ->table('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE')
                    ->insert([
                        'ID'        => $validated['id_tindakan'],
                        'NM_TIND'   => $validated['nama_tindakan'],
                        'code'      => $validated['satusehat_code'],
                        'display'   => $validated['satusehat_display'],
                        'codesystem' => 'http://loinc.org',
                        'CATEGORY'  => 2,       // Laboratory
                    ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
