<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogTraits;
use App\Jobs\DispatchCIRequest;
use App\Jobs\DispatchToEndpoint;
use App\Services\ClinicalImpressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DispatchController extends Controller
{
    use LogTraits;
    public function dispatchController(Request $request)
    {
        $payload = $request->except('url');
        $urls = $request->input('url');
        Session::put('id_unit', $request->input('id_unit'));

        /** Check encounter dulu */
        $encounterId = DB::selectOne("
            SELECT ID_SATUSEHAT_ENCOUNTER FROM fn_getDataKunjungan(?, 'RAWAT_JALAN')
            WHERE ID_TRANSAKSI = ?
            UNION ALL
            SELECT ID_SATUSEHAT_ENCOUNTER FROM fn_getDataKunjungan(?, 'RAWAT_INAP')
            WHERE ID_TRANSAKSI = ?
        ", [
            $request->id_unit,
            $payload['karcis'],
            $request->id_unit,
            $payload['karcis'],
        ]);

        foreach ($urls as $val) {
            $endpoint = explode('/', $val)[1];

            if ($endpoint !== 'encounter' && !$encounterId->ID_SATUSEHAT_ENCOUNTER) {
                continue;
            }

            DispatchToEndpoint::dispatch(
                $endpoint,
                $payload
            )->onQueue('incoming');
        }

        return response()->json(['status' => 'queued']);

        // $payload = $request->except(['url']); // array murni
        // $urls    = $request->input('url');   // array endpoint
        // Session::put('id_unit', $request->input('id_unit'));

        // $this->logInfo('dispatchci', 'Receive param', [
        //     'payload' => $payload,
        //     'urls' => $urls
        // ]);

        // DispatchCIRequest::dispatch($payload, $urls)->onQueue('incoming');

        // return response()->json([
        //     'status' => 'queued'
        // ]);
    }
}
