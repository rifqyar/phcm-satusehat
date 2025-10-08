<?php

namespace App\Http\Controllers;

use App\MasterRadiology;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MasterRadiologyController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::connection('sqlsrv')
        ->table('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as a')
        ->join('SIRS_PHCM.dbo.RJ_DGRUP_TIND as b', 'a.KD_TIND', '=', 'b.KD_TIND')
        ->join('SIRS_PHCM.dbo.RJ_MGRUP_TIND as c', 'b.ID_GRUP_TIND', '=', 'c.ID_GRUP_TIND')
        ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as d', 'a.KD_TIND', '=', 'd.ID')
        ->select(
            'c.ID_GRUP_TIND as ID_GRUP',
            'c.NM_GRUP_TIND as NAMA_GRUP',
            'a.KD_TIND as ID_TINDAKAN',
            'a.NM_TIND as NAMA_TINDAKAN',
            'd.code as SATUSEHAT_CODE',
            'd.codesystem as SATUSEHAT_SYSTEM',
            'd.display as SATUSEHAT_DISPLAY',
            'd.CATEGORY'
        )
        ->whereIn('b.KDKLINIK', function ($sub) {
            $sub->select('KODE_KLINIK')
                ->from('SIRS_PHCM.dbo.RJ_KLINIK_RADIOLOGI')
                ->where('AKTIF', 'true')
                ->where('IDUNIT', '001');
        })
        ->whereRaw('ISNULL(a.STT_ACT,0) <> 0')
        ->distinct()
        ->orderBy('c.ID_GRUP_TIND')
        ->orderBy('a.NM_TIND');

        // search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('a.NM_TIND', 'like', "%{$search}%")
                  ->orWhere('d.code', 'like', "%{$search}%")
                  ->orWhere('d.display', 'like', "%{$search}%");
            });
        }

        $data = $query->paginate(10);

        return view('pages.master_radiology', compact('data'));
    }

    public function show(Request $request)
    {
        $id = $request->input('id');

        $query = DB::connection('sqlsrv')
        ->table('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as a')
        ->join('SIRS_PHCM.dbo.RJ_DGRUP_TIND as b', 'a.KD_TIND', '=', 'b.KD_TIND')
        ->join('SIRS_PHCM.dbo.RJ_MGRUP_TIND as c', 'b.ID_GRUP_TIND', '=', 'c.ID_GRUP_TIND')
        ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as d', 'a.KD_TIND', '=', 'd.ID')
        ->select(
            'a.KD_TIND as ID_TINDAKAN',
            'c.NM_GRUP_TIND as NAMA_GRUP',
            'a.NM_TIND as NAMA_TINDAKAN',
            'd.code as SATUSEHAT_CODE',
            'd.display as SATUSEHAT_DISPLAY'
        )
        ->whereIn('b.KDKLINIK', function ($sub) {
            $sub->select('KODE_KLINIK')
                ->from('SIRS_PHCM.dbo.RJ_KLINIK_RADIOLOGI')
                ->where('AKTIF', 'true')
                ->where('IDUNIT', '001');
        })
        ->where('a.KD_TIND', $id)
        ->first();

        if (!$query) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($query);
    }

    public function saveLoinc(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'nm_tind' => 'required',
            'code' => 'required',
            'display' => 'required',
        ]);

        try {
            DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE')
                ->updateOrInsert(
                    ['ID' => $request->id],
                    [
                        'NM_TIND' => $request->nm_tind,
                        'code' => $request->code,
                        'display' => $request->display,
                        'codesystem' => 'http://loinc.org',
                        'CATEGORY' => 'Radiology',
                    ]
                );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'DB error'], 500);
        }
    }

    public function searchLoinc(Request $request)
    {
        $q = $request->get('query', '');
        if (trim($q) === '') {
            return response()->json(['Results' => []]);
        }

        $client = new Client([
            'timeout' => 10,
        ]);

        try {
            $res = $client->get('https://loinc.regenstrief.org/searchapi/loincs', [
                'auth' => ['rifqyar', 'Rif1912Qy!'],
                'query' => ['query' => $q],
            ]);

            $data = json_decode((string)$res->getBody(), true);
            $results = $data['Results'] ?? [];

            return response()->json(['Results' => array_slice($results, 0, 10)]);
        } catch (\Exception $e) {
            return response()->json(['Results' => []], 500);
        }
    }

}
