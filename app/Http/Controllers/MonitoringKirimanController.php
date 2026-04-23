<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class MonitoringKirimanController extends Controller
{
    public function index()
    {
        return view('pages.monitoring_kiriman');
    }

    public function getQueueMonitor()
    {
        $queues = [
            'encounter',
            'Condition',
            'observasi',
            'procedure',
            'Composition',
            'Immunization',
            'MedicationRequest',
            'MedicationDispense',
            'AllergyIntolerance',
            'ServiceRequest',
            'ClinicalImpression',
            'specimen',
            'DiagnosticReport',
            'CarePlan',
            'EpisodeOfCare',
            'QuestionnaireResponse'
        ];

        $monitoringData = [];

        foreach ($queues as $q) {
            // Queue::size() otomatis ngecek ke Redis (karena driver lu redis)
            $pending = Queue::size($q);

            // Catatan: Kalau job gagal, defaultnya Laravel mindahin ke tabel SQL 'failed_jobs'
            $failed = DB::table('failed_jobs')->where('queue', $q)->count();

            $monitoringData[] = [
                'endpoint' => str_replace('satusehat-', '', $q), // Bersihin nama buat UI
                'pending' => $pending,
                'failed' => $failed
            ];
        }

        dd($monitoringData);
        return response()->json([
            'status' => 'success',
            'data' => $monitoringData
        ]);
    }
}
