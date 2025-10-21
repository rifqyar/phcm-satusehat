<?php

namespace App\Http\Controllers;

use App\MasterObat;
use Illuminate\Http\Request;
use DB;

class MasterObatController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterObat::query();

        // 🔍 Filter pencarian
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('KDBRG_CENTRA', 'like', "%{$search}%")
                    ->orWhere('NAMABRG', 'like', "%{$search}%")
                    ->orWhere('KD_BRG_KFA', 'like', "%{$search}%")
                    ->orWhere('NAMABRG_KFA', 'like', "%{$search}%");
            });
        }

        // 🧩 Filter status mapping
        $status = $request->get('status', 'all'); // default: semua

        if ($status === 'mapped') {
            $query->whereNotNull('KD_BRG_KFA')->where('KD_BRG_KFA', '<>', '');
        } elseif ($status === 'unmapped') {
            $query->where(function ($q) {
                $q->whereNull('KD_BRG_KFA')->orWhere('KD_BRG_KFA', '');
            });
        }

        // 🔢 Hitung total
        $total_all = MasterObat::count();
        $total_mapped = MasterObat::whereNotNull('KD_BRG_KFA')->where('KD_BRG_KFA', '<>', '')->count();
        $total_unmapped = MasterObat::whereNull('KD_BRG_KFA')->orWhere('KD_BRG_KFA', '')->count();

        // 🔽 Ambil data utama
        $data = $query->select('ID', 'KDBRG_CENTRA', 'NAMABRG', 'KD_BRG_KFA', 'NAMABRG_KFA', 'IS_COMPOUND', 'DESCRIPTION')
            ->orderByRaw('CASE WHEN KD_BRG_KFA IS NULL OR KD_BRG_KFA = \'\' THEN 0 ELSE 1 END')
            ->orderBy('NAMABRG', 'asc')
            ->paginate(10)
            ->appends(['search' => $request->search, 'status' => $status]);

        return view('pages.master_obat', compact(
            'data',
            'status',
            'total_all',
            'total_mapped',
            'total_unmapped'
        ));
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
                'nama_kfa' => 'nullable|string|max:255',
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
            $obat->IS_COMPOUND  = (int) $request->input('is_compound', 0);
            $obat->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }


}

