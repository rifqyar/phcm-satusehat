<?php

use Illuminate\Support\Facades\Session;

if (!function_exists('ci_session')) {

    function ci_session($sessKey = null)
    {
        if (!isset($_COOKIE['ci_session_tpid'])) {
            return null;
        }

        $sessionId = $_COOKIE['ci_session_tpid'];

        $path = 'C:\SIMRS\sessions\ci_session_tpid' . $sessionId;

        if (!file_exists($path)) {
            return null;
        }

        $raw = file_get_contents($path);

        $data = ci_session_decode($raw);

        foreach ($data as $key => $value) {
            Session::put($key, $value);
        }

        // Tandai bahwa ini sudah disync
        Session::put('ci_synced', true);
        Session::save();

        if ($sessKey === null) {
            return $data;
        }

        return $data[$sessKey] ?? null;
    }
}

if (!function_exists('ci_session_decode')) {

    function ci_session_decode($session)
    {
        $return = [];
        $offset = 0;

        while ($offset < strlen($session)) {
            if (!strstr(substr($session, $offset), '|')) {
                break;
            }

            $pos = strpos($session, '|', $offset);
            $key = substr($session, $offset, $pos - $offset);
            $offset = $pos + 1;

            $value = unserialize(substr($session, $offset));
            $return[$key] = $value;

            $offset += strlen(serialize($value));
        }

        return $return;
    }
}
