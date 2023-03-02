<?php

namespace App\Http\Controllers\VueControllers;

use App\Utility\PayfastUtility;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Address;
use App\Models\Carrier;
use App\Models\CombinedOrder;
use App\Models\Product;
use App\Utility\PayhereUtility;
use App\Utility\NotificationUtility;
use Session;
use Auth;
use App\Http\Resources\VUE\ProductCollection;

use App\Http\Resources\VUE\AddressCollection;
use App\Http\Resources\VUE\CarrierCollection;

class CheckoutController extends Controller
{

    public function __construct()
    {
        //
    }

    //check the selected payment gateway and redirect to that controller accordingly
    public function checkout(Request $request)
    {
        // Minumum order amount check
        $returnType = null;
        $message = null;

        if(get_setting('minimum_order_amount_check') == 1){
            $subtotal = 0;
            foreach (Cart::where('user_id', Auth::user()->id)->get() as $key => $cartItem){
                $product = Product::find($cartItem['product_id']);
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
            }
            // return get_setting('minimum_order_amount');
            if ($subtotal < get_setting('minimum_order_amount')) {
                $returnType = "worning";
                $message = 'You order amount is less then the minimum order amount';
            }
        }

        if ($request->payment_option != null) {
           $combined_order_id = (new OrderController)->store($request);

            // $request->session()->put('payment_type', 'cart_payment');

            $data['combined_order_id'] = $combined_order_id;
            // $request->session()->put('payment_data', $data);

            if ($combined_order_id != null) {

                // If block for Online payment, wallet and cash on delivery. Else block for Offline payment
                $decorator = __NAMESPACE__ . '\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $request->payment_option))) . "Controller";
                if (class_exists($decorator)) {
                    return (new $decorator)->pay($request);
                }
                else {
                    $combined_order = CombinedOrder::findOrFail($combined_order_id);
                    $manual_payment_data = array(
                        'name'   => $request->payment_option,
                        'amount' => $combined_order->grand_total,
                        'trx_id' => $request->trx_id,
                        'photo'  => $request->photo
                    );
                    foreach ($combined_order->orders as $order) {
                        $order->manual_payment = 1;
                        $order->manual_payment_data = json_encode($manual_payment_data);
                        $order->save();
                    }
                    $returnType = "success";
                    $message = 'Your order has been placed successfully. Please submit payment information from purchase history';
                    // return redirect()->route('order_confirmed');
                }
            }
        } else {
            $returnType = "worning";
            $message = 'Select Payment Option.';
        }

        return response()->json(["returnType" => $returnType, "message" => $message], 200);
    }

    //redirects to this method after a successfull checkout
    public function checkout_done($combined_order_id, $payment)
    {
        $combined_order = CombinedOrder::findOrFail($combined_order_id);

        foreach ($combined_order->orders as $key => $order) {
            $order = Order::findOrFail($order->id);
            $order->payment_status = 'paid';
            $order->payment_details = $payment;
            $order->save();

            calculateCommissionAffilationClubPoint($order);
        }
        Session::put('combined_order_id', $combined_order_id);
        return redirect()->route('order_confirmed');
    }

    public function get_shipping_info(Request $request)
    {
        // $carts = Cart::where('user_id', Auth::user()->id)->get();

        // if ($carts && count($carts) > 0) {
        //     $categories = Category::all();


        //     // return view('frontend.shipping_info', compact('categories', 'carts'));
        // }
        // flash(translate('Your cart is empty'))->success();
        // return back();


        return $addresses = new AddressCollection(Auth::user()->addresses);
    }

    public function getAddressInfo(){

        $country = \App\Models\Country::where('status', 1)->get();

        return $country;
    }

    public function store_shipping_info(Request $request){
        
        $carts = Cart::where('user_id', Auth::user()->id)->get();

        foreach ($carts as $key => $cartItem) {
            $cartItem->address_id = $request->address_id;
            $cartItem->update();
        }
        return $request;
    }

