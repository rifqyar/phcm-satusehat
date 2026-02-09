<?php

namespace App\Jobs;

use App\Services\AllergyIntoleranceService;
use App\Services\ClinicalImpressionService;
use App\Services\EncounterService;
use App\Services\ObservasiService;
use App\Services\ProcedureService;
use App\Services\ServiceRequestService;
use App\Services\SpecimenService;
use App\Services\MedicationRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DispatchToEndpoint implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $param;
    public $endpoint;
    public $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $endpoint,
        array $payload
    ) {
        $this->endpoint = $endpoint;
        $this->payload = $payload;
        $this->onQueue('incoming');
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

        switch ($this->endpoint) {
            case 'encounter':
                app(EncounterService::class)->process($this->payload);
                break;

            case 'observasi':
                app(ObservasiService::class)->process($this->payload);
                break;

            case 'procedure':
                app(ProcedureService::class)->process($this->payload);
                break;

            case 'allergy-intolerance':
                app(AllergyIntoleranceService::class)->process($this->payload);
                break;

            case 'service-request':
                app(ServiceRequestService::class)->process($this->payload);
                break;

            case 'specimen':
                app(SpecimenService::class)->process($this->payload);
                break;

            case 'medication-request':
                app(MedicationRequestService::class)->process($this->payload);
                break;

            case 'medication-dispense':
                app(MedicationRequestService::class)->process($this->payload);
                break;

            case 'clinical-impression':
                app(ClinicalImpressionService::class)->process($this->payload);
                break;

            case 'care-plan':
                app(MedicationRequestService::class)->process($this->payload);
                break;

            case 'episode-of-care':
                app(MedicationRequestService::class)->process($this->payload);
                break;

            default:
                throw new \Exception("Unknown endpoint: {$this->endpoint}");
        }
    }
}
