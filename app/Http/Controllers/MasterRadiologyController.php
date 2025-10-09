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
                  ->orWhere('c.NM_GRUP_TIND', 'like', "%{$search}%")
                  ->orWhere('d.code', 'like', "%{$search}%")
                  ->orWhere('d.display', 'like', "%{$search}%");
            });
        }

        // Mapped filter: mapped/unmapped
        if ($request->filled('mapped_filter')) {
            if ($request->mapped_filter === 'mapped') {
                $query->whereNotNull('d.code')->where('d.code', '<>', '');
            } elseif ($request->mapped_filter === 'unmapped') {
                $query->where(function($q) {
                    $q->whereNull('d.code')->orWhere('d.code', '');
                });
            }
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
                        'codesystem'=> 'http://loinc.org',
                        'CATEGORY'  => 1,       // Radiology
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
                        'codesystem'=> 'http://loinc.org',
                        'CATEGORY'  => 1,       // Radiology
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

    public function searchLoinc(Request $request)
    {
        $query = $request->input('query', '');

        $baseUrl = 'https://loinc.regenstrief.org/searchapi/loincs';
        $username = 'rifqyar'; // hardcode dulu
        $password = 'Rif1912Qy!';
        
        try {
            $response = Http::withBasicAuth($username, $password)
                ->accept('application/json')
                ->withoutVerifying()
                ->get($baseUrl, [
                    'query' => $query,
                ]);

            $data = json_decode($response->body(), true);
            $results = $data['Results'] ?? [];

            return response()->json([
                'Results' => array_slice($results, 0, 10)
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
