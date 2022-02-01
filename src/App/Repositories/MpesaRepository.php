<?php
namespace App\Repositories;
use App\Models\Core\Payment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
class MpesaRepository
{

    //c2b mpesa functions
    public function C2BMpesaApi($payment){

        $timestamp = '20210116143600';
        $password = base64_encode(env('C2B_SHORTCODE').env('PASSKEY').$timestamp);
        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'BusinessShortCode' =>env('C2B_SHORTCODE'),
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => round($payment->amount,0),
            'PartyA' => $this->formatPhone($payment->phone),
            'PartyB' => env('C2B_SHORTCODE'),
            'PhoneNumber' => $this->formatPhone($payment->phone),
            'CallBackURL' =>  url('api/complete-payment/'.$payment->id),
            'AccountReference' => 'Property#'.$payment->property_id,
            'TransactionDesc' => "Transaction for payment ID #".$payment->id
        );
        $data_string = json_encode($curl_post_data);
        $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        $headers = array('Content-Type:application/json','Authorization:Bearer '.$this->getAccessToken()->access_token);
        $res = $this->doCurl($url,$data_string,'POST',$headers);
        return $res;
    }

    protected function getAccessToken(){

        $consumer_key = env('C2B_CONSUMER_KEY');
        $consumer_secret = env('C2B_CONSUMER_SECRET');
        $url = "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
        $data = '';
        $header = [
            'Authorization: Basic '.base64_encode($consumer_key.':'.$consumer_secret)
        ];
        $response = $this->doCurl($url,$data,'GET',$header);
        return $response;
    }

    protected function doCurl($url,$data,$method='POST',$header = null){
        if (!$header) {
            $header = array(
                'Accept: application/json',
                'Accept-Language: en_US',
            );
        }
        $curl = \curl_init($url);
        \curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POST, true);

        \curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        \ curl_setopt($curl, CURLOPT_HEADER, 0);
        \curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        \curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $content = \curl_exec($curl);
        $status = \curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $json_response = null;
        if ($status == 200 || $status == 201) {
            $json_response = json_decode($content);
            return $json_response;
        } else {
            throw new \Exception($content);
        }
    }


    //b2c mpesa functions
    public function B2CMpesaApi($amount,$phone_number,$callback_url,$withdrawal_id)
    {
        $formatted_user_phone = $this->formatPhone($phone_number);

        /*
       * B2C parameters
       */

        $InitiatorName =env('B2C_USERNAME');
        $SecurityCredential =env('B2C_PASSWORD');
        $CommandID = 'BusinessPayment';
        $Amount = $amount;
        $PartyA = env('B2C_SHORTCODE');
        $PartyB = $formatted_user_phone;
        $Remarks = 'Withdrawal from MobiTip to M-Pesa';
        $QueueTimeOutURL = $callback_url.'/'.$withdrawal_id;
        $ResultURL = $callback_url.'/'.$withdrawal_id;
        $Occasion = "Withdrawal";


        /*
        * Innitiate payment from paybill to fund withdrawal
        */
        $return = self::b2c($InitiatorName, $SecurityCredential, $CommandID, $Amount, $PartyA, $PartyB, $Remarks, $QueueTimeOutURL, $ResultURL, $Occasion);

        return $return;
//
    }

    public static function b2c($InitiatorName, $SecurityCredential, $CommandID, $Amount, $PartyA, $PartyB, $Remarks, $QueueTimeOutURL, $ResultURL, $Occasion){


        $token=self::generateB2CLiveToken();
        $url = 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';


        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$token));
        $pk = openssl_pkey_get_public(self::returnCert());
        $res = openssl_public_encrypt(env('B2C_PASSWORD'), $encrypted, $pk, OPENSSL_PKCS1_PADDING);
        $SecurityCredential = base64_encode($encrypted);
        $curl_post_data = array(
            'InitiatorName' => $InitiatorName,
            'SecurityCredential' => $SecurityCredential,
            'CommandID' => 'BusinessPayment' ,
            'Amount' => $Amount,
            'PartyA' => $PartyA ,
            'PartyB' => $PartyB,
            'Remarks' => $Remarks,
            'QueueTimeOutURL' => $QueueTimeOutURL,
            'ResultURL' => $ResultURL,
            'Occasion' => $Occasion
        );
