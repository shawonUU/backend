<?php

namespace App\Http\Controllers\VueControllers\Seller;

use DB;
use Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\SmsTemplate;
use App\Utility\SmsUtility;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use App\Utility\NotificationUtility;
use App\Http\Resources\VUE\ProductMiniDetailsCollection;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource to seller.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $payment_status = null;
        $delivery_status = null;
        $sort_search = null;
        $orders = DB::table('orders')
            ->orderBy('id', 'desc')
            ->where('seller_id', Auth::user()->id)
            ->select('orders.id','orders.user_id','orders.delivery_status','orders.updated_at')
            ->distinct();

        if ($request->payment_status != null) {
            $orders = $orders->where('payment_status', $request->payment_status);
            $payment_status = $request->payment_status;
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->search != null) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }
        $totalOrder = $orders->count();
        $orders = $orders->paginate(10);

        $order = [];
        $today= Carbon::today();
        foreach ($orders as $key => $value) {
            $order[$key] = Order::find($value->id);
            $order[$key]->user = User::find($value->user_id);
            $order[$key]->number_of_products = OrderDetail::where('seller_id',Auth::user()->id)->where('order_id',$value->id)->count();
            $order[$key]->delivery_status  = ucfirst(str_replace('_', ' ', $value->delivery_status));
            $order[$key]->diffdate = $today->diffInDays($value->updated_at);
            $order[$key]->viewed = 1;
        }
        return response()->json(['orders'=>$order,'totalOrder'=>$totalOrder]);
        // return view('seller.orders.index', compact('orders', 'payment_status', 'delivery_status', 'sort_search'));
    }

    public function show($id)
    {
        $order = Order::findOrFail($id);
        $order_shipping_address = json_decode($order->shipping_address);
        $delivery_boys = User::where('city', $order_shipping_address->city)
            ->where('user_type', 'delivery_boy')
            ->get();
        $user = User::find($order->user_id);
        $order->viewed = 1;
        $order->order_date = date('d-m-Y h:i A', $order->date);
        $order->payment_type = ucfirst(str_replace('_', ' ', $order->payment_type));
        $order->product_sub_total =single_price($order->orderDetails->sum('price'));
        $order->tax_total =single_price($order->orderDetails->sum('tax'));
        $order->total_shipping =single_price($order->orderDetails->sum('shipping_cost'));
        $order->orderDetails = $order->orderDetails;

            foreach($order->orderDetails as $key => $product){
                $product->product_info = Product::where('id',$product->product_id)->first();
                $product->product_info->img = uploaded_asset($product->product_info->thumbnail_img);
                $product->product_price = single_price($product->price / $product->quantity);
                $order->orderDetails[$key] = $product;
            }

            // foreach( $data->reviews as $key => $review ){
            //     $review->image = uploaded_asset($review->image);
            //     $review->user_name =User::find($review->user_id)->name;
            //     $review->created = date('d-m-Y', strtotime($review->created_at));
            //     $review->updated= date('d-m-Y', strtotime($review->updated_at));
            //     $data->reviews[$key]=$review;
            // }
        return response()->json([
            'order'=>$order,
            'delivery_boys'=>$delivery_boys,
            'order_shipping_address'=>$order_shipping_address,
            'user'=>$user,
            'products' => $order->orderDetails
        ]);
        // $order->save();
        // return view('seller.orders.show', compact('order', 'delivery_boys'));
    }

    // Update Delivery Status
    public function update_delivery_status(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->delivery_viewed = '0';
        $order->delivery_status = $request->status;
        $order->save();

        if ($request->status == 'cancelled' && $order->payment_type == 'wallet') {
            $user = User::where('id', $order->user_id)->first();
            $user->balance += $order->grand_total;
            $user->save();
        }


        foreach ($order->orderDetails->where('seller_id', Auth::user()->id) as $key => $orderDetail) {
            $orderDetail->delivery_status = $request->status;
            $orderDetail->save();

            if ($request->status == 'cancelled') {
                $variant = $orderDetail->variation;
                if ($orderDetail->variation == null) {
                    $variant = '';
                }

                $product_stock = ProductStock::where('product_id', $orderDetail->product_id)
                    ->where('variant', $variant)
                    ->first();

                if ($product_stock != null) {
                    $product_stock->qty += $orderDetail->quantity;
                    $product_stock->save();
                }
            }
        }

        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'delivery_status_change')->first()->status == 1) {
            try {
                SmsUtility::delivery_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {

            }
        }

        //sends Notifications to user
        NotificationUtility::sendNotification($order, $request->status);
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order updated !";
            $status = str_replace("_", "", $order->delivery_status);
            $request->text = " Your order {$order->code} has been {$status}";

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;

            NotificationUtility::sendFirebaseNotification($request);
        }


        if (addon_is_activated('delivery_boy')) {
            if (Auth::user()->user_type == 'delivery_boy') {
                $deliveryBoyController = new DeliveryBoyController;
                $deliveryBoyController->store_delivery_history($order);
            }
        }

        return 1;
    }

    // Update Payment Status
    public function update_payment_status(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->payment_status_viewed = '0';
        $order->save();

        foreach ($order->orderDetails->where('seller_id', Auth::user()->id) as $key => $orderDetail) {
            $orderDetail->payment_status = $request->status;
            $orderDetail->save();
        }

        $status = 'paid';
        foreach ($order->orderDetails as $key => $orderDetail) {
            if ($orderDetail->payment_status != 'paid') {
                $status = 'unpaid';
            }
        }
        $order->payment_status = $status;
        $order->save();


        if ($order->payment_status == 'paid' && $order->commission_calculated == 0) {
            calculateCommissionAffilationClubPoint($order);
        }

        //sends Notifications to user
        NotificationUtility::sendNotification($order, $request->status);
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order updated !";
            $status = str_replace("_", "", $order->payment_status);
            $request->text = " Your order {$order->code} has been {$status}";

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;

            NotificationUtility::sendFirebaseNotification($request);
        }


        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'payment_status_change')->first()->status == 1) {
            try {
                SmsUtility::payment_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {

            }
        }
        return 1;
    }

}
