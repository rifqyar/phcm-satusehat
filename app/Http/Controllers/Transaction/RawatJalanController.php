<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchToEndpoint;
use App\Lib\LZCompressor\LZString;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\DataTables;

class RawatJalanController extends Controller
{
    public function index()
    {
        $satuSehatMenu = [
            [
                'title' => 'Encounter',
                'url' => 'satusehat.encounter.index',
                'id' => 'encounter_sent'
            ],
            [
                'title' => 'Condition / Diagnosis',
                'url' => 'satusehat.diagnosis.index',
                'id' => 'condition_sent'
            ],
            [
                'title' => 'Observation',
                'url' => 'satusehat.observasi.index',
                'id' => 'observation_sent'
            ],
            [
                'title' => 'Procedure',
                'url' => 'satusehat.procedure.index',
                'id' => 'procedure_sent'
            ],
            [
                'title' => 'Immunization',
                'url' => 'satusehat.imunisasi.index',
                'id' => ''
            ],
            [
                'title' => 'Medication Request',
                'url' => 'satusehat.medication-request.index',
                'id' => 'medicationrequest_sent'
            ],
            [
                'title' => 'Medication Dispense',
                'url' => 'satusehat.medication-dispense.index',
                'id' => 'medicationdispense_sent'
            ],
            [
                'title' => 'Allergy Intolerance',
                'url' => 'satusehat.allergy-intolerance.index',
                'id' => 'allergyintolerance_sent'
            ],
            [
                'title' => 'Service Request',
                'url' => 'satusehat.service-request.index',
                'id' => 'servicerequest_sent'
            ],
            [
                'title' => 'Specimen',
                'url' => 'satusehat.specimen.index',
                'id' => 'specimen_sent'
            ],
            [
                'title' => 'Imaging Study',
                'url' => 'satusehat.imaging-study.index',
                'id' => ''
            ],
            [
                'title' => 'Diagnostic Report',
                'url' => 'satusehat.diagnostic-report.index',
                'id' => 'diagnosticreport_sent'
            ],
            [
                'title' => 'ClinicalImpression',
                'url' => 'satusehat.clinical-impression.index',
                'id' => 'clinical_impression_sent'
            ],
            [
                'title' => 'Care Plan',
                'url' => 'satusehat.care-plan.index',
                'id' => 'careplan_sent'
            ],
            [
                'title' => 'Medication Statement',
                'url' => 'satusehat.medstatement.index',
                'id' => 'medicationstatement_sent'
            ],
            [
                'title' => 'Respon Kuesioner',
                'url' => 'satusehat.questionnaire-response.index',
                'id' => 'questionnaireresponse_sent'
            ],
            [
                'title' => 'Episode of Care',
                'url' => 'satusehat.episode-of-care.index',
                'id' => 'episodeofcare_sent'
            ],
            [
                'title' => 'Resume Medis',
                'url' => 'satusehat.resume-medis.index',
                'id' => 'composition_sent'
            ],
        ];

        $startDate = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
        $endDate   = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
        $id_unit = Session::get('id_unit', '001');

        $dataKunjungan = collect(DB::select("
            EXEC dbo.sp_getKunjunganSatusehat_Header ?, ?, ?, ?, ?, ?, ?
        ", [
            $id_unit,
            $startDate,
            $endDate,
            'RAWAT_JALAN',
            null,
            1,
            1
        ]));

        $queryService = "SELECT
                DISTINCT
                CASE
                    WHEN CHARINDEX('/', slt.service) > 0
                    THEN LEFT(slt.service, CHARINDEX('/', slt.service) - 1)
                    ELSE slt.service
                END AS service_name
            FROM SATUSEHAT.dbo.SATUSEHAT_LOG_TRANSACTION slt
            where service not in ('anamnese', 'lab', 'rad');";

        $listService = DB::select($queryService);

        $summary = $dataKunjungan->first();
        $result = [
            'total_semua' => $summary->total_semua ?? 0,
            'total_integrasi' => $summary->total_integrasi ?? 0,
            'total_belum_integrasi' => $summary->total_belum_integrasi ?? 0,
        ];

        return view('pages.transaksi.rawatjalan.index', compact('result', 'satuSehatMenu', 'listService'));
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

        // Handle jika user memilih "All" (DataTables mengirimkan value -1)
        if ($length == -1) {
            $pageNumber = 1;
            $pageSize   = 9999999;
        } else {
            $safeLength = $length > 0 ? $length : 10;
            $pageNumber = ($start / $safeLength) + 1;
            $pageSize   = $safeLength;
        }