    public function get_delivery_info(Request $request)
    {

        $worning = null;

         $carts = Cart::where('user_id', Auth::user()->id)->get();
        if($carts->isEmpty()) {
            $worning = "Your cart is empty";
        }

        $admin_products = array();
        $seller_products = array();



        $shipping_type = get_setting('shipping_type');

        $pickup_point_list = array();
        if (get_setting('pickup_point') == 1) {
            $pickup_point_list = \App\Models\PickupPoint::where('pick_up_status',1)->get();
        }

        $carrier_list = array();
        $zone = null;

        if(get_setting('shipping_type') == 'carrier_wise_shipping' && $worning==null){
            $zone = \App\Models\Country::where('id',$carts[0]['address']['country_id'])->first()->zone_id;

            $carrier_query = Carrier::query();
            $carrier_query->whereIn('id',function ($query) use ($zone) {
                $query->select('carrier_id')->from('carrier_range_prices')
                ->where('zone_id', $zone);
            })->orWhere('free_shipping', 1);
        }


        $site_name = get_setting('site_name');
        $adminId = \App\Models\User::where('user_type', 'admin')->first()->id;



        $carrier_list = $carrier_query->get();

        foreach ($carts as $key => $cartItem) {


            $product = \App\Models\Product::find($cartItem['product_id']);


            if($product->added_by == 'admin'){
                $product = new ProductCollection([$product]);

                $carrierList = $carrier_list;
                foreach($carrierList as $carrier_key => $carrier){
                    $carrier->logo = uploaded_asset($carrier->logo);
                    $carrier->carrier_base_price = single_price(carrier_base_price($carts, $carrier->id, $adminId));
                    $carrierList[$carrier_key] = $carrier;
                }
                array_push($admin_products, ['products' => $product, 'carrier_list' => $carrierList]);
            }
            else{
                $product_ids = array();
                if(isset($seller_products[$product->user_id])){
                    $product_ids = $seller_products[$product->user_id]['products'];
                }

                $shopName = \App\Models\Shop::where('user_id', $product->user_id)->first()->name;
                $product->shop_name = $shopName;

                $salerProduct = new ProductCollection([$product]);
                array_push($product_ids, $salerProduct);

                $productUserId = 1;
                // $productUserId = $product->user_id;

                $seller_products[$product->user_id]['products'] = $product_ids;

                $carrierList = $carrier_list;
                foreach($carrierList as $carrier_key => $carrier){
                    $carrier->logo_img = uploaded_asset($carrier->logo);
                    $carrier->carrier_base_price = single_price(carrier_base_price($carts, $carrier->id, $productUserId));
                    $carrierList[$carrier_key] = $carrier;
                }
                $seller_products[$product->user_id]["carrier_list"] = $carrierList;
            }
        }
        return response()->json([
            "worning" => $worning,
            'carts' => $carts,
            'admin_products' => $admin_products,
            'seller_products' => $seller_products,
            'shipping_type' => $shipping_type,
            'site_name' => $site_name,
            'adminId' =>$adminId,
            'pickup_point_list' => $pickup_point_list,
        ], 200);
        // return view('frontend.delivery_info', compact('carts','carrier_list'));
    }


    public function store_delivery_info(Request $request)
    {
        // return $request;
        $carts = Cart::where('user_id', Auth::user()->id)->get();

        if($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        // $shipping_info = Address::where('id', $carts[0]['address_id'])->first();
        $total = 0;
        $tax = 0;
        $shipping = 0;
        $subtotal = 0;

        if ($carts && count($carts) > 0) {
            foreach ($carts as $key => $cartItem) {
                $product = Product::find($cartItem['product_id']);
                $tax += cart_product_tax($cartItem, $product,false) * $cartItem['quantity'];
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];

                if(get_setting('shipping_type') != 'carrier_wise_shipping' || $request['shipping_type_' . $product->user_id] == 'pickup_point'){
                    if ($request['shipping_type_' . $product->user_id] == 'pickup_point') {
                        $cartItem['shipping_type'] = 'pickup_point';
                        $cartItem['pickup_point'] = $request['pickup_point_id_' . $product->user_id];
                    } else {
                        $cartItem['shipping_type'] = 'home_delivery';
                    }
                    $cartItem['shipping_cost'] = 0;
                    if ($cartItem['shipping_type'] == 'home_delivery') {
                        $cartItem['shipping_cost'] = getShippingCost($carts, $key);
                    }
                }
                else{
                    $cartItem['shipping_type'] = 'carrier';
                    $cartItem['carrier_id'] = $request['carrier_id_' . $product->user_id];
                    $cartItem['shipping_cost'] = getShippingCost($carts, $key, $cartItem['carrier_id']);
                }

                $shipping += $cartItem['shipping_cost'];
                $cartItem->save();
            }
            $total = $subtotal + $tax + $shipping;

            // return view('frontend.payment_select', compact('carts', 'shipping_info', 'total'));

        }
        // else {
        //     flash(translate('Your Cart was empty'))->warning();
        //     return redirect()->route('home');
        // }
    }

