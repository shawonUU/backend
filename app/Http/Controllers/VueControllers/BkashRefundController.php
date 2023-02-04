<?php

namespace App\Http\Controllers\VueControllers;

use App\Http\Controllers\VueControllers\Controller;
use Illuminate\Http\Request;
use Session;

class BkashRefundController extends Controller
{
    private $base_url;

    public function __construct()
    {
        // Live
        $this->base_url = 'https://checkout.pay.bka.sh/v1.2.0-beta'; 
    }

    public function authHeaders(){
        return array(
            'Content-Type:application/json',
            'Authorization:' .Session::get('bkash_token'),
            'X-APP-Key:'.env('BKASH_CHECKOUT_APP_KEY')
        );
    }
         
    public function curlWithBody($url,$header,$method,$body_data_json){
        $curl = curl_init($this->base_url.$url);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_POSTFIELDS, $body_data_json);
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function grant()
    {
        $header = array(
                'Content-Type:application/json',
                'username:'.env('BKASH_CHECKOUT_USER_NAME'),
                'password:'.env('BKASH_CHECKOUT_PASSWORD')
                );
        $header_data_json=json_encode($header);

        $body_data = array('app_key'=> env('BKASH_CHECKOUT_APP_KEY'), 'app_secret'=>env('BKASH_CHECKOUT_APP_SECRET'));
        $body_data_json=json_encode($body_data);

        $response = $this->curlWithBody('/checkout/token/grant',$header,'POST',$body_data_json);

        $token = json_decode($response)->id_token;

        return $token;
    }

    public function getRefund(Request $request)
    {
        return view('Bkash.refund');
    }

    public function refund(Request $request)
    {
        $token = $this->grant();
        Session::put('bkash_token', $token);

        $header =$this->authHeaders();

        $body_data = array(
            'paymentID' => $request->paymentID,
            'amount' => $request->amount,
            'trxID' => $request->trxID,
            'sku' => 'sku',
            'reason' => 'Quality issue'
        );
     
        $body_data_json=json_encode($body_data);

        $response = $this->curlWithBody('/checkout/payment/refund',$header,'POST',$body_data_json);

        $arr = json_decode($response,true);
        
        // your database operation

        return view('Bkash.refund')->with([
            'response' => $response,
        ]);
    }

    public function getRefundStatus(Request $request)
    {
        return view('Bkash.refund-status');
    }
    
    public function refundStatus(Request $request)
    {       
        $token = $this->grant();
        Session::put('bkash_token', $token);
        
        $header =$this->authHeaders();

        $body_data = array(
            'paymentID' => $request->paymentID,
            'trxID' => $request->trxID,
        );
        $body_data_json = json_encode($body_data);

        $response = $this->curlWithBody('/checkout/payment/refund',$header,'POST',$body_data_json);
        
        return view('Bkash.refund-status')->with([
            'response' => $response,
        ]);
    }

}
