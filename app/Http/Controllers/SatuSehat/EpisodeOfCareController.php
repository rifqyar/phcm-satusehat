<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\Karcis;
use App\Models\SATUSEHAT\SATUSEHAT_EPISODEOFCARE;
use App\Models\SATUSEHAT\SS_Kode_API;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\DataTables;

class EpisodeOfCareController extends Controller
{
    use SATUSEHATTraits, LogTraits;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result = [
            'total_semua' => 0,
            'total_rawat_jalan' => 0,
            'total_rawat_inap' => 0,
            'total_sudah_integrasi' => 0,
            'total_belum_integrasi' => 0,
        ];

        return view('pages.satusehat.episode-of-care.index', compact('result'));
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

        // ================= DATATABLES PAGINATION =================
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $draw   = (int) $request->input('draw', 1);

        $pageNumber = ($start / $length) + 1;
        $pageSize   = $length;

        $data = DB::select("
            EXEC dbo.sp_getDataEpisodeOfCare ?, ?, ?, ?, ?, ?
        ", [
            $id_unit,
            $tgl_awal_db,
            $tgl_akhir_db,
            $request->input('cari') == '' ? 'unmapped' : $request->input('cari'),
            $pageNumber,
            $pageSize
        ]);

        if (count($data) == 0) {
            return response()->json([
                "draw" => $draw,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
                "summary" => [
                    'total_semua' => 0,
                    'total_sudah_integrasi' => 0,
                    'total_belum_integrasi' => 0,
                    'total_rawat_jalan' => 0,
                    'total_rawat_inap' => 0,
                ]
            ]);
        }

        $summary = $data[0] ?? null;
        $totalData = [
            'total_semua' => $summary->total_semua ?? 0,
            'total_rawat_jalan' => $summary->total_rawat_jalan ?? 0,
            'total_rawat_inap' => $summary->total_rawat_inap ?? 0,
            'total_sudah_integrasi' => $summary->total_sudah_integrasi ?? 0,
            'total_belum_integrasi' => $summary->total_belum_integrasi ?? 0,
        ];
        $recordsTotal    = $summary->total_semua ?? 0;
        $recordsFiltered = $summary->recordsFiltered ?? $recordsTotal;

        $dataEpisode = [];
        $index = $start + 1;
        foreach ($data as $row) {
            $jenis = LZString::compressToEncodedURIComponent($row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI');
            $id_transaksi = LZString::compressToEncodedURIComponent($row->ID_TRANSAKSI);
            $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
            $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
            $kdLokasiSS = LZString::compressToEncodedURIComponent($row->ID_LOKASI_SS);
            $paramSatuSehat = "jenis_perawatan=" . $jenis . "&id_transaksi=" . $id_transaksi . "&kd_pasien_ss=" . $kdPasienSS . "&kd_nakes_ss=" . $kdNakesSS . "&kd_lokasi_ss=" .  $kdLokasiSS;
            $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

            $dataEpisode[] = [
                'DT_RowIndex' => $index++,
                'ID_TRANSAKSI' => $row->ID_TRANSAKSI,
                'NO_PESERTA' => $row->NO_PESERTA,
                'KBUKU' => $row->KBUKU,
                'checkbox' => $this->renderCheckbox($row, $paramSatuSehat),
                'JENIS_PERAWATAN' => $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI',
                'TANGGAL' => date('Y-m-d', strtotime($row->TANGGAL)),
                'NAMA_PASIEN' => $row->NAMA_PASIEN,
                'DOKTER' => $row->DOKTER,
                'status_integrasi' => $row->sudah_integrasi > 0
                    ? '<span class="badge badge-success">Sudah Integrasi</span>'
                    : '<span class="badge badge-danger">Belum Integrasi</span>',
                'action' => $this->renderAction($row, $paramSatuSehat),
            ];
        }

        return response()->json([
            "draw" => intval($request->draw),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $dataEpisode,
            "summary" => $totalData
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

    private function renderCheckbox($row, $paramSatuSehat = null)
    {
        $checkBox = "";
        $kondisiDasar = (
            $row->ID_PASIEN_SS != null &&
            $row->ID_NAKES_SS != null &&
            $row->ID_LOKASI_SS != null &&
            $row->sudah_integrasi == 0
        );

        if (!$kondisiDasar) {
            return;
        } else {
            $checkBox = "
                        <input type='checkbox' class='select-row chk-col-purple' value='$row->ID_TRANSAKSI' data-param='$paramSatuSehat' id='$row->ID_TRANSAKSI' />
                        <label for='$row->ID_TRANSAKSI' style='margin-bottom: 0px !important; line-height: 25px !important; font-weight: 500'> &nbsp; </label>
                    ";
        }

        return $checkBox;
    }

    private function renderAction($row, $paramSatuSehat = null)
    {
        $btn = '';
        if ($row->ID_PASIEN_SS == null) {
            $btn = '<i class="text-muted">Pasien Belum Mapping</i>';
        } else if (($row->DOKTER == null || $row->KODE_DOKTER == null) && $row->JENIS_PERAWATAN == 'RAWAT_INAP') {
            $btn .= '<i class="text-muted">Dokter DPJP Belum Dipilih</i>';
        } else if ($row->ID_NAKES_SS == null) {
            $btn .= '<i class="text-muted">Nakes Belum Mapping</i>';
        } else if ($row->ID_LOKASI_SS == null) {
            $btn .= '<i class="text-muted">Lokasi Belum Mapping</i>';
        } else {
            if ($row->sudah_integrasi == '0') {
                $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
            } else {
                $btn = '<a href="javascript:void(0)" onclick="resendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
            }
        }

        return $btn;
    }

    public function send(Request $request, $resend = false)
    {
        /**
         * TO DO: Implementasi Send Episode Of Care to SatuSehat FHIR Server
         * 1. pengiriman episode of care di awal pemeriksaan (setelah ada encounter & condition) ✅
         * 2. status awal = active ✅
         * 3. update episode of care (resend)
         * 4. saat resend jika pengobatan sudah selesai (pasien pulang / discharge) maka status = finished ✅
         * 5. jika masih dalam perawatan maka status = active ✅
         * 6. catatan: episode of care harus terintegrasi setelah encounter & condition ✅
         */

        $id_unit = Session::get('id_unit', '001');
        $param = $request->param;
        $params = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('&', $params);

        $arrParam = [];
        for ($i = 0; $i < count($parts); $i++) {
            $partsParam = explode('=', $parts[$i]);
            $key = $partsParam[0];
            $val = $partsParam[1];
            $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
        }

        $data = collect(DB::select("
            EXEC dbo.sp_getDataEpisodeOfCare ?, ?, ?, ?, ?, ?, ?
        ", [
            $id_unit,
            null,
            null,
            'all',
            1,
            1,
            $arrParam['id_transaksi']
        ]))->first();

        $baseurl = '';
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

        try {
            $satusehatPayload = $this->buildSatusehatParam($arrParam, $data, $organisasi);

            if ($resend) {
                $currData = SATUSEHAT_EPISODEOFCARE::where('KARCIS', $arrParam['id_transaksi'])
                    ->where('NO_PESERTA', $data->NO_PESERTA)
                    ->where('ID_UNIT', $id_unit)
                    ->select('ID_SATUSEHAT_EPISODE_OF_CARE')
                    ->first();
                $satusehatPayload['id'] = $currData->ID_SATUSEHAT_EPISODE_OF_CARE;
            }

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }
            $token = $login['response']['token'];

            $url = $resend ? 'EpisodeOfCare/' . $currData->ID_SATUSEHAT_EPISODE_OF_CARE : 'EpisodeOfCare';
            $dataEpisodeOfCare = $this->consumeSATUSEHATAPI($resend ? 'PUT' : 'POST', $baseurl, $url, $satusehatPayload, true, $token);
            $result = json_decode($dataEpisodeOfCare->getBody()->getContents(), true);

            if ($dataEpisodeOfCare->getStatusCode() >= 400) {
                $response = json_decode($dataEpisodeOfCare->getBody(), true);

                $this->logError('EpisodeOfCare', 'Gagal kirim data EpisodeOfCare', [
                    'payload' => $satusehatPayload,
                    'response' => $response,
                    'user_id' => Session::get('nama', 'system') //Session::get('id')
                ]);

                $this->logDb(json_encode($response), 'EpisodeOfCare', json_encode($satusehatPayload), 'system'); //Session::get('id')

                $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Kirim Data EpisodeOfCare';
                throw new Exception($msg, $dataEpisodeOfCare->getStatusCode());
            } else {
                DB::beginTransaction();
                try {
                    $EpisodeOfCare_satusehat = SATUSEHAT_EPISODEOFCARE::firstOrCreate(
                        [
                            'KARCIS' => $data->ID_TRANSAKSI,
                            'NO_PESERTA' => $data->NO_PESERTA,
                            'ID_UNIT' => $id_unit,
                        ],
                        [
                            'KBUKU' => $data->KBUKU,
                            'ID_SATUSEHAT_EPISODE_OF_CARE' => $result['id'],
                            'ID_SATUSEHAT_ENCOUNTER' => $data->ID_SATUSEHAT_ENCOUNTER,
                            'JENIS_PERAWATAN' => $data->JENIS_PERAWATAN,
                            'CRTUSR' => Session::get('nama', 'system'),
                            'CRTDT' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        ]
                    );

                    $this->logInfo('EpisodeOfCare', 'Sukses kirim data EpisodeOfCare', [
                        'payload' => $satusehatPayload,
                        'response' => $result,
                        'user_id' => Session::get('nama', 'system') //Session::get('id')
                    ]);
                    $this->logDb(json_encode($result), 'EpisodeOfCare', json_encode($satusehatPayload), 'system'); //Session::get('id')

                    DB::commit();
                    return response()->json([
                        'status' => JsonResponse::HTTP_OK,
                        'message' => 'Berhasil Kirim Data EpisodeOfCare',
                        'redirect' => [
                            'need' => false,
                            'to' => null,
                        ]
                    ], 200);
                } catch (Exception $th) {
                    DB::rollBack();
                    throw new Exception($th->getMessage(), 500);
                }
            }
        } catch (Exception $th) {
            return response()->json([
                'status' => [
                    'msg' => $th->getMessage() != '' ? $th->getMessage() : 'Err',
                    'code' => $th->getCode() != '' ? $th->getCode() : 500,
                ],
                'data' => null,
                'err_detail' => $th->getTrace(),
                'message' => $th->getMessage() != '' ? $th->getMessage() : 'Terjadi Kesalahan Saat Kirim Data, Harap Coba lagi!'
            ], 500);
        }
    }

    private function buildSatusehatParam($arrParam, $data, $organisasi)
    {
        //Get Condition if Exists
        $condition = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_DIAGNOSA')
            ->where('idunit', Session::get('id_unit', '001'))
            ->where('karcis', $arrParam['id_transaksi'])
            ->first();

        $id_unit = Session::get('id_unit', '001');
        $dataKarcis = Karcis::leftJoin('RJ_KARCIS_BAYAR AS KarcisBayar', function ($query) use ($arrParam, $id_unit) {
            $query->on('RJ_KARCIS.KARCIS', '=', 'KarcisBayar.KARCIS')
                ->on('RJ_KARCIS.IDUNIT', '=', 'KarcisBayar.IDUNIT')
                ->whereRaw('ISNULL(KarcisBayar.STBTL,0) = 0')
                ->where('KarcisBayar.IDUNIT', $id_unit); // pindahkan ke sini
        })
            ->with([
                'ermkunjung' => function ($query) use ($arrParam, $id_unit) {
                    $query->select('KARCIS', 'NO_KUNJUNG', 'CRTDT AS WAKTU_ERM')
                        ->where('IDUNIT', $id_unit);
                }
            ])
            ->with('inap')
            ->select('RJ_KARCIS.NOREG', 'RJ_KARCIS.KARCIS', 'RJ_KARCIS.KBUKU', 'RJ_KARCIS.NO_PESERTA', 'RJ_KARCIS.KLINIK', 'RJ_KARCIS.KDDOK', 'RJ_KARCIS.TGL_VERIF_KARCIS', 'RJ_KARCIS.CRTDT AS WAKTU_BUAT_KARCIS', 'KarcisBayar.TGL_CETAK AS WAKTU_NOTA', 'KarcisBayar.NOTA', 'RJ_KARCIS.TGL')
            ->where(function ($query) use ($arrParam) {
                if ($arrParam['jenis_perawatan'] == 'RI') {
                    $query->where('RJ_KARCIS.NOREG', $arrParam['id_transaksi']);
                } else {
                    $query->where('RJ_KARCIS.KARCIS', $arrParam['id_transaksi']);
                }
            })
            ->where('RJ_KARCIS.IDUNIT', $id_unit)
            ->first();

        $status = 'waitlist';
        $statusHistory = [];

        if ($dataKarcis->inap) {
            $historyTime = $this->getHistoryTime($dataKarcis);
            $historyTimeInap = $this->getHistoryTimeInap($dataKarcis);
            if ($dataKarcis->inap->TGL_KELUAR != null) {
                $status = 'finished';
                $statusHistory = [
                    [
                        "status" => 'active',
                        "period" => [
                            "start" => Carbon::createFromFormat('Y-m-d H:i:s', $historyTime['jam_start'])->toIso8601String(),
                            "end" => Carbon::createFromFormat('Y-m-d H:i:s', $historyTimeInap['jam_finish'])->toIso8601String()
                        ]
                    ],
                    [
                        "status" => $status,
                        "period" => [
                            "start" => Carbon::createFromFormat('Y-m-d H:i:s', $historyTimeInap['jam_finish'])->toIso8601String(),
                            "end" => Carbon::createFromFormat('Y-m-d H:i:s', $historyTimeInap['jam_finish'])->toIso8601String()
                        ]
                    ]
                ];
            } else {
                $status = 'active';
                $statusHistory = [
                    [
                        "status" => $status,
                        "period" => [
                            "start" => Carbon::createFromFormat('Y-m-d H:i:s', $historyTimeInap['jam_start'])->toIso8601String(),
                        ]
                    ]
                ];
            }
        } else {
            $historyTime = $this->getHistoryTime($dataKarcis);
            if ($dataKarcis->NOTA != null && $dataKarcis->WAKTU_NOTA != null) {
                $status = 'finished';

                $statusHistory = [
                    [
                        "status" => "active",
                        "period" => [
                            "start" => Carbon::parse($historyTime['jam_start'])->toIso8601String(),
                            "end"   => Carbon::parse($historyTime['jam_finish'])->toIso8601String(),
                        ]
                    ],
                    [
                        "status" => "finished",
                        "period" => [
                            "start" => Carbon::parse($historyTime['jam_finish'])->toIso8601String(),
                            "end"   => Carbon::parse($historyTime['jam_finish'])->toIso8601String(),
                        ]
                    ]
                ];
            } else {
                $status = 'active';
                $statusHistory = [
                    [
                        "status" => "active",
                        "period" => [
                            "start" => Carbon::parse($historyTime['jam_start'])->toIso8601String(),
                        ]
                    ]
                ];
            }
        }

        $diagnosisRole = ($status === 'finished')
            ? ['DD', 'Discharged Diagnosis']
            : ['AD', 'Admission Diagnosis'];

        $diagnosis = [
            [
                "condition" => [
                    "reference" => "Condition/{$condition->id_satusehat_condition}",
                    "display"   => $condition->nama_diagnosa ?? null,
                ],
                "role" => [
                    "coding" => [
                        [
                            "system"  => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                            "code"    => $diagnosisRole[0],
                            "display" => $diagnosisRole[1],
                        ]
                    ]
                ],
                "rank" => 1
            ]
        ];

        $type = [];

        $dxKhusus = DB::table('E_RM_PHCM.dbo.ERM_DX_KHUSUSPX')
            ->where('KARCIS', $arrParam['id_transaksi'])
            ->orWhere('NOREG', $arrParam['id_transaksi'])
            ->join(
                'E_RM_PHCM.dbo.ERM_MASTER_DX_KHUSUS',
                'ERM_DX_KHUSUSPX.ID_DX_KHUSUS',
                '=',
                'ERM_MASTER_DX_KHUSUS.ID_DX_KHUSUS'
            )
            ->select('EPISODEOFCARE_TYPE')
            ->first();

        if ($dxKhusus) {
            $type[] = [
                "coding" => [
                    [
                        "system"  => "http://terminology.kemkes.go.id/CodeSystem/episodeofcare-type",
                        "code"    => $dxKhusus->EPISODEOFCARE_TYPE,
                    ]
                ]
            ];
        }


        $period = [
            "start" => Carbon::parse($data->TANGGAL)->toIso8601String(),
        ];

        if ($status == 'finished') {
            $period['end'] = Carbon::parse(
                $dataKarcis->inap
                    ? $historyTimeInap['jam_finish']
                    : $historyTime['jam_finish']
            )->toIso8601String();
        }

        $identifier = now()->timestamp;
        $payload = [
            "resourceType" => "EpisodeOfCare",
            "status" => $status,
            "statusHistory" => $statusHistory,
            "type" => $type,
            "diagnosis" => $diagnosis,
            "patient" => [
                "reference" => "Patient/$data->ID_PASIEN_SS",
                "display" => $data->NAMA_PASIEN,
            ],
            'managingOrganization' => [
                "reference" => "Organization/$organisasi",
            ],
            "period" => $period,
        ];
        return $payload;
    }

    private function getHistoryTimeInap($dataKarcis)
    {
        $waktu_buat_karcis = $dataKarcis->WAKTU_BUAT_KARCIS
            ? Carbon::parse($dataKarcis->WAKTU_BUAT_KARCIS, 'Asia/Jakarta')
            : null;

        $waktu_verif_karcis = null;
        if ($dataKarcis->inap && $dataKarcis->inap->tgmas && $dataKarcis->inap->jamas) {
            $waktu_verif_karcis = Carbon::parse($dataKarcis->inap->tgmas, 'Asia/Jakarta')
                ->setTimeFromTimeString($dataKarcis->inap->jamas);
        }

        $waktu_nota = null;
        if ($dataKarcis->inap && $dataKarcis->inap->tgl_plng && $dataKarcis->inap->jam_plng) {
            $waktu_nota = Carbon::parse($dataKarcis->inap->tgl_plng, 'Asia/Jakarta')
                ->setTimeFromTimeString($dataKarcis->inap->jam_plng);
        }

        $waktu_erm = ($dataKarcis->ermkunjung && $dataKarcis->ermkunjung->WAKTU_ERM)
            ? Carbon::parse($dataKarcis->ermkunjung->WAKTU_ERM, 'Asia/Jakarta')
            : null;

        // ======================================
        // Fallback jika nota null (belum pulang)
        // ======================================
        if (!$waktu_nota) {
            $waktu_nota = $waktu_erm
                ?? $waktu_verif_karcis
                ?? $waktu_buat_karcis
                ?? Carbon::now('Asia/Jakarta');
        }

        // ======================
        // JAM START
        // ======================
        if ($waktu_verif_karcis && $waktu_buat_karcis) {
            $jam_start = $waktu_verif_karcis >= $waktu_buat_karcis
                ? $waktu_verif_karcis
                : $waktu_buat_karcis;
        } else {
            $jam_start = $waktu_verif_karcis ?? $waktu_buat_karcis ?? Carbon::now('Asia/Jakarta');
        }

        // ======================
        // JAM PROGRESS
        // Rawat inap: progress = ERM (jika ada)
        // ======================
        $jam_progress = $waktu_erm ?? $waktu_nota;

        // ======================
        // JAM FINISH
        // ======================
        if ($jam_progress && $waktu_erm && $jam_progress == $waktu_erm) {
            $jam_finish = $waktu_nota;
        } else {
            $jam_finish = $jam_progress
                ? Carbon::parse($jam_progress->toDateTimeString(), 'Asia/Jakarta')->addMinutes(rand(3, 6))
                : $waktu_nota;
        }

        // ==========================================
        // Koreksi jika progress lebih awal dari start
        // ==========================================
        if ($jam_progress && $jam_start && $jam_progress < $jam_start) {
            $selisih = $jam_start->diffInMinutes($jam_progress);
            $acak = rand(3, 10);
            $jam_start->subMinutes($selisih + $acak);
        }

        // ==========================================
        // Koreksi jika finish tidak boleh < progress
        // ==========================================
        if ($jam_finish && $jam_progress && $jam_finish <= $jam_progress) {
            $selisih = $jam_finish->diffInMinutes($jam_progress);
            $jam_finish->addMinutes($selisih + rand(6, 10));
        }

        return [
            'jam_start' => $jam_start,
            'jam_progress' => $jam_progress,
            'jam_finish' => $jam_finish
        ];
    }

    private function getHistoryTime($dataKarcis)
    {
        $tz = 'Asia/Jakarta';

        // ===============================
        // PARSE SEMUA WAKTU (IMMUTABLE)
        // ===============================
        $waktu_buat_karcis = $dataKarcis->WAKTU_BUAT_KARCIS
            ? Carbon::parse($dataKarcis->WAKTU_BUAT_KARCIS, $tz)
            : null;

        $waktu_verif_karcis = $dataKarcis->TGL_VERIF_KARCIS
            ? Carbon::parse($dataKarcis->TGL_VERIF_KARCIS, $tz)
            : null;

        $waktu_nota = $dataKarcis->WAKTU_NOTA
            ? Carbon::parse($dataKarcis->WAKTU_NOTA, $tz)
            : null;

        $waktu_erm = (
            $dataKarcis->ermkunjung &&
            $dataKarcis->ermkunjung->WAKTU_ERM
        )
            ? Carbon::parse($dataKarcis->ermkunjung->WAKTU_ERM, $tz)
            : null;

        // ===============================
        // FALLBACK NOTA
        // ===============================
        if (!$waktu_nota) {
            $waktu_nota = collect([
                $waktu_erm,
                $waktu_verif_karcis,
                $waktu_buat_karcis,
            ])->filter()->max();

            if (!$waktu_nota) {
                $waktu_nota = Carbon::now($tz);
            }
        }

        // ===============================
        // JAM START (PALING AKHIR DARI BUAT / VERIF)
        // ===============================
        $jam_start = collect([
            $waktu_buat_karcis,
            $waktu_verif_karcis,
        ])->filter()->max() ?? Carbon::now($tz);

        // ===============================
        // JAM PROGRESS (PALING AWAL ERM / NOTA)
        // ===============================
        $jam_progress = collect([
            $waktu_erm,
            $waktu_nota,
        ])->filter()->min() ?? $jam_start->copy()->addMinutes(5);

        // ===============================
        // NORMALISASI START ≤ PROGRESS
        // ===============================
        if ($jam_progress->lt($jam_start)) {
            $jam_start = $jam_progress
                ->copy()
                ->subMinutes(rand(3, 6));
        }

        // ===============================
        // JAM FINISH
        // ===============================
        if ($waktu_erm && $jam_progress->equalTo($waktu_erm)) {
            $jam_finish = $waktu_nota->copy();
        } else {
            $jam_finish = $jam_progress
                ->copy()
                ->addMinutes(rand(3, 6));
        }

        // ===============================
        // NORMALISASI PROGRESS ≤ FINISH
        // ===============================
        if ($jam_finish->lte($jam_progress)) {
            $jam_finish = $jam_progress
                ->copy()
                ->addMinutes(rand(6, 10));
        }

        return [
            'jam_start'    => $jam_start,
            'jam_progress' => $jam_progress,
            'jam_finish'   => $jam_finish,
        ];
    }

    public function resend(Request $request)
    {
        $id_unit = Session::get('id_unit', '001');
        $param = $request->param;
        $params = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('&', $params);

        $arrParam = [];
        for ($i = 0; $i < count($parts); $i++) {
            $partsParam = explode('=', $parts[$i]);
            $key = $partsParam[0];
            $val = $partsParam[1];
            $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
        }

        $data = collect(DB::select("
            EXEC dbo.sp_getDataEpisodeOfCare ?, ?, ?, ?, ?, ?, ?
        ", [
            $id_unit,
            null,
            null,
            'all',
            1,
            1,
            $arrParam['id_transaksi']
        ]))->first();

        $baseurl = '';
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

        $currData = SATUSEHAT_EPISODEOFCARE::where('KARCIS', $arrParam['id_transaksi'])
            ->where('NO_PESERTA', $data->NO_PESERTA)
            ->where('ID_UNIT', $id_unit)
            ->select('ID_SATUSEHAT_EPISODE_OF_CARE')
            ->first();

        $dataKarcis = Karcis::leftJoin('RJ_KARCIS_BAYAR AS KarcisBayar', function ($query) use ($arrParam, $id_unit) {
            $query->on('RJ_KARCIS.KARCIS', '=', 'KarcisBayar.KARCIS')
                ->on('RJ_KARCIS.IDUNIT', '=', 'KarcisBayar.IDUNIT')
                ->whereRaw('ISNULL(KarcisBayar.STBTL,0) = 0')
                ->where('KarcisBayar.IDUNIT', $id_unit); // pindahkan ke sini
        })
            ->with([
                'ermkunjung' => function ($query) use ($arrParam, $id_unit) {
                    $query->select('KARCIS', 'NO_KUNJUNG', 'CRTDT AS WAKTU_ERM')
                        ->where('IDUNIT', $id_unit);
                }
            ])
            ->with('inap')
            ->select('RJ_KARCIS.NOREG', 'RJ_KARCIS.KARCIS', 'RJ_KARCIS.KBUKU', 'RJ_KARCIS.NO_PESERTA', 'RJ_KARCIS.KLINIK', 'RJ_KARCIS.KDDOK', 'RJ_KARCIS.TGL_VERIF_KARCIS', 'RJ_KARCIS.CRTDT AS WAKTU_BUAT_KARCIS', 'KarcisBayar.TGL_CETAK AS WAKTU_NOTA', 'KarcisBayar.NOTA', 'RJ_KARCIS.TGL')
            ->where(function ($query) use ($arrParam) {
                if ($arrParam['jenis_perawatan'] == 'RI') {
                    $query->where('RJ_KARCIS.NOREG', $arrParam['id_transaksi']);
                } else {
                    $query->where('RJ_KARCIS.KARCIS', $arrParam['id_transaksi']);
                }
            })
            ->where('RJ_KARCIS.IDUNIT', $id_unit)
            ->first();

        try {
            if ($dataKarcis->inap) {
                $historyTime = $this->getHistoryTime($dataKarcis);
                $historyTimeInap = $this->getHistoryTimeInap($dataKarcis);
                if ($dataKarcis->inap->TGL_KELUAR !== null) {

                    $finishedTime = Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $historyTimeInap['jam_finish']
                    )->toIso8601String();

                    $patchPayload[] = [
                        'op'    => 'replace',
                        'path'  => '/status',
                        'value' => 'finished',
                    ];

                    $patchPayload[] = [
                        'op'   => 'add',
                        'path' => '/statusHistory/-',
                        'value' => [
                            'status' => 'finished',
                            'period' => [
                                'start' => $finishedTime,
                            ],
                        ],
                    ];

                    $patchPayload[] = [
                        'op'    => 'add',
                        'path'  => '/period/end',
                        'value' => $finishedTime,
                    ];
                }
            } else {
                $historyTime = $this->getHistoryTime($dataKarcis);
                if ($dataKarcis->NOTA !== null && $dataKarcis->WAKTU_NOTA !== null) {
                    $finishedTime = Carbon::parse(
                        $historyTime['jam_finish']
                    )->toIso8601String();

                    $patchPayload[] = [
                        'op'    => 'replace',
                        'path'  => '/status',
                        'value' => 'finished',
                    ];

                    $patchPayload[] = [
                        'op'   => 'add',
                        'path' => '/statusHistory/-',
                        'value' => [
                            'status' => 'finished',
                            'period' => [
                                'start' => $finishedTime,
                            ],
                        ],
                    ];

                    $patchPayload[] = [
                        'op'    => 'add',
                        'path'  => '/period/end',
                        'value' => $finishedTime,
                    ];
                }
            }

            if (empty($patchPayload)) {
                return response()->json([
                    'status' => JsonResponse::HTTP_OK,
                    'message' => 'EpisodeOfCare belum selesai, tidak perlu kirim ulang',
                    'data' => null,
                    'redirect' => [
                        'need' => false,
                        'to' => null,
                    ]
                ], 200);
            }

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }
            $token = $login['response']['token'];

            $url = 'EpisodeOfCare/' . $currData->ID_SATUSEHAT_EPISODE_OF_CARE;
            $dataEpisodeOfCare = $this->consumeSATUSEHATAPI('PATCH', $baseurl, $url, $patchPayload, true, $token);
            $result = json_decode($dataEpisodeOfCare->getBody()->getContents(), true);

            if ($dataEpisodeOfCare->getStatusCode() >= 400) {
                $response = json_decode($dataEpisodeOfCare->getBody(), true);

                $this->logError('EpisodeOfCare', 'Gagal kirim data EpisodeOfCare', [
                    'payload' => $patchPayload,
                    'response' => $response,
                    'user_id' => Session::get('nama', 'system') //Session::get('id')
                ]);

                $this->logDb(json_encode($response), 'EpisodeOfCare', json_encode($patchPayload), 'system');

                $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Update Data EpisodeOfCare';
                throw new Exception($msg, $dataEpisodeOfCare->getStatusCode());
            } else {
                DB::beginTransaction();
                try {
                    $this->logInfo('EpisodeOfCare', 'Sukses Update data EpisodeOfCare', [
                        'payload' => $patchPayload,
                        'response' => $result,
                        'user_id' => Session::get('nama', 'system')
                    ]);
                    $this->logDb(json_encode($result), 'EpisodeOfCare', json_encode($patchPayload), 'system');

                    DB::commit();
                    return response()->json([
                        'status' => JsonResponse::HTTP_OK,
                        'message' => 'Berhasil Update Data EpisodeOfCare',
                        'redirect' => [
                            'need' => false,
                            'to' => null,
                        ]
                    ], 200);
                } catch (Exception $th) {
                    DB::rollBack();
                    throw new Exception($th->getMessage(), 500);
                }
            }
        } catch (Exception $th) {
            return response()->json([
                'status' => [
                    'msg' => $th->getMessage() != '' ? $th->getMessage() : 'Err',
                    'code' => $th->getCode() != '' ? $th->getCode() : 500,
                ],
                'data' => null,
                'err_detail' => $th->getTrace(),
                'message' => $th->getMessage() != '' ? $th->getMessage() : 'Terjadi Kesalahan Saat Kirim Data, Harap Coba lagi!'
            ], 500);
        }
        return $this->send(new Request($request->all()), true);
    }
}
