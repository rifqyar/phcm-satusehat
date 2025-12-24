<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Jobs\SendEncounter;
use App\Lib\LZCompressor\LZString;
use App\Models\GlobalParameter;
use App\Models\Karcis;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA_DIAGNOSA;
use App\Models\SATUSEHAT\SS_Kode_API;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\DataTables;

class EncounterController extends Controller
{
    use SATUSEHATTraits, LogTraits;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
        $rjAll = $summary->rjAll ?? 0;
        $ri = $summary->ri ?? 0;
        $unmapped = $summary->total_belum_integrasi ?? 0;
        return view('pages.satusehat.encounter.index', compact('mergedAll', 'mergedIntegrated', 'rjAll', 'ri', 'unmapped'));
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

        $dataKunjungan = collect(DB::select("
            EXEC dbo.sp_getDataEncounter ?, ?, ?, ?
        ", [
            $id_unit,
            $tgl_awal_db,
            $tgl_akhir_db,
            $request->input('cari') ?? 'all'
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
            ->addColumn('checkbox', function ($row) {
                $checkBox = '';
                $jenisPerawatan = $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
                $id_transaksi = LZString::compressToEncodedURIComponent($row->ID_TRANSAKSI);
                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                $kdLokasiSS = LZString::compressToEncodedURIComponent($row->ID_LOKASI_SS);
                $paramSatuSehat = "jenis_perawatan=" . $jenisPerawatan . "&id_transaksi=" . $id_transaksi . "&kd_pasien_ss=" . $kdPasienSS . "&kd_nakes_ss=" . $kdNakesSS . "&kd_lokasi_ss=" .  $kdLokasiSS;
                $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

                $checkBox = "";

                $kondisiDasar = (
                    $row->ID_PASIEN_SS != null &&
                    $row->ID_NAKES_SS != null &&
                    $row->ID_LOKASI_SS != null &&
                    $row->JUMLAH_NOTA_SATUSEHAT == 0
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
                    ($row->STATUS_SELESAI == "9" || $row->STATUS_SELESAI == "10")
                ) {
                    return;
                } else {
                    $checkBox = "
                        <input type='checkbox' class='select-row chk-col-purple' value='$row->ID_TRANSAKSI' data-param='$paramSatuSehat' id='$row->ID_TRANSAKSI' />
                        <label for='$row->ID_TRANSAKSI' style='margin-bottom: 0px !important; line-height: 25px !important; font-weight: 500'> &nbsp; </label>
                    ";
                }

                return $checkBox;
            })
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
                $kdbuku = LZString::compressToEncodedURIComponent($row->KBUKU);
                $kdDok = LZString::compressToEncodedURIComponent($row->KODE_DOKTER);
                $kdKlinik = LZString::compressToEncodedURIComponent($row->KODE_KLINIK);
                $idUnit = LZString::compressToEncodedURIComponent($row->ID_UNIT);
                $param = LZString::compressToEncodedURIComponent($kdbuku . '+' . $kdDok . '+' . $kdKlinik . '+' . $idUnit);

                $jenisPerawatan = $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
                $id_transaksi = LZString::compressToEncodedURIComponent($row->ID_TRANSAKSI);
                $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
                $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
                $kdLokasiSS = LZString::compressToEncodedURIComponent($row->ID_LOKASI_SS);
                $paramSatuSehat = "jenis_perawatan=" . $jenisPerawatan . "&id_transaksi=" . $id_transaksi . "&kd_pasien_ss=" . $kdPasienSS . "&kd_nakes_ss=" . $kdNakesSS . "&kd_lokasi_ss=" .  $kdLokasiSS;
                $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

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
                    if ($row->JENIS_PERAWATAN == 'RAWAT_JALAN') {
                        if ($row->JUMLAH_NOTA_SATUSEHAT == 0) {
                            if ($row->STATUS_SELESAI != "9" && $row->STATUS_SELESAI != "10") {
                                $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                            } else {
                                $btn .= '<i class="text-muted">Tunggu Verifikasi Pendaftaran</i>';
                            }
                        } else {
                            $btn = '<a href="javascript:void(0)" onclick="resendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                        }
                    } else {
                        if ($row->JUMLAH_NOTA_SATUSEHAT == 0) {
                            $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                        } else {
                            $btn = '<a href="javascript:void(0)" onclick="resendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                        }
                    }
                }
                // $btn .= '<br>';
                // $btn .= '<a href="' . route('satusehat.encounter.lihat-erm', $param) . '" class="mt-2 btn btn-sm btn-info w-100"><i class="fas fa-info-circle mr-2"></i>Lihat ERM</a>';
                return $btn;
            })
            ->addColumn('status_integrasi', function ($row) {
                if ($row->JUMLAH_NOTA_SATUSEHAT > 0) {
                    return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                } else {
                    return '<span class="badge badge-pill badge-danger p-2 w-100">Belum Integrasi</span>';
                }
            })
            ->rawColumns(['STATUS_SELESAI', 'action', 'status_integrasi', 'checkbox'])
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

    public function lihatERM($param)
    {
        $params = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('|', $params);
        $kdbuku = LZString::decompressFromEncodedURIComponent($parts[0]);
        $kdDok = LZString::decompressFromEncodedURIComponent($parts[1]);
        $kdKlinik = LZString::decompressFromEncodedURIComponent($parts[2]);
        $idUnit = LZString::decompressFromEncodedURIComponent($parts[3]);

        return view('pages.satusehat.encounter.lihat-erm', compact('kdbuku', 'kdDok', 'kdKlinik', 'idUnit'));
    }

    public function sendSatuSehat($param, $resend = false)
    {
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
        $idTransaksi = $arrParam['id_transaksi'];
        $kdPasienSS = $arrParam['kd_pasien_ss'];
        $kdNakesSS = $arrParam['kd_nakes_ss'];
        $kdLokasiSS = $arrParam['kd_lokasi_ss'];
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

        /**
         * Cek Status Kiriman Diagnosis dlu sebelum kirim encounter discharge
         * jika tidak ada otomatis kirim Encounter arrived dulu
         */
        $diagnosisSatuSehat = SATUSEHAT_NOTA_DIAGNOSA::where('karcis', (int)$idTransaksi)
            ->where('idunit', $id_unit)
            ->first();

        $status = 'finished';
        if ($diagnosisSatuSehat == null) {
            $status = 'arrived';
        }

        if ($jenisPerawatan == 'RJ') {
            $payloadRJ = $this->definePayloadRawatJalan($dataKarcis, $arrParam, $id_unit, $diagnosisSatuSehat);
        } else {
            $payloadRI = $this->definePayloadRawatInap($dataKarcis, $arrParam, $id_unit, $diagnosisSatuSehat);
        }

        $patient = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN')
            ->where('idpx', $kdPasienSS)
            ->first();

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
                "status" => $status,
                "resourceType" => "Encounter",
                "subject" => [
                    "reference" => "Patient/{$kdPasienSS}",
                    "display" => $patient->nama,
                ],
                "participant" => [[
                    "type" => [[
                        "coding" => [[
                            "system" => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                            "code" => "ATND",
                            "display" => "attender"
                        ]]
                    ]],
                    "individual" => [
                        "reference" => "Practitioner/{$kdNakesSS}",
                        "display" => $nakes->nama,
                    ]
                ]],
                "location" => [[
                    "location" => [
                        "reference" => "Location/{$kdLokasiSS}",
                        "display" => $location->name,
                    ]
                ]],
                "serviceProvider" => [
                    "reference" => "Organization/{$organisasi}"
                ],
                "identifier" => [[
                    "system" => "http://sys-ids.kemkes.go.id/encounter/{$organisasi}",
                    "value" => $arrParam['id_transaksi']
                ]]
            ];

            if ($resend) {
                $encounterId = SATUSEHAT_NOTA::where('karcis', $idTransaksi)
                    ->where('no_peserta', $dataKarcis->NO_PESERTA)
                    ->where('idunit', $id_unit)
                    ->select('*')
                    ->first();
                $payload['id'] = $encounterId->id_satusehat_encounter;
            }

            $payload = array_merge($payload, $jenisPerawatan == 'RJ' ? $payloadRJ : $payloadRI);

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }

