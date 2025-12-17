<?php

namespace App\Jobs;

use App\Http\Traits\LogTraits;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LogTraits;

    public $payload;
    public $meta;

    public $tries = 5;
    public $backoff = 10;


    /**
     * Create a new job instance.
     */
    public function __construct(array $payload, array $meta = [])
    {
        $this->payload = $payload;
        $this->meta = $meta;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Ambil data dari constructor
        $payload = $this->payload;
        $meta = $this->meta;
        //setup access token
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception("Access token tidak tersedia di database.");
        }
        //setup organisasi
        $id_unit = Session::get('id_unit_simrs', '001');
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;

        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        }
        // setup baseurl
        $baseurl = '';
        if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL_STAGING')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        } else {
            $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_BASEURL')->select('valStr')->first()->valStr;
            $organisasi = SS_Kode_API::where('idunit', $id_unit)->where('env', 'Dev')->select('org_id')->first()->org_id;
        }
        //
        $url = 'MedicationDispense';

        try {
            $client = new \GuzzleHttp\Client();

            $baseuri = rtrim($baseurl, '/') . '/' . ltrim($url, '/');
            $response = $client->post(
                $baseuri,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode($payload),
                    'verify' => false,
                    'timeout' => 30
                ]
            );

            $responseBody = json_decode($response->getBody(), true);
            $httpStatus = $response->getStatusCode();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // request ke API gagal (timeout / 500 / dns error / dsb)
            throw $e; // lempar biar Laravel retry otomatis
        }
        // =======================
        // LOGGING
        // =======================

        $this->logDb(json_encode($responseBody), $url, json_encode($this->payload), 'system');

        // =======================
        // LOGGING DB KHUSUS MEDICATION REQUEST
        // =======================

        $idTrans = $meta['idTrans'] ?? null;
        $item = $meta['item'] ?? null;

        $logData = [
            'LOG_TYPE' => 'MedicationDispense',
            'LOCAL_ID' => $item->ID_RESEP_FARMASI,
            'FHIR_MEDICATION_ID' => $item->medicationReference_reference ?? null,
            'NAMA_OBAT' => $item->medicationReference_display ?? '-',
            'KFA_CODE' => $item->KD_BRG_KFA ?? '-',
            'FHIR_MEDICATION_DISPENSE_ID' => $responseBody['id'] ?? null,
            'FHIR_MEDICATION_REQUEST_ID' => $item->FHIR_MEDICATION_REQUEST_ID ?? null,
            'PATIENT_ID' => $item->idpx ?? null,
            'ENCOUNTER_ID' => $item->ENCOUNTER_ID ?? null,
            'STATUS' => isset($responseBody['id']) ? 'success' : 'failed',
            'HTTP_STATUS' => $httpStatus,
            'RESPONSE_MESSAGE' => json_encode($responseBody),
            'CREATED_AT' => now(),
            'PAYLOAD' => json_encode($payload)
        ];

        $existing = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
            ->where('LOCAL_ID', $item->ID_RESEP_FARMASI)
            ->where('FHIR_MEDICATION_ID', $item->medicationReference_reference)
            ->where('LOG_TYPE', 'MedicationDispense')
            ->first();

        if ($existing) {
            DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_MEDICATION')
                ->where('ID', $existing->ID)
                ->update([
                    'FHIR_MEDICATION_DISPENSE_ID' => $logData['FHIR_MEDICATION_DISPENSE_ID'],
                    'FHIR_MEDICATION_REQUEST_ID' => $logData['FHIR_MEDICATION_REQUEST_ID'],
                    'FHIR_MEDICATION_ID' => $item->medicationReference_reference ?? null,
                    'PATIENT_ID' => $logData['PATIENT_ID'],
                    'ENCOUNTER_ID' => $logData['ENCOUNTER_ID'],
                    'KFA_CODE' => $item->KD_BRG_KFA ?? '-',
                    'STATUS' => $logData['STATUS'],
                    'HTTP_STATUS' => $logData['HTTP_STATUS'],
                    'RESPONSE_MESSAGE' => $logData['RESPONSE_MESSAGE'],
                    'UPDATED_AT' => now(),
                    'PAYLOAD' => json_encode($payload)
                ]);
        } else {
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


    private function getAccessToken()
    {
        $tokenData = DB::connection('sqlsrv')
            ->table('SATUSEHAT.dbo.RIRJ_SATUSEHAT_AUTH')
            ->select('issued_at', 'expired_in', 'access_token')
            ->where('idunit', '001')
            ->orderBy('id', 'desc')
            ->first();

        return $tokenData->access_token ?? null;
    }
}
