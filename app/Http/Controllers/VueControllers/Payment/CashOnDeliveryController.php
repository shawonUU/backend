<?php

namespace App\Http\Controllers\VueControllers\Payment;

use App\Http\Controllers\VueControllers\Controller;

class CashOnDeliveryController extends Controller
{
    public function pay(){
        flash(translate("Your order has been placed successfully"))->success();
        return redirect()->route('order_confirmed');
    }
}
