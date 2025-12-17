<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchCIRequest;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function dispatchController(Request $request)
    {
        $url = $request->url;
        DispatchCIRequest::dispatch($request, $url);
    }
}