        $searchValue = $request->search['value'];
        $dataKunjungan = DB::select("
            EXEC dbo.sp_getKunjunganSatusehat_Header ?, ?, ?, ?, ?, ?, ?, ?
        ", [
            $id_unit,
            $tgl_awal_db,
            $tgl_akhir_db,
            'RAWAT_JALAN',
            $searchValue,
            $request->input('cari') ?? 'all',
            $pageNumber,
            $pageSize,

        ]);

        $dataKunjunganAll = DB::select("
            EXEC dbo.sp_getKunjunganSatusehat_Header ?, ?, ?, ?, ?, ?, ?, ?
        ", [
            $id_unit,
            $tgl_awal_db,
            $tgl_akhir_db,
            'RAWAT_JALAN',
            null,
            'all',
            1,
            1,

        ]);

        if (count($dataKunjunganAll) == 0) {
            return response()->json([
                "draw" => $draw,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
                // "summary" => [
                //     'total_semua' => 0,
                //     'rjAll' => 0,
                //     'ri' => 0,
                //     'total_sudah_integrasi' => 0,
                //     'total_belum_integrasi' => 0,
                // ]
            ]);
        }

        $summary = $dataKunjunganAll[0] ?? null;
        $totalData = [
            'total_semua' => $summary->recordsFiltered ?? 0,
            'total_integrasi' => $summary->total_lengkap ?? 0,
            'total_belum_integrasi' => $summary->total_belum_lengkap ?? 0,
        ];
        $recordsTotal    = $summary->recordsTotal ?? 0;
        $recordsFiltered = $summary->recordsFiltered ?? $recordsTotal;

        $data = [];
        $index = $start + 1;
        foreach ($dataKunjungan as $row) {
            $jenis = $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
            $id_transaksi = LZString::compressToEncodedURIComponent($row->KARCIS);
            $kdPasienSS = LZString::compressToEncodedURIComponent($row->ID_PASIEN_SS);
            $kdNakesSS = LZString::compressToEncodedURIComponent($row->ID_NAKES_SS);
            $kdLokasiSS = LZString::compressToEncodedURIComponent($row->ID_LOKASI_SS);
            $unit = LZString::compressToEncodedURIComponent($id_unit);
            $paramSatuSehat = "jenis_perawatan=" . $jenis . "&id_transaksi=" . $id_transaksi . "&kd_pasien_ss=" . $kdPasienSS . "&kd_nakes_ss=" . $kdNakesSS . "&kd_lokasi_ss=" .  $kdLokasiSS . "&id_unit=" .  $unit;
            $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);
            $data[] = [
                'DT_RowIndex' => $index++,
                'ID_TRANSAKSI' => $row->KARCIS,
                'NO_PESERTA' => $row->NO_PESERTA,
                'KBUKU' => $row->KBUKU,
                'checkbox' => $this->renderCheckbox($row, $paramSatuSehat),
                'JENIS_PERAWATAN' => $jenis,
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
            "data" => $data,
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

    private function renderCheckbox($row, $paramSatuSehat)
    {
        $checkBox = '';

        if ($row->ID_PASIEN_SS != null && $row->ID_NAKES_SS != null && $row->sudah_integrasi != 1) {
            $checkBox = "
                        <input type='checkbox' class='select-row chk-col-purple' value='$row->KARCIS' data-resend='$row->sudah_integrasi' data-param='$paramSatuSehat' id='$row->KARCIS' />
                        <label for='$row->KARCIS' style='margin-bottom: 0px !important; line-height: 25px !important; font-weight: 500'> &nbsp; </label>
                    ";
        }

        return $checkBox;
    }

    private function renderAction($row, $paramSatuSehat)
    {
        $btn = '';

        $dataErm = null;

        if ($row->ID_PASIEN_SS == null) {
            $btn = '<i class="text-muted">Pasien Belum Mapping</i>';
        } else if ($row->ID_NAKES_SS == null) {
            $btn .= '<i class="text-muted">Nakes Belum Mapping</i>';
        } else {
            if ($row->sudah_integrasi == '0') {
                $btn = '<a href="javascript:void(0)" onclick="sendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-primary w-100"><i class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>';
            } else {
                $btn = '<a href="javascript:void(0)" onclick="resendSatuSehat(`' . $paramSatuSehat . '`)" class="btn btn-sm btn-warning w-100"><i class="fas fa-link mr-2"></i>Kirim Ulang</a>';
            }
            $btn .= '<br>';
            $btn .= '<a href="javascript:void(0)" onclick="lihatDetail(`' . $row->KARCIS . '`)" class="mt-2 btn btn-sm btn-info w-100"><i class="fas fa-info-circle mr-2"></i>Lihat Detail</a>';
        }
        return $btn;
    }

    public function lihatDetail($param)
    {
        $param = base64_decode($param);
        $id_unit = trim((string)Session::get('id_unit', '001'));
        $detailKiriman = collect(DB::select("
            EXEC dbo.sp_getKunjunganSatusehat_Detail ?, ?, ?
        ", [
            $param,
            $id_unit,
            'RAWAT_JALAN'
        ]))->first();

        $data = collect(DB::select("SELECT DISTINCT * FROM dbo.fn_getDataKunjungan(?, 'RAWAT_JALAN') where ID_TRANSAKSI = ?", [
            $id_unit,
            $param
        ]))->first();

        // Dummy detail data
        $dataPasien = [
            'NAMA' => $data->NAMA_PASIEN,
            'KBUKU' => $data->KBUKU,
            'NO_PESERTA' => $data->NO_PESERTA,
            'KARCIS' => $data->ID_TRANSAKSI,
            'DOKTER' => $data->DOKTER,
            'statusIntegrated' => $detailKiriman->sudah_integrasi == '1' ? true : false,
        ];

        return response()->json([
            'dataPasien' => $dataPasien,
            'dataKirimanSatusehat' => $detailKiriman,
        ]);
    }

    public function getLog($param, $service)
    {
        $id_unit = Session::get('id_unit', '001');
        $karcis = $param;
        $encounter = DB::select("SELECT TOP 1 * FROM dbo.fn_getDataKunjungan(?, 'RAWAT_JALAN') where ID_TRANSAKSI = ?", [
            $id_unit,
            $karcis
        ]);

        $log = collect(DB::select(
            "SELECT * FROM SATUSEHAT.dbo.SATUSEHAT_LOG_TRANSACTION WHERE service = ? AND (request LIKE ? OR response LIKE ?)",
            [
                $service,
                '%' . $encounter[0]->ID_SATUSEHAT_ENCOUNTER . '%', // Tambahkan % di sini
                '%' . $encounter[0]->ID_SATUSEHAT_ENCOUNTER . '%', // Tambahkan % di sini
            ]
        ));

        return response()->json([
            'log' => $log
        ]);
    }

    public function sendSatuSehat(Request $request)
    {
        try {
            $params = LZString::decompressFromEncodedURIComponent($request->param);
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

            $id_unit = Session::get('id_unit', $arrParam['id_unit'] ?? null);
            $dataPeserta = DB::selectOne("SELECT no_peserta FROM SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN_MAPPING where idpx = ?", [$arrParam['kd_pasien_ss']]);
            $dataMedication = DB::selectOne("SELECT ID_TRANS from IF_HTRANS_OL where KARCIS = ? AND IDUNIT = ?", [$arrParam['id_transaksi'], $id_unit]);
            $dataErm = DB::selectOne("
                EXEC dbo.sp_getClinicalImpression ?, ?, ?, ?, ?, ?, ?, ?
            ", [
                $id_unit,
                null,
                null,
                'all',
                $arrParam['id_transaksi'] ?? '',
                null,
                1,
                1
            ]);

            // Base payload yang isinya general
            $post = [
                'id_unit' => $id_unit,
                'karcis' => $arrParam['id_transaksi'] ?? null,
                'aktivitas' => 'RAWAT JALAN',
                'jenis_layanan' => 'Rawat Jalan, RAWAT_JALAN, RAWAT JALAN',
                'noPeserta' => $dataPeserta->no_peserta ?? null,
                'ID_TRANS' => $dataMedication->ID_TRANS ?? null,
                'id_erm' => $dataErm->ID_ERM ?? null,
            ];

            // ==========================================
            // 1. DISPATCH BASE URLS (13 Endpoints)
            // ==========================================
            $urls = array(
                'api/encounter',
                'api/observasi',
                'api/allergy-intolerance',
                'api/service-request',
                'api/specimen',
                'api/medication-request',
                'api/medication-dispense',
                'api/clinical-impression',
                'api/care-plan',
                'api/episode-of-care',
                'api/diagnosis',
                'api/medstatement',
                'api/composition',
            );

            $postBase = $post;
            $postBase['aktivitas'] = 'KIRIM TRANSAKI RAWAT JALAN ALL IN';
            $postBase['post_from'] = 'Trigger Otomatis Update Rajal From Ranap';
            $postBase['url'] = $urls;

            $this->triggerDispatchInternal($postBase);

            // ==========================================
            // 2. KHUSUS PROCEDURE
            // ==========================================
            $typeProcedure = ['anamnese', 'lab', 'rad', 'operasi'];
            $icd9 = DB::selectOne("SELECT ICD9, DIPLAY_ICD9 FROM fn_getDataKunjungan(?, 'RAWAT_JALAN') WHERE ID_TRANSAKSI = ?", [$id_unit, $arrParam['id_transaksi']]);

            for ($i = 0; $i < count($typeProcedure); $i++) {
                $postProc = $post;
                $postProc['type'] = $typeProcedure[$i];
                $postProc['icd9_pm'] = $icd9->ICD9 ?? null;
                $postProc['text_icd9_pm'] = $icd9->DIPLAY_ICD9 ?? null;
                $postProc['url'] = ['api/procedure'];

                $this->triggerDispatchInternal($postProc);
            }

            // ==========================================
            // 3. KHUSUS SERVICE REQUEST & SPECIMEN
            // ==========================================
            $dataServiceRequest = DB::select("SELECT DISTINCT ID_RIWAYAT_ELAB, KLINIK_TUJUAN, KARCIS_RUJUKAN FROM vw_getData_Elab vgde where vgde.KARCIS_ASAL = ? AND vgde.IDUNIT = ?", [$arrParam['id_transaksi'], $id_unit]);

            foreach ($dataServiceRequest as $serviceRequest) {
                $postSr = $post;
                $postSr['idElab'] = $serviceRequest->ID_RIWAYAT_ELAB;
                $postSr['klinik'] = $serviceRequest->KLINIK_TUJUAN;
                $postSr['karcis'] = $serviceRequest->KARCIS_RUJUKAN;
                $postSr['url'] = ['api/service-request'];

                if ($serviceRequest->KLINIK_TUJUAN == '0017') {
                    array_push($postSr['url'], 'api/specimen');
                }

                $this->triggerDispatchInternal($postSr);
            }

            // ==========================================
            // 4. KHUSUS DIAGNOSTIC REPORT
            // ==========================================
            $dataDiagnosticReport = DB::select(
                "SELECT DISTINCT
                rdp.id, vgde.ID_RIWAYAT_ELAB, vgde.KLINIK_TUJUAN, vgde.KARCIS_ASAL, vgde.KARCIS_RUJUKAN
            from vw_getData_Elab vgde
            inner join RIRJ_DOKUMEN_PX rdp ON vgde.KARCIS_ASAL = rdp.karcis
                AND vgde.KD_TINDAKAN = rdp.kd_tindakan
            where vgde.KARCIS_ASAL = ?
                and vgde.IDUNIT = ?
                AND rdp.id_kategori = 1",
                [$arrParam['id_transaksi'], $id_unit]
            );

            foreach ($dataDiagnosticReport as $diagnosticReport) {
                $postDr = $post;
                $postDr['iddokumen'] = $diagnosticReport->id;
                $postDr['karcis'] = $diagnosticReport->KARCIS_ASAL;
                $postDr['karcis_rujukan'] = $diagnosticReport->KARCIS_RUJUKAN;
                $postDr['url'] = ['api/diagnostic-report'];

                $this->triggerDispatchInternal($postDr);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Integrasi berhasil diantrikan ke background job!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Gagal memproses integrasi: ' . $e->getMessage()
            ], 500);
        }
    }

    private function triggerDispatchInternal(array $payload)
    {
        $urls = $payload['url'];
        unset($payload['url']);

        Session::put('id_unit', $payload['id_unit']);

        $encounterId = DB::selectOne("
            SELECT ID_SATUSEHAT_ENCOUNTER FROM fn_getDataKunjungan(?, 'RAWAT_JALAN')
            WHERE ID_TRANSAKSI = ? AND TANGGAL >= DATEADD(YEAR, -1, GETDATE())
            UNION ALL
            SELECT ID_SATUSEHAT_ENCOUNTER FROM fn_getDataKunjungan(?, 'RAWAT_INAP')
            WHERE ID_TRANSAKSI = ? AND TANGGAL >= DATEADD(YEAR, -1, GETDATE())
        ", [
            $payload['id_unit'],
            $payload['karcis'],
            $payload['id_unit'],
            $payload['karcis'],
        ]);

        $arrKlinikRadLab = ['0016', '0015', '0021', '0017', '0031'];

        if (!is_array($urls)) {
            $urls = [$urls];
        }

        foreach ($urls as $val) {
            $endpoint = explode('/', $val)[1];

            if (isset($payload['klinik']) && !in_array($payload['klinik'], $arrKlinikRadLab)) {
                if ($endpoint !== 'encounter' && empty($encounterId->ID_SATUSEHAT_ENCOUNTER)) {
                    continue;
                }
            }

            DispatchToEndpoint::dispatch(
                $endpoint,
                $payload
            )->onQueue('incoming');
        }
    }
}
