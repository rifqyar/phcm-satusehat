<?php

namespace App\Http\Traits;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait LogTraits
{
    public function logInfo($channel, $message, $context = [])
    {
        Log::channel('' . $channel)->info($message, array_merge($context, ['timestamp' => Carbon::now()]));
    }

    public function logError($channel, $message, $context = [])
    {
        Log::channel('' . $channel)->error($message, array_merge($context, ['timestamp' => Carbon::now()]));
    }

    public function logWarning($channel, $message, $context = [])
    {
        Log::channel('' . $channel)->warning($message, array_merge($context, ['timestamp' => Carbon::now()]));
    }

    public function logDb($response = null, $service = null, $payload = null, $user_id = null)
    {
        DB::beginTransaction();
        try {
            $insert = DB::table('SATUSEHAT.dbo.SATUSEHAT_LOG_TRANSACTION')->insert([
                'service' => $service ?? 'UNKNOWN',
                'request' => $payload ?? 'N/A',
                'response' => $response ?? 'N/A',
                'created_by' => $user_id ?? 'system',
                'created_at' => Carbon::now(),
            ]);
            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            $this->logError('logdb', 'Gagal menyimpan log ke database', [
                'error' => $th->getMessage(),
                'service' => $service,
                'user_id' => $user_id
            ]);
        }

    }
}
