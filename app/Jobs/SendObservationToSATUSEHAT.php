<?php

namespace App\Jobs;

use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Models\SATUSEHAT\SATUSEHAT_OBSERVATION;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendObservationToSATUSEHAT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SATUSEHATTraits, LogTraits;

    protected $payload;
    protected $arrParam;
    protected $dataKarcis;
    protected $dataPeserta;
    protected $baseurl;
    protected $url;
    protected $token;
    protected $type; // TD (Sistolik, Diastolik), DJ, Tinggi, Berat

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, $type)
    {
        $this->payload = $payload;
        $this->arrParam = $arrParam;
        $this->dataKarcis = $dataKarcis;
        $this->dataPeserta = $dataPeserta;
        $this->baseurl = $baseurl;
        $this->url = $url;
        $this->token = $token;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if (count($this->payload) > 0) {
                $observasiSatuSehat = SATUSEHAT_OBSERVATION::where('JENIS', $this->type);
                $controller = app('App\\Http\\Controllers\\SatuSehat\\ObservasiController');
                $response = $controller->consumeSATUSEHATAPI('POST', $this->baseurl, $this->url, $this->payload, true, $this->token);

                $result = json_decode($response->getBody()->getContents(), true);

                if ($response->getStatusCode() >= 400) {
                    $res = json_decode($response->getBody(), true);

                    $this->logError($this->url, 'Gagal kirim data Observation ' . $this->type, [
                        'payload' => $this->payload,
                        'response' => $res,
                        'user_id' => 'system'
                    ]);

                    $this->logDb(json_encode($res), $this->url, json_encode($this->payload), 'system'); //Session::get('id')

                    $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Kirim Data Encounter';
                    throw new Exception($msg, $response->getStatusCode());
                } else {
                    if ($this->arrParam['jenis_pemeriksaan'] == 'RAWAT_JALAN') {
                        $dataErm = DB::table('E_RM_PHCM.dbo.ERM_RM_IRJA')
                            ->where('KARCIS', (int)$this->dataKarcis->KARCIS)
                            ->where('AKTIF', 1)
                            ->first();
                    } else {
                        $dataErm = DB::table('E_RM_PHCM.dbo.ERM_RI_F_ASUHAN_KEP_AWAL_HEAD')
                            ->where('NOREG', (int)$this->dataKarcis->KARCIS)
                            ->where('AKTIF', 1)
                            ->first();
                    }

                    $observasiData = [
                        'KBUKU' => $this->dataKarcis->KBUKU,
                        'NO_PESERTA' => $this->dataPeserta->NO_PESERTA,
                        'KDDOK' => $this->dataKarcis->KDDOK,
                        'ID_SATUSEHAT_ENCOUNTER' => $this->arrParam['encounter_id'],
                        'JENIS' => $this->type,
                        'ID_ERM' => $dataErm->NOMOR,
                        'ID_SATUSEHAT_OBSERVASI' => $result['id'],
                        'STATUS_SINCRON' => 1,
                        'SINCRON_DATE' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s')
                    ];

                    $existingObservasi = $observasiSatuSehat->where('KARCIS', (int)$this->dataKarcis->KARCIS)
                        ->where('ID_ERM', $dataErm->NOMOR)
                        ->first();

                    if ($existingObservasi) {
                        DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_OBSERVASI')
                            ->where('KARCIS', $this->dataKarcis->KARCIS)
                            ->where('JENIS', $this->type)
                            ->update($observasiData);
                    } else {
                        $observasiData['KARCIS'] = (int)$this->dataKarcis->KARCIS;
                        $observasiData['CRTDT'] = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                        $observasiData['CRTUSER'] = 'system';
                        SATUSEHAT_OBSERVATION::create($observasiData);
                    }

                    $this->logInfo($this->url, 'Sukses kirim data Observasi ' . $this->type, [
                        'payload' => $this->payload,
                        'response' => $result,
                        'user_id' => 'system' //Session::get('id')
                    ]);

                    $this->logDb(json_encode($result), $this->url, json_encode($this->payload), 'system'); //Session::get('id')
                }
            } else {
                $this->logInfo($this->url, 'Sudah Integrasi ' . $this->type, [
                    'payload' => $this->payload,
                    'response' => 'Data Observasi Untuk jenis ini sudah pernah dikirim ke satusehat',
                    'user_id' => 'system' //Session::get('id')
                ]);
            }
        } catch (Exception $e) {
            $this->logError($this->url, 'Gagal kirim data Observasi ' . $this->type, [
                'payload' => $this->payload,
                'response' => $e->getMessage(),
                'user_id' => 'system' //Session::get('id')
            ]);
            $this->fail($e);
        }
    }
}
