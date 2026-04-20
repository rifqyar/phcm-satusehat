<?php

namespace App\Jobs;

use App\Http\Controllers\SatuSehat\ImunisasiController;
use App\Http\Traits\LogTraits;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SendImmunization implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LogTraits;

    public $param;
    public $tries = 3; // Number of attempts
    public $timeout = 30; // Timeout in seconds
    public $meta;
    public $id_unit;
    public $resend = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param, $meta, $id_unit, $resend = false)
    {
        $this->param = $param;
        $this->meta = $meta;
        $this->id_unit = $id_unit;
        $this->resend = $resend;
        $this->onQueue('Immunization');
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
            $controller = app(ImunisasiController::class);
            $result = $controller->kirimImunisasiToSatuSehat($this->param, $this->meta, $this->id_unit, $this->resend);
            $this->logInfo('Immunization', 'Sending Immunization Using Jobs', [
                'payload' => $this->param,
                'response' => $result,
                'user_id' => Session::get('nama', 'system')
            ]);
        } catch (Exception $e) {
            $this->logError('Immunization', 'Failed Sending Immunization Using Jobs', [
                'payload' => $this->param,
                'response' => $e->getMessage(),
                'user_id' => Session::get('nama', 'system'), //Session::get('id')
                'trace' => $e->getTrace()
            ]);
            $this->fail($e);
        }
    }
}
