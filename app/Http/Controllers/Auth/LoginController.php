<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    // Show the login form
    public function showLoginForm()
    {
        $sites = DB::table('RIRJ_MKODE_UNIT')
            ->select(['ID_UNIT AS IDUNIT', 'NAMA_UNIT'])
            ->where('AKTIF', 1)
            ->get();

        return view('auth.login', compact('sites'));
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'id_unit' => 'required',
            'nipp' => 'required',
            'password' => 'required',
        ]);

        $nipp = $request->nipp;
        $password = $request->password;
        $idunit = $request->id_unit;

        $url = env('API_URL') . "/restsirs-simrs-gcp/public/master/user/login";

        $param  = array(
            "idunit" => $idunit,
            "password" => $password,
        );
        $param['nipp'] = $nipp;

        $param['bypass'] = isset($request->bypass) ? 1 : 0;
        $method = "POST";

        $execLogin = self::doLogin($url, $param, $method);

        $response = json_decode($execLogin, true);

        if (!$response) {
            return back()->withErrors([
                'login' => 'Gagal menghubungi server autentikasi',
            ]);
        }

        // contoh asumsi response API
        if (isset($response['metadata']) && $response['metadata']['code'] === 200) {
            // simpan session
            $userData = json_decode(json_encode($response['response']['data']));

            $sess = array(
                "id"         => $userData->id,
                "nipp"       => $userData->nipp,
                "nama"       => $userData->nama,
                "tipe"       => $userData->tipe,
                "foto"       => $userData->foto,
                "id_unit"    => $userData->lokasi->idunit,
                "data"       => $userData,
                "p"          => $userData->password,
                "sdh_masuk"  => true,
                'token_centra' => $userData->token_centra,
                'kirim_satusehat' => $userData->kirim_satusehat,
            );

            session($sess);
            return redirect()->route('home');
        }

        return back()->withErrors([
            'login' => $response['message'] ?? 'Username atau password salah',
        ])->withInput();
    }

    public function loginDirect(Request $request)
    {
        $data = $request->all();
        foreach ($data as $key => $value) {
            $data[$key] = $this->_decrypt_url($value);
        }

        $data['sdh_masuk'] = true;
        session($data);
        return redirect()->route('home');
    }

    public static function doLogin($url, $param, $method, $authorized = '')
    {
        date_default_timezone_set("Asia/Jakarta");
        if ($param['bypass'] == 1) {
            $bypass = 'true';
        } else {
            $bypass = 'false';
        }

        $headers                = array(
            "Accept: application/json",
            "X-id: 202212150017017",
            "X-key: a852b51b48d2a5a4b0954a5b469f44c5fb3848c328e7770d2c42a3a5d80c481b67bfd894a9ef4eea99b58c94946f07690d37236624e7714b9fcddb163a9654c1",
            "Cookie:storageToken=MTYzNjE2NDAwNXxwaTZfS1JpM1otSUtnRVRGVUZVWUNJY05LaHFjdmN4bjE2dURZN28ya3pMcTI3T0ozRkVhR3dESkR2blp1dWd3SFV5RG9idE1rWnZWSTlxRkZJLXNmNHB3VmZtMDktVWVNX2N2OWF5eTZQa3pRTzF5fKz80-zI5qzteU_SQMmJrERCS8wp4FRRv3GeUA8pXP3_",
            "Authorization: Bearer " . $authorized,
            "bypasscentra: " . $bypass,
        );

        if ($method != "POST") {
            $param = http_build_query($param);
            $url = $url . '?' . $param;
        }

        $request                = curl_init($url);
        curl_setopt($request, CURLOPT_TIMEOUT, 30);
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_CUSTOMREQUEST, $method);
        if ($param != "") {
            curl_setopt($request, CURLOPT_POSTFIELDS, $param);
        }
        curl_setopt($request, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);

        $result                 = curl_exec($request);

        if ($result === false) {
            $result             = curl_error($request);
        } else {
            $result             = $result;
        }
        curl_close($request);

        return $result;
    }

    public function logout()
    {
        Session::invalidate();
        Session::flush();
        Session::regenerateToken();
        return redirect(env('APP_URL'));
    }

    private function _decrypt_url($encryptedData)
    {
        $key = $this->_get_key();
        $decodedData = $this->_base64url_decode($encryptedData);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($decodedData, 0, $ivLength);
        $encrypted = substr($decodedData, $ivLength);
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
        return $decrypted;
    }
    private function _base64url_encode($data)
    {
        $base64 = base64_encode($data);
        $base64url = strtr($base64, '+/', '-_');
        $base64url = rtrim($base64url, '=');
        return $base64url;
    }
    private function _base64url_decode($data)
    {
        $base64url = strtr($data, '-_', '+/');
        $paddedBase64 = str_pad($base64url, strlen($base64url) % 4, '=', STR_PAD_RIGHT);
        return base64_decode($paddedBase64);
    }
    private function _get_key()
    {
        return 'zXSVBcNtyuiOpwqWERtcySDgas657923';
    }
}