            $token = $login['response']['token'];

            $url = $resend ? 'Encounter/' . $encounterId->id_satusehat_encounter : 'Encounter';
            $dataencounter = $this->consumeSATUSEHATAPI($resend ? 'PUT' : 'POST', $baseurl, $url, $payload, true, $token);
            $result = json_decode($dataencounter->getBody()->getContents(), true);

            if ($dataencounter->getStatusCode() >= 400) {
                $response = json_decode($dataencounter->getBody(), true);

                $this->logError('encounter', 'Gagal kirim data encounter', [
                    'payload' => $payload,
                    'response' => $response,
                    'user_id' => Session::get('nama', 'system') //Session::get('id')
                ]);

                $this->logDb(json_encode($response), 'Encounter', json_encode($payload), 'system'); //Session::get('id')

                $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Kirim Data Encounter';
                throw new Exception($msg, $dataencounter->getStatusCode());
            } else {
                DB::beginTransaction();
                try {
                    $historyTime = $arrParam['jenis_perawatan'] == 'RJ' ? $this->getHistoryTime($dataKarcis) : $this->getHistoryTimeInap($dataKarcis);
                    $jam_start = $historyTime['jam_start'];
                    $jam_progress = $historyTime['jam_progress'];
                    $jam_finish = $historyTime['jam_finish'];

                    // $dataKarcis = DB::table('RJ_KARCIS as rk')
                    //     ->leftJoin('RJ_KARCIS_BAYAR as rkb', function ($join) {
                    //         $join->on('rk.KARCIS', '=', 'rkb.KARCIS')
                    //             ->on('rk.IDUNIT', '=', 'rkb.IDUNIT')
                    //             ->whereRaw('ISNULL(rkb.STBTL,0) = 0');
                    //     })
                    //     ->select('rk.NO_PESERTA', 'rk.KARCIS', 'rk.NOREG', 'rk.IDUNIT', 'rk.KLINIK', 'rk.TGL', 'rk.KDDOK', 'rk.KBUKU', 'rkb.NOTA')
                    //     ->where(function ($query) use ($arrParam) {
                    //         if ($arrParam['jenis_perawatan'] == 'RI') {
                    //             $query->where('rk.NOREG', $arrParam['id_transaksi']);
                    //         } else {
                    //             $query->where('rk.KARCIS', $arrParam['id_transaksi']);
                    //         }
                    //     })
                    //     ->where('rk.IDUNIT', $id_unit)
                    //     ->orderBy('rk.TGL', 'DESC')
                    //     ->first();
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

                    $dataPeserta = DB::table('RIRJ_MASTERPX')
                        ->where('KBUKU', $dataKarcis->KBUKU)
                        ->first();

                    $nota_satusehat = SATUSEHAT_NOTA::firstOrCreate(
                        [
                            'karcis' => $arrParam['jenis_perawatan'] == 'RJ' ? (int)$dataKarcis->KARCIS : (int)$dataKarcis->NOREG,
                            'no_peserta' => $dataPeserta->NO_PESERTA,
                            'kbuku' => $dataKarcis->KBUKU
                        ],
                        [
                            'id_satusehat_encounter' => $result['id'],
                            'crtusr' => 'system',
                            'crtdt' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        ]
                    );

                    if ($nota_satusehat->wasRecentlyCreated === false) {
                        $nota_satusehat->encounter_pulang = $result['id'];
                    }

                    $nota_satusehat->nota        = (int)$dataKarcis->NOTA;
                    $nota_satusehat->idunit      = $id_unit;
                    $nota_satusehat->tgl         = Carbon::parse($dataKarcis->TGL, 'Asia/Jakarta')->format('Y-m-d');
                    $nota_satusehat->kbuku       = $dataPeserta->KBUKU;
                    $nota_satusehat->no_peserta  = $dataPeserta->NO_PESERTA;
                    $nota_satusehat->id_satusehat_px = $kdPasienSS;
                    $nota_satusehat->kddok       = $dataKarcis->KDDOK;
                    $nota_satusehat->id_satusehat_dokter = $kdNakesSS;
                    $nota_satusehat->kdklinik    = $dataKarcis->KLINIK;
                    $nota_satusehat->id_satusehat_klinik_location = $kdLokasiSS;

                    $nota_satusehat->jam_datang    = Carbon::parse($jam_start, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                    $nota_satusehat->jam_progress  = Carbon::parse($jam_progress, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                    $nota_satusehat->jam_selesai   = $jam_finish != null ? Carbon::parse($jam_finish, 'Asia/Jakarta')->format('Y-m-d H:i:s') : null;
                    $nota_satusehat->status_sinkron = 1;
                    $nota_satusehat->sinkron_date   = now('Asia/Jakarta')->format('Y-m-d H:i:s');

                    $nota_satusehat->save();

                    $this->logInfo('encounter', 'Sukses kirim data encounter', [
                        'payload' => $payload,
                        'response' => $result,
                        'user_id' => Session::get('nama', 'system') //Session::get('id')
                    ]);
                    $this->logDb(json_encode($result), 'Encounter', json_encode($payload), 'system'); //Session::get('id')

                    DB::commit();
                    return response()->json([
                        'status' => JsonResponse::HTTP_OK,
                        'message' => 'Berhasil Kirim Data Encounter',
                        'redirect' => [
                            'need' => false,
                            'to' => null,
                        ]
                    ], 200);
                } catch (Exception $th) {
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

    public function bulkSend(Request $request)
    {
        $resp = null;
        foreach ($request->selected_ids as $selected) {
            $param = $selected['param'];
            SendEncounter::dispatch($param)->onQueue('encounter');
            // $this->sendSatuSehat(base64_encode($param));
        }

        return response()->json([
            'status' => JsonResponse::HTTP_OK,
            'message' => 'Pengiriman Data Allergy Intolerance Pasien Sedang Diproses oleh sistem',
            'redirect' => [
                'need' => false,
                'to' => null,
            ]
        ], 200);
    }

    public function resendSatuSehat($param)
    {
        return $this->sendSatuSehat($param, true);
    }

    public function receiveSatuSehat(Request $request)
    {
        $id_unit = Session::get('id_unit', $request->input('id_unit'));
        $this->logInfo('encounter', 'Receive Encounter dari SIMRS', [
            'request' => $request->all(),
            'karcis' => $request->karcis,
            'aktifitas' => $request->aktivitas,
            'user_id' => 'system'
        ]);

        $encounterId = SATUSEHAT_NOTA::where('karcis', (int)$request->karcis)
            ->select('*')
            ->first();

        $data = null;
        $jenisPerawatan = 'RJ';
        if (str_contains(strtoupper($request->aktivitas), 'RAWAT JALAN')) {
            $jenisPerawatan = 'RJ';

            $rj = DB::table('v_kunjungan_rj as v')
                ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as n', function ($join) {
                    $join->on('n.KARCIS', '=', 'v.ID_TRANSAKSI')
                        ->on('n.IDUNIT', '=', 'v.ID_UNIT')
                        ->on('n.KBUKU', '=', 'v.KBUKU')
                        ->on('n.NO_PESERTA', '=', 'v.NO_PESERTA');
                })
                ->select(
                    'v.*',
                    DB::raw('COUNT(DISTINCT n.ID_SATUSEHAT_ENCOUNTER) as JUMLAH_NOTA_SATUSEHAT')
                )
                ->groupBy('v.ICD9', 'v.DIPLAY_ICD9', 'v.JENIS_PERAWATAN', 'v.STATUS_SELESAI', 'v.STATUS_KUNJUNGAN', 'v.DOKTER', 'v.DEBITUR', 'v.LOKASI', 'v.STATUS_MAPPING_PASIEN', 'v.STATUS_MAPPING_LOKASI', 'v.ID_PASIEN_SS', 'v.ID_NAKES_SS', 'v.KODE_DOKTER', 'v.ID_LOKASI_SS', 'v.UUID', 'v.STATUS_MAPPING_NAKES', 'v.ID_TRANSAKSI', 'v.ID_UNIT', 'v.KODE_KLINIK', 'v.KBUKU', 'v.NO_PESERTA', 'v.TANGGAL', 'v.NAMA_PASIEN')
                ->where('v.ID_UNIT', $id_unit)
                ->where('v.ID_TRANSAKSI', $request->karcis)
                ->first();
            $data = $rj;
        } else {
            $jenisPerawatan = 'RI';

            $ri = DB::table('v_kunjungan_ri as v')
                ->leftJoin('SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as n', function ($join) {
                    $join->on('n.KARCIS', '=', 'v.ID_TRANSAKSI')
                        ->on('n.IDUNIT', '=', 'v.ID_UNIT')
                        ->on('n.KBUKU', '=', 'v.KBUKU')
                        ->on('n.NO_PESERTA', '=', 'v.NO_PESERTA');
                })
                ->select(
                    'v.*',
                    DB::raw('COUNT(DISTINCT n.ID_SATUSEHAT_ENCOUNTER) as JUMLAH_NOTA_SATUSEHAT')
                )
                ->groupBy('v.ICD9', 'v.DIPLAY_ICD9', 'v.JENIS_PERAWATAN', 'v.STATUS_SELESAI', 'v.STATUS_KUNJUNGAN', 'v.DOKTER', 'v.DEBITUR', 'v.LOKASI', 'v.STATUS_MAPPING_PASIEN', 'v.STATUS_MAPPING_LOKASI', 'v.ID_PASIEN_SS', 'v.ID_NAKES_SS', 'v.KODE_DOKTER', 'v.ID_LOKASI_SS', 'v.UUID', 'v.STATUS_MAPPING_LOKASI', 'v.STATUS_MAPPING_NAKES', 'v.ID_TRANSAKSI', 'v.ID_UNIT', 'v.KODE_KLINIK', 'v.KBUKU', 'v.NO_PESERTA', 'v.TANGGAL', 'v.NAMA_PASIEN')
                ->where('v.ID_UNIT', $id_unit)
                ->where('v.ID_TRANSAKSI', $request->karcis)
                ->first();
            $data = $ri;
        }

        $id_transaksi = LZString::compressToEncodedURIComponent($request->karcis);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $kdLokasiSS = LZString::compressToEncodedURIComponent($data->ID_LOKASI_SS);
        $paramSatuSehat = "jenis_perawatan=" . $jenisPerawatan . "&id_transaksi=" . $id_transaksi . "&kd_pasien_ss=" . $kdPasienSS . "&kd_nakes_ss=" . $kdNakesSS . "&kd_lokasi_ss=" .  $kdLokasiSS;
        $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

        if ($data->ID_LOKASI_SS == null && $data->ID_NAKES_SS == null && $data->ID_PASIEN_SS == null) return;
        SendEncounter::dispatch($paramSatuSehat, $encounterId ? true : false)->onQueue('encounter');

        // return response()->json([
        //     'status' => JsonResponse::HTTP_OK,
        //     'message' => 'Pengiriman Data Encounter Pasien Sedang Diproses oleh sistem',
        //     'redirect' => [
        //         'need' => false,
        //         'to' => null,
        //     ]
        // ], 200);
    }

    private function definePayloadRawatJalan($dataKarcis, $param, $id_unit, $diagnosisSatuSehat)
    {
        $jenisEncounter = [
            "class" => [
                "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                "code" => "AMB",
                "display" => "ambulatory"
            ]
        ];

        $historyTime = $this->getHistoryTime($dataKarcis);
        $jam_start = $historyTime['jam_start'];
        $jam_progress = $historyTime['jam_progress'];
        $jam_finish = $historyTime['jam_finish'];

        $statusHistory = [
            'statusHistory' => [
                [
                    'status' => 'arrived',
                    'period' => [
                        'start' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_start)->toIso8601String(),
                        'end' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_progress)->toIso8601String(),
                    ],
                ],

            ],
        ];

        $dischargeType = [];
        if ($diagnosisSatuSehat != null) {
            array_push(
                $statusHistory['statusHistory'],
                [
                    'status' => 'in-progress',
                    'period' => [
                        'start' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_progress)->toIso8601String(),
                        'end' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_finish)->toIso8601String(),
                    ],
                ],
                [
                    'status' => 'finished',
                    'period' => [
                        'start' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_finish)->toIso8601String(),
                        'end' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_finish)->toIso8601String(),
                    ],
                ]
            );

            $textDischage = $dataKarcis->NOREG == null ? "Anjuran dokter untuk pulang" : "Pasien dirujuk ke rawat inap untuk perawatan lebih lanjut";
            $dischargeType = [
                "hospitalization" => [
                    "dischargeDisposition" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                                "code" => $dataKarcis->NOREG == null ? "home" : "long",
                                "display" => $dataKarcis->NOREG == null ? "Home" : "Long-term care"
                            ]
                        ],
                        "text" => $textDischage
                    ]
                ],
            ];
        }

        $period = [
            "period" => [
                "start" => $jam_start->toIso8601String(),
                "end" => $jam_finish->toIso8601String(),
            ],
        ];

        $payload = array_merge($jenisEncounter, $statusHistory, $period, $dischargeType);
        return $payload;
    }

    private function definePayloadRawatInap($dataKarcis, $param, $id_unit, $diagnosisSatuSehat)
    {
        $jenisEncounter = [
            "class" => [
                "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                "code" => "IMP",
                "display" => "inpatient"
            ]
        ];

        $historyTime = $this->getHistoryTimeInap($dataKarcis);
        $jam_start = $historyTime['jam_start'];
        $jam_progress = $historyTime['jam_progress'];
        $jam_finish = $historyTime['jam_finish'];

        $statusHistory = [
            'statusHistory' => [
                [
                    'status' => 'arrived',
                    'period' => [
                        'start' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_start)->toIso8601String(),
                        'end' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_progress)->toIso8601String(),
                    ],
                ],

            ],
        ];

        $dischargeType = [];
        if ($diagnosisSatuSehat != null) {
            if ($jam_finish != null) {
                array_push(
                    $statusHistory['statusHistory'],
                    [
                        'status' => 'in-progress',
                        'period' => [
                            'start' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_progress)->toIso8601String(),
                            'end' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_finish)->toIso8601String(),
                        ],
                    ],
                    [
                        'status' => 'finished',
                        'period' => [
                            'start' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_finish)->toIso8601String(),
                            'end' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_finish)->toIso8601String(),
                        ],
                    ]
                );

                $textDischage = "Anjuran dokter untuk pulang";
                $dischargeType = [
                    "hospitalization" => [
                        "dischargeDisposition" => [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                                    "code" => "home",
                                    "display" => "Home"
                                ]
                            ],
                            "text" => $textDischage
                        ]
                    ],
                ];
            } else {
                array_push(
                    $statusHistory['statusHistory'],
                    [
                        'status' => 'in-progress',
                        'period' => [
                            'start' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_progress)->toIso8601String(),
                            'end' => Carbon::createFromFormat('Y-m-d H:i:s', $jam_progress)->toIso8601String(),
                        ],
                    ]
                );
            }
        }

        $period = [
            "period" => [
                "start" => $jam_start->toIso8601String(),
                "end" => $jam_finish != null ? $jam_finish->toIso8601String() : $jam_progress->toIso8601String(),
            ],
        ];

        $payload = array_merge($jenisEncounter, $statusHistory, $period, $dischargeType);
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
        $waktu_buat_karcis = $dataKarcis->WAKTU_BUAT_KARCIS
            ? Carbon::parse($dataKarcis->WAKTU_BUAT_KARCIS, 'Asia/Jakarta')
            : null;

        $waktu_verif_karcis = $dataKarcis->TGL_VERIF_KARCIS
            ? Carbon::parse($dataKarcis->TGL_VERIF_KARCIS, 'Asia/Jakarta')
            : null;

        $waktu_nota = $dataKarcis->WAKTU_NOTA
            ? Carbon::parse($dataKarcis->WAKTU_NOTA, 'Asia/Jakarta')
            : null;

        $waktu_erm = ($dataKarcis->ermkunjung && $dataKarcis->ermkunjung->WAKTU_ERM)
            ? Carbon::parse($dataKarcis->ermkunjung->WAKTU_ERM, 'Asia/Jakarta')
            : null;

        // ===============================
        // HANDLE DEFAULT VALUE
        // Jika waktu nota null (belum bayar),
        // set ke max dari waktu lain supaya tidak error
        // ===============================
        if (!$waktu_nota) {
            // fallback: gunakan waktu ERM atau verif atau buat karcis
            $waktu_nota = $waktu_erm
                ?? $waktu_verif_karcis
                ?? $waktu_buat_karcis;

            // Jika semua null, set ke sekarang
            if (!$waktu_nota) {
                $waktu_nota = Carbon::now('Asia/Jakarta');
            }
        }

        // ===============================
        // JAM START
        // ===============================
        if ($waktu_verif_karcis && $waktu_buat_karcis) {
            $jam_start = $waktu_verif_karcis >= $waktu_buat_karcis
                ? $waktu_verif_karcis
                : $waktu_buat_karcis;
        } else {
            // fallback jika salah satu null
            $jam_start = $waktu_verif_karcis ?? $waktu_buat_karcis ?? Carbon::now('Asia/Jakarta');
        }

        // ===============================
        // JAM PROGRESS
        // ===============================
        if ($waktu_erm && $waktu_nota) {
            $jam_progress = $waktu_erm <= $waktu_nota
                ? $waktu_erm
                : $waktu_nota;
        } else {
            // fallback
            $jam_progress = $waktu_erm ?? $waktu_nota;
        }

        // ===============================
        // JAM FINISH
        // ===============================
        if ($jam_progress && $waktu_erm && $jam_progress == $waktu_erm) {
            $jam_finish = $waktu_nota;
        } else {
            $jam_finish = $jam_progress
                ? Carbon::parse($jam_progress->toDateTimeString(), 'Asia/Jakarta')->addMinutes(rand(3, 6))
                : $waktu_nota;
        }

        // ===============================
        // PENYESUAIAN WAKTU
        // ===============================
        if ($jam_progress && $jam_start && $jam_progress < $jam_start) {
            $minutes = $jam_start->diff($jam_progress)->format('%i') + rand(3, 6);
            $jam_start->subMinutes($minutes);
        }

        if ($jam_finish && $jam_progress && $jam_finish <= $jam_progress) {
            $minutes = $jam_finish->diff($jam_progress)->format('%i') + rand(6, 10);
            $jam_finish->addMinutes($minutes);
        }

        return [
            'jam_start' => $jam_start,
            'jam_progress' => $jam_progress,
            'jam_finish' => $jam_finish
        ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
