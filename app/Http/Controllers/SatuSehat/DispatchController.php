<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Jobs\DispatchCIRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class DispatchController extends Controller
{
    use LogTraits;
    public function dispatchController(Request $request)
    {
        $payload = $request->except(['url']); // array murni
        $urls    = $request->input('url');   // array endpoint
        Session::put('id_unit_simrs', $request->input('id_unit'));

        $this->logInfo('dispatchci', 'Receive param', [
            'payload' => $payload,
            'urls' => $urls
        ]);

        DispatchCIRequest::dispatch($payload, $urls)->onQueue('incoming');

        return response()->json([
            'status' => 'queued'
        ]);
    }
}
