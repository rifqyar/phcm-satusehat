<?php

namespace App\Jobs;

use App\Http\Controllers\SatuSehat\AllergyIntoleranceController;
use App\Http\Traits\LogTraits;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Session;

class SendAllergyIntolerance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LogTraits;

    public $param;
    // public $tries = 3; // Number of attempts
    public $timeout = 5; // Timeout in seconds
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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $controller = app(AllergyIntoleranceController::class);
            $encodedParam = base64_encode($this->param);
            $result = $controller->sendSatuSehat($encodedParam, $this->resend);
            $this->logInfo('AllergyIntolerance', 'Sending Allergy Intollerance Using Jobs', [
                'payload' => $this->param,
                'response' => $result,
                'user_id' => Session::get('username', 'system')
            ]);
        } catch (Exception $e) {
            $this->logError('AllergyIntolerance', 'Failed Sending Allergy Intollerance Using Jobs', [
                'payload' => $this->param,
                'response' => $e->getMessage(),
                'user_id' => Session::get('username', 'system') //Session::get('id')
            ]);
            $this->fail($e);
        }
    }
}
