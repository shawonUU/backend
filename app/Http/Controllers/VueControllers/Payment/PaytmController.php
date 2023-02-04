<?php

namespace App\Http\Controllers\VueControllers\Payment;

use App\Http\Controllers\VueControllers\CheckoutController;
use App\Http\Controllers\VueControllers\WalletController;
use App\Http\Controllers\VueControllers\Controller;
use App\Models\CombinedOrder;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Session;
use PaytmWallet;
use Auth;

class PaytmController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:asian_payment_gateway_configuration'])->only('credentials_index');
    }

    public function pay(){
        if (Auth::user()->phone == null) {
            flash('Please add phone number to your profile')->warning();
            return redirect()->route('profile');
        }
        if(Session::has('payment_type')){
            $transaction = new Transaction;
            $transaction->user_id = Auth::user()->id;
            $transaction->gateway = 'paytm';
            $transaction->payment_type = Session::get('payment_type');
            $transaction->additional_content = json_encode(Session::get('payment_data'));
            $transaction->save();

            if(Session::get('payment_type') == 'cart_payment'){
                $combined_order = CombinedOrder::findOrFail(Session::get('combined_order_id'));
                $amount = $combined_order->grand_total;
                $payment = PaytmWallet::with('receive');
                $payment->prepare([
					'order' => $transaction->id,
					'user' => $combined_order->id,
					'mobile_number' => Auth::user()->phone,
					'email' => Auth::user()->email,
					'amount' => $amount,
					'callback_url' => route('paytm.callback')
                ]);
                return $payment->receive();
            }
            elseif (Session::get('payment_type') == 'wallet_payment') {
                if(Auth::user()->phone != null){
                    $amount= Session::get('payment_data')['amount'];
                    $payment = PaytmWallet::with('receive');
                    $payment->prepare([
                      'order' => $transaction->id,
                      'user' => Auth::user()->id,
                      'mobile_number' => Auth::user()->phone,
                      'email' => Auth::user()->email,
                      'amount' => $amount,
                      'callback_url' => route('paytm.callback')
                    ]);
                    return $payment->receive();
                }
                else {
                    flash('Please add phone number to your profile')->warning();
                    return back();
                }
            }
        }
    }

    public function callback(Request $request){
        $transaction = PaytmWallet::with('receive');

        $response = $transaction->response(); // To get raw response as array
        //Check out response parameters sent by paytm here -> http://paywithpaytm.com/developer/paytm_api_doc?target=interpreting-response-sent-by-paytm

        if($transaction->isSuccessful()){
            $transaction = Transaction::findOrFail($response['ORDERID']);
            if($transaction->payment_type == 'cart_payment'){
                Auth::login(User::findOrFail($transaction->user_id));
                return (new CheckoutController)->checkout_done(json_decode($transaction->additional_content)->combined_order_id, json_encode($response));
            }
            elseif ($transaction->payment_type == 'wallet_payment') {
                Auth::login(User::findOrFail($transaction->user_id));
                return (new WalletController)->wallet_payment_done(json_decode($transaction->additional_content, true), json_encode($response));
            }
        }else if($transaction->isFailed()){
            $request->session()->forget('combined_order_id');
            $request->session()->forget('payment_data');
            flash(translate('Payment cancelled'))->error();
        	return back();
        }else if($transaction->isOpen()){
          //Transaction Open/Processing
        }
        $transaction->getResponseMessage(); //Get Response Message If Available
        //get important parameters via public methods
        $transaction->getOrderId(); // Get order id
        $transaction->getTransactionId(); // Get transaction id
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function credentials_index()
    {
        return view('paytm.index');
    }

    /**
     * Update the specified resource in .env
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update_credentials(Request $request)
    {
        foreach ($request->types as $key => $type) {
                $this->overWriteEnvFile($type, $request[$type]);
        }

        flash("Settings updated successfully")->success();
        return back();
    }

    /**
    *.env file overwrite
    */
    public function overWriteEnvFile($type, $val)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            $val = '"'.trim($val).'"';
            if(is_numeric(strpos(file_get_contents($path), $type)) && strpos(file_get_contents($path), $type) >= 0){
                file_put_contents($path, str_replace(
                    $type.'="'.env($type).'"', $type.'='.$val, file_get_contents($path)
                ));
            }
            else{
                file_put_contents($path, file_get_contents($path)."\r\n".$type.'='.$val);
            }
        }
    }
}
