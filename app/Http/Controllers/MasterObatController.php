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


        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('KDBRG_CENTRA', 'like', "%{$search}%")
                  ->orWhere('NAMABRG', 'like', "%{$search}%")
                  ->orWhere('KD_BRG_KFA', 'like', "%{$search}%")
                  ->orWhere('NAMABRG_KFA', 'like', "%{$search}%");
            });
        }

        $data = $query->select('ID', 'KDBRG_CENTRA', 'NAMABRG', 'KD_BRG_KFA', 'NAMABRG_KFA', 'DESCRIPTION')
                      ->orderBy('NAMABRG', 'asc')
                      ->paginate(10); // tampil 10 per halaman

        return view('pages.master_obat', compact('data'));
    }
}
