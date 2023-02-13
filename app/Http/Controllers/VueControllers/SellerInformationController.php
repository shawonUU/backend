<?php

namespace App\Http\Controllers\VueControllers;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Resources\VUE\ProductMiniCollection;

class SellerInformationController extends Controller
{
    public function sideNav(Request $request){

        $token =$request->token;

        $token = PersonalAccessToken::findToken($token);
        if(!$token) return response()->json(["Unauthorized"], 401);
        $user = $token->tokenable;
        if(!$user) return response()->json(["Unauthorized"], 401);

        $shop = $user->shop;
        $shop_logo =  uploaded_asset($user->shop->logo);
        $header_logo = uploaded_asset(get_setting('header_logo'));
        $checkConversationSystem = get_setting('conversation_system');
        $conversation = \App\Models\Conversation::where('sender_id', $user->id)
                            ->where('sender_viewed', 0)
                            ->get();
        $seller_subscription=  addon_is_activated('seller_subscription');
        $coupon_system = "";
        if(get_setting('coupon_system') == 1){
            $coupon_system=1;
        }else{
            $coupon_system=0;
        }
        $wholesale = "";
        if(addon_is_activated('wholesale') && get_setting('seller_wholesale_product') == 1){
            $wholesale=1;
        }else{
            $wholesale=0;
        }
        $auction="";
        if(addon_is_activated('auction') && get_setting('seller_auction_product') == 1){
            $auction = 1;
        }else{
            $auction = 2;
        }
        $pos_system = addon_is_activated('pos_system');
        $pos_activation_for_seller="";
        if(get_setting('pos_activation_for_seller') != null && get_setting('pos_activation_for_seller') != 0){
            $pos_activation_for_seller=1;
        }else{
            $pos_activation_for_seller = 0;
        }
        $refund_request = addon_is_activated('refund_request');

        $product_query_activation="";
        if(get_setting('product_query_activation') == 1){
            $product_query_activation=1;
        }else{
            $product_query_activation=0;
        }

        $support_ticket = DB::table('tickets')
        ->where('client_viewed', 0)
        ->where('user_id', $user->id)
        ->count();



        return response()->json([
           'shop'=> $shop,
           'shop_logo' => $shop_logo,
           'header_logo'=> $header_logo,
           'checkConversationSystem' => $checkConversationSystem,
           'conversation'=> $conversation,
           'seller_subscription'=>$seller_subscription,
           'coupon_system'=>$coupon_system,
           'wholesale'=> $wholesale,
           'auction'=>$auction,
           'pos_system'=>$pos_system,
           'pos_activation_for_seller'=>$pos_activation_for_seller,
           'refund_request'=>$refund_request,
           'product_query_activation'=>$product_query_activation,
           'support_ticket'=>$support_ticket,

        ]);

    }

    public function dashboard(Request $request){
        $token =$request->token;

        $token = PersonalAccessToken::findToken($token);
        if(!$token) return response()->json(["Unauthorized"], 401);
        $user = $token->tokenable;
        if(!$user) return response()->json(["Unauthorized"], 401);


        $product = \App\Models\Product::where('user_id', $user->id)->count();
        $rating =$user->shop->rating;
        $total_order = \App\Models\Order::where('seller_id', $user->id)->where('delivery_status', 'delivered')->count();

        $orderDetails = \App\Models\OrderDetail::where('seller_id', $user->id)->get();
        $total = 0;
        foreach ($orderDetails as $key => $orderDetail) {
            if ($orderDetail->order != null && $orderDetail->order->payment_status == 'paid') {
                $total += $orderDetail->price;
            }
        }

        $date = date('Y-m-d');
        $days_ago_30 = date('Y-m-d', strtotime('-30 days', strtotime($date)));
        $days_ago_60 = date('Y-m-d', strtotime('-60 days', strtotime($date)));

        $orderTotalCurrentMonth = \App\Models\Order::where('seller_id',$user->id)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $days_ago_30)
            ->sum('grand_total');

        $orderTotalLastMonth = \App\Models\Order::where('seller_id', $user->id)
        ->where('payment_status', 'paid')
        ->where('created_at', '>=', $days_ago_60)
        ->where('created_at', '<=', $days_ago_30)
        ->sum('grand_total');

        $new_order = \App\Models\OrderDetail::where('seller_id', $user->id)->where('delivery_status', 'pending')->count();

        $cancelled_order = \App\Models\OrderDetail::where('seller_id',  $user->id)->where('delivery_status', 'cancelled')->count();
        $onDelivery = \App\Models\OrderDetail::where('seller_id', $user->id)->where('delivery_status', 'on_the_way')->count();
        $delivered = \App\Models\OrderDetail::where('seller_id', $user->id)->where('delivery_status', 'delivered')->count();
        $seller_subscription = addon_is_activated('seller_subscription');
        $sellerPackageDetails = $user->shop->seller_package;
        $seller_package_logo = uploaded_asset($user->shop->seller_package->logo);
        $shop = $user->shop;

        $data['products'] = filter_products(Product::where('user_id', $user->id)->orderBy('num_of_sale', 'desc'))->limit(12)->get();
        $data['last_7_days_sales'] = Order::where('created_at', '>=', Carbon::now()->subDays(7))
                                ->where('seller_id', '=', $user->id)
                                ->where('delivery_status', '=', 'delivered')
                                ->select(DB::raw("sum(grand_total) as total, DATE_FORMAT(created_at, '%d %b') as date"))
                                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')"))
                                ->get()->pluck('total', 'date');


        $data['products'] =  new ProductMiniCollection($data['products']);
        return response()->json([
            'product'=>$product,
            'rating'=>$rating,
            'total_order'=>$total_order,
            'total_sales'=>$total,
            'orderTotalCurrentMonth'=>$orderTotalCurrentMonth,
            'orderTotalLastMonth'=>$orderTotalLastMonth,
            'new_order'=>$new_order,
            'cancelled_order'=>$cancelled_order,
            'onDelivery'=>$onDelivery,
            'delivered'=>$delivered,
            'seller_subscription'=>$seller_subscription,
            'sellerPackageDetails'=>$sellerPackageDetails,
            'seller_package_logo'=>$seller_package_logo,
            'shop'=>$shop,
            'data'=>$data
         ]);

    }
}
