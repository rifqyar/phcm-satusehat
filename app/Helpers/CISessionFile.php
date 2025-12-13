<?php

if (!function_exists('ci_session')) {

    function ci_session($key = null)
    {
        if (!isset($_COOKIE['ci_session'])) {
            return null;
        }

        $sessionId = $_COOKIE['ci_session'];

        $path = 'C:\SIMRS\sessions\ci_session_' . $sessionId;

        if (!file_exists($path)) {
            return null;
        }

        $raw = file_get_contents($path);

        $data = ci_session_decode($raw);

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? null;
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
