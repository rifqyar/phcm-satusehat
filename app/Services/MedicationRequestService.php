<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SatuSehat\MedicationRequestController;

class MedicationRequestService
{
    public function process(array $payload): void
    {
        $idTrans = $payload['ID_TRANS'] ?? null;

        if (!$idTrans) {
            throw new \Exception('ID_TRANS wajib ada');
        }

        $encounter = $this->getEncounterByTrans($idTrans);

        if (!$encounter || !$encounter->id_satusehat_encounter) {
            throw new \Exception("Encounter SATUSEHAT belum ada untuk $idTrans");
        }

        // query obat
        $items = $this->getMedicationByTrans($idTrans);

        // filter obat valid
        $items = $items->filter(function ($item) {
            return !empty($item->KD_BRG_KFA)
                && $item->KD_BRG_KFA != '0'
                && $item->KD_BRG_KFA !== '000';
        });

        if ($items->isEmpty()) {
            return;
        }

        // ===============================
        // 🔥 PANGGIL FUNGSI LAMA
        // ===============================
        app(MedicationRequestController::class)
            ->sendMedRequestPayload($idTrans);
    }


    private function getEncounterByTrans(string $idTrans)
    {
        return DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.IF_HTRANS_OL as H')
            ->leftJoin(
                'SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as SS',
                'SS.karcis',
                '=',
                'H.KARCIS'
            )
            ->select([
                'H.ID_TRANS',
                'H.TGL as TGL_ENTRY',
                'SS.id_satusehat_encounter',
            ])
            ->where('H.ID_TRANS', $idTrans)
            ->where('H.ACTIVE', 1)
            ->whereIn('H.IDUNIT', ['001', '002'])
            ->first();
    }

    private function getMedicationByTrans(string $idTrans)
    {
        return DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.IF_HTRANS_OL as H')
            ->join(
                'SIRS_PHCM.dbo.IF_TRANS_OL as T',
                'H.ID_TRANS',
                '=',
                'T.ID_TRANS'
            )
            ->leftJoin(
                'SIRS_PHCM.dbo.M_TRANS_KFA as K',
                'T.KDBRG_CENTRA',
                '=',
                'K.KDBRG_CENTRA'
            )
            ->leftJoin(
                'SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION as LM',
                function ($join) {
                    $join->on('T.ID_TRANS', '=', 'LM.LOCAL_ID')
                        ->on('K.KD_BRG_KFA', '=', 'LM.KFA_CODE')
                        ->where('LM.status', '=', 'success');
                }
            )
            ->select([
                'T.ID_TRANS',
                'T.NO',
                'T.NAMABRG as NAMA_OBAT',
                'T.SIGNA2 as SIGNA',
                'T.KDBRG',
                'T.KETQTY as KET',
                'T.JUMLAH',
                'H.TGL as TGL_ENTRY',
                'T.ID_TRANS as IDTRANS',
                'K.KD_BRG_KFA',
                'K.NAMABRG_KFA',
                'T.KDBRG_CENTRA',
                'LM.ID as SATUSEHAT_LOG_ID',
            ])
            ->where('H.ID_TRANS', $idTrans)
            ->where('H.ACTIVE', 1)
            ->whereIn('H.IDUNIT', ['001', '002'])
            ->get();
    }
}
