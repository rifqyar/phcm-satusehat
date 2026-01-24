<?php

namespace App\Jobs;

use App\Http\Controllers\SatuSehat\ResumeMedisController;
use App\Http\Traits\LogTraits;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SendResumeMedis implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LogTraits;

  public $param;
  public $tries = 3; // Number of attempts
  public $timeout = 30; // Timeout in seconds
  public $resend = false;
  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($param, $resend = false)
  {
    $this->param = $param;
    $this->resend = $resend;
    $this->onQueue('Composition');
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    DB::disconnect('sqlsrv');
    DB::reconnect('sqlsrv');

    try {
      // Resolve controller from container (keeps middleware / traits accessible)
      $controller = app(ResumeMedisController::class);

      // Create a minimal Request instance to pass (controller doesn't require request body for send)
      $request = request();

      // The sendSatuSehat method expects a base64 encoded parameter
      // but our param is already the LZString compressed data from frontend
      // So we need to base64 encode it first
      $encodedParam = base64_encode($this->param);

      // Call controller method. We ignore the return payload here since it's async.
      $result = $controller->sendSatuSehat($request, $encodedParam);

      // Log successful processing
      Log::info('Send Resume Medis job completed successfully', [
        'param' => $this->param,
        'status_code' => $result->getStatusCode()
      ]);
    } catch (Exception $e) {
      // Log error, job will be retried according to queue config
      Log::error('Send Resume Medis job failed: ' . $e->getMessage(), [
        'param' => $this->param,
        'attempt' => $this->attempts(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      // Re-throw the exception to trigger retry mechanism
      throw $e;
    }
  }
}
