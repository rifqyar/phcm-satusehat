<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Jobs\SendDiagnosticReport;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA;
use App\Models\SATUSEHAT\SS_Kode_API;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;

class DiagnosticReportController extends Controller
{
    use SATUSEHATTraits, LogTraits;

    public function index()
    {
        return view('pages.satusehat.diagnostic-report.index');
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $id_unit = Session::get('id_unit', '001');
        $search = $request->input('cari');

        if (empty($tgl_awal) && empty($tgl_akhir)) {
            $tgl_awal  = Carbon::now()->startOfDay();
            $tgl_akhir = Carbon::now()->endOfDay();
        } else {
            if (empty($tgl_awal)) {
                $tgl_awal = Carbon::parse($tgl_akhir)->startOfDay();
            }
            if (empty($tgl_akhir)) {
                $tgl_akhir = Carbon::now()->endOfDay();
            } else {
                // Force the end date to be at 23:59:59 (end of that day)
                $tgl_akhir = Carbon::parse($tgl_akhir)->endOfDay();
            }
        }

        if (!$this->checkDateFormat($tgl_awal) || !$this->checkDateFormat($tgl_akhir)) {
            return DataTables::of([])->make(true);
        }

        $tgl_awal_db  = Carbon::parse($tgl_awal)->format('Y-m-d H:i:s');
        $tgl_akhir_db = Carbon::parse($tgl_akhir)->format('Y-m-d H:i:s');
        // dd($id_unit, $tgl_awal_db, $tgl_akhir_db, $search ?? 'all');

        // Build the base query
        $baseQuery = collect(DB::select("
            EXEC dbo.sp_getDataDiagnosticReport ?, ?, ?, ?
        ", [
            $id_unit,
            $tgl_awal_db,
            $tgl_akhir_db,
            $search ?? 'unmapped'
        ]));
        $summary = $baseQuery->first();
        // dd($summary);

        $totalData = [
            'total_semua' => $summary->total_semua ?? 0,
            'total_sudah_integrasi' => $summary->total_mapped ?? 0,
            'total_belum_integrasi' => $summary->total_unmapped ?? 0
        ];

        return DataTables::of($baseQuery)
            ->addIndexColumn()
            ->addColumn('checkbox', function ($row) {
                $checkbox = '';
                if ($row->JUMLAH_SERVICE_REQUEST > 0) {
                    if ($row->SATUSEHAT == 0) {
                        $checkbox = '<input type="checkbox"  class="select-row chk-col-purple" value="' . $row->id . '" id="checkbox_' . $row->id . '" />
                        <label for="checkbox_' . $row->id . '"></label>';
                    }
                }
                return $checkbox;
            })
            ->addColumn('pasien', function ($row) {
                return $row->NM_PASIEN ?? '-';
            })
            ->addColumn('karcis_rujukan', function ($row) {
                return $row->karcis_rujukan ?? '-';
            })
            ->addColumn('diupload_oleh', function ($row) {
                return $row->usr_crt ?? '-';
            })
            ->addColumn('kategori', function ($row) {
                return $row->nama_kategori ?? '-';
            })
            ->addColumn('status_integrasi', function ($row) {
                if ($row->SATUSEHAT > 0) {
                    return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                } else {
                    return '<span class="badge badge-pill badge-danger p-2 w-100">Belum Integrasi</span>';
                }
            })
            ->addColumn('aksi', function ($row) {
                $paramDetail = LZString::compressToEncodedURIComponent("id=" . $row->id . "&karcis_asal=" . $row->karcis_asal . "&karcis_rujukan=" . $row->karcis_rujukan);

                // $openFileBtn = '<button type="button" class="btn btn-success btn-sm mr-1" onclick="openFile(\'' . url('assets/dokumen_px/' . $row->kbuku . '/' . $row->file_name) . '\')">
                //     <i class="fa fa-search"></i> Lihat File
                // </button>';

                $openFileBtn = '';
                $btnDetail = '<button type="button" class="btn btn-sm btn-info" onclick="lihatDetail(\'' . $paramDetail . '\')"><i class="fas fa-info-circle mr-2"></i>Lihat Detail</button>';
                if ($row->JUMLAH_SERVICE_REQUEST > 0) {
                    if ($row->SATUSEHAT == 0) {
                        $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramDetail . '`)" class="btn btn-sm btn-primary"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                    } else {
                        $btn = '<a href="javascript:void(0)" onclick="reSendSatuSehat(`' . $paramDetail . '`)" class="btn btn-sm btn-warning"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                    }
                } else {
                    $btn = '<span class="badge badge-pill badge-secondary p-2">Belum Integrasi Service Request</span>';
                }

                return $btnDetail . ' ' . $btn;
            })
            ->rawColumns(['aksi', 'checkbox', 'status_integrasi'])
            ->with($totalData)
            ->make(true);
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

    /**
     * Handler for bulk sending multiple DiagnosticReport documents.
     * This is a stub/handler only — business logic should be implemented later.
     */
    public function bulkSend(Request $request)
    {
        try {
            $selectedIds = $request->input('selected_ids', []);
            // dd($selectedIds);

            // Debug logging
            Log::info('Bulk send request received', [
                'selected_ids_count' => count($selectedIds),
                'first_few_params' => array_slice($selectedIds, 0, 2),
                'user_id' => Session::get('nama', 'system')
            ]);

            if (empty($selectedIds)) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Tidak ada data yang dipilih untuk dikirim'
                ], 422);
            }

