<?php

namespace App\Services;

use App\Http\Traits\LogTraits;
use App\Jobs\SendQuestionnaireResJob;
use App\Lib\LZCompressor\LZString;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA;
use Illuminate\Support\Facades\DB;

class QuestionnaireResponseService
{
  use LogTraits;

  public function process(array $payload): void
  {
    DB::disableQueryLog();
    $id_unit = $payload['id_unit'] ?? null;
    $this->logInfo('QuestionnaireResponse', 'Process Questionnaire Response dari SIMRS', [
      'payload' => $payload,
      'user_id' => 'system',
    ]);

    $data = $this->getKunjunganData($payload, $id_unit);
    if (! $data) {
      throw new \Exception('Data kunjungan tidak ditemukan');
    }

    if (
      empty($data->ID_LOKASI_SS) ||
      empty($data->ID_NAKES_SS) ||
      empty($data->ID_PASIEN_SS)
    ) {
      return;
    }

    $param = $this->buildEncryptedParam($payload, $data);
    SendQuestionnaireResJob::dispatch($param)->onQueue('QuestionnaireResponse');
  }

  protected function getKunjunganData(array $payload, $id_unit)
  {
    $jenisPerawatan = 'RAWAT_JALAN';
    if (strtoupper($payload['jenis_layanan']) == 'JALAN' || str_contains(strtoupper($payload['jenis_layanan']), 'JALAN')) {
      $jenisPerawatan = 'RAWAT_JALAN';
    } else {
      $jenisPerawatan = 'RAWAT_INAP';
    }

    $data = DB::selectOne('SELECT * FROM dbo.fn_getDataKunjungan(?, ?) WHERE ID_TRANSAKSI = ?', [
      $id_unit,
      $jenisPerawatan,
      $payload['karcis']
    ]);


    if (! $data) {
      throw new \Exception('Data Kunjungan ' . $jenisPerawatan . ' tidak ditemukan');
    }

    $data->jenisPerawatan = $jenisPerawatan == 'RAWAT_JALAN' ? 'RJ' : 'RI';
    return $data;
  }

  protected function buildEncryptedParam(array $payload, $data): string
  {
    $id_transaksi = LZString::compressToEncodedURIComponent($payload['karcis']);
    $kdPasienSS = LZString::compressToEncodedURIComponent($data->ID_PASIEN_SS);
    $kdNakesSS = LZString::compressToEncodedURIComponent($data->ID_NAKES_SS);
    $kdLokasiSS = LZString::compressToEncodedURIComponent($data->ID_LOKASI_SS);
    $id_unit = LZString::compressToEncodedURIComponent($payload['id_unit']);
    $paramSatuSehat = "jenis_perawatan=" . $data->jenisPerawatan . "&id_transaksi=" . $id_transaksi . "&kd_pasien_ss=" . $kdPasienSS . "&kd_nakes_ss=" . $kdNakesSS . "&kd_lokasi_ss=" .  $kdLokasiSS . "&id_unit=" .  $id_unit;
    $paramSatuSehat = LZString::compressToEncodedURIComponent($paramSatuSehat);

    return $paramSatuSehat;
  }
}
