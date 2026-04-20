<?php

namespace App\Services;

use App\Http\Controllers\SatuSehat\ImunisasiController;
use App\Http\Traits\LogTraits;
use App\Jobs\SendEncounter;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImmunizationService
{
    use LogTraits;

    public function process(array $payload): void
    {
        DB::disableQueryLog();
        $id_unit = $payload['id_unit'] ?? null;
        $this->logInfo('Immunization', 'Process Immunization dari SIMRS', [
            'payload' => $payload,
            'user_id' => 'system',
        ]);

        $immunizationId = DB::table('E_RM_PHCM.dbo.ERM_IMUNISASI_PX')
            ->where('KARCIS', $payload['karcis'])
            ->where('IDUNIT', $id_unit)
            ->get();

        $data = $this->getKunjunganData($payload, $id_unit);
        if (! $data) {
            throw new \Exception('Data imunisasi tidak ditemukan');
        }

        if (
            empty($data->ID_LOKASI_SS) ||
            empty($data->ID_NAKES_SS) ||
            empty($data->ID_PASIEN_SS)
        ) {
            return;
        }

        foreach ($immunizationId as $val) {
            $resend = $val->SATUSEHAT_STATUS === 'SUCCESS' ? true : false;

            app(ImunisasiController::class)->kirimImunisasiSatusehat(new Request([
                'id_imunisasi_px' => $val->id_imunisasi_px,
                'id_unit' => $payload['id_unit'],
                'resend' => $resend
            ]));
        }
    }

    protected function getKunjunganData(array $payload, $id_unit)
    {
        $data = DB::select("SELECT
            A.ID_IMUNISASI_PX,
            C.id_satusehat_encounter,
            C.ID_TRANSAKSI as karcis,
            C.NAMA_PASIEN,
            C.ID_PASIEN_SS,
            C.ID_NAKES_SS,
            C.ID_LOKASI_SS,
            A.TANGGAL,
            A.JENIS_VAKSIN,
            A.DOSIS,
            A.KODE_CENTRA,
            A.KODE_VAKSIN,
            A.DISPLAY_VAKSIN,
            A.SATUSEHAT_STATUS,
            A.CRTDT
        FROM E_RM_PHCM.dbo.ERM_IMUNISASI_PX A
        LEFT JOIN fn_getDataKunjungan(?, 'RAWAT_JALAN') C
	        ON A.KARCIS = C.ID_TRANSAKSI
        WHERE A.IDUNIT = ? AND C.ID_TRANSAKSI = ?", [$id_unit, $id_unit, $payload['karcis']]);


        if (! $data) {
            throw new \Exception('Data Immunisasi tidak ditemukan');
        }

        return $data;
    }
}
