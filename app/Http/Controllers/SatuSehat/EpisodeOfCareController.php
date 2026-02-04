<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Lib\LZCompressor\LZString;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\DataTables;

class EpisodeOfCareController extends Controller
{
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
            $jenis = $row->JENIS_PERAWATAN == 'RAWAT_JALAN' ? 'RJ' : 'RI';
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
         * 1. pengiriman episode of care di awal pemeriksaan (setelah ada encounter & condition)
         * 2. status awal = active
         * 3. update episode of care (resend)
         * 4. saat resend jika pengobatan sudah selesai (pasien pulang / discharge) maka status = finished
         * 5. jika masih dalam perawatan maka status = active
         * 6. catatan: episode of care harus terintegrasi setelah encounter & condition
         */
    }
}
