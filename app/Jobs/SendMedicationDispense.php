<?php

namespace App\Jobs;

use App\Http\Traits\LogTraits;
use App\Http\Traits\SATUSEHATTraits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\GlobalParameter;
use App\Models\SATUSEHAT\SS_Kode_API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SendMedicationDispense implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LogTraits, SATUSEHATTraits;

    public $payload;
    public $meta;
    public $id_unit;

    public $tries = 5;
    public $backoff = 10;


    /**
     * Create a new job instance.
     */
    public function __construct(array $payload, array $meta = [], string $id_unit)
    {
        $this->payload = $payload;
        $this->meta = $meta;
        $this->id_unit = $id_unit;
        $this->onQueue('MedicationDispense');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $payload = $this->payload;
        $meta    = $this->meta;

        $item       = $meta['item'] ?? null;
        $resendData = $meta['resendData'] ?? [];

        $isResend = !empty($resendData['resend'])
            && !empty($resendData['fhir_medicationdispense_id']);

        $dispenseId = $resendData['fhir_medicationdispense_id'] ?? null;

        try {
            $accessToken = $this->getAccessToken();
            $baseUrl     = $this->getBaseUrl();

            $url    = $isResend
                ? 'MedicationDispense/' . $dispenseId
                : 'MedicationDispense';

            $method = $isResend ? 'put' : 'post';

            $response = $this->sendRequest(
                $method,
                $baseUrl,
                $url,
                $payload,
                $accessToken
            );

            $responseBody = json_decode((string) $response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from SATUSEHAT');
            }

            $httpStatus = $response->getStatusCode();

            $this->saveLog($payload, $responseBody, $httpStatus, $item);
        } catch (\GuzzleHttp\Exception\RequestException $e) {

            $responseBody = [];
            $httpStatus   = 500;

            if ($e->hasResponse()) {
                $httpStatus = $e->getResponse()->getStatusCode();
                $responseBody = json_decode(
                    (string) $e->getResponse()->getBody(),
                    true
                ) ?: [];
            }

            $this->logError(
                'MedicationDispense',
                'Gagal kirim MedicationDispense',
                [
                    'payload'  => $payload,
                    'meta'     => $meta,
                    'response' => $responseBody,
                ]
            );

            throw new \Exception(
                json_encode($responseBody),
                $httpStatus,
                $e
            );
        }
    }

    /**
     * =====================================================
     * ACCESS TOKEN
     * =====================================================
     */
    private function getAccessToken()
    {
        $login = $this->login($this->id_unit);

        if (($login['metadata']['code'] ?? 500) != 200) {
            throw new \Exception('Login SATUSEHAT gagal');
        }

        $token = $login['response']['token'] ?? null;

        if (!$token) {
            throw new \Exception('Access token tidak tersedia');
        }

        return $token;
    }

    /**
     * =====================================================
     * BASE URL
     * =====================================================
     */
    private function getBaseUrl()
    {
        $env = strtoupper(env('SATUSEHAT', 'PRODUCTION'));

        $type = $env == 'DEVELOPMENT'
            ? 'SATUSEHAT_BASEURL_STAGING'
            : 'SATUSEHAT_BASEURL';

        return GlobalParameter::where('tipe', $type)
            ->value('valStr');
    }

    /**
     * =====================================================
     * HTTP REQUEST
     * =====================================================
     */
    private function sendRequest($method, $baseUrl, $url, $payload, $token)
    {
        $client = new \GuzzleHttp\Client();

        return $client->$method(
            rtrim($baseUrl, '/') . '/' . ltrim($url, '/'),
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => json_encode($payload),
                'verify'  => false,
                'timeout' => 30,
            ]
        );
    }

    /**
     * =====================================================
     * SAVE LOG
     * =====================================================
     */
    private function saveLog($payload, $responseBody, $httpStatus, $item)
    {
        $dispenseId = $responseBody['id'] ?? null;

        $this->logDb(
            json_encode($responseBody),
            'MedicationDispense',
            json_encode($payload),
            'system'
        );

        $data = [
            'LOG_TYPE' => 'MedicationDispense',
            'LOCAL_ID' => $item->ID_RESEP_FARMASI,
            'FHIR_MEDICATION_ID' =>
            $item->medicationReference_reference ?? null,
            'FHIR_MEDICATION_DISPENSE_ID' => $dispenseId,
            'FHIR_MEDICATION_REQUEST_ID' =>
            $item->FHIR_MEDICATION_REQUEST_ID ?? null,
            'PATIENT_ID' => $item->idpx ?? null,
            'ENCOUNTER_ID' => $item->ENCOUNTER_ID ?? null,
            'KFA_CODE' => $item->KD_BRG_KFA ?? '-',
            'STATUS' => $dispenseId ? 'success' : 'failed',
            'HTTP_STATUS' => $httpStatus,
            'RESPONSE_MESSAGE' => json_encode($responseBody),
            'PAYLOAD' => json_encode($payload),
            'UPDATED_AT' => now(),
        ];

        $existing = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
            ->where('LOCAL_ID', $item->ID_RESEP_FARMASI)
            ->where('LOG_TYPE', 'MedicationDispense')
            ->first();

        if ($existing) {
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
                ->where('ID', $existing->ID)
                ->update($data);
        } else {
            $data['CREATED_AT'] = now();

            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
                ->insert($data);
        }

        $this->logInfo(
            'MedicationDispense',
            'Sukses kirim MedicationDispense',
            [
                'payload'  => $payload,
                'response' => $responseBody,
            ]
        );
    }

    /**
     * Fungsi ini otomatis dipanggil ketika job gagal setelah semua retry habis.
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception)
    {
        $meta = $this->meta;
        $idTrans = $meta['idTrans'] ?? null;
        $item = $meta['item'] ?? null;

        DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert([
            'LOG_TYPE' => 'MedicationDispense',
            'LOCAL_ID' => $idTrans,
            'KFA_CODE' => $item->KD_BRG_KFA ?? null,
            'NAMA_OBAT' => $item->NAMABRG_KFA ?? null,
            'FHIR_ID' => null,
            'FHIR_MEDICATION_ID' => null,
            'PATIENT_ID' => $item->idpx ?? null,
            'ENCOUNTER_ID' => $item->id_satusehat_encounter ?? null,
            'STATUS' => 'failed-permanent',
            'HTTP_STATUS' => null,
            'PAYLOAD' => json_encode($this->payload),
            'RESPONSE_MESSAGE' => $exception->getMessage(),
            'CREATED_AT' => now()
        ]);
    }
}
