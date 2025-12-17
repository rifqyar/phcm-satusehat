<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Jobs\DispatchCIRequest;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    use LogTraits;
    public function dispatchController(Request $request)
    {
        $this->logInfo('dispatchci', 'Receive param', [
            'payload' => $request,
        ]);

        $url = $request->url;
        DispatchCIRequest::dispatch($request, $url);
    }
}
