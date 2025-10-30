<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DiagnosticReportController extends Controller
{
    public function index()
    {
        return view('pages.satusehat.diagnostic-report.index');
    }
}
