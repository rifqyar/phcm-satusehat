<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\SATUSEHAT\SS_Kode_API;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class QuestionnaireResponseController extends Controller
{
    use SATUSEHATTraits, LogTraits;

    public function index()
    {
        return view('pages.satusehat.questionnaire-response.index');
    }

    public function datatable(Request $request)
    {
        // Return dummy data to populate the table and summary counters
        $rows = [];

        // create 12 dummy rows
        for ($i = 1; $i <= 12; $i++) {
            $statusIntegrated = ($i % 3 === 0); // every 3rd row is integrated
            $rows[] = [
                'DT_RowIndex' => $i,
                'ID_TRANSAKSI' => 'TRX' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'JENIS_PERAWATAN' => ($i % 2 == 0) ? 'Rawat Jalan' : 'Rawat Inap',
                'STATUS_SELESAI' => ($i % 2 == 0) ? 'Selesai' : 'Proses',
                'TANGGAL' => Carbon::now()->subDays($i)->format('Y-m-d H:i:s'),
                'NO_PESERTA' => 'PES' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'KBUKU' => 'KBK' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'NAMA_PASIEN' => 'Pasien ' . $i,
                'DOKTER' => 'Dr. Dokter ' . $i,
                'DEBITUR' => ($i % 2 == 0) ? 'BPJS' : 'Umum',
                'LOKASI' => 'Poli ' . ($i % 5 + 1),
                'status_integrasi' => $statusIntegrated ? '<span class="badge badge-pill badge-success p-2">Sudah Integrasi</span>' : '<span class="badge badge-pill badge-danger p-2">Belum Integrasi</span>',
                'action' => '<button class="btn btn-sm btn-primary" onclick="tambahRespon(\'' . $i . '\')">Isi Respon Kuesioner</button>'
            ];
        }

        // build summary counts
        $total = count($rows);
        $rjAll = collect($rows)->filter(fn($r) => $r['JENIS_PERAWATAN'] === 'Rawat Jalan')->count();
        $ri = collect($rows)->filter(fn($r) => $r['JENIS_PERAWATAN'] === 'Rawat Inap')->count();
        $total_integrated = collect($rows)->filter(fn($r) => str_contains($r['status_integrasi'], 'Sudah Integrasi'))->count();
        $total_unmapped = $total - $total_integrated;

        return response()->json([
            'data' => $rows,
            'total_semua' => $total,
            'rjAll' => $rjAll,
            'ri' => $ri,
            'total_sudah_integrasi' => $total_integrated,
            'total_belum_integrasi' => $total_unmapped
        ]);
    }

    public function getQuestions(Request $request)
    {
        // Return dummy questions
        $questions = [
            [
                'id' => 1,
                'text' => 'Apakah ketepatan indikasi, dosis, dan waktu penggunaan obat sudah sesuai?'
            ],
            [
                'id' => 2,
                'text' => 'Apakah terdapat duplikasi pengobatan?'
            ],
            [
                'id' => 3,
                'text' => 'Apakah terdapat alergi dan reaksi obat yang tidak dikehendaki (ROTD)?"'
            ],
            [
                'id' => 4,
                'text' => 'Apakah terdapat kontraindikasi pengobatan?'
            ],
            [
                'id' => 5,
                'text' => 'Apakah terdapat dampak interaksi obat?'
            ]
        ];

        return response()->json([
            'questions' => $questions
        ]);
    }
}