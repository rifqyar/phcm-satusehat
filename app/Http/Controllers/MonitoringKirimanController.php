<?php

namespace App\Http\Controllers;

use App\Lib\LZCompressor\LZString;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class MonitoringKirimanController extends Controller
{
    public function index()
    {
        $response  = $this->getQueueMonitor();
        $monitoringData = json_decode($response->getContent(), true)['data'];

        return view('pages.monitoring_kiriman', compact('monitoringData'));
    }

    public function getQueueMonitor()
    {
        $queues = [
            'encounter',
            'Condition',
            'observasi',
            'procedure',
            'Composition',
            'AllergyIntolerance',
            'DiagnosticReport',
            'MedicationDispense',
            'MedicationRequest',
            'ServiceRequest',
            'specimen',
            'CarePlan',
            'ClinicalImpression',
            'EpisodeOfCare',
            'QuestionnaireResponse'
        ];

        $monitoringData = [];

        foreach ($queues as $q) {
            $monitoringData[] = [
                'queue'   => $q,
                'pending' => $this->getPendingJobs($q),
                'failed'  => $this->getFailedJobs($q),
            ];
        }

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

    private function decodeJobParam(array $data): ?array
    {
        try {
            $command = $data['command'] ?? null;
            if (!$command) return null;

            $job   = unserialize($command);
            $param = $job->param ?? null;
            if (!$param) return null;

            $decoded = base64_decode($param);
            $decoded = LZString::decompressFromEncodedURIComponent($decoded);
            $parts   = explode('&', $decoded);

            $arrParam   = [];
            $partsParam = explode('=', $parts[0]);
            $arrParam[$partsParam[0]] = $partsParam[1];

            for ($i = 1; $i < count($parts); $i++) {
                $partsParam       = explode('=', $parts[$i]);
                $key              = $partsParam[0];
                $val              = $partsParam[1] ?? '';
                $arrParam[$key]   = LZString::decompressFromEncodedURIComponent($val);
            }

            // Extract known keys
            return [
                'jenis_perawatan' => $arrParam['jenis_perawatan'] ?? null,
                'id_transaksi'    => $arrParam['id_transaksi']    ?? null,
                'kd_pasien_ss'    => $arrParam['kd_pasien_ss']    ?? null,
                'kd_nakes_ss'     => $arrParam['kd_nakes_ss']     ?? null,
                'kd_lokasi_ss'    => $arrParam['kd_lokasi_ss']    ?? null,
                'id_unit'         => $arrParam['id_unit']         ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
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
