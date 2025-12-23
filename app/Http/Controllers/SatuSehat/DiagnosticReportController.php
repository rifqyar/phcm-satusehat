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
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_DOKUMEN_PX_KATEGORI as b'), 'a.id_kategori', '=', 'b.id')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MASTERPX as c'), 'a.kbuku', '=', 'c.kbuku')
            ->join(DB::raw('SIRS_PHCM.dbo.RIRJ_MTINDAKAN as m'), 'a.kd_tindakan', '=', 'm.KD_TIND')
            ->select([
                'a.id',
                'a.kbuku',
                'c.NAMA as NM_PASIEN',
                'm.NM_TIND as NM_TIND',
                'a.file_name',
                'a.keterangan',
                'b.nama_kategori',
                'a.usr_crt',
                'a.crt_dt',
                DB::raw('0 as SATUSEHAT'), // Placeholder for SATUSEHAT status
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
            ->where('a.AKTIF', 1);

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

        $dataTable = DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('pasien', function ($row) {
                return $row->NM_PASIEN ?? '-';
            })
            ->addColumn('checkbox', function ($row) {
                return '
                    <input type="checkbox"  class="select-row chk-col-purple" value="' . $row->id . '" id="checkbox_' . $row->id . '" />
                    <label for="checkbox_' . $row->id . '"></label>
                ';
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
                return ($row->file_name ?? '-') . '<br>' . ($row->keterangan ?? '');
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

                if ($row->SATUSEHAT == 0) {
                    $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $row->id . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                } else {
                    $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $row->id . '`)" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                }

                return '<div style="min-width: 200px;">'.$btn.'</div>';
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
        $ids = $request->input('ids', []);

        // For now just return the received IDs and a success message.
        return response()->json([
            'status' => JsonResponse::HTTP_OK,
            'message' => 'Received ids for bulk send (handler stub).',
            'data' => $ids,
        ], 200);
    }

    public function sendSatuSehat($idDokumenPx)
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

        $dokter = (object)[
            'id_dokter' => ''
        ];

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


        $dateTimeNow = Carbon::now()->toIso8601String();
        $categories = [];

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

        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

        $data = [
            "resourceType" => "DiagnosticReport",
            "identifier" => [
                [
                    "system" => "http://sys-ids.kemkes.go.id/diagnostic/{$organisasi}/lab",
                    "use" => "official",
                    "value" => "$riwayat->ID_RIWAYAT_ELAB"
                ]
            ],
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
            // "effectiveDateTime" => "2012-12-01T12:00:00+01:00",
            // "issued" => "2012-12-01T12:00:00+01:00",
            "performer" => [
                [
                    "reference" => "Practitioner/{$dokter->id_dokter}",
                ],
                [
                    "reference" => "Organization/{$organisasi}"
                ]
            ],
            // "imagingStudy" => [
            //     [
            //         "reference" => "ImagingStudy/{{ImagingStudy_id}}"
            //     ]
            // ],
            "result" => [
                [
                    "reference" => "Observation/$observation->ID_SATUSEHAT_OBSERVASI",
                ]
            ],
            // "basedOn" => [
            //     [
            //         "reference" => "ServiceRequest/{{ServiceRequest_Rad}}"
            //     ]
            // ],
            'conclusion' => $dokumen_px->keterangan ?? '',
            // "specimen" => [
            //     [
            //         "reference" => "Specimen/3095e36e-1624-487e-9ee4-737387e7b55f"
            //     ]
            // ],
            // "conclusionCode" => [
            //     [
            //         "coding" => [
            //             [
            //                 "system" => "http://snomed.info/sct",
            //                 "code" => "260347006",
            //                 "display" => "+"
            //             ]
            //         ]
            //     ]
            // ]
        ];

        dd($data);

        try {
            $login = $this->login($id_unit);

            $token = $login['response']['token'];

            if (!$token) {
                throw new Exception('Gagal auth dengan satu sehat');
            }

            $data = json_decode(json_encode($data));

            dd($data);

            $diagnosticReportRequest = $this->consumeSATUSEHATAPI('POST', $baseurl, 'DiagnosticReport', $data, true, $token);

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
