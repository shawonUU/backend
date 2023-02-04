<?php

namespace App\Http\Controllers\VueControllers\Api\VUE\Seller;

use App\Http\Controllers\VueControllers\Controller;
use App\Http\Resources\VUE\Seller\SellerPaymentResource;
use Illuminate\Http\Request;
use App\Models\Payment;

class PaymentController extends Controller
{
    //
    public function getHistory(){
        $sellerId = auth()->user()->id;
        $payments = Payment::orderBy('created_at', 'desc')->where('seller_id',$sellerId)->latest()->paginate(10);;
        return  SellerPaymentResource::collection($payments);
    }
}