//        dd($curl_post_data);

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        return json_encode($curl_response);

    }

    public static function generateB2CLiveToken(){

        $consumer_key = env('B2C_CONSUMER_KEY');
        $consumer_secret = env('B2C_CONSUMER_SECRET');

        $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        $credentials = base64_encode($consumer_key.':'.$consumer_secret);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$credentials)); //setting a custom header
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $curl_response = curl_exec($curl);

        return json_decode($curl_response)->access_token;

    }

    public function formatPhone($phone){
        $phone = 'hfhsgdgs'.$phone;
        $phone = str_replace('hfhsgdgs0','',$phone);
        $phone = str_replace('hfhsgdgs','',$phone);
        $phone = str_replace('+','',$phone);
        if(strlen($phone) == 9){
            $phone = '254'.$phone;
        }
        return $phone;
    }


    public function queryMpesaPaymentTransaction($payment_id){
        /*
        * create a security credential for M-Pesa C2B
        */
        $pk = openssl_pkey_get_public(self::returnCert());
        $res = openssl_public_encrypt(env('C2B_API_PASSWORD'), $encrypted, $pk, OPENSSL_PKCS1_PADDING);
        $SecurityCredential = base64_encode($encrypted);

        $payment = Payment::find($payment_id);
        $curl_post_data = array(
            'Initiator' => env('C2B_API_USERNAME'),
            'SecurityCredential' => $SecurityCredential,
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => $payment->reference,
            'PartyA' => env('C2B_SHORTCODE'),
            'IdentifierType' => 4,
            'ResultURL' => url('/api/hermes/mobitip/transaction-result/').'/'.$payment_id,
            'QueueTimeOutURL' => url('/api/hermes/mobitip/transaction-lookup/').'/'.($payment_id),
            'Remarks' => 'Payment Transaction Lookup',
            'Occasion' => 'Payment Transaction Lookup'
        );

        $data_string = json_encode($curl_post_data);
        $url = 'https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query';
        $headers = array('Content-Type:application/json','Authorization:Bearer '.$this->getAccessToken()->access_token);
        $res = $this->doCurl($url,$data_string,'POST',$headers);
        return $res;

    }

    public static function returnCert(){
        $str ='-----BEGIN CERTIFICATE-----
MIIGkzCCBXugAwIBAgIKXfBp5gAAAD+hNjANBgkqhkiG9w0BAQsFADBbMRMwEQYK
CZImiZPyLGQBGRYDbmV0MRkwFwYKCZImiZPyLGQBGRYJc2FmYXJpY29tMSkwJwYD
VQQDEyBTYWZhcmljb20gSW50ZXJuYWwgSXNzdWluZyBDQSAwMjAeFw0xNzA0MjUx
NjA3MjRaFw0xODAzMjExMzIwMTNaMIGNMQswCQYDVQQGEwJLRTEQMA4GA1UECBMH
TmFpcm9iaTEQMA4GA1UEBxMHTmFpcm9iaTEaMBgGA1UEChMRU2FmYXJpY29tIExp
bWl0ZWQxEzARBgNVBAsTClRlY2hub2xvZ3kxKTAnBgNVBAMTIGFwaWdlZS5hcGlj
YWxsZXIuc2FmYXJpY29tLmNvLmtlMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIB
CgKCAQEAoknIb5Tm1hxOVdFsOejAs6veAai32Zv442BLuOGkFKUeCUM2s0K8XEsU
t6BP25rQGNlTCTEqfdtRrym6bt5k0fTDscf0yMCoYzaxTh1mejg8rPO6bD8MJB0c
FWRUeLEyWjMeEPsYVSJFv7T58IdAn7/RhkrpBl1dT7SmIZfNVkIlD35+Cxgab+u7
+c7dHh6mWguEEoE3NbV7Xjl60zbD/Buvmu6i9EYz+27jNVPI6pRXHvp+ajIzTSsi
eD8Ztz1eoC9mphErasAGpMbR1sba9bM6hjw4tyTWnJDz7RdQQmnsW1NfFdYdK0qD
RKUX7SG6rQkBqVhndFve4SDFRq6wvQIDAQABo4IDJDCCAyAwHQYDVR0OBBYEFG2w
ycrgEBPFzPUZVjh8KoJ3EpuyMB8GA1UdIwQYMBaAFOsy1E9+YJo6mCBjug1evuh5
TtUkMIIBOwYDVR0fBIIBMjCCAS4wggEqoIIBJqCCASKGgdZsZGFwOi8vL0NOPVNh
ZmFyaWNvbSUyMEludGVybmFsJTIwSXNzdWluZyUyMENBJTIwMDIsQ049U1ZEVDNJ
U1NDQTAxLENOPUNEUCxDTj1QdWJsaWMlMjBLZXklMjBTZXJ2aWNlcyxDTj1TZXJ2
aWNlcyxDTj1Db25maWd1cmF0aW9uLERDPXNhZmFyaWNvbSxEQz1uZXQ/Y2VydGlm
aWNhdGVSZXZvY2F0aW9uTGlzdD9iYXNlP29iamVjdENsYXNzPWNSTERpc3RyaWJ1
dGlvblBvaW50hkdodHRwOi8vY3JsLnNhZmFyaWNvbS5jby5rZS9TYWZhcmljb20l
MjBJbnRlcm5hbCUyMElzc3VpbmclMjBDQSUyMDAyLmNybDCCAQkGCCsGAQUFBwEB
BIH8MIH5MIHJBggrBgEFBQcwAoaBvGxkYXA6Ly8vQ049U2FmYXJpY29tJTIwSW50
ZXJuYWwlMjBJc3N1aW5nJTIwQ0ElMjAwMixDTj1BSUEsQ049UHVibGljJTIwS2V5
JTIwU2VydmljZXMsQ049U2VydmljZXMsQ049Q29uZmlndXJhdGlvbixEQz1zYWZh
cmljb20sREM9bmV0P2NBQ2VydGlmaWNhdGU/YmFzZT9vYmplY3RDbGFzcz1jZXJ0
aWZpY2F0aW9uQXV0aG9yaXR5MCsGCCsGAQUFBzABhh9odHRwOi8vY3JsLnNhZmFy
aWNvbS5jby5rZS9vY3NwMAsGA1UdDwQEAwIFoDA9BgkrBgEEAYI3FQcEMDAuBiYr
BgEEAYI3FQiHz4xWhMLEA4XphTaE3tENhqCICGeGwcdsg7m5awIBZAIBDDAdBgNV
HSUEFjAUBggrBgEFBQcDAgYIKwYBBQUHAwEwJwYJKwYBBAGCNxUKBBowGDAKBggr
BgEFBQcDAjAKBggrBgEFBQcDATANBgkqhkiG9w0BAQsFAAOCAQEAC/hWx7KTwSYr
x2SOyyHNLTRmCnCJmqxA/Q+IzpW1mGtw4Sb/8jdsoWrDiYLxoKGkgkvmQmB2J3zU
ngzJIM2EeU921vbjLqX9sLWStZbNC2Udk5HEecdpe1AN/ltIoE09ntglUNINyCmf
zChs2maF0Rd/y5hGnMM9bX9ub0sqrkzL3ihfmv4vkXNxYR8k246ZZ8tjQEVsKehE
dqAmj8WYkYdWIHQlkKFP9ba0RJv7aBKb8/KP+qZ5hJip0I5Ey6JJ3wlEWRWUYUKh
gYoPHrJ92ToadnFCCpOlLKWc0xVxANofy6fqreOVboPO0qTAYpoXakmgeRNLUiar
0ah6M/q/KA==
-----END CERTIFICATE-----';

        return $str;
    }

}