            $dispatched = 0;
            $errors = [];

            foreach ($selectedIds as $param) {
                try {
                    // Validate that param is not empty and has proper format
                    if (empty($param) || !is_string($param)) {
                        $errors[] = "Invalid parameter format: " . json_encode($param);
                        continue;
                    }

                    // Dispatch job to queue for background processing
                    SendDiagnosticReport::dispatch($param)->onQueue('DiagnosticReport');
                    $dispatched++;
                } catch (Exception $e) {
                    $errors[] = "Failed to dispatch job for param: " . $e->getMessage();
                    Log::error('Failed to dispatch single job', [
                        'param' => $param,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($dispatched === 0) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Gagal mengirim ke antrian untuk semua item yang dipilih.'
                ], 500);
            }

            // Log the bulk dispatch
            Log::info('Bulk diagnostic report jobs dispatched', [
                'total_dispatched' => $dispatched,
                'total_errors' => count($errors),
                'user_id' => Session::get('nama', 'system'),
                'params_count' => count($selectedIds)
            ]);

            $message = "Berhasil mengirim {$dispatched} diagnostic report ke antrian untuk diproses. Pengiriman akan berlanjut di background.";

            if (!empty($errors)) {
                $message .= " " . count($errors) . " item gagal dikirim ke antrian.";
                Log::warning('Some jobs failed to dispatch', ['errors' => $errors]);
            }

            return response()->json([
                'status' => JsonResponse::HTTP_OK,
                'message' => $message,
                'data' => [
                    'dispatched_count' => $dispatched,
                    'error_count' => count($errors),
                    'total_selected' => count($selectedIds),
                    'errors' => !empty($errors) ? array_slice($errors, 0, 3) : [] // Show first 3 errors
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('Bulk send dispatch failed', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('nama', 'system') // Session::get('id')
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Gagal mengirim ke antrian: ' . $e->getMessage()
            ], 500);
        }
    }

    public function lihatDetail($param)
    {
        $decoded = base64_decode($param);
        $params = LZString::decompressFromEncodedURIComponent($decoded);
        $id_unit = Session::get('id_unit', '001');
        // dd($params);

        $arrParam = [];
        $parts = explode('&', $params);
        for ($i = 0; $i < count($parts); $i++) {
            $partsParam = explode('=', $parts[$i]);
            $key = $partsParam[0];
            $val = $partsParam[1];
            $arrParam[$key] = $val;
        }
        // dd($arrParam);

        $dataDetail = collect(DB::select("
            EXEC dbo.sp_getDataDiagnosticReportDetail ?, ?, ?, ?
        ", [
            $id_unit,
            $arrParam['id'],
            $arrParam['karcis_asal'],
            $arrParam['karcis_rujukan']
        ]));

        return response()->json([
            'dataDetail' => $dataDetail,
        ]);
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

    public function reSendSatuSehat($param)
    {
        return $this->sendSatuSehat($param, true);
    }

    public function sendSatuSehat($param, $resend = false)
    {
        $decoded = base64_decode($param);
        $params = LZString::decompressFromEncodedURIComponent($decoded);
        $id_unit = Session::get('id_unit', '001');
        // dd($params);

        $arrParam = [];
        $parts = explode('&', $params);
        for ($i = 0; $i < count($parts); $i++) {
            $partsParam = explode('=', $parts[$i]);
            $key = $partsParam[0];
            $val = $partsParam[1];
            $arrParam[$key] = $val;
        }
        // dd($arrParam);

        // $detail = collect(DB::select("
        //     EXEC dbo.sp_getDataDiagnosticReportDetail ?, ?, ?, ?
        // ", [
        //     $id_unit,
        //     $arrParam['id'],
        //     $arrParam['karcis_asal'],
        //     $arrParam['karcis_rujukan']
        // ]));
        // dd($detail);

        $dokumen_px =  DB::connection('sqlsrv')
            ->table(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX as a'))
            ->join(DB::raw('SIRS_PHCM.dbo.vw_getData_Elab as l'), 'a.karcis', '=', 'l.KARCIS_ASAL')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX_KATEGORI as b'), 'a.id_kategori', '=', 'b.id')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MASTERPX as c'), 'a.kbuku', '=', 'c.kbuku')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as m'), 'l.kd_tindakan', '=', 'm.KD_TIND')
            ->select([
                'a.*',
                'b.nama_kategori',
                'm.KD_TIND'
            ])
            ->where('a.AKTIF', 1)
            ->where('a.id', $arrParam['id'])
            ->where('a.karcis', $arrParam['karcis_asal'])
            ->where('l.karcis_rujukan', $arrParam['karcis_rujukan'])
            ->get();
        // dd($dokumen_px);

        $riwayat = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.vw_getData_Elab')
            ->where('IDUNIT', $id_unit)
            ->where('KARCIS_ASAL', $arrParam['karcis_asal'])
            ->where('KARCIS_RUJUKAN', $arrParam['karcis_rujukan'])
            ->first();

        $dokumen_px_codings = DB::connection('sqlsrv')
            ->table(DB::raw('SATUSEHAT.dbo.SATUSEHAT_M_SERVICEREQUEST_CODE as a'))
            ->select('a.*')
            ->whereIn('a.id', $dokumen_px->pluck('KD_TIND')->toArray())
            ->get();
        // dd($dokumen_px_codings);

        $patient = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN_MAPPING as a')
            ->join('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN as b', 'a.idpx', '=', 'b.idpx')
            ->select('a.idpx', 'b.nama', 'b.no_peserta')
            ->where('a.no_peserta', $dokumen_px->first()->no_peserta)
            ->first();

        $dokter = DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.vw_getData_Elab as a')
            ->leftJoin('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES as b', 'a.kddok', '=', 'b.kddok')
            ->where('IDUNIT', $id_unit)
            ->where('KARCIS_ASAL', $arrParam['karcis_asal'])
            ->where('KARCIS_RUJUKAN', $arrParam['karcis_rujukan'])
            ->first();

        $encounter = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
            ->where('karcis', $dokumen_px->first()->karcis)
            ->where('idunit', $id_unit)
            ->first();

        $observation = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI')
            ->where('KARCIS', $dokumen_px->first()->karcis)
            ->where('KBUKU', $dokumen_px->first()->kbuku)
            ->first();

        $serviceRequest = DB::connection('sqlsrv')
            ->table(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX as a'))
            ->leftJoin(DB::raw('SIRS_PHCM.dbo.vw_getData_Elab as l'), function ($join) {
                $join->on('a.karcis', '=', 'l.KARCIS_ASAL');
            })
            ->leftJoin(DB::raw('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as s'), function ($join) {
                $join->on('l.karcis_rujukan', '=', 's.karcis')
                    ->on('l.kbuku', '=', 's.kbuku');
            })
            ->where('a.id', $arrParam['id'])
            ->where('a.karcis', $arrParam['karcis_asal'])
            ->where('l.karcis_rujukan', $arrParam['karcis_rujukan'])
            ->orderBy('s.crtdt', 'desc')
            ->first();

        // dd($dokumen_px, $riwayat, $dokumen_px_codings, $patient, $dokter, $encounter, $observation, $serviceRequest);

        $dateTimeNow = Carbon::now()->toIso8601String();
        $categories = [];
        $codings = [
            "coding" => []
        ];

        foreach ($dokumen_px_codings as $coding) {
            $codings['coding'][] = [
                "system" => $coding->codesystem,
                "code" => $coding->code,
                "display" => $coding->display
            ];
        }

        $baseurl = '';
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

        // Category
        if ($dokumen_px->first()->nama_kategori === 'HASIL LAB') {
            $categories = [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/v2-0074",
                            "code" => "LAB",
                            "display" => "Laboratory"
                        ]
                    ]
                ]
            ];
            $identifier = [
                [
                    "system" => "http://sys-ids.kemkes.go.id/diagnostic/{$organisasi}/lab",
                    "use" => "official",
                    "value" => "$riwayat->ID_RIWAYAT_ELAB"
                ]
            ];
        } else if ($dokumen_px->first()->nama_kategori === 'HASIL RADIOLOGI') {
            $categories = [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/v2-0074",
                            "code" => "RAD",
                            "display" => "Radiology"
                        ]
                    ]
                ]
            ];
            $identifier = [
                [
                    "system" => "http://sys-ids.kemkes.go.id/diagnostic/{$organisasi}/rad",
                    "use" => "official",
                    "value" => "$riwayat->ID_RIWAYAT_ELAB"
                ]
            ];
        } else {
            $categories = [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/v2-0074",
                            "code" => "OTH",
                            "display" => "Other"
                        ]
                    ]
                ]
            ];
        }

        $data = [
            "resourceType" => "DiagnosticReport",
            "identifier" => $identifier,
            "status" => "final",
            "category" => $categories,
            "code" => $codings,
            "subject" => [
                "reference" => "Patient/{$patient->idpx}",
                "display" => "$patient->nama"
            ],
            "encounter" => [
                "reference" => "Encounter/{$encounter->id_satusehat_encounter}"
            ],
            "performer" => [
                [
                    "reference" => "Practitioner/{$dokter->idnakes}",
                    "display" => "$dokter->nama"
                ],
                [
                    "reference" => "Organization/{$organisasi}"
                ]
            ],
            "result" => [
                [
                    "reference" => "Observation/$observation->ID_SATUSEHAT_OBSERVASI",
                ]
            ],
            "basedOn" => [
                [
                    "reference" => "ServiceRequest/{$serviceRequest->id_satusehat_servicerequest}"
                ]
            ],
            'conclusion' => $dokumen_px->first()->keterangan ?? '',
        ];

        // dd($data);
        // dd($dokumen_px, $riwayat, $dokumen_px_codings, $patient, $dokter, $encounter, $observation, $serviceRequest, $data);

        try {
            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }

            $token = $login['response']['token'];

            if ($resend) {
                $diagnosticRep = DB::connection('sqlsrv')
                    ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_DIAGNOSTICREPORT')
                    ->where('iddokumen', $arrParam['id'])
                    ->where('karcis', $arrParam['karcis_asal'])
                    ->where('karcis_rujukan', $arrParam['karcis_rujukan'])
                    ->where('idunit', $id_unit)
                    ->first();

                if ($diagnosticRep) {
                    $data['id'] = $diagnosticRep->id_satusehat_diagnosticreport;
                }
            }

            // dd($data);

            $url = $resend ? 'DiagnosticReport/' . $diagnosticRep->id_satusehat_diagnosticreport : 'DiagnosticReport';
            $diagnosticReportRequest = $this->consumeSATUSEHATAPI($resend ? 'PUT' : 'POST', $baseurl, $url, $data, true, $token);
            $result = json_decode($diagnosticReportRequest->getBody()->getContents(), true);

            if ($diagnosticReportRequest->getStatusCode() >= 400) {
                $response = json_decode($diagnosticReportRequest->getBody(), true);

                $this->logError('DiagnosticReport', 'Gagal kirim data diagnostic report', [
                    'payload' => $data,
                    'response' => $response,
                    'user_id' => Session::get('nama', 'system')
                ]);

                $this->logDb(json_encode($response), 'DiagnosticReport', json_encode($data), 'system');

                $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Kirim Data Diagnostic Report';
                throw new Exception($msg, $diagnosticReportRequest->getStatusCode());
            } else {
                try {
                    $dataKarcis = DB::connection('sqlsrv')
                        ->table('SIRS_PHCM.dbo.RJ_KARCIS as rk')
                        ->select('rk.KARCIS', 'rk.IDUNIT', 'rk.KLINIK', 'rk.TGL', 'rk.KDDOK', 'rk.KBUKU')
                        ->where('rk.KARCIS', $dokumen_px->first()->karcis)
                        ->where('rk.IDUNIT', $id_unit)
                        ->orderBy('rk.TGL', 'DESC')
                        ->first();
                    // dd($dataKarcis);

                    $dataServiceRequest = DB::connection('sqlsrv')
                        ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST')
                        ->where('karcis', $dokumen_px->first()->karcis)
                        ->first();

                    $dataPeserta = DB::connection('sqlsrv')
                        ->table('SIRS_PHCM.dbo.RIRJ_MASTERPX')
                        ->where('KBUKU', $dataKarcis->KBUKU)
                        ->first();

                    DB::connection('sqlsrv')
                        ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_DIAGNOSTICREPORT')
                        ->insert([
                            'karcis'                        => $arrParam['karcis_asal'],
                            'karcis_rujukan'                => $arrParam['karcis_rujukan'],
                            'nota'                          => $encounter->nota,
                            'idriwayat'                     => $riwayat->ID_RIWAYAT_ELAB,
                            'iddokumen'                     => $arrParam['id'],
                            'idunit'                        => $id_unit,
                            'tgl'                           => Carbon::parse($dataKarcis->TGL, 'Asia/Jakarta')->format('Y-m-d'),
                            'id_satusehat_encounter'        => $encounter->id_satusehat_encounter,
                            'id_satusehat_servicerequest'   => $serviceRequest->id_satusehat_servicerequest,
                            'id_satusehat_diagnosticreport' => $result['id'],
                            'kbuku'                         => $dataPeserta->KBUKU,
                            'no_peserta'                    => $dataPeserta->NO_PESERTA,
                            'id_satusehat_px'               => $patient->idpx,
                            'kddok'                         => $dataKarcis->KDDOK,
                            'id_satusehat_dokter'           => $dokter->idnakes,
                            'kdklinik'                      => $dataKarcis->KLINIK,
                            'status_sinkron'                => 1,
                            'crtdt'                         => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                            'crtusr'                        => 'system', // Session::get('id'),
                            'sinkron_date'                  => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                            'jam_datang'                    => Carbon::parse($riwayat->TANGGAL_ENTRI, 'Asia/Jakarta')->format('Y-m-d H:i:s'),
                        ]);

                    $this->logInfo('diagnosticreport', 'Sukses kirim data diagnostic report', [
                        'payload' => $data,
                        'response' => $result,
                        'user_id' => Session::get('nama', 'system') //Session::get('id')
                    ]);
                    $this->logDb(json_encode($result), 'DiagnosticReport', json_encode($data), 'system'); //Session::get('id')

                    return response()->json([
                        'status' => JsonResponse::HTTP_OK,
                        'message' => 'Berhasil Kirim Data Diagnostic Report',
                        'redirect' => [
                            'need' => false,
                            'to' => null,
                        ]
                    ], 200);
                } catch (Exception $th) {
                    // dd($th);
                    throw new Exception($th->getMessage(), $th->getCode());
                }
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => JsonResponse::HTTP_BAD_REQUEST,
                'message' => $e->getMessage() ?? 'Gagal mengirim data laporan pemeriksaan',
                'redirect' => [
                    'need' => false,
                    'to' => null,
                ]
            ], 400);
        }
    }
}
