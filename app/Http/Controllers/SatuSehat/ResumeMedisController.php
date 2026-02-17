<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Jobs\SendResumeMedis;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\Karcis;
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

class ResumeMedisController extends Controller
{
    use SATUSEHATTraits, LogTraits;

    public function index()
    {
        return view('pages.satusehat.resume-medis.index');
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $search = $request->input('cari');
        $id_unit = Session::get('id_unit', '001');

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

        $dataResumeMedis = collect(DB::select("
            EXEC dbo.sp_getDataComposition ?, ?, ?, ?
        ", [
            $id_unit,
            $tgl_awal_db,
            $tgl_akhir_db,
            $search ?? 'unmapped'
        ]));

        $summary = $dataResumeMedis->first();
        // dd($summary);

        $totalData = [
            'total_semua' => $summary->total_semua ?? 0,
            'rjAll' => $summary->rjAll ?? 0,
            'ri' => $summary->ri ?? 0,
            'total_sudah_integrasi' => $summary->total_sudah_integrasi ?? 0,
            'total_belum_integrasi' => $summary->total_belum_integrasi ?? 0,
        ];

        return DataTables::of($dataResumeMedis)
            ->addIndexColumn()
            ->addColumn('checkbox', function ($row) {
                $checkBox = '';
                $jenisPerawatan = $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
                $id_transaksi = LZString::compressToEncodedURIComponent($row->ID_TRANSAKSI);
                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                $kdLokasiSS = LZString::compressToEncodedURIComponent($row->ID_LOKASI_SS);
                $paramSatuSehat = LZString::compressToEncodedURIComponent($jenisPerawatan . '+' . $id_transaksi . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' .  $kdLokasiSS);

                $checkBox = "";

                $kondisiDasar = (
                    $row->ID_PASIEN_SS != null &&
                    $row->ID_NAKES_SS != null &&
                    $row->ID_LOKASI_SS != null &&
                    $row->JUMLAH_NOTA_SATUSEHAT > 0 &&
                    $row->JUMLAH_RESUME_MEDIS == 0
                );

                $rawatInapInvalid = (
                    $row->JENIS_PERAWATAN == 'RAWAT_INAP' &&
                    ($row->DOKTER == null || $row->KODE_DOKTER == null)
                );

                if (!$kondisiDasar) {
                    return;
                } else if ($rawatInapInvalid) {
                    return;
                } else if (
                    $row->JENIS_PERAWATAN == 'RAWAT_JALAN' &&
                    ($row->JUMLAH_RESUME_MEDIS > 0 || $row->JUMLAH_NOTA_SATUSEHAT == 0)
                ) {
                    return;
                } else {
                    $checkBox = "
                        <input type='checkbox' class='select-row chk-col-purple' value='$paramSatuSehat' id='$paramSatuSehat' />
                        <label for='$paramSatuSehat' style='margin-bottom: 0px !important; line-height: 25px !important; font-weight: 500'> &nbsp; </label>
                    ";
                }

                return $checkBox;
            })
            ->editColumn('JENIS_PERAWATAN', function ($row) {
                return $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'Rawat Jalan' : 'Rawat Inap';
            })
            ->editColumn('TANGGAL', function ($row) {
                return date('Y-m-d', strtotime($row->TANGGAL));
            })
            // ->editColumn('STATUS_SELESAI', function ($row) {
            //     if ($row->JENIS_PERAWATAN == 'RAWAT_JALAN') {
            //         if ($row->STATUS_SELESAI == "9" || $row->STATUS_SELESAI == "10") {
            //             return '<span class="badge badge-pill badge-secondary p-2 w-100">Belum Verif</span>';
            //         } else {
            //             return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Verif</span>';
            //         }
            //     } else {
            //         return $row->STATUS_SELESAI == 1 ? '<span class="badge badge-pill badge-success p-2 w-100">Sudah Pulang</span>' : '<span class="badge badge-pill badge-secondary p-2 w-100">Belum Pulang</span>';
            //     }
            // })
            ->addColumn('action', function ($row) {
                $jenisPerawatan = $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
                $id_transaksi = LZString::compressToEncodedURIComponent($row->ID_TRANSAKSI);
                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                $kdLokasiSS = LZString::compressToEncodedURIComponent($row->ID_LOKASI_SS);
                $paramSatuSehat = LZString::compressToEncodedURIComponent($jenisPerawatan . '+' . $id_transaksi . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' .  $kdLokasiSS);

                $btn = '';
                $btnDetail = '<button type="button" class="btn btn-sm btn-info" onclick="lihatDetail(\'' . $row->ID_TRANSAKSI . '\')"><i class="fas fa-info-circle mr-2"></i>Lihat Detail</button>';
                if ($row->ID_PASIEN_SS == null) {
                    $btn = '<i class="text-muted">Pasien Belum Mapping</i>';
                } else if (($row->DOKTER == null || $row->KODE_DOKTER == null) && $row->JENIS_PERAWATAN == 'RAWAT_INAP') {
                    $btn .= '<i class="text-muted">Dokter DPJP Belum Dipilih</i>';
                } else if ($row->ID_NAKES_SS == null) {
                    $btn .= '<i class="text-muted">Nakes Belum Mapping</i>';
                } else if ($row->ID_LOKASI_SS == null) {
                    $btn .= '<i class="text-muted">Lokasi Belum Mapping</i>';
                } else {
                    if ($row->JENIS_PERAWATAN == 'RAWAT_JALAN') {
                        if ($row->JUMLAH_NOTA_SATUSEHAT == 0) {
                            $btn = '<i class="text-muted">Encounter belum kirim Satu Sehat</i>';
                        } else {
                            if ($row->JUMLAH_RESUME_MEDIS > 0) {
                                $btn = '<a href="javascript:void(0)" onclick="resendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-warning"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                            } else {
                                $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                            }
                        }
                    } else {
                        if ($row->JUMLAH_NOTA_SATUSEHAT == 0) {
                            $btn = '<i class="text-muted">Encounter belum kirim Satu Sehat</i>';
                        } else {
                            if ($row->JUMLAH_RESUME_MEDIS > 0) {
                                $btn = '<a href="javascript:void(0)" onclick="resendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-warning"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                            } else {
                                $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                            }
                        }
                    }
                }
                // $btn .= '<br>';
                // $btn .= '<a href="' . route('satusehat.encounter.lihat-erm', $param) . '" class="mt-2 btn btn-sm btn-info w-100"><i class="fas fa-info-circle mr-2"></i>Lihat ERM</a>';
                return $btnDetail . ' ' . $btn;
                // return $btn;
            })
            ->addColumn('status_integrasi', function ($row) {
                if ($row->JUMLAH_RESUME_MEDIS > 0) {
                    return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                } else {
                    return '<span class="badge badge-pill badge-danger p-2 w-100">Belum Integrasi</span>';
                }
            })
            ->rawColumns(['action', 'status_integrasi', 'checkbox'])
            ->with($totalData)
            ->make(true);
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

    public function lihatDetail($param)
    {
        // decode param = karcis
        $decoded = base64_decode($param);

        $dataPasien = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as a')
            ->select(
                'b.NAMA as NAMA',
                'a.kbuku as KBUKU',
                'a.no_peserta as NO_PESERTA',
                'a.karcis as KARCIS',
                'a.kddok as KODE_DOKTER',
                'c.nama as DOKTER',
                DB::raw('COUNT(DISTINCT d.id_satusehat_composition) as STATUS_INTEGRATED')
            )
            ->leftJoin('SIRS_PHCM.dbo.RIRJ_MASTERPX as b', 'a.no_peserta', '=', 'b.NO_PESERTA')
            ->leftJoin('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES as c', 'a.kddok', '=', 'c.kddok')
            ->leftJoin('SATUSEHAT.dbo.SATUSEHAT_LOG_COMPOSITION as d', function ($join) {
                $join->on('a.karcis', '=', 'd.karcis')
                    ->on('a.id_satusehat_encounter', '=', 'd.id_satusehat_encounter');
            })
            ->where('a.karcis', $decoded)
            ->groupBy(
                'b.NAMA',
                'a.kbuku',
                'a.no_peserta',
                'a.karcis',
                'a.kddok',
                'c.nama'
            )
            ->first();

        $dataAlergi = DB::table('E_RM_PHCM.dbo.ERM_RM_IRJA as eri')
            ->select([
                'ead.JENIS',
                'ead.ALERGEN',
                'ead.ID_ALERGEN_SS'
            ])
            ->leftJoin('E_RM_PHCM.dbo.ERM_ALERGIPX as ea', function ($join) {
                $join->on('eri.KARCIS', '=', 'ea.KARCIS');
            })
            ->leftJoin('E_RM_PHCM.dbo.ERM_ALERGIPX_DTL as ead', function ($join) {
                $join->on('ea.ID_ALERGI_PX', '=', 'ead.ID_HDR');
            })
            ->where('eri.KARCIS', $decoded)
            ->groupBy([
                'ead.JENIS',
                'ead.ALERGEN',
                'ead.ID_ALERGEN_SS'
            ])
            ->where('ea.STATUS_AKTIF', '1')
            ->get();

        // $dataPasien = [
        //     'NAMA' => 'Pasien Dummy',
        //     'KBUKU' => 'KBK001',
        //     'NO_PESERTA' => 'PES000001',
        //     'KARCIS' => 'KRC20250101001',
        //     'DOKTER' => 'Dr. Dokter Dummy',
        //     'statusIntegrated' => 'Belum Integrasi'
        // ];

        $dataErm = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as a')
            ->select(
                'a.karcis as ID_TRANSAKSI',
                'b.ANAMNESE as KELUHAN',
                'b.PEMERIKSAAN as PEMERIKSAAN',
                'b.TERAPI as TERAPI',
                'b.DIAGNOSTIK as ANJURAN',
                'b.DIAG_UTAMA as DIAGNOSA',
                DB::raw("ISNULL(CONCAT(b.TD, ' mmHg'), '-') as TD"),
                DB::raw("ISNULL(CONCAT(b.DJ, ' x/menit'), '-') as DJ"),
                DB::raw("ISNULL(CONCAT(b.BB, ' kg'), '-') as BB"),
                DB::raw("ISNULL(CONCAT(b.TB, ' cm'), '-') as TB"),
                DB::raw("ISNULL(CONCAT(c.SUHU, ' °C'), '-') as SUHU"),
                DB::raw("ISNULL(CONCAT(c.RR, ' x/menit'), '-') as P"),
            )
            ->leftJoin('E_RM_PHCM.dbo.ERM_RM_IRJA as b', 'a.karcis', '=', 'b.KARCIS')
            ->leftJoin('E_RM_PHCM.dbo.ERM_IGD as c', 'b.NO_KUNJUNG', '=', 'c.NO_KUNJUNG')
            ->where('a.karcis', $decoded)
            ->first();

        return response()->json([
            'dataPasien' => $dataPasien,
            'dataErm' => $dataErm,
            'dataAlergi' => $dataAlergi,
        ]);
    }

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
                    SendResumeMedis::dispatch($param)->onQueue('Composition');
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
            Log::info('Bulk composition jobs dispatched', [
                'total_dispatched' => $dispatched,
                'total_errors' => count($errors),
                'user_id' => Session::get('nama', 'system'),
                'params_count' => count($selectedIds)
            ]);

            $message = "Berhasil mengirim {$dispatched} composition ke antrian untuk diproses. Pengiriman akan berlanjut di background.";

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

    public function resendSatuSehat($param)
    {
        return $this->sendSatuSehat($param, true);
    }

    public function sendSatuSehat($param, $resend = false)
    {
        try {
            $params = base64_decode($param);
            // dd($params);

            if ($params === false) {
                throw new Exception('Invalid base64 parameter');
            }

            $params = LZString::decompressFromEncodedURIComponent($params);
            $parts = explode('+', $params);
            // dd($param, $params, $parts);

            if (count($parts) < 5) {
                throw new Exception('Parameter does not contain minimum 5 parts, got: ' . count($parts));
            }

            $jenisPerawatan = $parts[0];
            $idTransaksi = LZString::decompressFromEncodedURIComponent($parts[1]);
            $kdPasienSS = LZString::decompressFromEncodedURIComponent($parts[2]);
            $kdNakesSS = LZString::decompressFromEncodedURIComponent($parts[3]);
            $kdLokasiSS = LZString::decompressFromEncodedURIComponent($parts[4]);

            $id_unit = isset($parts[5])
                ? LZString::decompressFromEncodedURIComponent($parts[5])
                : Session::get('id_unit', '001');

            // dd($jenisPerawatan, $idTransaksi, $kdPasienSS, $kdNakesSS, $kdLokasiSS, $id_unit);
        } catch (Exception $e) {
            Log::error('Parameter parsing failed in sendSatuSehat', [
                'param' => $param,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 400,
                'message' => 'Invalid parameter format: ' . $e->getMessage(),
                'redirect' => [
                    'need' => false,
                    'to' => null,
                ]
            ], 400);
        }

        $dataKarcis = Karcis::leftJoin('RJ_KARCIS_BAYAR AS KarcisBayar', function ($query) use ($idTransaksi, $id_unit) {
            $query->on('RJ_KARCIS.KARCIS', '=', 'KarcisBayar.KARCIS')
                ->on('RJ_KARCIS.IDUNIT', '=', 'KarcisBayar.IDUNIT')
                ->whereRaw('ISNULL(KarcisBayar.STBTL,0) = 0')
                ->where('KarcisBayar.IDUNIT', $id_unit); // pindahkan ke sini
        })
            ->with([
                'ermkunjung' => function ($query) use ($id_unit) {
                    $query->select('KARCIS', 'NO_KUNJUNG', 'CRTDT AS WAKTU_ERM')
                        ->where('IDUNIT', $id_unit);
                }
            ])
            ->with('inap')
            ->select('RJ_KARCIS.NOREG', 'RJ_KARCIS.KARCIS', 'RJ_KARCIS.KBUKU', 'RJ_KARCIS.NO_PESERTA', 'RJ_KARCIS.KLINIK', 'RJ_KARCIS.KDDOK', 'RJ_KARCIS.TGL_VERIF_KARCIS', 'RJ_KARCIS.CRTDT AS WAKTU_BUAT_KARCIS', 'KarcisBayar.TGL_CETAK AS WAKTU_NOTA', 'KarcisBayar.NOTA', 'RJ_KARCIS.TGL')
            ->where(function ($query) use ($jenisPerawatan, $idTransaksi) {
                if ($jenisPerawatan == 'RI') {
                    $query->where('RJ_KARCIS.NOREG', $idTransaksi);
                } else {
                    $query->where('RJ_KARCIS.KARCIS', $idTransaksi);
                }
            })
            ->where('RJ_KARCIS.IDUNIT', $id_unit)
            ->first();

        $dataEncounterSatuSehat = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
            ->where('karcis', $idTransaksi)
            ->where('no_peserta', $dataKarcis->NO_PESERTA)
            ->where('idunit', $id_unit)
            ->first();
        // dd($dataEncounterSatuSehat);

        // $diagnosisSatuSehat = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_DIAGNOSA')
        //     ->where('karcis', $idTransaksi)
        //     ->where('no_peserta', $dataKarcis->NO_PESERTA)
        //     ->where('idunit', $id_unit)
        //     ->first();

        $status = $resend ? 'final' : 'preliminary';

        $patient = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN')
            ->where('idpx', $kdPasienSS)
            ->first();
        // dd($patient);

        if ($jenisPerawatan == 'RJ') {
            $payloadRJ = $this->definePayloadRawatJalan($dataKarcis, $patient);
        } else {
            $payloadRI = $this->definePayloadRawatInap($dataKarcis, $patient);
        }

        $dataClinicalImpression = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_CLINICALIMPRESSION')
            ->select(
                'karcis',
                'ID_SATUSEHAT_ENCOUNTER',
                'NO_PESERTA',
                'ID_UNIT',
                'ID_CLINICALIMPRESSION',
                'PROGNOSIS_CODE',
                'PROGNOSIS_TEXT',
                'ID_ERM',
                'CRTUSR'
            )
            ->where('karcis', $idTransaksi)
            ->where('no_peserta', $dataKarcis->NO_PESERTA)
            ->where('ID_SATUSEHAT_ENCOUNTER', $dataEncounterSatuSehat->id_satusehat_encounter)
            ->distinct()
            ->limit(100)
            ->get();
        // dd($dataClinicalImpression);

        $dataCarePlan = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_CAREPLAN')
            ->select(
                'KARCIS',
                'JENIS_PERAWATAN',
                'NO_PESERTA',
                'ID_SATUSEHAT_ENCOUNTER',
                'ID_SATUSEHAT_CAREPLAN',
                'ID_UNIT',
                'ID_ERM',
            )
            ->where('KARCIS', $idTransaksi)
            ->where('no_peserta', $dataKarcis->NO_PESERTA)
            ->where('ID_SATUSEHAT_ENCOUNTER', $dataEncounterSatuSehat->id_satusehat_encounter)
            ->distinct()
            ->limit(100)
            ->get();
        // dd($dataCarePlan);

        $dataAllergy = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_ALLERGYINTOLERANCE')
            ->select(
                'karcis',
                'NO_PESERTA',
                'ID_SATUSEHAT_ENCOUNTER',
                'ID_ALLERGY_INTOLERANCE',
                'KBUKU',
                'IDUNIT',
                'CRTUSR'
            )
            ->where('karcis', $idTransaksi)
            ->where('no_peserta', $dataKarcis->NO_PESERTA)
            ->where('ID_SATUSEHAT_ENCOUNTER', $dataEncounterSatuSehat->id_satusehat_encounter)
            ->distinct()
            ->limit(100)
            ->get();
        // dd($dataAllergy);

        $dataCondition = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_DIAGNOSA')
            ->select(
                'karcis',
                'rank',
                'code',
                'display',
                'id_satusehat_condition',
                'crtusr'
            )
            ->where('karcis', $idTransaksi)
            ->where('nota', $dataKarcis->NOTA)
            ->distinct()
            ->limit(100)
            ->get();
        // dd($dataCondition);

        $dataObservation = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI')
            ->select(
                'KARCIS',
                'KBUKU',
                'NO_PESERTA',
                'ID_SATUSEHAT_ENCOUNTER',
                'JENIS',
                'ID_SATUSEHAT_OBSERVASI',
                'ID_ERM',
                'CRTUSER'
            )
            ->where('KARCIS', $idTransaksi)
            ->where('NO_PESERTA', $dataKarcis->NO_PESERTA)
            ->where('ID_SATUSEHAT_ENCOUNTER', $dataEncounterSatuSehat->id_satusehat_encounter)
            ->distinct()
            ->limit(100)
            ->get();
        // dd($dataObservation);

        $dataProcedure = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE')
            ->select(
                'KARCIS',
                'KBUKU',
                'ID_SATUSEHAT_ENCOUNTER',
                'KD_ICD9',
                'DISP_ICD9',
                'JENIS_TINDAKAN',
                'ID_SATUSEHAT_PROCEDURE',
                'ID_JENIS_TINDAKAN',
                'CRTUSER'
            )
            ->where('KARCIS', $idTransaksi)
            ->where('ID_SATUSEHAT_ENCOUNTER', $dataEncounterSatuSehat->id_satusehat_encounter)
            ->where('NO_PESERTA', $dataKarcis->NO_PESERTA)
            ->distinct()
            ->limit(100)
            ->get();
        // dd($dataProcedure);

        $dataMedication = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
            ->select(
                'LOCAL_ID',
                'LOG_TYPE',
                'IDENTIFIER_VALUE',
                'PATIENT_ID',
                'ENCOUNTER_ID',
                'FHIR_MEDICATION_ID',
                'FHIR_MEDICATION_REQUEST_ID',
                'KFA_CODE',
                'NAMA_OBAT',
                'FHIR_ID',
                'FHIR_MEDICATION_DISPENSE_ID',
                'STATUS'
            )
            ->whereRaw("
                RIGHT(ENCOUNTER_ID, LEN(ENCOUNTER_ID) - CHARINDEX('/', ENCOUNTER_ID)) = ?
            ", [$dataEncounterSatuSehat->id_satusehat_encounter])
            ->where('STATUS', 'success')
            ->distinct()
            ->limit(100)
            ->get();
        // dd($dataMedication);

        $dataServiceRequest = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_SERVICEREQUEST as a')
            ->select(
                'a.karcis',
                'a.nota',
                'a.idriwayat',
                'a.idunit',
                'a.id_satusehat_encounter',
                'a.id_satusehat_servicerequest',
                'a.kbuku',
                'a.no_peserta',
                'a.id_satusehat_px',
                'b.KLINIK_TUJUAN'
            )
            ->leftJoin('SIRS_PHCM.dbo.vw_getData_Elab as b', 'a.idriwayat', '=', 'b.ID_RIWAYAT_ELAB')
            ->where('a.karcis', $idTransaksi)
            ->where('a.no_peserta', $dataKarcis->NO_PESERTA)
            ->where('a.id_satusehat_encounter', $dataEncounterSatuSehat->id_satusehat_encounter)
            ->distinct()
            ->limit(100)
            ->get();
        // dd($dataServiceRequest);

        $dataSpecimen = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_SPECIMEN')
            ->select(
                'karcis',
                'nota',
                'idriwayat',
                'idunit',
                'id_satusehat_encounter',
                'id_satusehat_servicerequest',
                'id_satusehat_specimen',
                'kbuku',
                'no_peserta',
                'id_satusehat_px'
            )
            ->where('karcis', $idTransaksi)
            ->where('no_peserta', $dataKarcis->NO_PESERTA)
            ->where('id_satusehat_encounter', $dataEncounterSatuSehat->id_satusehat_encounter)
            ->distinct()
            ->limit(100)
            ->get();
        // dd($dataSpecimen);

        // Anamenesis Sections
        $anamnesisSections = [];
        if ($dataCondition && count($dataCondition) > 0) {
            $keluhanUtamaEntries = [];

            foreach ($dataCondition as $item) {
                $keluhanUtamaEntries[] = [
                    "reference" => "Condition/" . $item->id_satusehat_condition
                ];
            }

            $anamnesisSections[] = [
                "title" => "Keluhan Utama",
                "code" => [
                    "coding" => [
                        [
                            "system"  => "http://loinc.org",
                            "code"    => "10154-3",
                            "display" => "Chief complaint Narrative - Reported"
                        ]
                    ]
                ],
                "entry" => $keluhanUtamaEntries
            ];

            if ($dataAllergy && count($dataAllergy) > 0) {
                $allergyEntries = [];

                foreach ($dataAllergy as $item) {
                    $allergyEntries[] = [
                        "reference" => "AllergyIntolerance/" . $item->ID_ALLERGY_INTOLERANCE
                    ];
                }

                $anamnesisSections[] = [
                    "title" => "Riwayat Alergi",
                    "code" => [
                        "coding" => [
                            [
                                "system"  => "http://loinc.org",
                                "code"    => "48765-2",
                                "display" => "Allergies"
                            ]
                        ]
                    ],
                    "entry" => $allergyEntries
                ];
            }

            $payloadAnamnesis = [
                "title" => "Anamnesis",
                "code" => [
                    "coding" => [
                        [
                            "system"  => "http://terminology.kemkes.go.id",
                            "code"    => "TK000003",
                            "display" => "Anamnesis"
                        ]
                    ]
                ],
                "section" => $anamnesisSections
            ];
        }

        // observation
        $observationSections = [];
        if ($dataObservation && count($dataObservation) > 0) {
            $tandaVitalEntries = [];
            foreach ($dataObservation as $item) {
                $tandaVitalEntries[] = [
                    "reference" => "Observation/" . $item->ID_SATUSEHAT_OBSERVASI
                ];
            }

            $observationSections[] = [
                "title" => "Tanda Vital",
                "code" => [
                    "coding" => [
                        [
                            "system"  => "http://loinc.org",
                            "code"    => "8716-3",
                            "display" => "Vital signs"
                        ]
                    ]
                ],
                "entry" => $tandaVitalEntries
            ];

            $payloadObservation = [
                "title" => "Pemeriksaan Fisik",
                "code" => [
                    "coding" => [
                        [
                            "system"  => "http://terminology.kemkes.go.id",
                            "code"    => "TK000007",
                            "display" => "Pemeriksaan Fisik"
                        ]
                    ]
                ],
                "section" => $observationSections
            ];
        }

        // clinical impression 
        $perencanaanPerawatanEntries = [];
        if ($dataClinicalImpression && count($dataClinicalImpression) > 0) {
            foreach ($dataClinicalImpression as $item) {
                $perencanaanPerawatanEntries[] = [
                    "reference" => "ClinicalImpression/" . $item->ID_CLINICALIMPRESSION
                ];
            }

            if ($dataCarePlan && count($dataCarePlan) > 0) {
                foreach ($dataCarePlan as $item) {
                    $perencanaanPerawatanEntries[] = [
                        "reference" => "CarePlan/" . $item->ID_SATUSEHAT_CAREPLAN
                    ];
                }
            }

            $payloadClinicalImpression = [
                "title" => "Perencanaan Perawatan",
                "code" => [
                    "coding" => [
                        [
                            "system"  => "http://loinc.org",
                            "code"    => "18776-5",
                            "display" => "Plan of care note"
                        ]
                    ]
                ],
                "entry" => $perencanaanPerawatanEntries
            ];
        }

        // Service Request (LAB & RAD)
        $ServiceRequestSections = [];
        $labEntries = [];
        $radEntries = [];
        if ($dataServiceRequest && count($dataServiceRequest) > 0) {
            foreach ($dataServiceRequest as $item) {
                // LAB
                if ((int)$item->KLINIK_TUJUAN === 17) {
                    $labEntries[] = [
                        "reference" => "ServiceRequest/" . $item->id_satusehat_servicerequest
                    ];
                }
                // RAD
                else {
                    $radEntries[] = [
                        "reference" => "ServiceRequest/" . $item->id_satusehat_servicerequest
                    ];
                }
            }

            if ($dataProcedure && count($dataProcedure) > 0) {
                foreach ($dataProcedure as $item) {
                    $jenis = strtolower(trim($item->JENIS_TINDAKAN));

                    if ($jenis === 'lab') {
                        $labEntries[] = [
                            "reference" => "Procedure/" . $item->ID_SATUSEHAT_PROCEDURE
                        ];
                    } else {
                        $radEntries[] = [
                            "reference" => "Procedure/" . $item->ID_SATUSEHAT_PROCEDURE
                        ];
                    }
                }

                // specimen
                if ($dataSpecimen && count($dataSpecimen) > 0) {
                    foreach ($dataSpecimen as $item) {
                        $labEntries[] = [
                            "reference" => "Specimen/" . $item->id_satusehat_specimen
                        ];
                    }
                }

                if (count($labEntries) > 0) {
                    $ServiceRequestSections[] = [
                        "title" => "Hasil Pemeriksaan Laboratorium",
                        "code" => [
                            "coding" => [
                                [
                                    "system"  => "http://loinc.org",
                                    "code"    => "11502-2",
                                    "display" => "Laboratory report"
                                ]
                            ]
                        ],
                        "entry" => $labEntries
                    ];
                }

                if (count($radEntries) > 0) {
                    $ServiceRequestSections[] = [
                        "title" => "Hasil Pemeriksaan Radiologi",
                        "code" => [
                            "coding" => [
                                [
                                    "system"  => "http://loinc.org",
                                    "code"    => "18782-3",
                                    "display" => "Radiology Study observation (narrative)"
                                ]
                            ]
                        ],
                        "entry" => $radEntries
                    ];
                }
            }

            $payloadServiceRequest = [
                "title" => "Pemeriksaan Penunjang",
                "code" => [
                    "coding" => [
                        [
                            "system"  => "http://terminology.kemkes.go.id",
                            "code"    => "TK000009",
                            "display" => "Hasil Pemeriksaan Penunjang"
                        ]
                    ]
                ],
                "section" => $ServiceRequestSections
            ];
        }

        // procedure
        $procedureEntries = [];
        if ($dataProcedure && count($dataProcedure) > 0) {
            foreach ($dataProcedure as $item) {
                $procedureEntries[] = [
                    "reference" => "Procedure/" . $item->ID_SATUSEHAT_PROCEDURE
                ];
            }

            if ($dataObservation && count($dataObservation) > 0) {
                foreach ($dataObservation as $item) {
                    $procedureEntries[] = [
                        "reference" => "Observation/" . $item->ID_SATUSEHAT_OBSERVASI
                    ];
                }
            }

            $payloadProcedure = [
                "title" => "Tindakan/Prosedur Medis",
                "code" => [
                    "coding" => [
                        [
                            "system"  => "http://terminology.kemkes.go.id",
                            "code"    => "TK000005",
                            "display" => "Tindakan/Prosedur Medis"
                        ]
                    ]
                ],
                "entry" => $procedureEntries
            ];
        }

        // medication
        $farmasiEntries = [];
        if ($dataMedication && count($dataMedication) > 0) {
            foreach ($dataMedication as $item) {
                // MedicationRequest
                if (
                    $item->LOG_TYPE === 'MedicationRequest' ||
                    $item->LOG_TYPE === 'MedicationRequestFromDispense'
                ) {
                    if (!empty($item->FHIR_MEDICATION_REQUEST_ID)) {
                        $farmasiEntries[] = [
                            "reference" => "MedicationRequest/" . $item->FHIR_MEDICATION_REQUEST_ID
                        ];
                    }
                }

                // MedicationDispense
                if ($item->LOG_TYPE === 'MedicationDispense') {
                    if (!empty($item->FHIR_MEDICATION_DISPENSE_ID)) {
                        $farmasiEntries[] = [
                            "reference" => "MedicationDispense/" . $item->FHIR_MEDICATION_DISPENSE_ID
                        ];
                    }
                }
            }

            $payloadFarmasi = [
                "title" => "Farmasi",
                "code" => [
                    "coding" => [
                        [
                            "system"  => "http://terminology.kemkes.go.id",
                            "code"    => "TK000013",
                            "display" => "Obat"
                        ]
                    ]
                ],
                "section" => [
                    [
                        "title" => "Obat Saat Kunjungan",
                        "code" => [
                            "coding" => [
                                [
                                    "system"  => "http://loinc.org",
                                    "code"    => "42346-7",
                                    "display" => "Medications on admission (narrative)"
                                ]
                            ]
                        ],
                        "entry" => $farmasiEntries
                    ]
                ]
            ];
        }

        // combine into one section
        $sections = [];

        // push anamnesis if exists
        if (!empty($payloadAnamnesis)) {
            $sections[] = $payloadAnamnesis;
        }

        // push pemeriksaan fisik
        if (!empty($payloadObservation)) {
            $sections[] = $payloadObservation;
        }

        // push pemeriksaan penunjang
        if (!empty($payloadServiceRequest)) {
            $sections[] = $payloadServiceRequest;
        }

        // push tindakan / prosedur medis
        if (!empty($payloadProcedure)) {
            $sections[] = $payloadProcedure;
        }

        // push perencanaan perawatan
        if (!empty($payloadClinicalImpression)) {
            $sections[] = $payloadClinicalImpression;
        }

        // push farmasi
        if (!empty($payloadFarmasi)) {
            $sections[] = $payloadFarmasi;
        }

        $nakes = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES')
            ->where('idnakes', $kdNakesSS)
            ->first();

        $location = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_LOCATION')
            ->where('idss', $kdLokasiSS)
            ->first();

        $baseurl = '';
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Prod')->select('org_id')->first()->org_id;
        }

        try {
            $payload = [
                "resourceType" => "Composition",
                "identifier" => [
                    "system" => "http://sys-ids.kemkes.go.id/composition/{$organisasi}",
                    "value" => $idTransaksi
                ],
                "status" => $status,
                "category" => [[
                    "coding" => [[
                        "system" => "http://loinc.org",
                        "code" => "LP173421-1",
                        "display" => "Report"
                    ]]
                ]],
                "subject" => [
                    "reference" => "Patient/{$kdPasienSS}",
                    "display" => $patient->nama,
                ],
                "date" => Carbon::now()->toIso8601String(),
                "author" => [[
                    "reference" => "Practitioner/10009880728",
                    "display" => "dr. Alexander",
                ]],
                "custodian" => [
                    "reference" => "Organization/{$organisasi}"
                ],
                "encounter" => [
                    "reference" => "Encounter/{$dataEncounterSatuSehat->id_satusehat_encounter}"
                ],
                "section" => $sections,
            ];

            if ($resend) {
                $encounterId = SATUSEHAT_NOTA::where('karcis', $idTransaksi)
                    ->where('no_peserta', $dataKarcis->NO_PESERTA)
                    ->where('idunit', $id_unit)
                    ->select('*')
                    ->first();

                $compositionId = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_COMPOSITION')
                    ->where('id_satusehat_encounter', $encounterId->id_satusehat_encounter)
                    ->where('karcis', $idTransaksi)
                    ->where('no_peserta', $dataKarcis->NO_PESERTA)
                    ->where('idunit', $id_unit)
                    ->select('*')
                    ->first();

                $payload['id'] = $compositionId->id_satusehat_composition;
            }

            $payload = array_merge($payload, $jenisPerawatan == 'RJ' ? $payloadRJ : $payloadRI);

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }

            $token = $login['response']['token'];

            $url = $resend ? 'Composition/' . $compositionId->id_satusehat_composition : 'Composition';
            $datacomposition = $this->consumeSATUSEHATAPI($resend ? 'PUT' : 'POST', $baseurl, $url, $payload, true, $token);
            $result = json_decode($datacomposition->getBody()->getContents(), true);

            if ($datacomposition->getStatusCode() >= 400) {
                $response = json_decode($datacomposition->getBody(), true);
                $this->logError('composition', 'Gagal kirim data composition', [
                    'payload' => $payload,
                    'response' => $response,
                    'user_id' => Session::get('nama', 'system') //Session::get('id')
                ]);

                $this->logDb(json_encode($response), 'Composition', json_encode($payload), 'system'); //Session::get('id')

                $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Kirim Data Composition';
                throw new Exception($msg, $datacomposition->getStatusCode());
            } else {
                DB::beginTransaction();
                try {
                    $dataKarcis = Karcis::leftJoin('RJ_KARCIS_BAYAR AS KarcisBayar', function ($query) use ($idTransaksi, $id_unit) {
                        $query->on('RJ_KARCIS.KARCIS', '=', 'KarcisBayar.KARCIS')
                            ->on('RJ_KARCIS.IDUNIT', '=', 'KarcisBayar.IDUNIT')
                            ->whereRaw('ISNULL(KarcisBayar.STBTL,0) = 0')
                            ->where('KarcisBayar.IDUNIT', $id_unit); // pindahkan ke sini
                    })
                        ->with([
                            'ermkunjung' => function ($query) use ($id_unit) {
                                $query->select('KARCIS', 'NO_KUNJUNG', 'CRTDT AS WAKTU_ERM')
                                    ->where('IDUNIT', $id_unit);
                            }
                        ])
                        ->with('inap')
                        ->select('RJ_KARCIS.NOREG', 'RJ_KARCIS.KARCIS', 'RJ_KARCIS.KBUKU', 'RJ_KARCIS.NO_PESERTA', 'RJ_KARCIS.KLINIK', 'RJ_KARCIS.KDDOK', 'RJ_KARCIS.TGL_VERIF_KARCIS', 'RJ_KARCIS.CRTDT AS WAKTU_BUAT_KARCIS', 'KarcisBayar.TGL_CETAK AS WAKTU_NOTA', 'KarcisBayar.NOTA', 'RJ_KARCIS.TGL')
                        ->where(function ($query) use ($jenisPerawatan, $idTransaksi) {
                            if ($jenisPerawatan == 'RI') {
                                $query->where('RJ_KARCIS.NOREG', $idTransaksi);
                            } else {
                                $query->where('RJ_KARCIS.KARCIS', $idTransaksi);
                            }
                        })
                        ->where('RJ_KARCIS.IDUNIT', $id_unit)
                        ->first();

                    $dataPeserta = DB::table('RIRJ_MASTERPX')
                        ->where('KBUKU', $dataKarcis->KBUKU)
                        ->first();

                    $dataEncounter = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA')
                        ->where('karcis', $idTransaksi)
                        ->where('no_peserta', $dataKarcis->NO_PESERTA)
                        ->where('idunit', $id_unit)
                        ->first();

                    $log_composition = [
                        'karcis'                    => $idTransaksi,
                        'nota'                      => $dataKarcis->NOTA,
                        'idunit'                    => $id_unit,
                        'tgl'                       => Carbon::now('Asia/Jakarta')->format('Y-m-d'),
                        'id_satusehat_composition'  => $result['id'],
                        'id_satusehat_encounter'    => $dataEncounter->id_satusehat_encounter,
                        'kbuku'                     => $dataPeserta->KBUKU,
                        'no_peserta'                => $dataPeserta->NO_PESERTA,
                        'id_satusehat_px'           => $kdPasienSS,
                        'kddok'                     => $dataKarcis->KDDOK,
                        'id_satusehat_dokter'       => $kdNakesSS,
                        'kdklinik'                  => $dataKarcis->KLINIK,
                        'status_sinkron'            => 1,
                        'crtusr'                    => Session::get('nama', 'system'), //Session::get
                        'crtdt'                     => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        'sinkron_date'              => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    ];

                    $existingComposition = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_COMPOSITION')
                        ->where('karcis', $idTransaksi)
                        ->where('no_peserta', $dataKarcis->NO_PESERTA)
                        ->where('idunit', $id_unit)
                        ->first();

                    if ($existingComposition) {
                        DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_COMPOSITION')
                            ->where('karcis', $idTransaksi)
                            ->where('no_peserta', $dataKarcis->NO_PESERTA)
                            ->where('idunit', $id_unit)
                            ->update($log_composition);
                    } else {
                        DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_COMPOSITION')
                            ->insert($log_composition);
                    }

                    $this->logInfo('composition', 'Sukses kirim data composition', [
                        'payload' => $payload,
                        'response' => $result,
                        'user_id' => Session::get('nama', 'system') //Session::get('id')
                    ]);
                    $this->logDb(json_encode($result), 'Composition', json_encode($payload), 'system'); //Session::get('id')

                    DB::commit();
                    return response()->json([
                        'status' => JsonResponse::HTTP_OK,
                        'message' => 'Berhasil Kirim Data Composition',
                        'redirect' => [
                            'need' => false,
                            'to' => null,
                        ]
                    ], 200);
                } catch (Exception $th) {
                    DB::rollBack();
                    throw new Exception($th->getMessage(), $th->getCode());
                }
            }
        } catch (Exception $th) {
            return response()->json([
                'status' => [
                    'msg' => $th->getMessage() != '' ? $th->getMessage() : 'Err',
                    'code' => $th->getCode() != '' ? $th->getCode() : 500,
                ],
                'data' => null,
                'err_detail' => $th,
                'message' => $th->getMessage() != '' ? $th->getMessage() : 'Terjadi Kesalahan Saat Kirim Data, Harap Coba lagi!'
            ], $th->getCode() != '' ? $th->getCode() : 500);
        }
    }

    private function definePayloadRawatJalan($dataKarcis, $dataPasien)
    {
        $jenisComposition = [
            "type" => [
                "coding" => [[
                    "system" => "http://loinc.org",
                    "code" => "88645-7",
                    "display" => "Outpatient hospital Discharge summary"
                ]]
            ]
        ];

        $titleComposition = [
            "title" => "Resume Medis Pasien Rawat Jalan " . $dataPasien->nama . " pada tanggal " . Carbon::parse($dataKarcis->TGL, 'Asia/Jakarta')->format('d-m-Y'),
        ];

        $payload = array_merge($jenisComposition, $titleComposition);
        // dd($payload);
        return $payload;
    }

    private function definePayloadRawatInap($dataKarcis, $dataPasien)
    {
        $jenisComposition = [
            "type" => [
                "coding" => [[
                    "system" => "http://loinc.org",
                    "code" => "34105-7",
                    "display" => "Hospital Discharge summary"
                ]]
            ]
        ];

        $titleComposition = [
            "title" => "Resume Medis Pasien Rawat Inap " . $dataPasien->nama . " pada tanggal " . Carbon::parse($dataKarcis->TGL, 'Asia/Jakarta')->format('d-m-Y'),
        ];

        $payload = array_merge($jenisComposition, $titleComposition);
        // dd($payload);
        return $payload;
    }
}
