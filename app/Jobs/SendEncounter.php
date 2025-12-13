<?php

namespace App\Jobs;

use App\Http\Controllers\SatuSehat\EncounterController;
use App\Http\Traits\LogTraits;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEncounter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LogTraits;

    public $param;
    public $tries = 3; // Number of attempts
    public $timeout = 120; // Timeout in seconds
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $controller = app(EncounterController::class);
            $encodedParam = base64_encode($this->param);
            $result = $controller->sendSatuSehat($encodedParam);
            $this->logInfo('encounter', 'Sending Encounter Using Jobs', [
                'payload' => $this->param,
                'response' => $result,
                'user_id' => Session::get('username', 'system')
            ]);
        } catch (Exception $e) {
            $this->logError('encounter', 'Failed Sending Encounter Using Jobs', [
                'payload' => $this->param,
                'response' => $e->getMessage(),
                'user_id' => Session::get('username', 'system') //Session::get('id')
            ]);
            $this->fail($e);
        }
    }
}
