<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class MonitoringKirimanController extends Controller
{
    public function index()
    {
        $redisConnected = $this->checkRedisConnection();

        $monitoringData = $redisConnected
            ? Cache::remember('monitoring_kiriman', 5, function () {
                return $this->buildQueueMonitor();
            })
            : [];

        return view('pages.monitoring_kiriman', compact('monitoringData', 'redisConnected'));
    }

    private function checkRedisConnection(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function buildQueueMonitor(): array
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

        // 🔥 Ambil failed jobs dengan limit global (hindari ambil semua)
        $allFailed = DB::table('failed_jobs')
            ->whereIn('queue', $queues)
            ->orderByDesc('failed_at')
            ->limit(200)
            ->get(['id', 'uuid', 'queue', 'payload', 'exception', 'failed_at', 'connection'])
            ->groupBy('queue');

        $data = [];

        foreach ($queues as $q) {
            $key = "queues:$q";

            $pendingTotal =
                Redis::llen($key) +
                Redis::zcard("$key:reserved") +
                Redis::zcard("$key:delayed");

            $failedJobs  = $allFailed->get($q, collect());
            $failedTotal = $failedJobs->count();

            $data[] = [
                'queue' => $q,

                // 🔥 hanya count (cepat)
                'pending_total' => $pendingTotal,

                // 🔥 ambil sample kecil saja
                'pending' => $this->getPendingJobs($q, 5),

                'failed_total' => $failedTotal,

                'failed' => $this->formatFailedJobs(
                    $failedJobs->take(5)
                ),
            ];
        }

        return $data;
    }

    /**
     * 🔥 Ambil hanya sample kecil dari Redis
     */
    private function getPendingJobs(string $queue, int $limit = 5): array
    {
        $key = "queues:$queue";

        $rawJobs = array_merge(
            Redis::lrange($key, 0, $limit - 1),
            Redis::zrange("$key:reserved", 0, $limit - 1),
            Redis::zrange("$key:delayed", 0, $limit - 1),
        );

        return collect($rawJobs)->map(function ($raw) {
            $payload = json_decode($raw, true);

            return [
                'id'         => $payload['uuid'] ?? $payload['id'] ?? null,
                'job_class'  => $payload['displayName'] ?? $payload['job'] ?? null,
                'attempts'   => $payload['attempts'] ?? 0,
                'max_tries'  => $payload['maxTries'] ?? null,
                'timeout'    => $payload['timeout'] ?? null,
                'pushed_at'  => isset($payload['pushedAt'])
                    ? date('Y-m-d H:i:s', $payload['pushedAt'])
                    : null,

                // ❌ disable decode berat
                'params' => null,
            ];
        })->toArray();
    }

    /**
     * 🔥 Format failed jobs (tanpa decode berat)
     */
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
                'connection'=> $job->connection,
            ];
        })->toArray();
    }

    /**
     * 🔥 Ambil ringkasan error saja (hemat)
     */
    private function parseException(string $exception): array
    {
        $lines = explode("\n", $exception);

        return [
            'message'     => $lines[0] ?? 'Unknown error',
            'stack_trace' => implode("\n", array_slice($lines, 1, 5)),
        ];
    }
}
