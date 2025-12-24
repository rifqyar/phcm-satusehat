<?php

namespace App\Jobs;

use App\Services\AllergyIntoleranceService;
use App\Services\EncounterService;
use App\Services\ObservasiService;
use App\Services\ProcedureService;
use App\Services\ServiceRequestService;
use App\Services\SpecimenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

            default:
                throw new \Exception("Unknown endpoint: {$this->endpoint}");
        }
    }
}
