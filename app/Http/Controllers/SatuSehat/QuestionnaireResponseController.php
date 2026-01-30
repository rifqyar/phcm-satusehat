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

        // Get all karcis IDs
        $allKarcisIds = $dataKunjungan->pluck('ID_TRANSAKSI')->filter()->unique()->toArray();
        
        // Count how many have logs in SATUSEHAT_LOG_RESPON_KUESIONER
        $integratedCount = 0;
        if (!empty($allKarcisIds)) {
            $integratedCount = DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_RESPON_KUESIONER')
                ->whereIn('karcis', $allKarcisIds)
                ->distinct('karcis')
                ->count('karcis');
        }

        $mergedAll = $dataKunjungan->count();
        $mergedIntegrated = $integratedCount;
        $unmapped = $mergedAll - $mergedIntegrated;
        
        return view('pages.satusehat.questionnaire-response.index', compact('mergedAll', 'mergedIntegrated', 'unmapped'));
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $cari = $request->input('cari'); // Filter: all, sent, pending
        $id_unit = Session::get('id_unit', '001');

        if (empty($tgl_awal) && empty($tgl_akhir)) {
            $tgl_awal  = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $tgl_akhir = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
        } else {
            $tgl_awal = Carbon::parse($tgl_awal)->startOfDay()->format('Y-m-d H:i:s');
            $tgl_akhir = Carbon::parse($tgl_akhir)->endOfDay()->format('Y-m-d H:i:s');
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

        // Fetch all log statuses in one query to avoid N+1 problem
        $allTransaksiIds = $dataKunjungan->pluck('ID_TRANSAKSI')->filter()->unique()->toArray();
        $logStatuses = [];
        
        if (!empty($allTransaksiIds)) {
            $logs = DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_RESPON_KUESIONER')
                ->whereIn('karcis', $allTransaksiIds)
                ->get();
            
            // Group by karcis and take the latest one for each
            foreach ($logs as $log) {
                if (!isset($logStatuses[$log->karcis])) {
                    $logStatuses[$log->karcis] = $log;
                }
            }
        }
        
        // Calculate totals based on log existence
        $total_semua = $dataKunjungan->count();
        $total_sudah_integrasi = count($logStatuses);
        $total_belum_integrasi = $total_semua - $total_sudah_integrasi;
        
        // Apply filter based on search type
        if (!empty($cari)) {
            switch ($cari) {
                case 'sent':
                    // Filter only integrated data (has log)
                    $dataKunjungan = $dataKunjungan->filter(function($row) use ($logStatuses) {
                        return isset($logStatuses[$row->ID_TRANSAKSI]);
                    });
                    break;
                case 'pending':
                    // Filter only non-integrated data (no log)
                    $dataKunjungan = $dataKunjungan->filter(function($row) use ($logStatuses) {
                        return !isset($logStatuses[$row->ID_TRANSAKSI]);
                    });
                    break;
                case 'all':
                default:
                    // No filter, show all data
                    break;
            }
        }
        
        $totalData = [
            'total_semua' => $total_semua,
            'total_sudah_integrasi' => $total_sudah_integrasi,
            'total_belum_integrasi' => $total_belum_integrasi,
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
                $jenisPerawatan = $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
                $id_transaksi = LZString::compressToEncodedURIComponent($row->ID_TRANSAKSI);
                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                $kdLokasiSS = LZString::compressToEncodedURIComponent($row->ID_LOKASI_SS ?? '');
                $paramSatuSehat = "jenis_perawatan=" . $jenisPerawatan . "&id_transaksi=" . $id_transaksi . "&kd_pasien_ss=" . $kdPasienSS . "&kd_nakes_ss=" . $kdNakesSS . "&kd_lokasi_ss=" . $kdLokasiSS;
                $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);
                $paramEncoded = base64_encode($paramSatuSehat);

                if ($row->JUMLAH_NOTA_SATUSEHAT > 0) {
                    return '<button style="white-space: nowrap" class="btn btn-sm btn-primary" onclick="tambahRespon(\'' . $row->ID_TRANSAKSI . '\', \'' . $paramEncoded . '\')">Isi respon kuesioner</button>';
                } else {
                    return '<i class="text-muted">Encounter Belum Dikirim</i>';   
                }
                
            })
            ->addColumn('status_integrasi', function ($row) use ($logStatuses) {
                // Use pre-fetched log status to avoid N+1 query
                $logStatus = $logStatuses[$row->ID_TRANSAKSI] ?? null;
                
                if ($logStatus) {
                    return '<span class="badge badge-success">Sudah Integrasi</span><br><small>' . date('d-m-Y H:i', strtotime($logStatus->crtdt)) . '</small>';
                } else {
                    return '<span class="badge badge-secondary">Belum Integrasi</span>';
                }
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

    public function sendSatuSehat(Request $request, $param)
    {
        try {
            $id_unit = Session::get('id_unit', '001');
            
            // Parse parameters same as EncounterController
            $params = base64_decode($param);
            $params = LZString::decompressFromEncodedURIComponent($params);
            $parts = explode('&', $params);

            $arrParam = [];
            $partsParam = explode('=', $parts[0]);
            $arrParam[$partsParam[0]] = $partsParam[1];
            for ($i = 1; $i < count($parts); $i++) {
                $partsParam = explode('=', $parts[$i]);
                $key = $partsParam[0];
                $val = $partsParam[1];
                $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
            }

            $jenisPerawatan = $arrParam['jenis_perawatan'];
            $id_transaksi = $arrParam['id_transaksi'];
            $kdPasienSS = $arrParam['kd_pasien_ss'];
            $kdNakesSS = $arrParam['kd_nakes_ss'];
            $kdLokasiSS = $arrParam['kd_lokasi_ss'];
            
            // Get visit data
            $visit = DB::connection('sqlsrv')
                ->table('SIRS_PHCM.dbo.v_kunjungan_rj as rj')
                ->leftJoin('SIRS_PHCM.dbo.v_kunjungan_ri as ri', 'rj.ID_TRANSAKSI', '=', 'ri.ID_TRANSAKSI')
                ->where('rj.ID_TRANSAKSI', $id_transaksi)
                ->orWhere('ri.ID_TRANSAKSI', $id_transaksi)
                ->select('rj.*', 'ri.ID_TRANSAKSI as ri_id')
                ->first();
                
            if (!$visit) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Data kunjungan tidak ditemukan',
                    'redirect' => ['need' => false, 'to' => '']
                ], 404);
            }
            
            // Get patient data using kdPasienSS
            $patient = DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN')
                ->where('idpx', $kdPasienSS)
                ->first();
                
            if (!$patient) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Data pasien SatuSehat tidak ditemukan',
                    'redirect' => ['need' => false, 'to' => '']
                ], 404);
            }
            
            // Get responses from request
            $responses = $request->input('responses', []);
            
            if (empty($responses)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Respon kuesioner tidak ditemukan',
                    'redirect' => ['need' => false, 'to' => '']
                ], 400);
            }
            
            // Build payload items based on responses
            $items = [];
            
            // Section 1: Persyaratan Administrasi
            $section1Items = [];
            foreach (['1.1', '1.2', '1.3', '1.4'] as $linkId) {
                if (isset($responses[$linkId])) {
                    $section1Items[] = [
                        'linkId' => $linkId,
                        'text' => $this->getQuestionText($linkId),
                        'answer' => [[
                            'valueCoding' => [
                                'system' => 'http://terminology.kemkes.go.id/CodeSystem/clinical-term',
                                'code' => $responses[$linkId],
                                'display' => $responses[$linkId] == 'OV000052' ? 'Sesuai' : 'Tidak Sesuai'
                            ]
                        ]]
                    ];
                }
            }
            
            if (!empty($section1Items)) {
                $items[] = [
                    'linkId' => '1',
                    'text' => 'Persyaratan Administrasi',
                    'item' => $section1Items
                ];
            }
            
            // Section 2: Persyaratan Farmasetik
            $section2Items = [];
            foreach (['2.1', '2.2', '2.3', '2.4'] as $linkId) {
                if (isset($responses[$linkId])) {
                    $section2Items[] = [
                        'linkId' => $linkId,
                        'text' => $this->getQuestionText($linkId),
                        'answer' => [[
                            'valueCoding' => [
                                'system' => 'http://terminology.kemkes.go.id/CodeSystem/clinical-term',
                                'code' => $responses[$linkId],
                                'display' => $responses[$linkId] == 'OV000052' ? 'Sesuai' : 'Tidak Sesuai'
                            ]
                        ]]
                    ];
                }
            }
            
            if (!empty($section2Items)) {
                $items[] = [
                    'linkId' => '2',
                    'text' => 'Persyaratan Farmasetik',
                    'item' => $section2Items
                ];
            }
            
            // Section 3: Persyaratan Klinis
            $section3Items = [];
            foreach (['3.1', '3.2', '3.3', '3.4', '3.5'] as $linkId) {
                if (isset($responses[$linkId])) {
                    $answer = [];
                    if ($linkId == '3.1') {
                        // valueCoding for 3.1
                        $answer = [[
                            'valueCoding' => [
                                'system' => 'http://terminology.kemkes.go.id/CodeSystem/clinical-term',
                                'code' => $responses[$linkId],
                                'display' => $responses[$linkId] == 'OV000052' ? 'Sesuai' : 'Tidak Sesuai'
                            ]
                        ]];
                    } else {
                        // valueBoolean for 3.2-3.5
                        $answer = [[
                            'valueBoolean' => $responses[$linkId] === 'true' ? true : false
                        ]];
                    }
                    
                    $section3Items[] = [
                        'linkId' => $linkId,
                        'text' => $this->getQuestionText($linkId),
                        'answer' => $answer
                    ];
                }
            }
            
            if (!empty($section3Items)) {
                $items[] = [
                    'linkId' => '3',
                    'text' => 'Persyaratan Klinis',
                    'item' => $section3Items
                ];
            }
            
            // Section 4: Resep yang dilakukan pengkajian resep (hardcoded)
            // $items[] = [
            //     'linkId' => '4',
            //     'text' => 'Resep yang dilakukan pengkajian resep',
            //     'answer' => [[
            //         'valueReference' => [
            //             'reference' => 'MedicationRequest/HARDCODED-MEDICATION-REQUEST-ID'
            //         ]
            //     ]]
            // ];
            
            // Get encounter ID
            $encounter = DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
                ->where('karcis', $id_transaksi)
                ->where('idunit', $id_unit)
                ->first();
                
            if (!$encounter || !$encounter->id_satusehat_encounter) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Encounter SatuSehat tidak ditemukan',
                    'redirect' => ['need' => false, 'to' => '']
                ], 404);
            }
            
            // Get practitioner data (author) using kdNakesSS
            $practitioner = DB::connection('sqlsrv')
                ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES')
                ->where('idnakes', $kdNakesSS)
                ->first();

                // dd($practitioner);
                
            if (!$practitioner) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Data practitioner tidak ditemukan',
                    'redirect' => ['need' => false, 'to' => '']
                ], 404);
            }
            
            $practitionerId = $practitioner->idnakes;
            $practitionerName = $practitioner->nama;
            
            // Build final payload
            $payload = [
                'resourceType' => 'QuestionnaireResponse',
                'questionnaire' => 'https://fhir.kemkes.go.id/Questionnaire/Q0007',
                'status' => 'completed',
                'subject' => [
                    'reference' => 'Patient/' . $patient->idpx,
                    'display' => $patient->nama ?? ''
                ],
                'encounter' => [
                    'reference' => 'Encounter/' . $encounter->id_satusehat_encounter
                ],
                'authored' => Carbon::now()->toIso8601String(),
                'author' => [
                    'reference' => 'Practitioner/' . $practitionerId,
                    'display' => $practitionerName
                ],
                'source' => [
                    'reference' => 'Patient/' . $patient->idpx
                ],
                'item' => $items
            ];


            // dd($payload);
            
            // Get base URL
            $baseurl = '';
            if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
                $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
                $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
            } else {
                $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
                $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
            }
            
            // Login to get access token
            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Gagal mendapatkan access token',
                    'redirect' => ['need' => false, 'to' => '']
                ], 500);
            }
            
            $token = $login['response']['token'];
            
            // Send to SatuSehat using consumeSATUSEHATAPI
            $url = 'QuestionnaireResponse';
            $data = json_decode(json_encode($payload));
            $response = $this->consumeSATUSEHATAPI('POST', $baseurl, $url, $data, true, $token);
            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);
            
            // Log to database
            DB::connection('sqlsrv')->table('SATUSEHAT.dbo.SATUSEHAT_LOG_RESPON_KUESIONER')->insert([
                'karcis' => $id_transaksi,
                'id_satusehat_respon_kuesioner' => $responseBody['id'] ?? null,
                'id_satusehat_encounter' => $encounter->id_satusehat_encounter,
                'id_satusehat_px' => $patient->idpx,
                'id_satusehat_dokter' => $practitionerId,
                'kbuku' => $visit->KBUKU ?? null,
                'no_peserta' => $visit->NO_PESERTA ?? null,
                'idunit' => $id_unit,
                'status_sinkron' => $statusCode == 201 ? 1 : 0,
                'crtdt' => Carbon::now(),
                'crtusr' => Session::get('user', 'system'),
                'sinkron_date' => $statusCode == 201 ? Carbon::now() : null
            ]);
            
            if ($statusCode == 201) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Data berhasil dikirim ke SatuSehat',
                    'redirect' => ['need' => false, 'to' => '']
                ]);
            } else {
                $msg = '';
                if (isset($responseBody['issue'])) {
                    foreach ($responseBody['issue'] as $issue) {
                        $msg .= $issue['details']['text'] ?? $issue['diagnostics'] ?? 'Unknown error';
                        $msg .= ' ';
                    }
                } else {
                    $msg = $responseBody['message'] ?? 'Unknown error';
                }
                
                return response()->json([
                    'status' => $statusCode,
                    'message' => 'Gagal mengirim data: ' . trim($msg),
                    'redirect' => ['need' => false, 'to' => '']
                ], $statusCode);
            }
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error: ' . $e->getMessage(),
                'redirect' => ['need' => false, 'to' => '']
            ], 500);
        }
    }
    
    private function getQuestionText($linkId)
    {
        $questions = [
            '1.1' => 'Apakah nama, umur, jenis kelamin, berat badan dan tinggi badan pasien sudah sesuai?',
            '1.2' => 'Apakah nama, nomor ijin, alamat dan paraf dokter sudah sesuai?',
            '1.3' => 'Apakah tanggal resep sudah sesuai?',
            '1.4' => 'Apakah ruangan/unit asal resep sudah sesuai?',
            '2.1' => 'Apakah nama obat, bentuk dan kekuatan sediaan sudah sesuai?',
            '2.2' => 'Apakah dosis dan jumlah obat sudah sesuai?',
            '2.3' => 'Apakah stabilitas obat sudah sesuai?',
            '2.4' => 'Apakah aturan dan cara penggunaan obat sudah sesuai?',
            '3.1' => 'Apakah ketepatan indikasi, dosis, dan waktu penggunaan obat sudah sesuai?',
            '3.2' => 'Apakah terdapat duplikasi pengobatan?',
            '3.3' => 'Apakah terdapat alergi dan reaksi obat yang tidak dikehendaki (ROTD)?',
            '3.4' => 'Apakah terdapat kontraindikasi pengobatan?',
            '3.5' => 'Apakah terdapat dampak interaksi obat?'
        ];
        
        return $questions[$linkId] ?? '';
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
