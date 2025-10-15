<?php

namespace App\Http\Traits;

use Exception;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\UnitPHC;
use App\Models\GlobalParameter;

use Illuminate\Support\Facades\DB;
use App\Models\SATUSEHAT\SS_Kode_API;
use App\Models\SATUSEHAT\SS_Location;
use App\Models\SATUSEHAT\SatuSehatAuth;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

trait SATUSEHATTraits
{
    public function login($idunit)
    {
        $baseurl = '';
        $clientKey = '';
        $secretKey = '';
        $token = '';

        $kode_unit = UnitPHC::where('ID_UNIT', $idunit)->first();
        $hasil = array();
        DB::connection('dbsatusehat')->beginTransaction();
        try {
            if ($kode_unit->KIRIM_SATUSEHAT == 0) {
                $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_AUTHURL_STAGING')->select('valStr')->first()->valStr;
                $clientKey = SS_Kode_API::where('idunit', $idunit)->where('env', 'Dev')->select('client_key')->first()['client_key'];
                $secretKey = SS_Kode_API::where('idunit', $idunit)->where('env', 'Dev')->select('secret_key')->first()['secret_key'];
            } else {
                $env = strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT' ? 'Dev' : 'Prod';
                $clientKey = SS_Kode_API::where('idunit', $idunit)->where('env', $env)->select('client_key')->first()['client_key'];
                $secretKey = SS_Kode_API::where('idunit', $idunit)->where('env', $env)->select('secret_key')->first()['secret_key'];
                if (strtoupper(env('SATUSEHAT', 'PRODUCTION')) == 'DEVELOPMENT') {
                    $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_AUTHURL_STAGING')->select('valStr')->first()->valStr;
                } else {
                    $baseurl = GlobalParameter::where('tipe', 'SATUSEHAT_AUTHURL')->select('valStr')->first()->valStr;
                }
            }

            $auth = SatuSehatAuth::where('issued_at', '<=', Date('Y-m-d H:i:s'))
                ->where('expired_in', '>=', Date('Y-m-d H:i:s'))
                ->where('client_id', $clientKey)->first();

            if ($auth == null) {
                $url = 'accesstoken?grant_type=client_credentials';
                $client = new Client([
                    'base_uri' => $baseurl,
                    'verify' => false,
                ]);
                $param = [
                    'form_params' => [
                        'client_id' => $clientKey,
                        'client_secret' => $secretKey,
                    ],
                ];

                $response = $client->request('POST', $url, $param)->getBody();
                $response = json_decode($response, true);

                $issued_at = Carbon::createFromTimestamp((float)$response['issued_at'] / 1000)->toDateTimeString();
                $expired_in = Carbon::createFromTimestamp((float)$response['issued_at'] / 1000)->addSeconds($response['expires_in'])->toDateTimeString();
                $token = $response['access_token'];

                $satusehat = new SatuSehatAuth();
                $satusehat->developer_email = $response['developer.email'];
                $satusehat->issued_at = $issued_at;
                $satusehat->expired_in = $expired_in;
                $satusehat->client_id = $response['client_id'];
                $satusehat->access_token = $response['access_token'];
                $satusehat->idunit = $idunit;
                $satusehat->save();
            } else {
                $token = $auth->access_token;
                $issued_at = $auth->issued_at;
                $expired_in = $auth->expired_in;
            }

            DB::connection('dbsatusehat')->commit();
            $hasil['metadata'] = array(
                'code' => 200,
                'message' => 'OK',
            );
            $hasil['response'] = array(
                'issued_at' => $issued_at,
                'expired_in' => $expired_in,
                'token' => $token,
            );
        } catch (Exception $e) {
            DB::connection('dbsatusehat')->rollBack();
            $hasil['metadata'] = array(
                'code' => 500,
                'message' => 'Error : ' . $e->getMessage(),
            );
        }

        return $hasil;
    }
    public function consumeSATUSEHATAPI($method, $baseuri, $url, $query_param, $isJSON, $token, $body = false)
    {
        try {
            if ($method == "GET") {
                $client = new Client([
                    'base_uri' => $baseuri,
                    'verify' => false,
                    'query' => $query_param,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                    ]
                ]);
                $response = $client->request($method, $url);
            } elseif ($method == 'PATCH') {
                $client = new Client([
                    'base_uri' => $baseuri,
                    'verify' => false,
                ]);
                $param = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json-patch+json',
                    ],
                    'json' => $query_param,
                ];

                $response = $client->request($method, $url, $param);
            } else {
                if ($isJSON) {
                    $client = new Client([
                        'base_uri' => $baseuri,
                        'verify' => false,
                    ]);
                    $param = [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $token,
                            'Content-Type'  => 'application/json',
                            'Accept'        => 'application/json',
                        ],
                        'json' => $query_param,
                    ];

                    $response = $client->request($method, $url, $param);
                } elseif ($body == true) {
                    $client = new Client([
                        'base_uri' => $baseuri,
                        'verify' => false,
                    ]);
                    $param = [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $token,
                        ],
                        'body' => $query_param,
                    ];

                    $response = $client->request($method, $url, $param);
                } else {
                    $client = new Client([
                        'base_uri' => $baseuri,
                        'verify' => false,
                    ]);
                    $param = [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $token,
                        ],
                        'form_params' => $query_param,
                    ];

                    $response = $client->request($method, $url, $param);
                }
            }
        } catch (ClientException $e) {
            $response = $e->getResponse();
        } catch (ServerException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }
}
