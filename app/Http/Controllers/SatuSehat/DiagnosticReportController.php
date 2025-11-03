<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DiagnosticReportController extends Controller
{
    public function index()
    {
        return view('pages.satusehat.diagnostic-report.index');
    }

    public function datatable(Request $request) {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $search = $request->input('search');

        // Build the base query
        $query = DB::connection('sqlsrv')
            ->table(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX as a'))
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX_KATEGORI as b'), 'a.id_kategori', '=', 'b.id')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MASTERPX as c'), 'a.kbuku', '=', 'c.kbuku')
            ->select([
                'a.id',
                'a.kbuku',
                'c.NAMA as NM_PASIEN',
                'a.file_name',
                'a.keterangan',
                'b.nama_kategori',
                'a.usr_crt',
                'a.crt_dt'
            ])
            ->where('a.AKTIF', 1);

        // Apply date filter only if both dates are provided
        if (!empty($tgl_awal) && !empty($tgl_akhir)) {
            $tgl_awal = Carbon::parse($tgl_awal)->format('Y-m-d');
            $tgl_akhir = Carbon::parse($tgl_akhir)->format('Y-m-d');
            
            $query->whereRaw("CONVERT(date, a.crt_dt) BETWEEN ? AND ?", [
                $tgl_awal,
                $tgl_akhir
            ]);
        }

        // Apply search filter if provided
        if (!empty($search)) {
            if ($search === 'sent') {
                // Assume documents with certain criteria are "sent" - you can modify this logic
                $query->whereNotNull('a.file_name');
            } elseif ($search === 'pending') {
                // Assume documents with certain criteria are "pending"
                $query->whereNull('a.keterangan');
            }
            // For 'all', no additional filter needed
        }
        
        // Get summary counts for the cards
        $baseQueryForCount = DB::connection('sqlsrv')
            ->table(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX as a'))
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX_KATEGORI as b'), 'a.id_kategori', '=', 'b.id')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MASTERPX as c'), 'a.kbuku', '=', 'c.kbuku')
            ->where('a.AKTIF', 1);

        // Apply same date filter for counts
        if (!empty($tgl_awal) && !empty($tgl_akhir)) {
            $baseQueryForCount->whereRaw("CONVERT(date, a.crt_dt) BETWEEN ? AND ?", [
                $tgl_awal,
                $tgl_akhir
            ]);
        }

        $allCount = (clone $baseQueryForCount)->count();
        $sentCount = (clone $baseQueryForCount)->whereNotNull('a.file_name')->count();
        $pendingCount = (clone $baseQueryForCount)->whereNull('a.keterangan')->count();
        
        // Don't add ORDER BY here - let DataTables handle it
        // $query->orderBy('a.id', 'desc');

        $dataTable = DataTables::of($query)
            ->addColumn('pasien', function ($row) {
                return $row->NM_PASIEN ?? '-';
            })
            ->addColumn('diupload_oleh', function ($row) {
                return $row->usr_crt ?? '-';
            })
            ->addColumn('tanggal_upload', function ($row) {
                return $row->crt_dt ? Carbon::parse($row->crt_dt)->format('d-m-Y H:i:s') : '-';
            })
            ->addColumn('kategori_file', function ($row) {
                return ($row->nama_kategori ?? '') . '<br>' . ($row->keterangan ?? '') . '<br>(' . ($row->file_name ?? '') . ')';
            })
            ->addColumn('aksi', function ($row) {
                $openFileBtn = '<button type="button" class="btn btn-success btn-sm mr-1" onclick="openFile(\'' . url('assets/dokumen_px/' . $row->kbuku . '/' . $row->file_name) . '\')">
                    <i class="fa fa-search"></i> Lihat File
                </button>';

                $sendBtn = '<button class="btn btn-warning btn-sm mr-1" onclick="sendSatuSehat()">
                    <i class="fa fa-link"></i> Kirim Satu Sehat
                </button>';
                
                return '
                    <div class="d-flex align-items-stretch">
                        '.$openFileBtn . $sendBtn.'
                    </div>
                ';
            })
            ->rawColumns(['kategori_file', 'aksi'])
            ->with([
                'summary' => [
                    'all' => $allCount,
                    'sent' => $sentCount,
                    'pending' => $pendingCount
                ]
            ])
            ->make(true);

        return $dataTable;
    }

    public function delete(Request $request)
    {
        try {
            $id = $request->input('id');
            
            if (!$id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ID dokumen tidak ditemukan'
                ]);
            }

            $result = DB::connection('sqlsrv')
                ->table(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX'))
                ->where('id', $id)
                ->update(['AKTIF' => 0]);

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Dokumen berhasil dihapus'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus dokumen'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    private function checkDateFormat($date) {
        try {
            if ($date instanceof Carbon) {
                return true;
            }
            Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
