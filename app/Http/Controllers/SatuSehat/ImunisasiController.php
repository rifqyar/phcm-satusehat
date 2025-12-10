<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ImunisasiController extends Controller
{
    public function index()
    {
        return response()->view('pages.satusehat.imunisasi.index');
    }

    public function getDetailDiagnosis(Request $request)
    {
        // Optional: ambil ID dari request (kalau frontend kirim)
        $idTrans = $request->id;

        // Static JSON Diagnosis
        $mockDiagnosis = [
            'diagnosis_id' => 'DX-001',
            'patient_id' => 'PAT-12345',
            'encounter_id' => 'ENC-20251210-01',
            'code' => [
                'icd10' => 'J45.9',
                'description' => 'Asthma, unspecified',
            ],
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'severity' => 'moderate',
            'onset_date' => '2025-12-10',
            'recorded_date' => '2025-12-10T09:30:00+07:00',
            'note' => 'Pasien mengeluhkan sesak napas dan wheezing sejak 2 hari.',
        ];

        return response()->json([
            'status' => 'success',
            'data' => $mockDiagnosis,
        ]);
    }

    public function datatabel(Request $request)
    {
        // Base query
        $query = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN')->whereNotNull('tglLahir')->select('id', 'nik', 'idpx', 'kk', 'nama', 'aktif', 'tglLahir', 'sex', 'kodebahasa', 'bahasa', 'use_alamat', 'alamat', 'kodepropinsi', 'propinsi', 'kodekota', 'kota', 'kodecamat', 'camat', 'kodelurah', 'lurah', 'rt', 'rw', 'no_peserta', 'kbuku', 'user_mapping', 'tgl_mapping', 'crtdt');

        // Total records
        $recordsTotal = (clone $query)->count();

        // DataTables server-side
        $dataTable = DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value']) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%")
                            ->orWhere('alamat', 'like', "%{$search}%")
                            ->orWhere('kota', 'like', "%{$search}%")
                            ->orWhere('propinsi', 'like', "%{$search}%")
                            ->orWhere('idpx', 'like', "%{$search}%");
                    });
                }
            })
            ->order(function ($query) {
                $query->orderBy('id', 'desc');
            })
            ->make(true);

        // Summary basic
        $json = $dataTable->getData(true);
        $json['summary'] = [
            'all' => $recordsTotal,
        ];

        return response()->json($json);
    }
}
