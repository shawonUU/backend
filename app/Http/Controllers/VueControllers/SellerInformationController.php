<?php

namespace App\Http\Controllers\VueControllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Laravel\Sanctum\PersonalAccessToken;

class SellerInformationController extends Controller
{
    public function dashboard(Request $request){
        $token =$request->token;;

        $token = PersonalAccessToken::findToken($token);
        if(!$token) return response()->json(["Unauthorized"], 401);
        $user = $token->tokenable;
        if(!$user) return response()->json(["Unauthorized"], 401);

        $shop = $user->shop;
        $shop_logo =  uploaded_asset($user->shop->logo);
        $header_logo = uploaded_asset(get_setting('header_logo'));

        return response()->json([
            $shop,
            $shop_logo,
            $header_logo
        ]);

    }
}
