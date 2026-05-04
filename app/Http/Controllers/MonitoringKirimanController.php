<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SatuSehat\AllergyIntoleranceController;
use App\Http\Controllers\SatuSehat\CarePlanController;
use App\Http\Controllers\SatuSehat\ClinicalImpressionController;
use App\Http\Controllers\SatuSehat\DiagnosticReportController;
use App\Http\Controllers\SatuSehat\EncounterController;
use App\Http\Controllers\SatuSehat\EpisodeOfCareController;
use App\Http\Controllers\SatuSehat\MedicationDispenseController;
use App\Http\Controllers\SatuSehat\MedicationRequestController;
use App\Http\Controllers\SatuSehat\ObservasiController;
use App\Http\Controllers\SatuSehat\ProcedureController;
use App\Http\Controllers\SatuSehat\QuestionnaireResponseController;
use App\Http\Controllers\SatuSehat\ResumeMedisController;
use App\Http\Controllers\SatuSehat\ServiceRequestController;
use App\Http\Controllers\SatuSehat\SpecimenController;
use App\Lib\LZCompressor\LZString;
use App\Models\Karcis;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA_DIAGNOSA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

class MonitoringKirimanController extends Controller
{
    public function index()
    {
        $redisConnected = false;
        try {
            Redis::connection()->ping();
            $redisConnected = true;
        } catch (\Exception $e) {
            $redisConnected = false;
        }

        $monitoringData = [];

        if ($redisConnected) {
            $response = $this->getQueueMonitor();
            $monitoringData = json_decode($response->getContent(), true)['data'];
            // dd($monitoringData);
        }

        return view('pages.monitoring_kiriman', compact('monitoringData', 'redisConnected'));
    }

    public function getQueueMonitor()
    {
        $queues = [
            'encounter',
            'Condition',
            'observasi',
            'procedure',
            'Composition',
            'Immunization',
            'MedicationRequest',
            'MedicationDispense',
            'AllergyIntolerance',
            'ServiceRequest',
            'ClinicalImpression',
            'specimen',
            'DiagnosticReport',
            'CarePlan',
            'EpisodeOfCare',
            'QuestionnaireResponse'
        ];

        $monitoringData = [];

        $allFailed = DB::table('failed_jobs')
            ->whereIn('queue', $queues)
            ->orderByDesc('failed_at')
            ->get()
            ->groupBy('queue');

        foreach ($queues as $q) {
            $monitoringData[] = [
                'queue'   => $q,
                'pending' => $this->getPendingJobs($q),
                'failed'  => $this->formatFailedJobs($allFailed->get($q, collect())),
            ];
        }

        // dd($monitoringData);
        return response()->json([
            'status' => 'success',
            'data'   => $monitoringData
        ]);
    }

    /**
     * Get pending job details directly from Redis.
     * Laravel stores queued jobs in a Redis list: queues:{queue_name}
     */
    private function getPendingJobs(string $queue): array
    {
        $redisKey = 'queues:' . $queue;

        // LRANGE gets all items in the list (0 to -1 = all)
        $rawJobs = array_merge(
            Redis::lrange($redisKey, 0, -1),                    // pending
            Redis::zrange($redisKey . ':reserved', 0, -1),      // reserved/processing
            Redis::zrange($redisKey . ':delayed', 0, -1),       // delayed
        );

        $jobs = [];
        foreach ($rawJobs as $raw) {
            $payload = json_decode($raw, true);

            $jobs[] = [
                'id'         => $payload['uuid'] ?? $payload['id'] ?? null,
                'job_class'  => $payload['displayName'] ?? $payload['job'] ?? null,
                'attempts'   => $payload['attempts'] ?? 0,
                'max_tries'  => $payload['maxTries'] ?? null,
                'timeout'    => $payload['timeout'] ?? null,
                'pushed_at'  => isset($payload['pushedAt']) ? date('Y-m-d H:i:s', $payload['pushedAt']) : null,
                'params'     => $this->decodeJobParam($payload['data'] ?? []), // decoded params here
                'raw'        => $payload,
            ];
        }

        return $jobs;
    }

