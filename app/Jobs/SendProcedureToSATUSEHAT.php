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

class SendProcedureToSATUSEHAT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SATUSEHATTraits, LogTraits;

    protected $payload;
    protected $arrParam;
    protected $dataKarcis;
    protected $dataPeserta;
    protected $baseurl;
    protected $url;
    protected $token;
    protected $type; // lab, rad, op
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
            if (count($this->payload['payload']) > 0) {
                $procedureSatuSehat = SATUSEHAT_PROCEDURE::where('ID_JENIS_TINDAKAN', $this->payload['id_tindakan']);
                $controller = app('App\\Http\\Controllers\\SatuSehat\\ProcedureController');
                $response = $controller->consumeSATUSEHATAPI('POST', $this->baseurl, $this->url, $this->payload['payload'], true, $this->token);

                $result = json_decode($response->getBody()->getContents(), true);

                if ($response->getStatusCode() >= 400) {
                    $res = json_decode($response->getBody(), true);

                    $this->logError($this->url, 'Gagal kirim data Procedure ' . $this->type, [
                        'payload' => $this->payload['payload'],
                        'response' => $res,
                        'user_id' => 'system'
                    ]);

                    $this->logDb(json_encode($res), $this->url, json_encode($this->payload['payload']), 'system'); //Session::get('id')

                    $msg = $response['issue'][0]['details']['text'] ?? 'Gagal Kirim Data Encounter';
                    throw new Exception($msg, $response->getStatusCode());
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

                    $existingProcedure = $procedureSatuSehat->where('KARCIS', (int)$this->dataKarcis->KARCIS)
                        ->where('JENIS_TINDAKAN', $this->type)
                        ->first();

                    if ($existingProcedure) {
                        DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_PROCEDURE')
                            ->where('KARCIS', $this->dataKarcis->KARCIS)
                            ->where('JENIS_TINDAKAN', $this->type)
                            ->update($procedureData);
                    } else {
                        $procedureData['KARCIS'] = (int)$this->dataKarcis->KARCIS;
                        $procedureData['CRTDT'] = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
                        $procedureData['CRTUSER'] = 'system';
                        SATUSEHAT_PROCEDURE::create($procedureData);
                    }

                    $this->logInfo($this->url, 'Sukses kirim data Procedure ' . $this->type, [
                        'payload' => $this->payload,
                        'response' => $result,
                        'user_id' => 'system' //Session::get('id')
                    ]);

                    $this->logDb(json_encode($result), $this->url, json_encode($this->payload), 'system'); //Session::get('id')
                }
            } else {
                $this->logInfo($this->url, 'Sudah Integrasi ' . $this->type, [
                    'payload' => $this->payload,
                    'response' => 'Data Procedure Untuk jenis ini sudah pernah dikirim ke satusehat',
                    'user_id' => 'system' //Session::get('id')
                ]);
            }
        } catch (Exception $e) {
            $this->logError($this->url, 'Gagal kirim data Procedure ' . $this->type, [
                'payload' => $this->payload,
                'response' => $e->getMessage(),
                'user_id' => 'system' //Session::get('id')
            ]);
            $this->fail($e);
        }
    }
}
