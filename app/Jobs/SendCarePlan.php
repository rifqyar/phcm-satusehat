<?php

namespace App\Jobs;

use App\Http\Controllers\SatuSehat\CarePlanController;
use App\Http\Traits\LogTraits;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SendCarePlan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LogTraits;

    public $param;
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
        $this->onQueue('CarePlan');
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
            $param = [
                '_token' => csrf_token(),
                'param' => $this->param,
            ];

            $controller = app(CarePlanController::class);
            $result = $controller->send(new Request($param), $this->resend);
            $this->logInfo('CarePlan', 'Sending CarePlan Using Jobs', [
                'payload' => $this->param,
                'response' => $result,
                'user_id' => Session::get('nama', 'system')
            ]);
        } catch (Exception $e) {
            $this->logError('CarePlan', 'Failed Sending CarePlan Using Jobs', [
                'payload' => $this->param,
                'response' => $e->getMessage(),
                'user_id' => Session::get('nama', 'system') //Session::get('id')
            ]);
            $this->fail($e);
        }
    }
}
