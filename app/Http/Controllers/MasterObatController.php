<?php

namespace App\Http\Controllers;

use App\MasterObat;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use DB;

class MasterObatController extends Controller
{

    public function index(Request $request)
    {

        $kode = $request->get('kode');
        // $total_all = MasterObat::count();
        // $total_mapped = MasterObat::whereNotNull('KD_BRG_KFA')->where('KD_BRG_KFA', '<>', '')->count();
        // $total_unmapped = MasterObat::whereNull('KD_BRG_KFA')->orWhere('KD_BRG_KFA', '')->count();
        $stats = MasterObat::selectRaw("
            COUNT(1) AS total_all,
            SUM(CASE WHEN KD_BRG_KFA IS NOT NULL AND KD_BRG_KFA <> '' THEN 1 ELSE 0 END) AS total_mapped,
            SUM(CASE WHEN KD_BRG_KFA IS NULL OR KD_BRG_KFA = '' THEN 1 ELSE 0 END) AS total_unmapped
        ")->first();

        $total_all = $stats->total_all;
        $total_mapped = $stats->total_mapped;
        $total_unmapped = $stats->total_unmapped;

        return view('pages.master_obat', compact(
            'total_all',
            'total_mapped',
            'total_unmapped',
            'kode'
        ));
    }
    public function getData(Request $request)
    {
        $query = MasterObat::query();

        /**
         * ===========================
         *  Filter status mapping
         * ===========================
         */
        $status = $request->get('status', 'all');

        if ($status === 'mapped') {
            $query->whereNotNull('KD_BRG_KFA')
                ->where('KD_BRG_KFA', '<>', '');
        } elseif ($status === 'unmapped') {
            $query->where(function ($q) {
                $q->whereNull('KD_BRG_KFA')
                    ->orWhere('KD_BRG_KFA', '');
            });
        }

        /**
         * ===========================
         *  Filter berdasarkan kode obat (URL)
         *  contoh → /master_obat?kode=FAR000123
         * ===========================
         */
        $kode = trim($request->get('kode'));

        if (!empty($kode)) {
            // Kamu bisa sesuaikan ke kolom KDBRG_CENTRA atau KODE lainnya
            $query->where('KDBRG_CENTRA', $kode);
        }

        /**
         * ===========================
         *  Return DataTables
         * ===========================
         */
        return DataTables::of($query)
            ->addColumn('status_mapping', function ($row) {
                if (empty($row->KD_BRG_KFA)) {
                    return '<span class="badge badge-danger">Unmapped</span>';
                }
                if ($row->KD_BRG_KFA === '000') {
                    return '<span class="badge badge-secondary">Non Farmasi</span>';
                }
                return '<span class="badge badge-success">Mapped</span>';
            })


            // Kolom aksi (button Mapping)
            ->addColumn('action', function ($row) {
                $isMapped = !empty($row->KD_BRG_KFA);
                $btnClass = $isMapped ? 'btn-warning' : 'btn-success';
                $icon = $isMapped ? 'fa-sync-alt' : 'fa-link';
                $label = $isMapped ? 'Mapping Ulang' : 'Mapping';

                return "
                <button type='button'
                    class='btn btn-sm $btnClass btnMappingObat'
                    data-toggle='modal'
                    data-target='#modalMapping'
                    data-id='{$row->ID}'
                    data-kode='{$row->KDBRG_CENTRA}'
                    data-nama='{$row->NAMABRG}'
                    data-kfa='{$row->KD_BRG_KFA}'
                    data-namakfa='{$row->NAMABRG_KFA}'
                    data-jenis='" . ($row->IS_COMPOUND ? 'Compound' : 'Non-compound') . "'
                    data-is-compound='" . ($row->IS_COMPOUND ? 1 : 0) . "'
                    data-deskripsi='{$row->DESCRIPTION}'
                >
                    <i class='fas $icon'></i> $label
                </button>
            ";
            })

            ->rawColumns(['status_mapping', 'action'])
            ->make(true);
    }

    public function show(Request $request)
    {
        $id = $request->input('id');

        $obat = MasterObat::select('ID', 'KDBRG_CENTRA', 'NAMABRG', 'KD_BRG_KFA', 'NAMABRG_KFA', 'DESCRIPTION')
            ->where('ID', $id)
            ->first();

        if (!$obat) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($obat);
    }
    public function saveMapping(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer',
                'kode_kfa' => 'nullable|string|max:100',
                'nama_kfa' => 'nullable|string',
                'deskripsi' => 'nullable|string|max:500',
                'is_compound' => 'nullable|in:0,1',
            ]);

            $obat = MasterObat::find($validated['id']);
            if (!$obat) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.']);
            }

            $obat->KD_BRG_KFA = $validated['kode_kfa'];
            $obat->NAMABRG_KFA = $validated['nama_kfa'];
            $obat->DESCRIPTION = $validated['deskripsi'];
            $obat->IS_COMPOUND = (int) $request->input('is_compound', 0);
            $obat->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
