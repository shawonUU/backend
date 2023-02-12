<?php

namespace App\Http\Controllers\VueControllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Cart;
use Auth;
use Session;
use Cookie;
use App\Http\Resources\VUE\ProductDetailCollection;
use App\Http\Resources\VUE\ProductCollection;

use Laravel\Sanctum\PersonalAccessToken;

class CartController extends Controller
{
    public function index(Request $request)
    {

        $user = null;
        $tempUser = $request->temp_user;
        $token = PersonalAccessToken::findToken($request->token);
        if($token){
            $user = $token->tokenable;
            if(!$user) $user = null;
        }

        $carts = [];
        if($user != null) {
            $user_id = $user->id;
            if($tempUser) {
                Cart::where('temp_user_id', $tempUser)
                        ->update(
                        [
                            'user_id' => $user_id,
                            'temp_user_id' => null,
                        ]
                );
            }
            $carts = Cart::where('user_id', $user_id)->get();
        } else {
            $temp_user_id = $tempUser;
            // $carts = Cart::where('temp_user_id', $temp_user_id)->get();
            $carts = ($temp_user_id != null) ? Cart::where('temp_user_id', $temp_user_id)->get() : [] ;
        }

        $total = 0;
        foreach ($carts as $key => $cartItem){
            // $carts[$key]->test = "test";
            $product = \App\Models\Product::find($cartItem['product_id']);
            $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
            $total = $total + cart_product_price($cartItem, $product, false) * $cartItem['quantity'];
            $product_name_with_choice = $product->getTranslation('name');
            if ($cartItem['variation'] != null) {
                $product_name_with_choice = $product->getTranslation('name') . ' - ' . $cartItem['variation'];
            }
            $carts[$key]->productData = new ProductCollection([$product]);
            $carts[$key]->product_name_with_choice = $product_name_with_choice;
            $carts[$key]->cart_product_price = cart_product_price($cartItem, $product, true, false);
            $carts[$key]->cart_product_tax = cart_product_tax($cartItem, $product);
            $carts[$key]->single_price = single_price(cart_product_price($cartItem, $product, false) * $cartItem['quantity']);
            $carts[$key]->product_stock = $product_stock->qty;
        }
       $total = single_price($total);
        return response()->json(["carts" => $carts,"total" => $total]);
    }

    public function showCartModal(Request $request)
    {

        $product = Product::find($request->id);


        // $product->home_price = home_price($product);
        // $product->home_discounted_price = home_discounted_price($product);


        $product->addon_is_activated = addon_is_activated('club_point');

        $qty = 0;
        foreach ($product->stocks as $key => $stock) {
            $qty += $stock->qty;
        }
        $product->stocksQty = $qty;
        $product->stock_visibility_state = $product->stock_visibility_state;

        // return $product;
        $productCollection = new ProductDetailCollection([$product]);
        return response()->json(["productCollection" => $productCollection], 200);

        // return $photos = explode(',',$product->photos);


        // return view('frontend.partials.addToCart', compact('product'));
    }

    public function showCartModalAuction(Request $request)
    {
        $product = Product::find($request->id);
        return view('auction.frontend.addToCartAuction', compact('product'));
    }