    public function payment_info(){

        $carts = Cart::where('user_id', Auth::user()->id)->get();
        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        $total = 0;
        $total_point = 0;
        $tax = 0;
        $shipping = 0;
        $subtotal = 0;
        $digital = 0;
        $cod_on = 1;
        $coupon_code = null;
        $coupon_discount = 0;
        $subtotal_for_min_order_amount = 0;
        $ck = 0;

        if ($carts && count($carts) > 0) {
            foreach ($carts as $key => $cartItem) {
                $product = Product::find($cartItem['product_id']);
                $tax += cart_product_tax($cartItem, $product,false) * $cartItem['quantity'];
                $temp_cart_product_price = cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                $subtotal += $temp_cart_product_price;

                $shipping += $cartItem['shipping_cost'];

                $total_point += $product->earn_point * $cartItem['quantity'];

                if ($product['digital'] == 1) {$digital = 1; }
                if ($product['cash_on_delivery'] == 0) {$cod_on = 0;}

                if (Auth::check() && get_setting('coupon_system') == 1){
                    if ($cartItem->coupon_applied == 1 && $ck==0){
                        $coupon_code = $cartItem->coupon_code;
                        $ck = 1;
                    }
                }

                $product_name_with_choice = $product->getTranslation('name');
                if ($cartItem['variant'] != null) {
                    $product_name_with_choice = $product->getTranslation('name') . ' - ' . $cartItem['variant'];
                }
                $cart_product_single_price = single_price($temp_cart_product_price);

                $carts[$key]->product_name_with_choice = $product_name_with_choice;
                $carts[$key]->cart_product_single_price = $cart_product_single_price;

            }

            if (Auth::check() && get_setting('coupon_system') == 1){
                $coupon_discount = carts_coupon_discount($coupon_code);
            }

            $total = $subtotal + $tax + $shipping;
        }

        $manualPaymentMethods = \App\Models\ManualPaymentMethod::all();
        foreach ($manualPaymentMethods as $key => $method){
            $method->photo = uploaded_asset($method->photo);
            $manualPaymentMethods[$key] = $method;

            if ($method->bank_info != null){
                $method->bank_info = json_decode($method->bank_info);
            }
        }

        $paymentMethod = [
            'paypal_payment' => get_setting('paypal_payment'),
            'paypal_img' => static_asset('assets/img/cards/paypal.png'),
            'stripe_payment' => get_setting('stripe_payment'),
            'stripe_img' => static_asset('assets/img/cards/stripe.png'),
            'mercadopago_payment' => get_setting('mercadopago_payment'),
            'mercadopago_img' => static_asset('assets/img/cards/mercadopago.png'),
            'sslcommerz_payment' => get_setting('sslcommerz_payment'),
            'sslcommerz_img' => static_asset('assets/img/cards/sslcommerz.png'),
            'instamojo_payment' => get_setting('instamojo_payment'),
            'instamojo_img'=> static_asset('assets/img/cards/instamojo.png'),
            'razorpay' => get_setting('razorpay'),
            'razorpay_img' => static_asset('assets/img/cards/rozarpay.png'),
            'paystack' => get_setting('paystack'),
            'paystack_img' => static_asset('assets/img/cards/paystack.png'),
            'voguepay' => get_setting('voguepay'),
            'voguepay_img' => static_asset('assets/img/cards/vogue.png'),
            'payhere' => get_setting('payhere'),
            'payhere_img' => static_asset('assets/img/cards/payhere.png'),
            'ngenius' => get_setting('ngenius'),
            'ngenius_img' => static_asset('assets/img/cards/ngenius.png'),
            'iyzico' => get_setting('iyzico'),
            'iyzico_img' => static_asset('assets/img/cards/iyzico.png'),
            'nagad' => get_setting('nagad'),
            'nagad_img' => static_asset('assets/img/cards/nagad.png'),
            'bkash' => get_setting('bkash'),
            'bkash_img' => static_asset('assets/img/cards/bkash.png'),
            'aamarpay' => get_setting('aamarpay'),
            'aamarpay_img' => static_asset('assets/img/cards/aamarpay.png'),
            'authorizenet' => get_setting('authorizenet'),
            'authorizenet_img' => static_asset('assets/img/cards/authorizenet.png'),
            'payku' => get_setting('payku'),
            'payku_img' => static_asset('assets/img/cards/payku.png'),
            'african_pg' => addon_is_activated('african_pg'),
            'mpesa' => get_setting('mpesa'),
            'mpesa_img' => static_asset('assets/img/cards/mpesa.png'),
            'flutterwave' => get_setting('flutterwave'),
            'flutterwave_img' => static_asset('assets/img/cards/flutterwave.png'),
            'payfast' => get_setting('payfast'),
            'payfast_img' => static_asset('assets/img/cards/payfast.png'),
            'paytm' => addon_is_activated('paytm'),
            'paytm_payment' => get_setting('paytm_payment'),
            'paytm_payment_img'=> static_asset('assets/img/cards/paytm.jpg'),
            'toyyibpay_payment' => get_setting('toyyibpay_payment'),
            'toyyibpay_payment_img' => static_asset('assets/img/cards/toyyibpay.png'),
            'cash_payment' => get_setting('cash_payment'),
            'offline_payment' => addon_is_activated('offline_payment'),
            'wallet_system' => get_setting('wallet_system'),
            'user_balance' => Auth::user()->balance,
            'user_balance_single_price' => single_price(Auth::user()->balance),
            'total' => $total,
            'total_single_price' => single_price($total),
            'coupon_code' => $coupon_code,
            'coupon_discount' => $coupon_discount,
            'coupon_discount_single_price' => single_price($coupon_discount),
            'coupon_system' => get_setting('coupon_system'),
            'digital' => $digital,
            'cod_on' => $cod_on,
            'cod_on_img' => static_asset('assets/img/cards/cod.png'),
            'shipping_info' => $shipping_info,
            'carts' => $carts,
            'manualPaymentMethods' => $manualPaymentMethods,
            'minimum_order_amount_check' => get_setting('minimum_order_amount_check'),
            'subtotal' => $subtotal,
            'subtotal_single_price' => single_price($subtotal),
            'minimum_order_amount' => get_setting('minimum_order_amount'),
            'minimum_order_amount_single_price' => single_price(get_setting('minimum_order_amount')),
            'club_point' => addon_is_activated('club_point'),
            'total_point' => $total_point,
            'tax' => $tax,
            'tax_single_price' => single_price($tax),
            'shipping_single_price' => single_price($shipping),
            'shipping' => $shipping,

        ];

        // return view('frontend.payment_select', compact('carts', 'shipping_info', 'total'));

        return response()->json($paymentMethod);
    }

