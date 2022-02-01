<?php
/**
 * Created by PhpStorm.
 * User: iankibet
 * Date: 3/10/17
 * Time: 1:52 PM
 */

namespace App\Repositories;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Auth;

class PaypalRepository
{
    protected $client_id = 'AT0XtS3_iENVVC63kF-8HQN4gOO1m3tzTt08pMb37I2ZCx-9X5eQOjwpiQ5VLSOf8hIdXf8Q5ScmpLu1';
    protected $secret = 'EOdAkdx0ww0RJv1nEX1aspD035EvtWwfKz2uko0jS4W-hbjWVf0wdXsyK6Eu2Qn7hBBs8b6LRgZgra_s';
    protected $return_url;
    protected $cancel_url;
    protected $url_head = "https://api.paypal.com/v1/payments/payment";
    protected $endpoint;
    protected $user_id;
    protected $password;
    protected $signature;
    protected $app_id;

    public function __construct($cancel_url,$return_url)
    {
        $this->cancel_url = url($cancel_url);
        $this->return_url = url($return_url);
        $config = json_decode(Storage::disk('local')->get('system/paypal.json'));
        if($config){
            $paypal = $config->paypal;
            $this->endpoint = $paypal->endpoint;
            $this->client_id = $paypal->client_id;
            $this->secret = $paypal->secret;
        }
    }

    public function payout($payout)
    {

        $user = $payout->user;
        $account_email = $user->paypal_email;
        if(!$account_email)
            $account_email = 'kibetian8@gmail.com';
        $amount = round($payout->amount,2);
        $this->return_url = url("admin/payments/payouts/confirm/$payout->id");
        $this->cancel_url = url("admin/payments/payouts");

        $url = $this->endpoint."AdaptivePayments/Pay";
        $header = $this->getHeaders();
        $data = '{
              "actionType":"PAY",
              "currencyCode":"USD",
              "receiverList":{
                "receiver":[
                  {
                    "amount":"'.$amount.'",
                    "email":"'.$account_email.'"
                  }
                ]
              },
              "returnUrl":"'.$this->return_url.'",
              "cancelUrl":"'.$this->cancel_url.'",
              "requestEnvelope":{
                "errorLanguage":"en_US",
                "detailLevel":"ReturnAll"
              }
            }';
        $content = $this->execCurl($url,$data,$header);
        $payKey = $content->payKey;
        return $payKey;
    }
    protected function getHeaders(){
        $header = array(
            'X-PAYPAL-SECURITY-USERID: '.$this->user_id,
            'X-PAYPAL-SECURITY-PASSWORD: '.$this->password,
            'X-PAYPAL-SECURITY-SIGNATURE: '.$this->signature,
            'X-PAYPAL-REQUEST-DATA-FORMAT: JSON',
            'X-PAYPAL-APPLICATION-ID: '.$this->app_id
        );
        return $header;
    }

    protected function execCurl($url,$data,$header=null)
    {
        if(!$header){
            $header = array(
                'Accept: application/json',
                'Accept-Language: en_US',
            );
        }

        $method = 'POST';
//        if($u)
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $header);
        $content = curl_exec($curl);
//        dd(json_decode($content));
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $json_response = null;
        if($status==200 || $status==201){
            $json_response = json_decode($content);

        }
        else{
            echo 'Curl error: ' . curl_error($curl);
//            $content = json_decode($content);
//            return redirect()->back()->with('notice',['class'=>'warning','message'=>$content->message]);
            var_dump($content,$status,"curl fetch empty");
            echo '<pre/>';
            var_dump($data,$header,$url);
            echo '<pre/>';
            exit;

        }

        return $json_response;

    }

    public function getAccessToken()
    {
        if($this->endpoint == 'https://api.paypal.com'){
            $url = "https://$this->client_id:$this->secret@api.paypal.com/v1/oauth2/token?grant_type=client_credentials";
        }else{
            $url = "https://$this->client_id:$this->secret@api.sandbox.paypal.com/v1/oauth2/token?grant_type=client_credentials";
        }
        $header = [
            'Accept: application/json',
            'Accept-Language: en_US'
        ];
        $data = [
            'grant_type'=>'client_credentials'
        ];
        $data = json_encode($data);
        $content = $this->execCurl($url,$data,$header);
        $access_token = $content->access_token;
        return $access_token;
    }

    public function checkout($amount,$user_id,$descr)
    {
        if($amount  == 0)
//            dd("Amount too low for checkout");
        $amount = round($amount,2);
        $url = $this->endpoint.'/v1/payments/payment';
        $header = [
            "Content-Type: application/json",
            "Authorization: Bearer ".$this->getAccessToken()
        ];
        $data =[
            'intent'=>'Sale',
            'payer'=>[
                'payment_method'=>'paypal'
            ],
            'transactions'=>[
                [
                    "amount"=>[
                        "total"=>$amount,
                        'currency'=>'USD',
                        'details'=>[
                            'subtotal'=>$amount,
                            'tax'=>0,
                            'shipping'=>0
                        ]
                    ],
                    'description'=>'Order Payment',
                    'item_list'=>[
                        'items'=>[
                            [
                                'quantity'=>1,
                                'name'=>$descr,
                                'sku'=>$user_id,
                                'currency'=>'USD',
                                'price'=>$amount
                            ]
                        ]

                    ]
                ]
            ],
            'note_to_payer'=>'contact ...',
            'redirect_urls'=>[
                'return_url'=>$this->return_url,
                'cancel_url'=>$this->cancel_url
            ]
        ];

        $data = json_encode($data);

        $content = $this->execCurl($url,$data,$header);
        $payerID = $content->id; //submit to database on
        $state = $content->state;

        $approval_url = "";
        if($state == "created"){
            $approval_url = $content->links[1]->href;
            $execute_url = $content->links[2]->href;

            $data = header('Location: '.$approval_url);
            var_dump("Checkout Failed"); exit;


        }
    }

    /**
     * Paypal checkout final request.
     *Execute payment after the payer has approvedapp
     */
    public function execute($payer_id,$payment_id)
    {
        $url = $this->endpoint."/v1/payments/payment"."/$payment_id/execute";
        $header = [
            "Content-Type:application/json",
            "Authorization: Bearer ".$this->getAccessToken().""
        ];

        $data = '{
          "payer_id": "'.$payer_id.'"
        }';
        return $this->execCurl($url,$data,$header);

    }

    public function paymentDetails($paykey)
    {
        $header = $this->getHeaders();
        $url = $this->endpoint.'AdaptivePayments/PaymentDetails';
        $data = '{"payKey":"'.$paykey.'","requestEnvelope":{
                          "errorLanguage":"en_US",
                          "detailLevel":"ReturnAll"
                          }}';
        $response = $this->execCurl($url,$data,$header);
        return $response;
    }
}