    /**
     * Get failed job details from the SQL failed_jobs table.
     */
    private function getFailedJobs(string $queue): array
    {
        return DB::table('failed_jobs')
            ->where('queue', $queue)
            ->orderByDesc('failed_at')
            ->get()
            ->map(function ($job) {
                $payload   = json_decode($job->payload, true);
                $exception = $job->exception;

                return [
                    'id'           => $job->id,
                    'uuid'         => $job->uuid ?? null,
                    'job_class'    => $payload['displayName'] ?? null,
                    'attempts'     => $payload['attempts'] ?? null,
                    'failed_at'    => $job->failed_at,
                    'exception'    => $this->parseException($exception),
                    'data'         => $payload['data'] ?? null,
                    'connection'   => $job->connection,
                ];
            })
            ->toArray();
    }

    private function formatFailedJobs($jobs): array
    {
        return $jobs->map(function ($job) {
            $payload = json_decode($job->payload, true);
            return [
                'id'        => $job->id,
                'uuid'      => $job->uuid ?? null,
                'job_class' => $payload['displayName'] ?? null,
                'attempts'  => $payload['attempts'] ?? null,
                'failed_at' => $job->failed_at,
                'exception' => $this->parseException($job->exception),
                'data'      => $payload['data'] ?? null,
                'connection' => $job->connection,
            ];
        })->toArray();
    }