    public function addToCart(Request $request)
    {


        $user = null;
        $tempUser = $request->temp_user;
        $token = PersonalAccessToken::findToken($request->token);
        if($token){
            $user = $token->tokenable;
            if(!$user) $user = null;
        }



        $product = Product::find($request->id);
        $carts = array();
        $data = array();

        if($user != null) {
           $user_id = $user->id;
            $data['user_id'] = $user_id;
            $carts = Cart::where('user_id', $user_id)->get();
        } else {

            if($tempUser != "null" && $tempUser !=null) {
                $temp_user_id = $tempUser;
            } else {
                $temp_user_id = bin2hex(random_bytes(10));
               $tempUser = $temp_user_id;
            }
            $data['temp_user_id'] = $temp_user_id;
            $carts = Cart::where('temp_user_id', $temp_user_id)->get();
        }

        $data['product_id'] = $product->id;
        $data['owner_id'] = $product->user_id;

        $str = '';
        $tax = 0;
        if($product->auction_product == 0){
            if($product->digital != 1 && $request->quantity < $product->min_qty) {
                return array(
                    'templete' => 'minQtyNotSatisfied',
                    'status' => 0,
                    'cart_count' => count($carts),
                    'min_qty' => $product->min_qty,
                    'temp_user' => $tempUser,
                    // 'modal_view' => view('frontend.partials.minQtyNotSatisfied', [ 'min_qty' => $product->min_qty ])->render(),
                    // 'nav_cart_view' => view('frontend.partials.cart')->render(),
                );
            }

            //check the color enabled or disabled for the product
            if($request->has('color')) {
                $str = $request['color'];
            }

            if ($product->digital != 1) {
                //Gets all the choice values of customer choice option and generate a string like Black-S-Cotton
                foreach (json_decode(Product::find($request->id)->choice_options) as $key => $choice) {
                    if($str != null){
                        $str .= '-'.str_replace(' ', '', $request['attribute_id_'.$choice->attribute_id]);
                    }
                    else{
                        $str .= str_replace(' ', '', $request['attribute_id_'.$choice->attribute_id]);
                    }
                }
            }

            $data['variation'] = $str;

            $product_stock = $product->stocks->where('variant', $str)->first();
            $price = $product_stock->price;

            if($product->wholesale_product){
                $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $request->quantity)->where('max_qty', '>=', $request->quantity)->first();
                if($wholesalePrice){
                    $price = $wholesalePrice->price;
                }
            }

            $quantity = $product_stock->qty;

            if($quantity < $request['quantity']){
                return array(
                    'templete' => 'outOfStockCart',
                    'status' => 0,
                    'cart_count' => count($carts),
                    'temp_user' => $tempUser,
                    // 'modal_view' => view('frontend.partials.outOfStockCart')->render(),
                    // 'nav_cart_view' => view('frontend.partials.cart')->render(),
                );
            }

            //discount calculation
            $discount_applicable = false;

            if ($product->discount_start_date == null) {
                $discount_applicable = true;
            }
            elseif (strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date) {
                $discount_applicable = true;
            }

            if ($discount_applicable) {
                if($product->discount_type == 'percent'){
                    $price -= ($price*$product->discount)/100;
                }
                elseif($product->discount_type == 'amount'){
                    $price -= $product->discount;
                }
            }

            //calculation of taxes
            foreach ($product->taxes as $product_tax) {
                if($product_tax->tax_type == 'percent'){
                    $tax += ($price * $product_tax->tax) / 100;
                }
                elseif($product_tax->tax_type == 'amount'){
                    $tax += $product_tax->tax;
                }
            }

            $data['quantity'] = $request['quantity'];
            $data['price'] = $price;
            $data['tax'] = $tax;
            //$data['shipping'] = 0;
            $data['shipping_cost'] = 0;
            $data['product_referral_code'] = null;
            $data['cash_on_delivery'] = $product->cash_on_delivery;
            $data['digital'] = $product->digital;

            if ($request['quantity'] == null){
                $data['quantity'] = 1;
            }

            if(Cookie::has('referred_product_id') && Cookie::get('referred_product_id') == $product->id) {
                $data['product_referral_code'] = Cookie::get('product_referral_code');
            }

            if($carts && count($carts) > 0){
                $foundInCart = false;

                foreach ($carts as $key => $cartItem){
                    $cart_product = Product::where('id', $cartItem['product_id'])->first();
                    if($cart_product->auction_product == 1){
                        return array(
                            'templete' => 'auctionProductAlredayAddedCart',
                            'status' => 0,
                            'cart_count' => count($carts),
                            'temp_user' => $tempUser,
                            // 'modal_view' => view('frontend.partials.auctionProductAlredayAddedCart')->render(),
                            // 'nav_cart_view' => view('frontend.partials.cart')->render(),
                        );
                    }

                    if($cartItem['product_id'] == $request->id) {
                        $product_stock = $cart_product->stocks->where('variant', $str)->first();
                        $quantity = $product_stock->qty;
                        if($quantity < $cartItem['quantity'] + $request['quantity']){
                            return array(
                                'templete' => 'outOfStockCart',
                                'status' => 0,
                                'cart_count' => count($carts),
                                'temp_user' => $tempUser,
                                // 'modal_view' => view('frontend.partials.outOfStockCart')->render(),
                                // 'nav_cart_view' => view('frontend.partials.cart')->render(),
                            );
                        }
                        if(($str != null && $cartItem['variation'] == $str) || $str == null){
                            $foundInCart = true;

                            $cartItem['quantity'] += $request['quantity'];

                            if($cart_product->wholesale_product){
                                $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $request->quantity)->where('max_qty', '>=', $request->quantity)->first();
                                if($wholesalePrice){
                                    $price = $wholesalePrice->price;
                                }
                            }

                            $cartItem['price'] = $price;

                            $cartItem->save();
                        }
                    }
                }
                if (!$foundInCart) {
                    Cart::create($data);
                }
            }
            else{
                Cart::create($data);
            }

            if($user != null) {
                $user_id = $user->id;
                $carts = Cart::where('user_id', $user_id)->get();
            } else {
                $temp_user_id = $tempUser;
                $carts = Cart::where('temp_user_id', $temp_user_id)->get();
            }


