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
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;

class QuestionnaireResponseController extends Controller
{
    use SATUSEHATTraits, LogTraits;

    public function index()
    {
        $startDate = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
        $endDate   = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
        $id_unit = Session::get('id_unit', '001');

        $dataKunjungan = collect(DB::select("
            EXEC dbo.sp_getDataEncounter ?, ?, ?, ?
        ", [
            $id_unit,
            $startDate,
            $endDate,
            'all'
        ]));

        $summary = $dataKunjungan->first();

        $totalData = [
            'total_semua' => $summary->total_semua ?? 0,
            'rjAll' => $summary->rjAll ?? 0,
            'ri' => $summary->ri ?? 0,
            'total_sudah_integrasi' => $summary->total_sudah_integrasi ?? 0,
            'total_belum_integrasi' => $summary->total_belum_integrasi ?? 0,
        ];

        $mergedAll = $summary->total_semua ?? 0;
        $mergedIntegrated = $summary->total_sudah_integrasi ?? 0;
        $unmapped = $summary->total_belum_integrasi ?? 0;
        return view('pages.satusehat.questionnaire-response.index', compact('mergedAll', 'mergedIntegrated', 'unmapped'));
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $id_unit = Session::get('id_unit', '001');

        if (empty($tgl_awal) && empty($tgl_akhir)) {
            $tgl_awal  = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $tgl_akhir = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
        } else {
            $tgl_awal = Carbon::parse($tgl_awal)->startOfDay()->format('Y-m-d H:i:s');
            $tgl_akhir = Carbon::parse($tgl_akhir)->endOfDay()->format('Y-m-d H:i:s');
        }

        if (!$this->checkDateFormat($tgl_awal) || !$this->checkDateFormat($tgl_akhir)) {
            return DataTables::of([])->make(true);
        }

        $tgl_awal_db  = Carbon::parse($tgl_awal)->format('Y-m-d H:i:s');
        $tgl_akhir_db = Carbon::parse($tgl_akhir)->format('Y-m-d H:i:s');

        // Call stored procedure without 'cari' filter - always use 'all'
        $dataKunjungan = collect(DB::select("
            EXEC dbo.sp_getDataEncounter ?, ?, ?, ?
        ", [
            $id_unit,
            $tgl_awal_db,
            $tgl_akhir_db,
            'all'
        ]));

        $summary = $dataKunjungan->first();

        $totalData = [
            'total_semua' => $summary->total_semua ?? 0,
            'rjAll' => $summary->rjAll ?? 0,
            'ri' => $summary->ri ?? 0,
            'total_sudah_integrasi' => $summary->total_sudah_integrasi ?? 0,
            'total_belum_integrasi' => $summary->total_belum_integrasi ?? 0,
        ];

        return DataTables::of($dataKunjungan)
            ->addIndexColumn()
            ->editColumn('JENIS_PERAWATAN', function ($row) {
                return $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
            })
            ->editColumn('TANGGAL', function ($row) {
                return date('Y-m-d', strtotime($row->TANGGAL));
            })
            ->editColumn('STATUS_SELESAI', function ($row) {
                if ($row->JENIS_PERAWATAN == 'RAWAT_JALAN') {
                    if ($row->STATUS_SELESAI == "9" || $row->STATUS_SELESAI == "10") {
                        return '<span class="badge badge-pill badge-secondary p-2 w-100">Belum Verif</span>';
                    } else {
                        return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Verif</span>';
                    }
                } else {
                    return $row->STATUS_SELESAI == 1 ? '<span class="badge badge-pill badge-success p-2 w-100">Sudah Pulang</span>' : '<span class="badge badge-pill badge-secondary p-2 w-100">Belum Pulang</span>';
                }
            })
            ->addColumn('action', function ($row) {
                return '<button class="btn btn-sm btn-primary" onclick="tambahRespon(\'' . $row->ID_TRANSAKSI . '\')">Isi Respon Kuesioner</button>';
            })
            ->addColumn('status_integrasi', function ($row) {
                return '';
            })
            ->rawColumns(['STATUS_SELESAI', 'action', 'status_integrasi'])
            ->with($totalData)
            ->make(true);
    }

    private function checkDateFormat($date)
    {
        try {
            if ($date instanceof \Carbon\Carbon) {
                return true;
            }

            \Carbon\Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getQuestions(Request $request)
    {
        // Return structured questions based on FHIR Q0007 questionnaire
        $sections = [
            [
                'linkId' => '1',
                'title' => 'Persyaratan Administrasi',
                'questions' => [
                    [
                        'linkId' => '1.1',
                        'text' => 'Apakah nama, umur, jenis kelamin, berat badan dan tinggi badan pasien sudah sesuai?',
                        'type' => 'valueCoding'
                    ],
                    [
                        'linkId' => '1.2',
                        'text' => 'Apakah nama, nomor ijin, alamat dan paraf dokter sudah sesuai?',
                        'type' => 'valueCoding'
                    ],
                    [
                        'linkId' => '1.3',
                        'text' => 'Apakah tanggal resep sudah sesuai?',
                        'type' => 'valueCoding'
                    ],
                    [
                        'linkId' => '1.4',
                        'text' => 'Apakah ruangan/unit asal resep sudah sesuai?',
                        'type' => 'valueCoding'
                    ]
                ]
            ],
            [
                'linkId' => '2',
                'title' => 'Persyaratan Farmasetik',
                'questions' => [
                    [
                        'linkId' => '2.1',
                        'text' => 'Apakah nama obat, bentuk dan kekuatan sediaan sudah sesuai?',
                        'type' => 'valueCoding'
                    ],
                    [
                        'linkId' => '2.2',
                        'text' => 'Apakah dosis dan jumlah obat sudah sesuai?',
                        'type' => 'valueCoding'
                    ],
                    [
                        'linkId' => '2.3',
                        'text' => 'Apakah stabilitas obat sudah sesuai?',
                        'type' => 'valueCoding'
                    ],
                    [
                        'linkId' => '2.4',
                        'text' => 'Apakah aturan dan cara penggunaan obat sudah sesuai?',
                        'type' => 'valueCoding'
                    ]
                ]
            ],
            [
                'linkId' => '3',
                'title' => 'Persyaratan Klinis',
                'questions' => [
                    [
                        'linkId' => '3.1',
                        'text' => 'Apakah ketepatan indikasi, dosis, dan waktu penggunaan obat sudah sesuai?',
                        'type' => 'valueCoding'
                    ],
                    [
                        'linkId' => '3.2',
                        'text' => 'Apakah terdapat duplikasi pengobatan?',
                        'type' => 'valueBoolean'
                    ],
                    [
                        'linkId' => '3.3',
                        'text' => 'Apakah terdapat alergi dan reaksi obat yang tidak dikehendaki (ROTD)?',
                        'type' => 'valueBoolean'
                    ],
                    [
                        'linkId' => '3.4',
                        'text' => 'Apakah terdapat kontraindikasi pengobatan?',
                        'type' => 'valueBoolean'
                    ],
                    [
                        'linkId' => '3.5',
                        'text' => 'Apakah terdapat dampak interaksi obat?',
                        'type' => 'valueBoolean'
                    ]
                ]
            ]
        ];

        return response()->json([
            'sections' => $sections
        ]);
    }
}