    private function decodeJobParam(array $data): ?array
    {
        try {
            $command = $data['command'] ?? null;
            if (!$command) return null;

            $job   = unserialize($command);
            $param = $job->param ?? null;
            $queueJob = $job->queue ?? null;
            if (!$param) return null;

            // if ($queueJob == "encounter") {
            //     $decoded = LZString::decompressFromEncodedURIComponent($param);
            //     $parts   = explode('&', $decoded);

            //     $arrParam   = [];
            //     $resParam   = [];
            //     $partsParam = explode('=', $parts[0]);
            //     $arrParam[$partsParam[0]] = $partsParam[1];

            //     for ($i = 1; $i < count($parts); $i++) {
            //         $partsParam       = explode('=', $parts[$i]);
            //         $key              = $partsParam[0];
            //         $val              = $partsParam[1] ?? '';
            //         $arrParam[$key]   = LZString::decompressFromEncodedURIComponent($val);
            //     }

            //     return app(EncounterController::class)->getEncounterDataQueue($arrParam);
            // } else if ($queueJob == "observasi") {
            //     $decoded = LZString::decompressFromEncodedURIComponent($param);
            //     $parts   = explode('&', $decoded);

            //     $arrParam   = [];
            //     $partsParam = explode('=', $parts[0]);
            //     $arrParam[$partsParam[0]] = $partsParam[1];

            //     for ($i = 1; $i < count($parts); $i++) {
            //         $partsParam       = explode('=', $parts[$i]);
            //         $key              = $partsParam[0];
            //         $val              = $partsParam[1] ?? '';
            //         $arrParam[$key]   = LZString::decompressFromEncodedURIComponent($val);
            //     }

            //     return app(ObservasiController::class)->getDataObservationQueue($arrParam);
            // } else if ($queueJob == "procedure") {
            //     $decoded = LZString::decompressFromEncodedURIComponent($param);
            //     $parts = explode('&', $decoded);

            //     $arrParam = [];
            //     $partsParam = explode('=', $parts[0]);
            //     $arrParam[$partsParam[0]] = $partsParam[1];
            //     for ($i = 1; $i < count($parts); $i++) {
            //         $partsParam = explode('=', $parts[$i]);
            //         $key = $partsParam[0];
            //         $val = $partsParam[1];
            //         $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
            //     }

            //     return app(ProcedureController::class)->getProcedureDataQueue($arrParam);
            // } else if ($queueJob == "Composition") {
            //     $decoded = LZString::decompressFromEncodedURIComponent($param);
            //     $parts = explode('+', $decoded);

            //     return app(ResumeMedisController::class)->getDataCompositionQueue($parts);
            // } else if ($queueJob == "Immunization") {
            //     $idImunisasi = $param;
            //     $data = $this->getDataImunisasi($idImunisasi);

            //     $resParam['Karcis'] = $data ? $data->KARCIS : "not found";
            //     $resParam['Pasien'] = $data ? $data->NAMA_PASIEN : "not found";
            //     $resParam['Dokter'] = $data ? $data->NAMA_NAKES : "not found";
            //     $resParam['Lokasi'] = $data ? $data->NAMA_UNIT : "not found";
            //     $resParam['TGL'] = $data ? $data->TANGGAL : "not found";

            //     return $resParam;
            // } else if ($queueJob == "MedicationRequest") {
            //     $idTrans = $param;

            //     return app(MedicationRequestController::class)->getDataMedicationRequestQueue($idTrans);
            // } else if ($queueJob == "MedicationDispense") {
            //     $idTrans = $param;

            //     return app(MedicationDispenseController::class)->getDataMedicationDispenseQueue($idTrans);
            // } else if ($queueJob == "AllergyIntolerance") {
            //     $decoded = LZString::decompressFromEncodedURIComponent($param);
            //     $parts   = explode('&', $decoded);

            //     $arrParam   = [];
            //     $resParam   = [];
            //     $partsParam = explode('=', $parts[0]);
            //     $arrParam[$partsParam[0]] = $partsParam[1];

            //     for ($i = 1; $i < count($parts); $i++) {
            //         $partsParam       = explode('=', $parts[$i]);
            //         $key              = $partsParam[0];
            //         $val              = $partsParam[1] ?? '';
            //         $arrParam[$key]   = LZString::decompressFromEncodedURIComponent($val);
            //     }

            //     return app(AllergyIntoleranceController::class)->getDataAllergyIntoleranceQueue($arrParam);
            // } else if ($queueJob == "ServiceRequest") {
            //     $decoded = LZString::decompressFromEncodedURIComponent($param);
            //     $parts   = explode('+', $decoded);

            //     return app(ServiceRequestController::class)->getDataServiceRequestQueue($parts);
            // } else if ($queueJob == "ClinicalImpression") {
            //     $decoded = LZString::decompressFromEncodedURIComponent($param);
            //     $parts = explode('&', $decoded);

            //     $arrParam = [];
            //     for ($i = 0; $i < count($parts); $i++) {
            //         $partsParam = explode('=', $parts[$i]);
            //         $key = $partsParam[0];
            //         $val = $partsParam[1];
            //         $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
            //     }

            //     return app(ClinicalImpressionController::class)->getDataClinicalImpressionQueue($arrParam);
            // } else if ($queueJob == "specimen") {
            //     $decoded = LZString::decompressFromEncodedURIComponent($param);
            //     $parts = explode('+', $decoded);

            //     return app(SpecimenController::class)->getDataSpecimenQueue($parts);
            // } else if ($queueJob == "DiagnosticReport") {
            //     $params = LZString::decompressFromEncodedURIComponent($param);

            //     $arrParam = [];
            //     $parts = explode('&', $params);
            //     for ($i = 0; $i < count($parts); $i++) {
            //         $partsParam = explode('=', $parts[$i]);
            //         $key = $partsParam[0];
            //         $val = $partsParam[1];
            //         $arrParam[$key] = $val;
            //     }

            //     return app(DiagnosticReportController::class)->getDataDiagnosticReportQueue($arrParam);
            // } else if ($queueJob == "CarePlan") {
            //     $params = LZString::decompressFromEncodedURIComponent($param);
            //     $parts = explode('&', $params);

            //     $arrParam = [];
            //     for ($i = 0; $i < count($parts); $i++) {
            //         $partsParam = explode('=', $parts[$i]);
            //         $key = $partsParam[0];
            //         $val = $partsParam[1];
            //         $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
            //     }

            //     return app(CarePlanController::class)->getDataCarePlanQueue($arrParam);
            // } else if ($queueJob == "EpisodeOfCare") {
            //     $params = LZString::decompressFromEncodedURIComponent($param);
            //     $parts = explode('&', $params);

            //     $arrParam = [];
            //     for ($i = 0; $i < count($parts); $i++) {
            //         $partsParam = explode('=', $parts[$i]);
            //         $key = $partsParam[0];
            //         $val = $partsParam[1];
            //         $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
            //     }

            //     return app(EpisodeOfCareController::class)->getDataEpisodeOfCareQueue($arrParam);
            // } else if ($queueJob == "QuestionnaireResponse") {
            //     $params = LZString::decompressFromEncodedURIComponent($param);
            //     $parts = explode('&', $params);

            //     $arrParam = [];
            //     $partsParam = explode('=', $parts[0]);
            //     $arrParam[$partsParam[0]] = $partsParam[1];
            //     for ($i = 1; $i < count($parts); $i++) {
            //         $partsParam = explode('=', $parts[$i]);
            //         $key = $partsParam[0];
            //         $val = $partsParam[1];
            //         $arrParam[$key] = LZString::decompressFromEncodedURIComponent($val);
            //     }

            //     return app(QuestionnaireResponseController::class)->getDataQuestionnaireResponse($arrParam);
            // } else {
            //     $decoded = LZString::decompressFromEncodedURIComponent($param);
            //     $parts   = explode('&', $decoded);

            //     $arrParam   = [];
            //     $resParam   = [];
            //     $partsParam = explode('=', $parts[0]);
            //     $arrParam[$partsParam[0]] = $partsParam[1];

            //     for ($i = 1; $i < count($parts); $i++) {
            //         $partsParam       = explode('=', $parts[$i]);
            //         $key              = $partsParam[0];
            //         $val              = $partsParam[1] ?? '';
            //         $arrParam[$key]   = LZString::decompressFromEncodedURIComponent($val);
            //     }

            //     return $arrParam;
            // }

            $queueJob = $job->queue ?? null;

            // Composition, ServiceRequest, specimen — use + as delimiter
            if (in_array($queueJob, ['Composition', 'ServiceRequest', 'specimen'])) {
                $decoded = LZString::decompressFromEncodedURIComponent($param);
                $parts   = explode('+', $decoded);
                return ['params' => implode(', ', $parts)];
            }

            // Immunization — param is just a plain ID, no decoding needed
            if ($queueJob === 'Immunization') {
                return ['id' => $param];
            }

            // MedicationRequest, MedicationDispense — plain ID too
            if (in_array($queueJob, ['MedicationRequest', 'MedicationDispense'])) {
                return ['id' => $param];
            }

            // Everything else — decode and split by &key=value
            $decoded = LZString::decompressFromEncodedURIComponent($param);
            $parts   = explode('&', $decoded);

            $arrParam = [];
            foreach ($parts as $part) {
                $kv  = explode('=', $part, 2);
                $key = $kv[0] ?? null;
                $val = $kv[1] ?? '';
                if (!$key) continue;

                // Try to decompress value too (some queues double-compress values)
                $decompressed = LZString::decompressFromEncodedURIComponent($val);
                $arrParam[$key] = $decompressed ?: $val;
            }

            return $arrParam ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getDataImunisasi($idImunisasi)
    {
        $sql = "
            SELECT
                A.ID_IMUNISASI_PX,
                A.DISPLAY_VAKSIN,
                A.KODE_VAKSIN,
                C.nama AS NAMA_PASIEN,
                C.idpx AS ID_SATUSEHAT_PASIEN,
                B.id_satusehat_encounter,
                A.TANGGAL,
                A.CRTDT,
                A.JENIS_VAKSIN,
                F.CODE_DISPLAY,
                D.idnakes,
                D.NAMA AS NAMA_NAKES,
                A.KODE_CENTRA,
                A.SATUSEHAT_STATUS,
                A.DOSIS,
                E.NAMA_UNIT
            FROM E_RM_PHCM.dbo.ERM_IMUNISASI_PX A
            JOIN SATUSEHAT.dbo.RJ_SATUSEHAT_NOTA B
                ON A.KARCIS = B.karcis
            JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_PASIEN C
                ON B.id_satusehat_px = C.idpx
            JOIN SATUSEHAT.dbo.RIRJ_SATUSEHAT_NAKES D
                ON B.id_satusehat_dokter = D.idnakes
            JOIN SIRS_PHCM.dbo.RIRJ_MKODE_UNIT E
                ON A.IDUNIT = E.ID_UNIT
            LEFT JOIN E_RM_PHCM.dbo.ERM_IMUNISASI_JENIS F
                on A.JENIS_VAKSIN = F.CODE_VALUE
            WHERE A.ID_IMUNISASI_PX = ?
        ";

        return DB::selectOne($sql, [$idImunisasi]);
    }

    /**
     * Extract only the first line (message) from the full exception stack trace.
     */
    private function parseException(string $exception): array
    {
        $lines = explode("\n", $exception);

        return [
            'message'     => $lines[0] ?? 'Unknown error',
            'stack_trace' => implode("\n", array_slice($lines, 1, 10)), // first 10 lines
        ];
    }
}
