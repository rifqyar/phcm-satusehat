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
        $search = $request->input('search');

        // Build the base query
        $query = DB::connection('sqlsrv')
            ->table(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX as a'))
            ->join(DB::raw('SIRS_PHCM.dbo.vw_getData_Elab as l'), function ($join) {
                $join->on('a.karcis', '=', 'l.KARCIS_ASAL')
                    ->on('a.kd_tindakan', '=', 'l.KD_TINDAKAN');
            })
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX_KATEGORI as b'), 'a.id_kategori', '=', 'b.id')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MASTERPX as c'), 'a.kbuku', '=', 'c.kbuku')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as m'), 'a.kd_tindakan', '=', 'm.KD_TIND')
            ->leftJoin(DB::raw('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as sr'), function ($join) {
                $join->on('l.karcis_rujukan', '=', 'sr.karcis')
                    ->on('a.kbuku', '=', 'sr.kbuku');
            })
            ->leftJoin(DB::raw('SATUSEHAT.dbo.SATUSEHAT_LOG_DIAGNOSTICREPORT as sdr'), function ($join) {
                $join->on('l.karcis_rujukan', '=', 'sdr.karcis')
                    ->on('a.kbuku', '=', 'sdr.kbuku')
                    ->on('a.id', '=', 'sdr.iddokumen');
            })
            ->select([
                'a.id',
                'l.karcis_asal',
                'l.karcis_rujukan',
                'a.kbuku',
                'c.NAMA as NM_PASIEN',
                'm.NM_TIND as NM_TIND',
                'a.file_name',
                'a.keterangan',
                'b.nama_kategori',
                'a.usr_crt',
                'a.crt_dt',
                DB::raw('COUNT(DISTINCT sr.id_satusehat_servicerequest) AS JUMLAH_SERVICE_REQUEST'), // Placeholder for SATUSEHAT status
                DB::raw('COUNT(DISTINCT sdr.id_satusehat_diagnosticreport) AS SATUSEHAT'), // Placeholder for SATUSEHAT status
            ])
            ->where('a.AKTIF', 1)
            ->where('a.id_kategori', 1)
            ->groupBy(
                'a.id',
                'l.karcis_asal',
                'l.karcis_rujukan',
                'a.kbuku',
                'c.NAMA',
                'm.NM_TIND',
                'a.file_name',
                'a.keterangan',
                'b.nama_kategori',
                'a.usr_crt',
                'a.crt_dt'
            );

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
                // Documents that have been integrated to SatuSehat
                $query->where(DB::raw('0'), '>', 0); // Using placeholder logic for SATUSEHAT > 0
            } elseif ($search === 'pending') {
                // Documents that haven't been integrated to SatuSehat
                $query->where(DB::raw('0'), '=', 0); // Using placeholder logic for SATUSEHAT = 0
            }
            // For 'all', no additional filter needed
        }

        // Get summary counts for the cards - using same base query as DataTable
        $baseQueryForCount = DB::connection('sqlsrv')
            ->table(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX as a'))
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX_KATEGORI as b'), 'a.id_kategori', '=', 'b.id')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MASTERPX as c'), 'a.kbuku', '=', 'c.kbuku')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as m'), 'a.kd_tindakan', '=', 'm.KD_TIND')
            ->where('a.AKTIF', 1)
            ->where('a.id_kategori', 1);

        // Apply same date filter for counts
        if (!empty($tgl_awal) && !empty($tgl_akhir)) {
            $baseQueryForCount->whereRaw("CONVERT(date, a.crt_dt) BETWEEN ? AND ?", [
                $tgl_awal,
                $tgl_akhir
            ]);
        }

        $allCount = (clone $baseQueryForCount)->count();
        // Sent: documents that have been integrated (SATUSEHAT > 0)
        $sentCount = (clone $baseQueryForCount)->where(DB::raw('0'), '>', 0)->count(); // Using placeholder logic
        // Pending: documents that haven't been integrated (SATUSEHAT = 0)
        $pendingCount = (clone $baseQueryForCount)->where(DB::raw('0'), '=', 0)->count(); // Using placeholder logic

        // Don't add ORDER BY here - let DataTables handle it
        // $query->orderBy('a.id', 'desc');
        // dd($query->toSql(), $query->getBindings());
        // dd($query->get());

        $dataTable = DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('pasien', function ($row) {
                return $row->NM_PASIEN ?? '-';
            })
            ->addColumn('checkbox', function ($row) {
                if ($row->JUMLAH_SERVICE_REQUEST > 0) {
                    if ($row->SATUSEHAT == 0) {
                        return '
                            <input type="checkbox"  class="select-row chk-col-purple" value="' . $row->id . '" id="checkbox_' . $row->id . '" />
                            <label for="checkbox_' . $row->id . '"></label>
                        ';
                    }
                }
            })
            ->addColumn('karcis_asal', function ($row) {
                return $row->karcis_asal ?? '-';
            })
            ->addColumn('karcis_rujukan', function ($row) {
                return $row->karcis_rujukan ?? '-';
            })
            ->addColumn('item_lab', function ($row) {
                return $row->NM_TIND ?? '-';
            })
            ->addColumn('diupload_oleh', function ($row) {
                return $row->usr_crt ?? '-';
            })
            ->addColumn('tanggal_upload', function ($row) {
                return $row->crt_dt ? Carbon::parse($row->crt_dt)->format('d-m-Y H:i:s') : '-';
            })
            ->addColumn('kategori', function ($row) {
                return $row->nama_kategori ?? '-';
            })
            ->addColumn('file', function ($row) {
                return $row->file_name ?? '-';
            })
            ->addColumn('status_integrasi', function ($row) {
                if ($row->SATUSEHAT > 0) {
                    return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                } else {
                    return '<span class="badge badge-pill badge-danger p-2 w-100">Belum Integrasi</span>';
                }
            })
            ->addColumn('aksi', function ($row) {

                // $openFileBtn = '<button type="button" class="btn btn-success btn-sm mr-1" onclick="openFile(\'' . url('assets/dokumen_px/' . $row->kbuku . '/' . $row->file_name) . '\')">
                //     <i class="fa fa-search"></i> Lihat File
                // </button>';

                $openFileBtn = '';
                if ($row->JUMLAH_SERVICE_REQUEST > 0) {
                    if ($row->SATUSEHAT == 0) {
                        $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $row->id . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                    } else {
                        $btn = '<a href="javascript:void(0)" onclick="reSendSatuSehat(`' . $row->id . '`)" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                    }
                } else {
                    $btn = '<span class="badge badge-pill badge-secondary p-2 w-100">Belum Integrasi Service Request</span>';
                }

                return '<div style="min-width: 100px;">' . $btn . '</div>';
            })
            ->rawColumns(['kategori', 'file', 'aksi', 'checkbox', 'status_integrasi'])
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

    public function reSendSatuSehat($idDokumenPx)
    {
        return $this->sendSatuSehat($idDokumenPx, true);
    }

    public function sendSatuSehat($idDokumenPx, $resend = false)
    {
        $id_unit = Session::get('id_unit', '001');
        $status = 'final';

        $dokumen_px =  DB::connection('sqlsrv')
            ->table(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX as a'))
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX_KATEGORI as b'), 'a.id_kategori', '=', 'b.id')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MASTERPX as c'), 'a.kbuku', '=', 'c.kbuku')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as m'), 'a.kd_tindakan', '=', 'm.KD_TIND')
            ->select([
                'a.*',
                'b.nama_kategori',
                DB::raw('0 as SATUSEHAT'), // Placeholder for SATUSEHAT status
            ])
            ->where('a.AKTIF', 1)
            ->where('a.id', $idDokumenPx)
            ->first();

        $riwayat = DB::connection('sqlsrv')
            ->table('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB')
            ->where('IDUNIT', $id_unit)
            ->where('KARCIS_ASAL', $dokumen_px->karcis)
            ->first();

        $dokumen_px_codings = DB::connection('sqlsrv')
            ->table(DB::raw('SATUSEHAT.dbo.DIAGNOSTIC_REPORT_CODINGS as a'))
            ->select('a.*')
            ->where('a.id_dokumen_px', $idDokumenPx)
            ->get();

        $patient = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN_MAPPING as a')
            ->join('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN as b', 'a.idpx', '=', 'b.idpx')
            ->select('a.idpx', 'b.nama', 'b.no_peserta')
            ->where('a.no_peserta', $dokumen_px->no_peserta)
            ->first();

        $dokter = DB::connection('sqlsrv')
            ->table('E_RM_PHCM.dbo.ERM_RIWAYAT_ELAB as a')
            ->leftJoin('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES as b', 'a.kddok', '=', 'b.kddok')
            ->where('IDUNIT', $id_unit)
            ->where('KARCIS_ASAL', $dokumen_px->karcis)
            ->first();

        $encounter = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
            ->where('karcis', $dokumen_px->karcis)
            ->where('idunit', $id_unit)
            ->first();

        $observation = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI')
            ->where('KARCIS', $dokumen_px->karcis)
            ->where('KBUKU', $dokumen_px->kbuku)
            ->first();

        $serviceRequest = DB::connection('sqlsrv')
            ->table(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX as a'))
            ->leftJoin(DB::raw('SIRS_PHCM.dbo.vw_getData_Elab as l'), function ($join) {
                $join->on('a.karcis', '=', 'l.KARCIS_ASAL')
                    ->on('a.kd_tindakan', '=', 'l.KD_TINDAKAN');
            })
            ->leftJoin(DB::raw('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as s'), function ($join) {
                $join->on('l.karcis_rujukan', '=', 's.karcis')
                    ->on('l.kbuku', '=', 's.kbuku');
            })
            ->where('a.id', $idDokumenPx)
            ->orderBy('s.crtdt', 'desc')
            ->first();
        // dd($dokumen_px, $riwayat, $patient, $dokter, $encounter, $observation, $serviceRequest);

        $dateTimeNow = Carbon::now()->toIso8601String();
        $categories = [];
        $codings = [
            "coding" => []
        ];

        foreach ($dokumen_px_codings as $coding) {
            $codings['coding'][] = [
                "system" => "http://loinc.org",
                "code" => $coding->loinc_num,
                "display" => $coding->loinc_common_name
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
        if ($dokumen_px->nama_kategori === 'HASIL LAB') {
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
        } else if ($dokumen_px->nama_kategori === 'HASIL RADIOLOGI') {
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
            "status" => "$status",
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
            'conclusion' => $dokumen_px->keterangan ?? '',
        ];

        // dd($data);

        try {
            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }

            $token = $login['response']['token'];

            if ($resend) {
                $diagnosticRep = DB::connection('sqlsrv')
                    ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_DIAGNOSTICREPORT')
                    ->where('iddokumen', $idDokumenPx)
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
                        ->where('rk.KARCIS', $dokumen_px->karcis)
                        ->where('rk.IDUNIT', $id_unit)
                        ->orderBy('rk.TGL', 'DESC')
                        ->first();
                    // dd($dataKarcis);

                    $dataServiceRequest = DB::connection('sqlsrv')
                        ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST')
                        ->where('karcis', $dokumen_px->karcis)
                        ->first();

                    $dataPeserta = DB::connection('sqlsrv')
                        ->table('SIRS_PHCM.dbo.RIRJ_MASTERPX')
                        ->where('KBUKU', $dataKarcis->KBUKU)
                        ->first();

                    DB::connection('sqlsrv')
                        ->table('SATUSEHAT.dbo.SATUSEHAT_LOG_DIAGNOSTICREPORT')
                        ->insert([
                            'karcis'                        => $serviceRequest->karcis,
                            'nota'                          => $encounter->nota,
                            'idriwayat'                     => $riwayat->ID_RIWAYAT_ELAB,
                            'iddokumen'                     => $idDokumenPx,
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
