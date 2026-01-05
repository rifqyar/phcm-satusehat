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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SendEncounter implements ShouldQueue
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
        $this->onQueue('encounter');
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
            $controller = app(EncounterController::class);
            $encodedParam = base64_encode($this->param);
            $result = $controller->sendSatuSehat($encodedParam, $this->resend);
            $this->logInfo('encounter', 'Sending Encounter Using Jobs', [
                'payload' => $this->param,
                'response' => $result,
                'user_id' => Session::get('nama', 'system')
            ]);
        } catch (Exception $e) {
            $this->logError('encounter', 'Failed Sending Encounter Using Jobs', [
                'payload' => $this->param,
                'response' => $e->getMessage(),
                'user_id' => Session::get('nama', 'system') //Session::get('id')
            ]);
            $this->fail($e);
        }
    }
}