            $addon_is_activated = addon_is_activated('club_point');
            $reletedProducts = filter_products(\App\Models\Product::where('category_id', $product->category_id)->where('id', '!=', $product->id))->limit(2)->get();
            $reletedProducts = new ProductCollection($reletedProducts);
            $product = new ProductCollection([$product]);

            return array(
                'templete' => 'addedToCart',
                'status' => 1,
                'cart_count' => count($carts),
                'product' => $product,
                'data' => $data,
                'addon_is_activated' => $addon_is_activated,
                'reletedProducts' => $reletedProducts,
                'product' => $product,
                'single_price' => single_price(($data['price'] + $data['tax']) * $data['quantity']),
                'temp_user' => $tempUser,

                // 'modal_view' => view('frontend.partials.addedToCart', compact('product', 'data'))->render(),
                // 'nav_cart_view' => view('frontend.partials.cart')->render(),
            );
        }
        else{
            $price = $product->bids->max('amount');

            foreach ($product->taxes as $product_tax) {
                if($product_tax->tax_type == 'percent'){
                    $tax += ($price * $product_tax->tax) / 100;
                }
                elseif($product_tax->tax_type == 'amount'){
                    $tax += $product_tax->tax;
                }
            }

            $data['quantity'] = 1;
            $data['price'] = $price;
            $data['tax'] = $tax;
            $data['shipping_cost'] = 0;
            $data['product_referral_code'] = null;
            $data['cash_on_delivery'] = $product->cash_on_delivery;
            $data['digital'] = $product->digital;

            if(count($carts) == 0){
                Cart::create($data);
            }
            if(auth()->user() != null) {
                $user_id = Auth::user()->id;
                $carts = Cart::where('user_id', $user_id)->get();
            } else {
                $temp_user_id = session()->get('temp_user_id');
                $carts = Cart::where('temp_user_id', $temp_user_id)->get();
            }
            return array(
                'templete' => 'addedToCart',
                'status' => 1,
                'cart_count' => count($carts),
                'product' => $product,
                'data' => $data,
                'temp_user' => $tempUser,
                // 'modal_view' => view('frontend.partials.addedToCart', compact('product', 'data'))->render(),
                // 'nav_cart_view' => view('frontend.partials.cart')->render(),
            );
        }
    }

    //removes from Cart
    public function removeFromCart(Request $request)
    {
        Cart::destroy($request->id);
        if(auth()->user() != null) {
            $user_id = Auth::user()->id;
            $carts = Cart::where('user_id', $user_id)->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $carts = Cart::where('temp_user_id', $temp_user_id)->get();
        }

        return array(
            'cart_count' => count($carts),
            'cart_view' => view('frontend.partials.cart_details', compact('carts'))->render(),
            'nav_cart_view' => view('frontend.partials.cart')->render(),
        );
    }

    //updated the quantity for a cart item
    public function updateQuantity(Request $request)
    {

        $user = null;
        $tempUser = $request->temp_user;
        $token = PersonalAccessToken::findToken($request->token);
        if($token){
            $user = $token->tokenable;
            if(!$user) $user = null;
        }

        $cartItem = Cart::findOrFail($request->id);
        $price = $cartItem['price'];
        if($cartItem['id'] == $request->id){
            $product = Product::find($cartItem['product_id']);
            $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
            $quantity = $product_stock->qty;
            $price = $product_stock->price;

			//discount calculation
            $discount_applicable = false;

            if ($product->discount_start_date == null) {
                $discount_applicable = true;
            }
            elseif (strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date) {
                $discount_applicable = true;
            }

            if ($discount_applicable) {
                if($product->discount_type == 'percent'){
                    $price -= ($price*$product->discount)/100;
                }
                elseif($product->discount_type == 'amount'){
                    $price -= $product->discount;
                }
            }

            if($quantity >= $request->quantity) {
                if($request->quantity >= $product->min_qty){
                    $cartItem['quantity'] = $request->quantity;
                }
            }

            if($product->wholesale_product){
                $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $request->quantity)->where('max_qty', '>=', $request->quantity)->first();
                if($wholesalePrice){
                    $price = $wholesalePrice->price;
                }
            }

            $cartItem['price'] = $price;
            $cartItem->save();
        }

        return $price;

        // if($user != null) {
        //     $user_id = $user->id;
        //     $carts = Cart::where('user_id', $user_id)->get();
        // } else {
        //     $temp_user_id = $tempUser;
        //     $carts = Cart::where('temp_user_id', $temp_user_id)->get();
        // }



        // return 'okkk';
        // return array(
        //     'cart_count' => count($carts),
        //     'cart_view' => view('frontend.partials.cart_details', compact('carts'))->render(),
        //     'nav_cart_view' => view('frontend.partials.cart')->render(),
        // );
    }
}
