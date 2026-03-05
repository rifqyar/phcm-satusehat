<?php

namespace App\Jobs;

use App\Models\GlobalParameter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\LogTraits;

class SendCondition implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,LogTraits;

    public $payload;
    public $meta;

    public $tries = 5;
    public $backoff = 10;

    public function __construct(array $payload, array $meta = [])
    {
        $this->payload = $payload;
        $this->meta = $meta;
        $this->onQueue('Condition');
    }

    public function handle()
    {

        $payload = $this->payload;
        $meta = $this->meta;

        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception('Access token tidak tersedia');
        }

        $baseurl = strtoupper(env('SATUSEHAT', 'PRODUCTION')) === 'DEVELOPMENT'
            ? GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->value('valStr')
            : GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->value('valStr');

        $url = rtrim($baseurl, '/') . '/Condition';

        try {

            $client = new \GuzzleHttp\Client();

            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($payload),
                'verify' => false,
                'timeout' => 30,
            ]);

            $httpStatus = $response->getStatusCode();
            $responseBody = json_decode($response->getBody(), true);
            $this->logInfo('Diagnosis', 'Sukses kirim data diagnosis', [
                'payload' => $payload,
                'response' => $responseBody,
                'user_id' => 'Jobs' //Session::get('id')
            ]);

        } catch (\GuzzleHttp\Exception\RequestException $e) {

            $status = null;
            $body   = null;

            if ($e->hasResponse()) {
                $status = $e->getResponse()->getStatusCode();
                $body   = (string) $e->getResponse()->getBody();
            }

            throw new \RuntimeException(
                'SATUSEHAT Condition failed HTTP=' . $status . ' BODY=' . $body
            );
        }

        $conditionId = $responseBody['id'] ?? null;
        $status = $conditionId ? 'success' : 'failed';

        DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_TRANSACTION')->insert([
            'service' => 'Condition',
            'request' => json_encode($payload),
            'response' => json_encode($responseBody),
            'created_by' => $meta['user'] ?? 'system',
            'created_at' => now(),
        ]);

        $logData = [
            'karcis'  => $meta['karcis'],
            'nota'    => $meta['nota'],
            'idunit'  => $meta['idunit'],
            'tgl'     => $meta['tgl'],
            'rank'    => $meta['rank'] ?? 1,

            'code'    => $payload['code']['coding'][0]['code'] ?? null,
            'display' => $payload['code']['coding'][0]['display'] ?? null,

            'id_satusehat_condition' => $conditionId,
            'status_sinkron'         => $status === 'success' ? 1 : 0,
            'crtusr'                 => $meta['user'] ?? 'system',
            'crtdt'                  => now(),
            'sinkron_date'           => $status === 'success' ? now() : null,
        ];

        $existing = DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_DIAGNOSA')
            ->where('karcis', $logData['karcis'])
            ->where('rank', $logData['rank'])
            ->where('idunit', $logData['idunit'])
            ->first();

        if ($existing) {

            DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_DIAGNOSA')
                ->where('id', $existing->id)
                ->update($logData);

        } else {

            DB::table('SATUSEHAT.dbo.RJ_SATUSEHAT_DIAGNOSA')
                ->insert($logData);

        }
    }

    public function failed(\Throwable $exception)
    {

        $meta = $this->meta;

        DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_TRANSACTION')->insert([
            'service' => 'Condition',
            'request' => json_encode($this->payload),
            'response' => json_encode([
                'error' => $exception->getMessage()
            ]),
            'created_by' => $meta['user'] ?? 'system',
            'created_at' => now(),
        ]);
    }

    private function getAccessToken()
    {
        $tokenData = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')
            ->select('access_token')
            ->where('idunit', '001')
            ->orderBy('id', 'desc')
            ->first();

        return $tokenData->access_token ?? null;
    }
}