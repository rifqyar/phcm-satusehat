<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
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
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate   = Carbon::now()->endOfDay();

        $rj = DB::table('v_kunjungan_rj as v')
            ->whereBetween('TANGGAL', [$startDate, $endDate])
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
            ->groupBy('v.JENIS_PERAWATAN', 'v.STATUS_SELESAI', 'v.STATUS_KUNJUNGAN', 'v.DOKTER', 'v.DEBITUR', 'v.LOKASI', 'v.STATUS_MAPPING_PASIEN', 'v.ID_PASIEN_SS', 'v.ID_NAKES_SS', 'v.KODE_DOKTER', 'v.ID_LOKASI_SS', 'v.UUID', 'v.STATUS_MAPPING_LOKASI', 'v.STATUS_MAPPING_NAKES', 'v.ID_TRANSAKSI', 'v.ID_UNIT', 'v.KODE_KLINIK', 'v.KBUKU', 'v.NO_PESERTA', 'v.TANGGAL', 'v.NAMA_PASIEN');

        $rjAll = $rj->get();
        $rjIntegrasi = $rj->whereNotNull('n.ID_SATUSEHAT_ENCOUNTER')->get();

        $ri = DB::table('v_kunjungan_ri')
            ->whereBetween('TANGGAL', [$startDate, $endDate])
            ->get();

        $mergedAll = $rjAll->merge($ri)
            ->sortByDesc('TANGGAL')
            ->values();

        $mergedIntegrated = $rjIntegrasi->merge($ri)
            ->sortByDesc('TANGGAL')
            ->values();

        $unmapped = count($mergedAll) - count($mergedIntegrated);
        return view('pages.satusehat.encounter.index', compact('mergedAll', 'mergedIntegrated', 'rjAll', 'rjIntegrasi', 'ri', 'unmapped'));
    }

    public function datatable(Request $request)
    {
        $tgl_awal  = $request->input('tgl_awal');
        $tgl_akhir = $request->input('tgl_akhir');
        $id_unit   = '001'; // session('id_klinik');

        if (empty($tgl_awal) && empty($tgl_akhir)) {
            $tgl_awal  = Carbon::now()->subDays(30)->startOfDay();
            $tgl_akhir = Carbon::now()->endOfDay();
        } else {
            if (empty($tgl_awal)) {
                $tgl_awal = Carbon::parse($tgl_akhir)->subDays(30)->startOfDay();
            }
            if (empty($tgl_akhir)) {
                $tgl_akhir = Carbon::parse($tgl_awal)->addDays(30)->endOfDay();
            }
        }

        if (!$this->checkDateFormat($tgl_awal) || !$this->checkDateFormat($tgl_akhir)) {
            return DataTables::of([])->make(true);
        }

        $tgl_awal_db  = Carbon::parse($tgl_awal)->format('Y-m-d H:i:s');
        $tgl_akhir_db = Carbon::parse($tgl_akhir)->format('Y-m-d H:i:s');

        $rj = DB::table('v_kunjungan_rj as v')
            ->whereBetween('TANGGAL', [$tgl_awal_db, $tgl_akhir_db])
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
            ->groupBy('v.JENIS_PERAWATAN', 'v.STATUS_SELESAI', 'v.STATUS_KUNJUNGAN', 'v.DOKTER', 'v.DEBITUR', 'v.LOKASI', 'v.STATUS_MAPPING_PASIEN', 'v.ID_PASIEN_SS', 'v.ID_NAKES_SS', 'v.KODE_DOKTER', 'v.ID_LOKASI_SS', 'v.UUID', 'v.STATUS_MAPPING_LOKASI', 'v.STATUS_MAPPING_NAKES', 'v.ID_TRANSAKSI', 'v.ID_UNIT', 'v.KODE_KLINIK', 'v.KBUKU', 'v.NO_PESERTA', 'v.TANGGAL', 'v.NAMA_PASIEN');

        $rjAll = $rj->get();

        $rjIntegrasi = $rj->whereNotNull('n.ID_SATUSEHAT_ENCOUNTER')->get();

        $ri = DB::table('v_kunjungan_ri')
            ->whereBetween('TANGGAL', [$tgl_awal_db, $tgl_akhir_db])
            ->get();

        $mergedAll = $rjAll->merge($ri)
            ->sortByDesc('TANGGAL')
            ->values();

        $mergedIntegrated = $rjIntegrasi->merge($ri)
            ->sortByDesc('TANGGAL')
            ->values();

        if ($request->input('cari') == 'mapped') {
            $dataKunjungan = $mergedIntegrated;
        } else if ($request->input('cari') == 'unmapped') {
            $dataKunjungan = $mergedAll->filter(function ($item) {
                return $item->JUMLAH_NOTA_SATUSEHAT == '0';
            })->values();
        } else {
            $dataKunjungan = $mergedAll;
        }

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
                    return $row->STATUS_SELESAI == 1 ? 'Selesai' : 'Belum Selesai';
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
                $paramSatuSehat = LZString::compressToEncodedURIComponent($jenisPerawatan . ' + ' . $id_transaksi . '+' . $kdPasienSS . '+' . $kdNakesSS . '+' .  $kdLokasiSS);

                if ($row->ID_PASIEN_SS == null) {
                    $btn = '<i class="text-muted">Pasien Belum Mapping Satu Sehat</i>';
                } else if ($row->ID_NAKES_SS == null) {
                    $btn = '<i class="text-muted">Nakes Belum Mapping Satu Sehat</i>';
                } else if ($row->ID_LOKASI_SS == null) {
                    $btn = '<i class="text-muted">Lokasi Belum Mapping Satu Sehat</i>';
                } else {
                    if ($row->JENIS_PERAWATAN == 'RAWAT_JALAN') {
                        if ($row->JUMLAH_NOTA_SATUSEHAT == 0) {
                            if ($row->STATUS_SELESAI != "9" && $row->STATUS_SELESAI != "10") {
                                $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
                            } else {
                                $btn = '<i class="text-muted">Tunggu Verifikasi Pasien</i>';
                            }
                        } else {
                            $btn = '<a href="#" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
                        }
                    } else {
                        // return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                    }
                }
                // $btn .= '<br>';
                // $btn .= '<a href="' . route('satusehat.encounter.lihat-erm', $param) . '" class="mt-2 btn btn-sm btn-info w-100"><i class="fas fa-info-circle mr-2"></i>Lihat ERM</a>';
                return $btn;
            })
            ->addColumn('status_integrasi', function ($row) {
                if ($row->JENIS_PERAWATAN == 'RAWAT_JALAN') {
                    if ($row->JUMLAH_NOTA_SATUSEHAT > 0) {
                        return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                    } else {
                        return '<span class="badge badge-pill badge-danger p-2 w-100">Belum Integrasi</span>';
                    }
                } else {
                    return '<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>';
                }
            })
            ->rawColumns(['STATUS_SELESAI', 'action', 'status_integrasi'])
            ->make(true);
    }

    private function checkDateFormat($date)
    {
        try {
            // Kalau $date sudah Carbon instance
            if ($date instanceof \Carbon\Carbon) {
                return true;
            }

            // Kalau string tapi masih bisa di-parse ke Carbon
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

    public function sendSatuSehat(Request $request, $param)
    {
        $param = base64_decode($param);
        $param = LZString::decompressFromEncodedURIComponent($param);
        $parts = explode('+', $param);

        $jenisPerawatan = trim($parts[0]);
        $idTransaksi = LZString::decompressFromEncodedURIComponent(trim($parts[1]));
        $kdPasienSS = LZString::decompressFromEncodedURIComponent($parts[2]);
        $kdNakesSS = LZString::decompressFromEncodedURIComponent($parts[3]);
        $kdLokasiSS = LZString::decompressFromEncodedURIComponent($parts[4]);
        $id_unit      = '001'; // session('id_klinik');

        $jenisEncounter = [];
        if ($jenisPerawatan == 'RJ') {
            $jenisEncounter = [
                "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                "code" => "AMB",
                "display" => "ambulatory"
            ];
        } else {
            $jenisEncounter = [
                "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                "code" => "IMP",
                "display" => "inpatient"
            ];
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

        $jam_datang = Carbon::parse($request->jam_datang, 'Asia/Jakarta')->toIso8601String();

        try {
            $payload = [
                "resourceType" => "Encounter",
                "status" => "arrived",
                "class" => $jenisEncounter,
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
                "period" => [
                    "start" => $jam_datang,
                ],
                "location" => [[
                    "location" => [
                        "reference" => "Location/{$kdLokasiSS}",
                        "display" => $location->name,
                    ]
                ]],
                "statusHistory" => [[
                    "status" => "arrived",
                    "period" => [
                        "start" => $jam_datang,
                    ]
                ]],
                "serviceProvider" => [
                    "reference" => "Organization/{$organisasi}"
                ],
                "identifier" => [[
                    "system" => "http://sys-ids.kemkes.go.id/encounter/{$organisasi}",
                    "value" => (string)$idTransaksi
                ]]
            ];

            $login = $this->login($id_unit);
            if ($login['metadata']['code'] != 200) {
                $hasil = $login;
            }

            $token = $login['response']['token'];

            $url = 'Encounter';
            $dataencounter = $this->consumeSATUSEHATAPI('POST', $baseurl, $url, $payload, true, $token);
            $result = json_decode($dataencounter->getBody()->getContents(), true);
            if ($dataencounter->getStatusCode() >= 400) {
                $response = json_decode($dataencounter->getBody(), true);

                $this->logError('encounter', 'Gagal kirim data encounter', [
                    'payload' => $payload,
                    'response' => $response,
                    'user_id' => 'system' //Session::get('id')
                ]);

                $this->logDb(json_encode($response), 'Encounter', json_encode($payload), 'system'); //Session::get('id')

                $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Kirim Data Encounter';
                throw new Exception($msg, $dataencounter->getStatusCode());
            } else {
                DB::beginTransaction();
                try {
                    $dataKarcis = DB::table('RJ_KARCIS as rk')
                        ->select('rk.KARCIS', 'rk.IDUNIT', 'rk.KLINIK', 'rk.TGL', 'rk.KDDOK', 'rk.KBUKU')
                        ->where('rk.KARCIS', $idTransaksi)
                        ->where('rk.IDUNIT', $id_unit)
                        ->orderBy('rk.TGL', 'DESC')
                        ->first();

                    $dataPeserta = DB::table('RIRJ_MASTERPX')
                        ->where('KBUKU', $dataKarcis->KBUKU)
                        ->first();

                    $nota_satusehat = new SATUSEHAT_NOTA();
                    $nota_satusehat->id_satusehat_encounter = $result['id'];
                    $nota_satusehat->karcis = (int)$dataKarcis->KARCIS;
                    $nota_satusehat->idunit = $id_unit;
                    $nota_satusehat->tgl = Carbon::parse($dataKarcis->TGL, 'Asia/Jakarta')->format('Y-m-d');
                    $nota_satusehat->kbuku = $dataPeserta->KBUKU;
                    $nota_satusehat->no_peserta = $dataPeserta->NO_PESERTA;
                    $nota_satusehat->id_satusehat_px = $kdPasienSS;
                    $nota_satusehat->kddok = $dataKarcis->KDDOK;
                    $nota_satusehat->id_satusehat_dokter = $kdNakesSS;
                    $nota_satusehat->kdklinik = $dataKarcis->KLINIK;
                    $nota_satusehat->id_satusehat_klinik_location = $kdLokasiSS;
                    $nota_satusehat->crtdt = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $nota_satusehat->crtusr = 'system';
                    $nota_satusehat->jam_datang = Carbon::parse($jam_datang, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                    $nota_satusehat->status_sinkron = 1;
                    $nota_satusehat->sinkron_date = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $nota_satusehat->save();

                    $this->logInfo('encounter', 'Sukses kirim data encounter', [
                        'payload' => $payload,
                        'response' => $result,
                        'user_id' => 'system' //Session::get('id')
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
                    dd($th);
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
