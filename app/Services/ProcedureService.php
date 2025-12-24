<?php

namespace App\Services;

use App\Http\Controllers\SatuSehat\ProcedureController;
use App\Http\Traits\LogTraits;
use App\Jobs\SendEncounter;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA;
use App\Models\SATUSEHAT\SATUSEHAT_PROCEDURE;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcedureService
{
    use LogTraits;

    public function process(array $payload): void
    {
        DB::disableQueryLog();
        $id_unit = $payload['id_unit'] ?? null;

        $this->logInfo('Procedure', 'Process Procedure dari SIMRS', [
            'payload' => $payload,
            'karcis' => $payload['karcis'],
            'jenis' => $payload['jenis'],
            'user_id' => 'system',
        ]);

        $data = $this->getKunjunganData($payload, $id_unit);
        if (! $data) {
            throw new \Exception('Data Procedure tidak ditemukan');
        }

        if ($data && ($data->id_satusehat_encounter == '' || $data->id_satusehat_encounter == null)) return;

        $param = $this->buildEncryptedParam($payload, $data);

        app(ProcedureController::class)->sendSatuSehat(new Request([
            'param' => $param['param'],
            'icd9_pm' => $param['icd9_pm'],
            'text_icd9_pm' => $param['text_icd9_pm'],
        ]), $param['resend'], $payload['type'] ?? 'all');
    }

    protected function getKunjunganData(array $payload, $id_unit)
    {
        $data = null;
        if (strtoupper($payload['jenis_layanan']) == 'JALAN' || str_contains(strtoupper($payload['jenis_layanan']), 'JALAN')) {
            $data = collect(DB::select("
                EXEC dbo.sp_getTindakanRawatJalan ?
            ", [
                $id_unit
            ]))->first();
        } else {
            $data = collect(DB::select("
                EXEC dbo.sp_getTindakanRawatInap ?
            ", [
                $id_unit
            ]))->first();
        }

        if (! $data) {
            throw new \Exception('Data Kunjungan ' . $payload['jenis_layanan'] . ' tidak ditemukan');
        }

        return $data;
    }

    protected function buildEncryptedParam(array $payload, $data): array
    {
        $id_transaksi = LZString::compressToEncodedURIComponent($data->KARCIS);
        $KbBuku = LZString::compressToEncodedURIComponent($data->KBUKU);
        $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
        $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
        $idEncounter = LZString::compressToEncodedURIComponent($data->id_satusehat_encounter);
        $jenisPerawatan = LZString::compressToEncodedURIComponent($data->JENIS_PERAWATAN);
        $paramSatuSehat = "sudah_integrasi=$data->sudah_integrasi&karcis=$id_transaksi&kbuku=$KbBuku&id_pasien_ss=$kdPasienSS&id_nakes_ss=$kdNakesSS&encounter_id=$idEncounter&jenis_perawatan=$jenisPerawatan";
        $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

        // get ICD 9 Anamnese
        $icd9 = DB::table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_ICD9 as icd9')
            ->where('icd9.ID', $payload['diagnosa9cm'])
            ->select('icd9.CODE as icd9_pm', 'icd9.NAME as text_icd9_pm')
            ->first();

        $resend = false;
        if ($payload['type'] == 'anamnese') {
            $procedureData = SATUSEHAT_PROCEDURE::where('karcis', (int)$payload['karcis'])
                ->where('JENIS_TINDAKAN', 'anamnese')
                ->count();

            if ($procedureData > 0) {
                $resend = true;
            }
        } else if ($payload['type'] == 'lab') {
            $dataLab = DB::table('vw_getData_Elab as ere')
                ->where('ere.KARCIS_RUJUKAN', $payload['karcis'])
                ->where('ere.KLINIK_TUJUAN', '0017')
                ->first();

            $procedureData = SATUSEHAT_PROCEDURE::where('karcis', (int)$payload['karcis'])
                ->where('JENIS_TINDAKAN', 'lab')
                ->where('ID_JENIS_TINDAKAN', $dataLab->ID_RIWAYAT_ELAB)
                ->count();

            if ($procedureData > 0) {
                $resend = true;
            }
        } else if ($payload['type'] == 'rad') {
            $dataRad = DB::table('vw_getData_Elab as ere')
                ->where('ere.KARCIS_RUJUKAN', $payload['karcis'])
                ->where(function ($query) {
                    $query->where('ere.KLINIK_TUJUAN', '0016')
                        ->orWhere('ere.KLINIK_TUJUAN', '0015');
                })
                ->first();
            $procedureData = SATUSEHAT_PROCEDURE::where('karcis', (int)$payload['karcis'])
                ->where('JENIS_TINDAKAN', 'rad')
                ->where('ID_JENIS_TINDAKAN', $dataRad->ID_RIWAYAT_ELAB)
                ->count();

            if ($procedureData > 0) {
                $resend = true;
            }
        } else if ($payload['type'] == 'operasi') {
            $procedureData = SATUSEHAT_PROCEDURE::where('karcis', (int)$payload['karcis'])
                ->where('JENIS_TINDAKAN', 'operasi')
                ->count();

            if ($procedureData > 0) {
                $resend = true;
            }
        }

        if (($icd9->icd9_pm == '' || $icd9->text_icd9_pm == null) && $payload['type'] == 'anamnese') {
            $this->logInfo('Procedure', 'Data Procedure Anamnese tidak diproses karena tidak ada ICd 9', [
                'request' => $payload,
                'user_id' => 'system'
            ]);

            return [
                'resend' => false,
                'param' => null
            ];
        }

        return [
            'resend' => $resend,
            'param' => $paramSatuSehat,
            'icd9_pm' => $icd9->icd9_pm ?? null,
            'text_icd9_pm' => $icd9->text_icd9_pm ?? null
        ];
    }
}