    public function apply_coupon_code(Request $request)
    {
        $coupon = Coupon::where('code', $request->code)->first();
        $response_message = array();

        if ($coupon != null) {
            if (strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date) {
                if (CouponUsage::where('user_id', Auth::user()->id)->where('coupon_id', $coupon->id)->first() == null) {
                    $coupon_details = json_decode($coupon->details);

                    $carts = Cart::where('user_id', Auth::user()->id)
                                    ->where('owner_id', $coupon->user_id)
                                    ->get();

                    $coupon_discount = 0;

                    if ($coupon->type == 'cart_base') {
                        $subtotal = 0;
                        $tax = 0;
                        $shipping = 0;
                        foreach ($carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                            $tax += cart_product_tax($cartItem, $product,false) * $cartItem['quantity'];
                            $shipping += $cartItem['shipping_cost'];
                        }
                        $sum = $subtotal + $tax + $shipping;
                        if ($sum >= $coupon_details->min_buy) {
                            if ($coupon->discount_type == 'percent') {
                                $coupon_discount = ($sum * $coupon->discount) / 100;
                                if ($coupon_discount > $coupon_details->max_discount) {
                                    $coupon_discount = $coupon_details->max_discount;
                                }
                            } elseif ($coupon->discount_type == 'amount') {
                                $coupon_discount = $coupon->discount;
                            }

                        }
                    } elseif ($coupon->type == 'product_base') {
                        foreach ($carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            foreach ($coupon_details as $key => $coupon_detail) {
                                if ($coupon_detail->product_id == $cartItem['product_id']) {
                                    if ($coupon->discount_type == 'percent') {
                                        $coupon_discount += (cart_product_price($cartItem, $product, false, false) * $coupon->discount / 100) * $cartItem['quantity'];
                                    } elseif ($coupon->discount_type == 'amount') {
                                        $coupon_discount += $coupon->discount * $cartItem['quantity'];
                                    }
                                }
                            }
                        }
                    }

                    if($coupon_discount > 0){
                        Cart::where('user_id', Auth::user()->id)
                            ->where('owner_id', $coupon->user_id)
                            ->update(
                                [
                                    'discount' => $coupon_discount / count($carts),
                                    'coupon_code' => $request->code,
                                    'coupon_applied' => 1
                                ]
                            );
                        $response_message['response'] = 'success';
                        $response_message['message'] = translate('Coupon has been applied');
                    }
                    else{
                        $response_message['response'] = 'warning';
                        $response_message['message'] = translate('This coupon is not applicable to your cart products!');
                    }

                } else {
                    $response_message['response'] = 'warning';
                    $response_message['message'] = translate('You already used this coupon!');
                }
            } else {
                $response_message['response'] = 'warning';
                $response_message['message'] = translate('Coupon expired!');
            }
        } else {
            $response_message['response'] = 'danger';
            $response_message['message'] = translate('Invalid coupon!');
        }

        $carts = Cart::where('user_id', Auth::user()->id)
                ->get();
        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        $returnHTML = view('frontend.partials.cart_summary', compact('coupon', 'carts', 'shipping_info'))->render();
        return response()->json(array('response_message' => $response_message, 'html'=>$returnHTML));
    }

