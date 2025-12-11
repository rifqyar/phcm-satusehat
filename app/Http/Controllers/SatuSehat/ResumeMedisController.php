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

class ResumeMedisController extends Controller
{
    use SATUSEHATTraits, LogTraits;

    public function index()
    {
        // Initialize summary counters
        $result = [
            'total_semua' => 12,
            'total_rawat_jalan' => 6,
            'total_rawat_inap' => 6,
            'total_sudah_integrasi' => 4,
            'total_belum_integrasi' => 8,
        ];

        return view('pages.satusehat.resume-medis.index', compact('result'));
    }

    public function datatable(Request $request)
    {
        // Return dummy data to populate the table and summary counters
        $rows = [];

        // create 12 dummy rows
        for ($i = 1; $i <= 12; $i++) {
            $statusIntegrated = ($i % 3 === 0);
            $jenisPerawatan = ($i % 2 == 0) ? 'Rawat Jalan' : 'Rawat Inap';
            $idTransaksi = 'TRX' . str_pad($i, 5, '0', STR_PAD_LEFT);

            $rows[] = [
                'DT_RowIndex' => $i,
                'checkbox' => '<input type="checkbox" class="row-checkbox chk-col-purple" data-id="' . $idTransaksi . '" id="check_' . $i . '"><label for="check_' . $i . '"></label>',
                'ID_TRANSAKSI' => $idTransaksi,
                'JENIS_PERAWATAN' => $jenisPerawatan,
                'NO_PESERTA' => 'PES' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'KBUKU' => 'KBK' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'NAMA' => 'Pasien ' . $i,
                'TGL_MASUK' => Carbon::now()->subDays($i)->format('Y-m-d'),
                'DOKTER' => 'Dr. Dokter ' . $i,
                'sudah_integrasi' => $statusIntegrated ? 1 : 0,
                'status_integrasi' => $statusIntegrated
                    ? '<span class="badge badge-success">Sudah Integrasi</span>'
                    : '<span class="badge badge-warning">Belum Integrasi</span>',
                'action' => $this->generateActionButtons($idTransaksi, $statusIntegrated),
            ];
        }

        $total = count($rows);
        $rjAll = collect($rows)->filter(function($r) { return $r['JENIS_PERAWATAN'] === 'Rawat Jalan'; })->count();
        $ri = collect($rows)->filter(function($r) { return $r['JENIS_PERAWATAN'] === 'Rawat Inap'; })->count();
        $total_integrated = collect($rows)->filter(function($r) { return $r['sudah_integrasi'] === 1; })->count();
        $total_unmapped = $total - $total_integrated;

        return response()->json([
            'data' => $rows,
            'total_semua' => $total,
            'total_rawat_jalan' => $rjAll,
            'total_rawat_inap' => $ri,
            'total_sudah_integrasi' => $total_integrated,
            'total_belum_integrasi' => $total_unmapped
        ]);
    }

    private function generateActionButtons($idTransaksi, $statusIntegrated)
    {
        $btnDetail = '<button type="button" class="btn btn-sm btn-info" onclick="lihatDetail(\'' . $idTransaksi . '\')"><i class="fas fa-info-circle mr-2"></i>Lihat Detail</button>';

        if (!$statusIntegrated) {
            $btnSend = '<button type="button" class="btn btn-sm btn-success ml-1" onclick="sendSatuSehat(\'' . $idTransaksi . '\')"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</button>';
            return $btnDetail . ' ' . $btnSend;
        } else {
            $btnResend = '<button type="button" class="btn btn-sm btn-warning ml-1" onclick="resendSatuSehat(\'' . $idTransaksi . '\')"><i class="fas fa-link mr-2"></i>Kirim Ulang</button>';
            return $btnDetail . ' ' . $btnResend;
        }
    }

    public function lihatDetail($param)
    {
        // Dummy detail data
        $dataPasien = [
            'NAMA' => 'Pasien Dummy',
            'KBUKU' => 'KBK001',
            'NO_PESERTA' => 'PES000001',
            'KARCIS' => 'KRC20250101001',
            'DOKTER' => 'Dr. Dokter Dummy',
            'statusIntegrated' => 'Belum Integrasi'
        ];

        $dataErm = [
            'ID_TRANSAKSI' => 'TRX00001',
            'CRTUSR' => 'Dr. Dokter Dummy',
            'KELUHAN' => 'Pasien mengeluh demam dan batuk sejak 3 hari yang lalu',
            'TD' => '120/80 mmHg',
            'DJ' => '80 x/menit',
            'P' => '20 x/menit',
            'SUHU' => '37.5 °C',
            'TB' => '170 cm',
            'BB' => '65 kg',
            'IMT' => '22.5 kg/m² (Normal)',
            'DIAGNOSA' => 'ISPA (Infeksi Saluran Pernapasan Akut)',
            'TERAPI' => 'Paracetamol 3x500mg, Amoxicillin 3x500mg',
            'TINDAKAN' => 'Observasi, istirahat cukup, banyak minum air putih',
            'ANJURAN' => 'Kontrol kembali jika keluhan tidak membaik dalam 3 hari',
        ];

        return response()->json([
            'dataPasien' => $dataPasien,
            'dataErm' => $dataErm,
        ]);
    }
}
