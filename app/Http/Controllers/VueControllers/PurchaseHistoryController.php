<?php

namespace App\Http\Controllers\VueControllers;

use DB;
use Auth;
use Carbon\Carbon;
use App\Models\Order;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class PurchaseHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = $request->header();
        $token = $data["token"][0];

        $token = PersonalAccessToken::findToken($token);
        if(!$token) return response()->json(["Unauthorized"], 401);
        $user = $token->tokenable;
        if(!$user) return response()->json(["Unauthorized"], 401);

       $orders = Order::where('user_id', $user->id)->orderBy('code', 'desc')->paginate(9);
        $totalOrder = Order::where('user_id', $user->id)->get()->count();

        foreach( Order::where('user_id', $user->id)->orderBy('code', 'desc')->paginate(9) as $key => $order ){
            $today= Carbon::today();
            $diffdate = $today->diffInDays($order->updated_at);
            $order->diff_date = $diffdate;
            $order->date = date('d-m-Y', $order->date);
            $orders[$key]=$order;
         }
          return response()->json([$orders,$totalOrder]);
        // return view('frontend.user.purchase_history', compact('orders'));
    }

    public function digital_index(Request $request)
    {
      return $orders = DB::table('orders')
      ->orderBy('code', 'desc')
      ->join('order_details', 'orders.id', '=', 'order_details.order_id')
    //   ->join('products', 'order_details.product_id', '=', 'products.id')
      ->where('orders.user_id', Auth::user()->id)
    //   ->where('products.digital', '1')
      ->where('order_details.payment_status', 'paid')
      ->select('order_details.id')
      ->paginate(15);

        $products = [];
        foreach ($orders as $key => $order_id){
            $order = \App\Models\OrderDetail::find($order_id->id);
            $product = $order->product;
            $temp = [];
            $temp['orderId'] = encrypt($product->id);
            $temp['name'] = $product->name;
            $temp['slug'] = $product->slug;
            array_push($products,$temp);
        }

        // return view('frontend.user.digital_purchase_history', compact('orders'));
    }

    public function purchase_history_details($id)
    {
        $order = Order::findOrFail(decrypt($id));
        $order->delivery_viewed = 1;
        $order->payment_status_viewed = 1;
        $order->save();
        return view('frontend.user.order_details_customer', compact('order'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function order_cancel($id)
    {
        $order = Order::where('id', $id)->where('user_id', auth()->user()->id)->first();
        if($order && ($order->delivery_status == 'pending' && $order->payment_status == 'unpaid')) {
            $order->delivery_status = 'cancelled';
            $order->save();

            flash(translate('Order has been canceled successfully'))->success();
        } else {
            flash(translate('Something went wrong'))->error();
        }

        return back();
    }

   public function dontpay($id){

       $order = Order::where('id', $id)->where('user_id', auth()->user()->id)->first();
       if($order->dontpay==null){
           $order->dontpay=1;
            $order->save();
           flash(translate('Your request succesfully submited'))->success();
       }
       else{
           $order->dontpay=null;
            $order->save();
           flash(translate('Your request withdraw succesfully'))->success();
       }

         return back();

   }

}
