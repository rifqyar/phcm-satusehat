<?php

namespace App\Services;

use App\Http\Controllers\SatuSehat\MedicationDispenseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicationDispenseService
{
    /**
     * Entry point dari Dispatch
     */
    public function process(array $payload): void
    {
        $idTrans = $payload['ID_TRANS'] ?? null;
        $id_unit = $payload['id_unit'] ?? null;

        if (!$idTrans) {
            throw new \Exception('ID_TRANS wajib ada');
        }
        $encounter = $this->getEncounterDispense($idTrans, $id_unit);

        if (!$encounter || !$encounter->id_satusehat_encounter) {
            // Dispense tidak boleh jalan tanpa encounter
            return;
        }

        $items = $this->getMedicationDispenseByTrans($idTrans, $id_unit);

        if ($items->isEmpty()) {
            // tidak ada obat
            return;
        }

        $items = $items->filter(function ($item) {
            return !empty($item->KD_BRG_KFA)
                && $item->KD_BRG_KFA !== '0'
                && $item->KD_BRG_KFA !== '000'; // alat kesehatan
        });

        if ($items->isEmpty()) {
            // ada obat, tapi tidak ada yang layak dikirim
            return;
        }

        // prepMedicationDispense
        $controller = app(MedicationDispenseController::class);
        $param = [
            '_token' => 'LmSrzcaz4B8OBpO671qyR8k9TmM6spX09D1YPS7O',
            'id_trans' => $idTrans,
        ];

        $result = $controller->prepMedicationDispense(new Request($param));
    }
    /**
     * Cek Encounter untuk Dispense
     */
    private function getEncounterDispense(string $idTrans, string $id_unit)
    {
        return DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.IF_HTRANS as ih')
            ->join(
                'SIRS_PHCM.dbo.IF_HTRANS_OL as iho',
                'ih.ID_TRANS_OL',
                '=',
                'iho.ID_TRANS'
            )
            ->leftJoin(
                'SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA as NT',
                'iho.KARCIS',
                '=',
                'NT.karcis'
            )
            ->select([
                'ih.ID_TRANS_OL',
                'iho.ID_TRANS as ID_TRANS_DOKTER',
                'NT.id_satusehat_encounter',
            ])
            ->where('ih.IDUNIT', $id_unit)
            ->where('ih.ID_TRANS', $idTrans)
            ->first();
    }

    /**
     * Ambil data obat untuk MedicationDispense
     */
    private function getMedicationDispenseByTrans(string $idTrans, string $id_unit)
    {
        return DB::connection('sqlsrv')
            ->table('SIRS_PHCM.dbo.IF_TRANS as i')
            ->join(
                'SIRS_PHCM.dbo.IF_HTRANS as ih',
                'i.ID_TRANS',
                '=',
                'ih.ID_TRANS'
            )
            ->leftJoin(
                'SIRS_PHCM.dbo.IF_HTRANS_OL as iho',
                'ih.ID_TRANS_OL',
                '=',
                'iho.ID_TRANS'
            )
            ->leftJoin(
                'SIRS_PHCM.dbo.M_TRANS_KFA as m',
                'i.KDBRG_CENTRA',
                '=',
                'm.KDBRG_CENTRA'
            )
            ->select([
                'i.ID_TRANS',
                'i.NAMABRG as NAMA_OBAT',
                'i.KDBRG_CENTRA',
                'm.KD_BRG_KFA',
                'm.NAMABRG_KFA',
                'ih.ID_TRANS_OL',
                'iho.ID_TRANS as ID_TRANS_DOKTER',
            ])
            ->where('ih.IDUNIT', $id_unit)
            ->where('i.ID_TRANS', $idTrans)
            ->get();
    }
}
