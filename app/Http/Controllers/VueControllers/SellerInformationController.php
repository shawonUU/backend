<?php

namespace App\Http\Controllers\VueControllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Laravel\Sanctum\PersonalAccessToken;

class SellerInformationController extends Controller
{
    public function dashboard(Request $request){

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

        $product = \App\Models\Product::where('user_id', $user->id)->count();
        $rating =$user->shop->rating;

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
           'product'=>$product,

        ]);

    }
}
