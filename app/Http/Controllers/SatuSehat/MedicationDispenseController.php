<?php

namespace App\Http\Controllers\SatuSehat;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MedicationDispenseController extends Controller
{
    public function index(Request $request)
    {
        return response()->view('pages.satusehat.medicationdispense.index');
    }

    public function datatable(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$startDate || !$endDate) {
            $endDate = now();
            $startDate = now()->subDays(30);
        }

        // 🧱 Query utama Medication Dispense (sudah ditambah join dokter ac.nmDok)
        $query = DB::table(DB::raw("
        (
            SELECT DISTINCT
                a.KLINIK AS KodeKlinik,
                a.NOURUT AS NomorUrut,
                a.KARCIS AS NomorKarcis,
                CONVERT(varchar(10), a.TGL, 105) AS TanggalKarcis,
                a.KBUKU AS KodeBuku,
                a.NO_PESERTA AS NomorPeserta,
                b.NAMA AS NamaPasien,
                c.NMDEBT AS NamaDebitur,
                b.TGL_LHR AS TanggalLahir,
                'N' AS FlagKirim,
                a.TGL AS TanggalKunjungan,
                CASE 
                    WHEN k.NO_KUNJUNG IS NULL THEN 'BELUM'
                    ELSE 'SELESAI'
                END AS StatusRekamMedis,
                CASE 
                    WHEN m.NO_KUNJUNG IS NULL THEN 'TUTUP'
                    ELSE 'BUKA'
                END AS StatusPermintaanIsian,
                aa.NOTA,
                ab.ID_TRANS,
                ac.nmDok AS NamaDokter
            FROM SIRS_PHCM.dbo.RJ_KARCIS a
            JOIN SIRS_PHCM.dbo.RIRJ_MASTERPX b
                ON a.NO_PESERTA = b.NO_PESERTA
            JOIN SIRS_PHCM.dbo.RIRJ_MDEBITUR c
                ON a.KDEBT = c.KDDEBT
            LEFT JOIN SIRS_PHCM.dbo.RJ_KARCIS_BAYAR aa
                ON a.KARCIS = aa.KARCIS
            LEFT JOIN SIRS_PHCM.dbo.IF_HTRANS ab
                ON aa.NOTA = ab.NOTA
            LEFT JOIN SIRS_PHCM.dbo.DR_MDOKTER ac
                ON a.KDDOK = ac.kdDok
            LEFT JOIN E_RM_PHCM.dbo.ERM_NOMOR_KUNJUNG j
                ON a.KARCIS = j.KARCIS AND a.IDUNIT = j.IDUNIT
            LEFT JOIN E_RM_PHCM.dbo.ERM_RM_IRJA k
                ON j.NO_KUNJUNG = k.NO_KUNJUNG AND k.AKTIF = '1'
            LEFT JOIN E_RM_PHCM.dbo.ERM_PERMINTAAN_ISIAN m
                ON j.NO_KUNJUNG = m.NO_KUNJUNG AND m.AKTIF = 'true'
            WHERE
                ISNULL(a.SELESAI, 0) NOT IN ('9','10')
                AND ISNULL(a.STBTL, 0) = 0
                AND ab.ID_TRANS IS NOT NULL
        ) AS src
    "))
            ->whereBetween(DB::raw('CONVERT(date, src.TanggalKunjungan)'), [$startDate, $endDate])
            ->select(
                'src.KodeKlinik',
                'src.NomorUrut',
                'src.NomorKarcis',
                'src.TanggalKarcis',
                'src.KodeBuku',
                'src.NomorPeserta',
                'src.NamaPasien',
                'src.NamaDebitur',
                DB::raw("CONVERT(varchar(10), src.TanggalLahir, 105) AS TanggalLahir"),
                'src.FlagKirim',
                DB::raw("CONVERT(varchar(10), src.TanggalKunjungan, 105) AS TanggalKunjungan"),
                'src.StatusRekamMedis',
                'src.StatusPermintaanIsian',
                'src.NOTA',
                'src.ID_TRANS',
                'src.NamaDokter'
            );

        // 🧮 Hitung total data (sebelum filter datatable)
        $allData = $query->get();
        $recordsTotal = $allData->count();

        // 🚀 DataTables server-side
        $dataTable = DataTables::of($query)
            ->order(function ($query) {
                $query->orderBy('src.TanggalKunjungan', 'desc');
            })
            ->make(true);

        // 📦 Tambahkan summary ke JSON
        $json = $dataTable->getData(true);
        $json['summary'] = [
            'all' => $recordsTotal,
        ];

        return response()->json($json);
    }

    public function getDetailObat(Request $request)
    {
        $idTrans = $request->id; // ID_TRANS dikirim dari tombol lihatObat(id)

        try {
            $data = DB::select("
            SELECT 
                i.ID_TRANS,
                i.NAMABRG AS NAMA_OBAT,
                i.KDBRG_CENTRA,
                m.KD_BRG_KFA,
                m.NAMABRG_KFA
            FROM SIRS_PHCM.dbo.IF_TRANS i
            LEFT JOIN SIRS_PHCM.dbo.M_TRANS_KFA m 
                ON i.KDBRG_CENTRA = m.KDBRG_CENTRA
            WHERE i.ID_TRANS = :idTrans
        ", ['idTrans' => $idTrans]);

            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data obat tidak ditemukan untuk transaksi tersebut.'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}