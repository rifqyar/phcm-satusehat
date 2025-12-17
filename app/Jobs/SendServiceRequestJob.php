<?php

namespace App\Jobs;

use App\Http\Controllers\SatuSehat\ServiceRequestController;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendServiceRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $param;
    // public $tries = 3; // Number of attempts
    public $timeout = 5; // Timeout in seconds

    /**
     * Create a new job instance.
     *
     * @param string $param Encoded param used by sendSatuSehat
     */
    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * This reuses the controller's sendSatuSehat method.
     */
    public function handle()
    {
        try {
            // Resolve controller from container (keeps middleware / traits accessible)
            $controller = app(ServiceRequestController::class);

            // Create a minimal Request instance to pass (controller doesn't require request body for send)
            $request = request();

            // The sendSatuSehat method expects a base64 encoded parameter
            // but our param is already the LZString compressed data from frontend
            // So we need to base64 encode it first
            $encodedParam = base64_encode($this->param);

            // Call controller method. We ignore the return payload here since it's async.
            $result = $controller->sendSatuSehat($request, $encodedParam);

            // Log successful processing
            Log::info('SendServiceRequestJob completed successfully', [
                'param' => $this->param,
                'status_code' => $result->getStatusCode()
            ]);

        } catch (Exception $e) {
            // Log error, job will be retried according to queue config
            Log::error('SendServiceRequestJob failed: ' . $e->getMessage(), [
                'param' => $this->param,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw the exception to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('SendServiceRequestJob permanently failed after all retries', [
            'param' => $this->param,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
