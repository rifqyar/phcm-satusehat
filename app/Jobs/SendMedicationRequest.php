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

class SendMedicationRequest implements ShouldQueue
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
    public function __construct(array $payload, array $meta = [], $id_unit)
    {
        $this->payload = $payload;
        $this->meta = $meta;
        $this->id_unit = $id_unit;
        $this->onQueue('MedicationRequest');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Ambil data dari constructor
        $payload = $this->payload;
        $meta    = $this->meta;

        // --- SETUP RESEND LOGIC DARI META ---
        $isResend     = $meta['resendData']['resend'] ?? false;
        $fhirMedReqId = $meta['resendData']['fhir_medicationrequest_id'] ?? null;

        $method = 'POST';
        $url    = 'MedicationRequest';

        if ($isResend === true && !empty($fhirMedReqId)) {
            $method = 'PUT';
            $url    = 'MedicationRequest/' . $fhirMedReqId;
        }

        // --- SETUP ACCESS TOKEN ---
        $login = $this->login($this->id_unit);
        if ($login['metadata']['code'] != 200) {
            $hasil = $login; // Sebaiknya di sini ada throw exception jika login gagal
        }
        $accessToken = $login['response']['token'];

        // --- SETUP BASEURL ---
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
        }

        try {
            $client = new \GuzzleHttp\Client();
            $baseuri = rtrim($baseurl, '/') . '/' . ltrim($url, '/');

            // Gunakan request dinamis (POST / PUT)
            $response = $client->request($method, $baseuri, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => json_encode($payload),
                'verify'  => false,
                'timeout' => 30,
            ]);

            $responseBody = json_decode($response->getBody(), true);
            $httpStatus   = $response->getStatusCode();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $status = null;
            $body   = null;

            if ($e->hasResponse()) {
                $status = $e->getResponse()->getStatusCode();
                $body   = (string) $e->getResponse()->getBody();
            }

            $message = 'SATUSEHAT MedicationRequest failed. ';
            if ($status !== null) {
                $message .= 'HTTP=' . $status . ' ';
            }

            if (!empty($body)) {
                $message .= 'BODY=' . $body;
            } else {
                $message .= 'ERROR=' . $e->getMessage();
            }

            $this->logError(
                'MedicationRequest',
                'Gagal mengirim MedicationRequest ke SATUSEHAT',
                [
                    'payload' => $payload,
                    'meta'    => $meta,
                    'error'   => $message,
                    'trace'   => $e->getTrace(),
                ]
            );

            // Disimpan base url resminya saja (MedicationRequest) untuk log
            $this->logDb(json_encode($body), 'MedicationRequest', json_encode($payload), 'system', 0);

            throw new \RuntimeException(
                $message,
                $status ?? 0,
                $e
            );
        }

        // =======================
        // LOGGING BERHASIL
        // =======================
        $this->logDb(json_encode($responseBody), 'MedicationRequest', json_encode($this->payload), 'system', 1);
        $this->logInfo('MedicationRequest', 'Berhasil mengirim MedicationRequest ke SATUSEHAT', [
            'payload'  => $payload,
            'response' => $responseBody,
            'user_id'  => Session::get('nama', 'system')
        ]);

        // =======================
        // LOGGING DB KHUSUS MEDICATION REQUEST
        // =======================
        $idTrans = $meta['idTrans'] ?? null;
        $item    = $meta['item'] ?? null;

        $logData = [
            'LOG_TYPE'                   => $item['FROM'] ?? 'MedicationRequest',
            'LOCAL_ID'                   => $idTrans,
            'KFA_CODE'                   => $item['KD_BRG_KFA'] ?? null,
            'NAMA_OBAT'                  => $item['NAMABRG_KFA'] ?? null,
            'FHIR_MEDICATION_REQUEST_ID' => $responseBody['id'] ?? null,
            'FHIR_ID'                    => $responseBody['id'] ?? null,
            'FHIR_MEDICATION_ID'         => $item['medicationReference'] ?? null,
            'PATIENT_ID'                 => $item['ID_PASIEN'] ?? null,
            'ENCOUNTER_ID'               => $item['id_satusehat_encounter'] ?? null,
            'STATUS'                     => isset($responseBody['id']) ? 'success' : 'failed',
            'HTTP_STATUS'                => $httpStatus,
            'RESPONSE_MESSAGE'           => json_encode($responseBody),
            'CREATED_AT'                 => now(),
        ];

        $existing = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
            ->where('LOCAL_ID', $idTrans)
            ->where('KFA_CODE', $item['KD_BRG_KFA'] ?? null)
            ->where('LOG_TYPE', 'MedicationRequest')
            ->first();

        if ($existing) {
            // Kalau data sudah ada (biasanya case resend / retry), lakukan UPDATE
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
                ->where('ID', $existing->ID)
                ->update([
                    'FHIR_ID'                    => $logData['FHIR_ID'],
                    'FHIR_MEDICATION_REQUEST_ID' => $logData['FHIR_MEDICATION_REQUEST_ID'],
                    'FHIR_MEDICATION_ID'         => $logData['FHIR_MEDICATION_ID'],
                    'PATIENT_ID'                 => $logData['PATIENT_ID'],
                    'ENCOUNTER_ID'               => $logData['ENCOUNTER_ID'],
                    'STATUS'                     => $logData['STATUS'],
                    'HTTP_STATUS'                => $logData['HTTP_STATUS'],
                    'RESPONSE_MESSAGE'           => $logData['RESPONSE_MESSAGE'],
                    'UPDATED_AT'                 => now(),
                ]);
        } else {
            // Kalau data belum ada, lakukan INSERT
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert($logData);
        }
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

        // Save failed log
        DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')->insert([
            'LOG_TYPE' => $item['FROM'] ?? 'MedicationRequest',
            'LOCAL_ID' => $idTrans,
            'KFA_CODE' => $item['KD_BRG_KFA'] ?? null,
            'NAMA_OBAT' => $item['NAMABRG_KFA'] ?? null,
            'FHIR_ID' => null,
            'FHIR_MEDICATION_ID' => null,
            'PATIENT_ID' => $item['ID_PASIEN'] ?? null,
            'ENCOUNTER_ID' => $item['id_satusehat_encounter'] ?? null,
            'STATUS' => 'failed-permanent',
            'HTTP_STATUS' => null,
            'RESPONSE_MESSAGE' => json_encode(
                [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
                JSON_UNESCAPED_UNICODE,
            ),
            'CREATED_AT' => now(),
            'PAYLOAD' => $this->payload,
        ]);
    }

    private function getAccessToken()
    {
        $tokenData = DB::connection('sqlsrv')->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')->select('issued_at', 'expired_in', 'access_token')->where('idunit', '001')->orderBy('id', 'desc')->first();

        return $tokenData->access_token ?? null;
    }
}