    public function remove_coupon_code(Request $request)
    {
        Cart::where('user_id', Auth::user()->id)
                ->update(
                        [
                            'discount' => 0.00,
                            'coupon_code' => '',
                            'coupon_applied' => 0
                        ]
        );

        $coupon = Coupon::where('code', $request->code)->first();
        $carts = Cart::where('user_id', Auth::user()->id)
                ->get();

        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        return view('frontend.partials.cart_summary', compact('coupon', 'carts', 'shipping_info'));
    }

    public function apply_club_point(Request $request) {
        if (addon_is_activated('club_point')){

            $point = $request->point;

            if(Auth::user()->point_balance >= $point) {
                $request->session()->put('club_point', $point);
                flash(translate('Point has been redeemed'))->success();
            }
            else {
                flash(translate('Invalid point!'))->warning();
            }
        }
        return back();
    }

    public function remove_club_point(Request $request) {
        $request->session()->forget('club_point');
        return back();
    }

    public function order_confirmed()
    {
        $combined_order = CombinedOrder::findOrFail(Session::get('combined_order_id'));

        Cart::where('user_id', $combined_order->user_id)
                ->delete();

        //Session::forget('club_point');
        //Session::forget('combined_order_id');

        foreach($combined_order->orders as $order){
            NotificationUtility::sendOrderPlacedNotification($order);
        }

        return view('frontend.order_confirmed', compact('combined_order'));
    }
}
