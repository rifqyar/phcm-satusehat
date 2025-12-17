<?php

namespace App\Jobs;

use App\Http\Traits\LogTraits;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class DispatchCIRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LogTraits;

    public $param;
    public $url;
    public $tries = 3; // Number of attempts
    public $timeout = 30; // Timeout in seconds

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param, $url)
    {
        $this->param = $param;
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->logInfo('dispatchci', 'Processing Job', [
            'payload' => $this->param,
        ]);

        try {
            foreach ($this->url as $val) {
                $endpoint = explode('/', $val)[1];

                Http::timeout(10)
                    ->post(route($endpoint), $this->param);
            }

            $this->logInfo('dispatchci', 'Job Processed');
        } catch (\Throwable $e) {
            $this->logInfo('dispatchci', 'Job Error', [
                'error' => $e->getMessage()
            ]);

            throw $e; // supaya retry jalan
        }
    }
}
