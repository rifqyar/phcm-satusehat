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
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as ss', 'a.KD_TIND', '=', 'ss.ID')
            ->select(
                'a.ID_GRUP_TIND as ID_GRUP',
                'c.NM_GRUP_TIND as NAMA_GRUP',
                'a.KDKLINIK',
                'a.KD_TIND as ID_TINDAKAN',
                DB::raw('ISNULL(a.URUTAN, 99) as URUTAN'),
                'b.NM_TIND as NAMA_TINDAKAN',
                // DB::raw('COUNT(DISTINCT a.ID_GRUP_TIND) OVER() as JUMLAH_DATA'),
                'ss.code as SATUSEHAT_CODE',
                'ss.codesystem as SATUSEHAT_SYSTEM',
                'ss.display as SATUSEHAT_DISPLAY',
                'ss.CATEGORY'
            )
            ->whereIn('a.ID_GRUP_TIND', $groupIds)
            ->orderByRaw('ISNULL(a.URUTAN, 99)');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $tindakanQuery->where(function($q) use ($search) {
                $q->where('b.NM_TIND', 'like', "%{$search}%")
                  ->orWhere('ss.code', 'like', "%{$search}%")
                  ->orWhere('ss.display', 'like', "%{$search}%");
            });
        }

        $tindakanData = $tindakanQuery->paginate(10);

        return view('pages.master_laboratory', compact('groups', 'tindakanData'));
    }

    public function saveLoinc(Request $request)
    {
        $request->validate([
            'id' => 'required|string',      // KD_TIND
            'nm_tind' => 'required|string', // NM_TIND
            'code' => 'required|string',
            'display' => 'required|string',
        ]);

        DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE')
            ->updateOrInsert(
                ['ID' => $request->id], // primary key
                [
                    'NM_TIND'    => $request->nm_tind,
                    'code'       => $request->code,
                    'display'    => $request->display,
                    'codesystem' => 'http://loinc.org',
                    'CATEGORY'   => 'Laboratory',
                ]
            );

        return response()->json(['success' => true]);
    }


}
