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
                
                // $openFileBtn = '<button type="button" class="btn btn-success btn-sm mr-1" onclick="openFile(\'' . url('assets/dokumen_px/' . $row->kbuku . '/' . $row->file_name) . '\')">
                //     <i class="fa fa-search"></i> Lihat File
                // </button>';

                $openFileBtn = '';

                $sendBtn = '<button class="btn btn-primary btn-sm mr-1" onclick="sendSatuSehat()">
                    <i class="fa fa-link"></i> Kirim Satu Sehat
                </button>';

                return '
                    <div class="d-flex align-items-stretch">
                        ' . $openFileBtn . $sendBtn . '
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

    public function sendSatuSehat($param)
    {
        $param = base64_decode($param);
        $param = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('+', $param);

        $idRiwayatElab = LZString::decompressFromEncodedURIComponent($parts[0]);
        $karcisAsal = LZString::decompressFromEncodedURIComponent($parts[1]);
        $karcisRujukan = LZString::decompressFromEncodedURIComponent($parts[2]);
        $kdKlinik = LZString::decompressFromEncodedURIComponent($parts[3]);
        $kdPasienSS = LZString::decompressFromEncodedURIComponent($parts[4]);
        $kdNakesSS = LZString::decompressFromEncodedURIComponent($parts[5]);
        $kdDokterSS = LZString::decompressFromEncodedURIComponent($parts[6]);

        $id_unit      = '001'; // session('id_klinik');
        $status = '';

        $dateTimeNow = Carbon::now()->toIso8601String();

        $categories = [
            [
                "coding" => [
                    [
                        "system" => "http://terminology.hl7.org/CodeSystem/v2-0074",
                        "code" => "MB",
                        "display" => "Microbiology"
                    ]
                ]
            ]
        ];

        $codings = [
            "coding" => [
                [
                    "system" => "http://loinc.org",
                    "code" => "11477-7",
                    "display" => "Microscopic observation [Identifier] in Sputum by Acid fast stain"
                ]
            ]
        ];

        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

        $patient = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN')
            ->where('idpx', $kdPasienSS)
            ->first();

        $encounter = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
            ->where('karcis', $karcisAsal)
            ->where('idunit', $id_unit)
            ->first();

        $data = [
            "resourceType" => "DiagnosticReport",
            "identifier" => [
                [
                    "system" => "http://sys-ids.kemkes.go.id/diagnostic/{$organisasi}/lab",
                    "use" => "official",
                    "value" => "$idRiwayatElab"
                ]
            ],
            "status" => "$status",
            "category" => $categories,
            "code" => $codings,
            "subject" => [
                "reference" => "Patient/{$kdPasienSS}",
                "display" => "$patient->nama"
            ],
            "encounter" => [
                "reference" => "Encounter/{$encounter->id_satusehat_encounter}"
            ],
            "effectiveDateTime" => "2012-12-01T12:00:00+01:00",
            "issued" => "2012-12-01T12:00:00+01:00",
            "performer" => [
                [
                    "reference" => "Practitioner/{$kdDokterSS}",
                ],
                [
                    "reference" => "Organization/{{$organisasi}}"
                ]
            ],
            "result" => [
                [
                    "reference" => "Observation/dc0b1b9c-d2c8-4830-b8bb-d73c68174f02"
                ]
            ],
            "specimen" => [
                [
                    "reference" => "Specimen/3095e36e-1624-487e-9ee4-737387e7b55f"
                ]
            ],
            "conclusionCode" => [
                [
                    "coding" => [
                        [
                            "system" => "http://snomed.info/sct",
                            "code" => "260347006",
                            "display" => "+"
                        ]
                    ]
                ]
            ]
        ];

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
                    'user_id' => 'system'
                ]);

                $this->logDb(json_encode($response), 'Specimen', json_encode($data), 'system');

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
