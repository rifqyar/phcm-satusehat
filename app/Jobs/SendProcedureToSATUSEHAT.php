<?php

namespace App\Jobs;

use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use App\Models\SATUSEHAT\SATUSEHAT_PROCEDURE;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SendProcedureToSATUSEHAT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SATUSEHATTraits, LogTraits;

    // public $tries = 2; // Number of attempts
    public $timeout = 5; // Timeout in seconds

    protected $payload;
    protected $arrParam;
    protected $dataKarcis;
    protected $dataPeserta;
    protected $baseurl;
    protected $url;
    protected $token;
    protected $type; // lab, rad, op
    protected $resend;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload, $arrParam, $dataKarcis, $dataPeserta, $baseurl, $url, $token, $type, $resend = false)
    {
        $this->payload = $payload;
        $this->arrParam = $arrParam;
        $this->dataKarcis = $dataKarcis;
        $this->dataPeserta = $dataPeserta;
        $this->baseurl = $baseurl;
        $this->url = $url;
        $this->token = $token;
        $this->type = $type;
        $this->resend = $resend;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $logChannel = explode('/', $this->url)[0];
        try {
            if (count($this->payload['payload']) > 0) {
                $controller = app('App\\Http\\Controllers\\SatuSehat\\ProcedureController');
                $response = $controller->consumeSATUSEHATAPI(!$this->resend ? 'POST' : 'PUT', $this->baseurl, $this->url, $this->payload['payload'], true, $this->token);

                $result = json_decode($response->getBody()->getContents(), true);

                if ($response->getStatusCode() >= 400) {
                    $res = json_decode($response->getBody(), true);

                    $this->logError($this->url, 'Gagal kirim data Procedure ' . $this->type, [
                        'payload' => $this->payload['payload'],
                        'response' => $res,
                        'user_id' => Session::get('username', 'system')
                    ]);

                    $this->logDb(json_encode($res), $this->url, json_encode($this->payload['payload']), 'system'); //Session::get('id')

                    $msg = $result['issue'][0]['details']['text'] ?? 'Gagal Kirim Data Encounter';
                    throw new Exception($msg, $response->getStatusCode());
                } else {
                    if ($this->type == 'lab' || $this->type == 'rad') {
                        $dataICD = $this->payload['dataICD'];
                        for ($i = 0; $i < count($dataICD); $i++) {
                            $procedureData = [
                                'KBUKU' => $this->dataKarcis->KBUKU,
                                'NO_PESERTA' => $this->dataPeserta->NO_PESERTA,
                                'ID_SATUSEHAT_ENCOUNTER' => $this->arrParam['encounter_id'],
                                'ID_JENIS_TINDAKAN' => $this->payload['id_tindakan'][$i],
                                'ID_TINDAKAN' => $this->payload['kd_tindakan'][$i],
                                'KD_ICD9' => $dataICD[$i]->icd9,
                                'DISP_ICD9' => $dataICD[$i]->text_icd9,
                                'JENIS_TINDAKAN' => $this->type,
                                'KDDOK' => $this->payload['kddok'],
                                'ID_SATUSEHAT_PROCEDURE' => $result['id'],
                                'STATUS_SINCRON' => 1,
                                'SINCRON_DATE' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s')
                            ];

                            $procedureSatuSehat = SATUSEHAT_PROCEDURE::where('ID_JENIS_TINDAKAN', $this->payload['id_tindakan'][$i]);
                            $existingProcedure = $procedureSatuSehat->where('KARCIS', $this->arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$this->dataKarcis->KARCIS : (int)$this->dataKarcis->NOREG)
                                ->where('JENIS_TINDAKAN', $this->type)
                                ->where('ID_TINDAKAN', $this->payload['kd_tindakan'][$i])
                                ->where('ID_JENIS_TINDAKAN', $this->payload['id_tindakan'][$i])
                                ->first();

                            if ($existingProcedure) {
                                DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE')
                                    ->where('KARCIS', $this->arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$this->dataKarcis->KARCIS : (int)$this->dataKarcis->NOREG)
                                    ->where('JENIS_TINDAKAN', $this->type)
                                    ->where('ID_TINDAKAN', $this->payload['kd_tindakan'][$i])
                                    ->where('ID_JENIS_TINDAKAN', $this->payload['id_tindakan'][$i])
                                    ->update($procedureData);
                            } else {
                                $procedureData['KARCIS'] = $this->arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$this->dataKarcis->KARCIS : (int)$this->dataKarcis->NOREG;
                                $procedureData['CRTDT'] = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                                $procedureData['CRTUSER'] = 'system';
                                SATUSEHAT_PROCEDURE::create($procedureData);
                            }

                            $this->logInfo($logChannel, 'Sukses kirim data Procedure ' . $this->type, [
                                'payload' => $this->payload,
                                'response' => $result,
                                'user_id' => Session::get('username', 'system') //Session::get('id')
                            ]);

                            $this->logDb(json_encode($result), $this->url, json_encode($this->payload), 'system'); //Session::get('id')
                        }
                    } else {
                        $procedureData = [
                            'KBUKU' => $this->dataKarcis->KBUKU,
                            'NO_PESERTA' => $this->dataPeserta->NO_PESERTA,
                            'ID_SATUSEHAT_ENCOUNTER' => $this->arrParam['encounter_id'],
                            'ID_JENIS_TINDAKAN' => $this->payload['id_tindakan'],
                            'KD_ICD9' => $this->payload['kodeICD'],
                            'DISP_ICD9' => $this->payload['textICD'],
                            'JENIS_TINDAKAN' => $this->type,
                            'KDDOK' => $this->payload['kddok'],
                            'ID_SATUSEHAT_PROCEDURE' => $result['id'],
                            'STATUS_SINCRON' => 1,
                            'SINCRON_DATE' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s')
                        ];

                        $procedureSatuSehat = SATUSEHAT_PROCEDURE::where('ID_JENIS_TINDAKAN', $this->payload['id_tindakan']);
                        $existingProcedure = $procedureSatuSehat->where('KARCIS', $this->arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$this->dataKarcis->KARCIS : (int)$this->dataKarcis->NOREG)
                            ->where('JENIS_TINDAKAN', $this->type)
                            ->first();

                        if ($existingProcedure) {
                            DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE')
                                ->where('KARCIS', $this->arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$this->dataKarcis->KARCIS : (int)$this->dataKarcis->NOREG)
                                ->where('JENIS_TINDAKAN', $this->type)
                                ->update($procedureData);
                        } else {
                            $procedureData['KARCIS'] = $this->arrParam['jenis_perawatan'] == 'RAWAT_JALAN' ? (int)$this->dataKarcis->KARCIS : (int)$this->dataKarcis->NOREG;
                            $procedureData['CRTDT'] = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                            $procedureData['CRTUSER'] = 'system';
                            SATUSEHAT_PROCEDURE::create($procedureData);
                        }

                        $this->logInfo($logChannel, 'Sukses kirim data Procedure ' . $this->type, [
                            'payload' => $this->payload,
                            'response' => $result,
                            'user_id' => Session::get('username', 'system') //Session::get('id')
                        ]);

                        $this->logDb(json_encode($result), $this->url, json_encode($this->payload), 'system'); //Session::get('id')
                    }
                }
            } else {
                $this->logInfo($logChannel, 'Sudah Integrasi ' . $this->type, [
                    'payload' => $this->payload,
                    'response' => 'Data Procedure Untuk jenis ini sudah pernah dikirim ke satusehat',
                    'user_id' => Session::get('username', 'system') //Session::get('id')
                ]);
            }
        } catch (Exception $e) {
            $this->logError($logChannel, 'Gagal kirim data Procedure ' . $this->type, [
                'payload' => $this->payload,
                'response' => $e->getMessage(),
                'user_id' => Session::get('username', 'system') //Session::get('id')
            ]);
            $this->fail($e);
        }
    }
}
