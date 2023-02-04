<?php

namespace App\Http\Controllers\VueControllers;

use Auth;
use Hash;
use Mail;
use Cache;
use Cookie;
use App\Models\job;
use App\Models\Page;
use App\Models\Shop;
use App\Models\User;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Category;

use App\Models\FlashDeal;

use App\Models\PickupPoint;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\ProductQuery;
use Illuminate\Http\Request;
use App\Models\AffiliateConfig;
use App\Models\CustomerPackage;
use App\Utility\CategoryUtility;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Resources\VUE\ShopCollection;
use App\Mail\SecondEmailVerifyMailManager;
use App\Http\Resources\VUE\ProductCollection;
use App\Http\Resources\VUE\CategoryCollection;

use App\Http\Resources\VUE\ShopDetailsCollection;
use App\Http\Resources\VUE\ProductDetailCollection;

class HomeController extends Controller
{
    /**
     * Show the application frontend home.
     *
     * @return \Illuminate\Http\Response
     */
    public function all_job(){

         $all_job=job::all();

         return view('frontend.all_job',compact('all_job'));


    }

    public function getNaveData(Request $request){
        $user = null;
        $tempUser = $request->temp_user;
        $token = PersonalAccessToken::findToken($request->token);
        if($token){
            $user = $token->tokenable;
            if(!$user) $user = null;
        }

        $cart = [];
        $totalCart = 0;
        $totalWishlist = 0;
        if($user){
            $totalWishlist = count($user->wishlists);
            $user_id = Auth::user()->id;
            $cart = \App\Models\Cart::where('user_id', $user_id)->get();
            $totalCart = count($cart);
        }else{
            $totalCart = 0;
            if($tempUser){
                $cart = \App\Models\Cart::where('temp_user_id', $tempUser)->get();
                $totalCart = count($cart);
            }
        }

        
        $total = 0;
        foreach ($cart as $key => $cartItem){
       
            $product = \App\Models\Product::find($cartItem['product_id']);
            if($product)$cart[$key]->product = new ProductCollection([$product]);
            else $product = null;
            $cart[$key]->cart_product_price = cart_product_price($cartItem, $product);
            $total = $total + cart_product_price($cartItem, $product, false) * $cartItem['quantity'];
        }
        $single_price = single_price($total);
        
        return response()->json(['totalCart' => $totalCart, 'totalWishlist' => $totalWishlist, 'cart' => $cart, 'single_price' => $single_price], 200);
    }

     public function load_custom_section(){

            return view('frontend.partials.customsection');
     }


     public function followed_shop($shop_id){

         if (Auth::check()) {

            if(Auth::user()->followed_shop!=null){


                  $list_desings_ids =explode(",",Auth::user()->followed_shop);
                        if(in_array($shop_id, $list_desings_ids))
                        {
                                  if (($key = array_search($shop_id, $list_desings_ids)) !== false)
                                  {
                                    unset($list_desings_ids[$key]);
                                    $followed_shop1=$list_desings_ids;

                                     $user = User::findorfail(Auth::user()->id);
                                    $user->followed_shop=implode(", ",$followed_shop1);
                                    $user->save();
                                     flash(translate('Unfollow done!'))->warning();
                                     }

                        }
                        else{
                            $follow=0;
                             $followed_shop[]= Auth::user()->followed_shop;
                                $array= Arr::add($followed_shop,'',$shop_id);
                                $followed_shop1= Arr::flatten($array);

                $user = User::findorfail(Auth::user()->id);
                $user->followed_shop=implode(", ",$followed_shop1);
                $user->save();
                           flash(translate('Successfully followed'))->success();
                        }

                         return redirect()->back();

                        }

                        else{
                            $user = User::findorfail(Auth::user()->id);
                $user->followed_shop=$shop_id;
                $user->save();
                flash(translate('Successfully followed'))->success();
                return redirect()->back();

                        }

            }
            else


            return redirect()->route('user.login');



        }




    public function index()
    {

        $featured_categories = Cache::rememberForever('featured_categories', function () {
            return new CategoryCollection(Category::where('featured', 1)->get());
        });

        $todays_deal_products = Cache::rememberForever('todays_deal_products', function () {
            return filter_products(Product::where('published', 1)->where('todays_deal', '1')->orderBy('updated_at', 'desc'))->get()->take(8);
        });

        $newest_products = Cache::remember('newest_products', 3600, function () {
            return filter_products(Product::latest())->limit(8)->get();
        });

       $categories =  Category::where('level', 0)->orderBy('order_level', 'desc')->get()->take(13);


      return response()->json(['categories' => $categories, 'featured_categories' => $featured_categories]);
        // return view('frontend.index', compact('featured_categories', 'todays_deal_products', 'newest_products'));

    }

    public function login()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('frontend.user_login');
    }

    public function registration(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        if ($request->has('referral_code') && addon_is_activated('affiliate_system')) {
            try {
                $affiliate_validation_time = AffiliateConfig::where('type', 'validation_time')->first();
                $cookie_minute = 30 * 24;
                if ($affiliate_validation_time) {
                    $cookie_minute = $affiliate_validation_time->value * 60;
                }

                Cookie::queue('referral_code', $request->referral_code, $cookie_minute);
                $referred_by_user = User::where('referral_code', $request->referral_code)->first();

                $affiliateController = new AffiliateController;
                $affiliateController->processAffiliateStats($referred_by_user->id, 1, 0, 0, 0);
            } catch (\Exception $e) {
            }
        }
        return view('frontend.user_registration');
    }

    public function cart_login(Request $request)
    {
        $user = null;
        if ($request->get('phone') != null) {
            $user = User::whereIn('user_type', ['customer', 'seller'])->where('phone', "+{$request['country_code']}{$request['phone']}")->first();
        } elseif ($request->get('email') != null) {
            $user = User::whereIn('user_type', ['customer', 'seller'])->where('email', $request->email)->first();
        }

        if ($user != null) {
            if (Hash::check($request->password, $user->password)) {
                if ($request->has('remember')) {
                    auth()->login($user, true);
                } else {
                    auth()->login($user, false);
                }
            } else {
                flash(translate('Invalid email or password!'))->warning();
            }
        } else {
            flash(translate('Invalid email or password!'))->warning();
        }
        return back();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the customer/seller dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard(Request $request)
    {
        $token = PersonalAccessToken::findToken($request->token);
        if(!$token) return response()->json(["Unauthorized"], 401);
        $user = $token->tokenable;
        if(!$user) return response()->json(["Unauthorized"], 401);

        if ($user->user_type == 'seller') {
            // return redirect()->route('seller.dashboard');



        } elseif ($user->user_type == 'customer') {


            $user_id = $user->id;
            $cart = \App\Models\Cart::where('user_id', $user_id)->get();
            $cartCount = count($cart);

            $orders = \App\Models\Order::where('user_id', $user->id)->get();
            $totalOrder = 0;
            foreach ($orders as $key => $order) {
                $totalOrder += count($order->orderDetails);
            }
            $wishlistProduct = count($user->wishlists);

            $address = $user->addresses->where('set_default', 1)->first();



            return response()->json([
                "cartCount" => $cartCount,
                "totalOrder" => $totalOrder,
                "wishlistCount" => $wishlistProduct,
                "address" => $address,
            ]);




            // return view('frontend.user.customer.dashboard');
        } elseif ($user->user_type == 'delivery_boy') {




            // return view('delivery_boys.frontend.dashboard');
        } else {
            abort(404);
        }
    }


    public function userFollowedShop(Request $request){

        $data = $request->header();
        $token = $data["token"][0];

        $token = PersonalAccessToken::findToken($token);
        if(!$token) return response()->json(["Unauthorized"], 401);
        $user = $token->tokenable;
        if(!$user) return response()->json(["Unauthorized"], 401);

        // $user = auth()->user();

        $shopIds = explode(',',Auth::user()->followed_shop);
        $shops= Shop::whereIn('id', $shopIds)->get();

        $shops = new ShopCollection($shops);

        return response()->json(["shops" => $shops], 200);

    }

    public function profile(Request $request)
    {
        if (Auth::user()->user_type == 'seller') {
            return redirect()->route('seller.profile.index');
        } elseif (Auth::user()->user_type == 'delivery_boy') {
            return view('delivery_boys.frontend.profile');
        } else {
            return view('frontend.user.profile');
        }
    }

    public function userProfileUpdate(Request $request)
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('Sorry! the action is not permitted in demo '))->error();
            return back();
        }

        $user = Auth::user();
        $user->name = $request->name;
        $user->address = $request->address;
        $user->country = $request->country;
        $user->city = $request->city;
        $user->postal_code = $request->postal_code;
        $user->phone = $request->phone;

        if ($request->new_password != null && ($request->new_password == $request->confirm_password)) {
            $user->password = Hash::make($request->new_password);
        }

        $user->avatar_original = $request->photo;
        $user->save();

        flash(translate('Your Profile has been updated successfully!'))->success();
        return back();
    }

    public function flash_deal_details($slug)
    {
        $flash_deal = FlashDeal::where('slug', $slug)->first();
        if ($flash_deal != null)
            return view('frontend.flash_deal_details', compact('flash_deal'));
        else {
            abort(404);
        }
    }

    public function load_featured_section()
    {
        return view('frontend.partials.featured_products_section');
    }

    public function load_best_selling_section()
    {
        return view('frontend.partials.best_selling_section');
    }

    public function load_auction_products_section()
    {
        if (!addon_is_activated('auction')) {
            return;
        }
        return view('auction.frontend.auction_products_section');
    }

    public function load_home_categories_section()
    {
        return view('frontend.partials.home_categories_section');
    }

    public function load_best_sellers_section()
    {
        return view('frontend.partials.best_sellers_section');
    }

    public function trackOrder(Request $request)
    {
        if ($request->has('order_code')) {
            $order = Order::where('code', $request->order_code)->first();
            if ($order != null) {
                return view('frontend.track_order', compact('order'));
            }
        }
        return view('frontend.track_order');
    }

    public function product(Request $request, $slug)
    {


        if($request->token!==null){
        $token = PersonalAccessToken::findToken($request->token);
        if(!$token) return response()->json(["Unauthorized"], 401);
        $user = $token->tokenable;
        if(!$user) return response()->json(["Unauthorized"], 401);

        if($user->referral_code == null){
            $user->referral_code = substr($user->id.Str::random(10), 0, 10);
            $user->save();
        }

         $referral_code =  $user->referral_code;
         $referral_code_url = ($request->selfDomain.'product').'/'.$slug."?product_referral_code=$referral_code";
         }else{
            $referral_code_url =null;
         }


          $detailedProduct  = Product::with('reviews', 'brand', 'stocks', 'user', 'user.shop')->where('auction_product', 0)->where('slug', $slug)->where('approved', 1)->first();
          $shopID =  $detailedProduct->user->shop->id;
          $shop_details = new ShopDetailsCollection(Shop::where('id', $shopID)->first());
          $products =  new ProductDetailCollection([$detailedProduct]);
         $relatedProducts =  new ProductCollection(Product::where('category_id', $detailedProduct->category_id)->where('id', '!=', $detailedProduct->id)->limit(6)->get());
         $topSellingProduct = new ProductCollection(Product::where('user_id', $detailedProduct->user_id)->orderBy('num_of_sale', 'desc')->limit(6)->get());
         $vendorActivation = '';
         if(get_setting('vendor_system_activation') == 1){
            $vendorActivation = 1;
         }else{
            $vendorActivation = 0;
         }
         $coversationSystem = '';
         if(get_setting('conversation_system') == 1){
            $coversationSystem = 1;
         }else{
            $coversationSystem = 0;
         }
         $club_point = '';
         if(addon_is_activated('club_point')){
            $club_point = 1;
         }else{
            $club_point = 0;
         }
         $affiliteCheck = '';
         if(addon_is_activated('affiliate_system') && (\App\Models\AffiliateOption::where('type', 'product_sharing')->first()->status || \App\Models\AffiliateOption::where('type', 'category_wise_affiliate')->first()->status)){
            $affiliteCheck = 1;
         }else{
            $affiliteCheck = 0;
         }
         $refund_sticker =  get_setting('refund_sticker');
         $refund_sticker_image = uploaded_asset($refund_sticker);
         $refund_check = '';
         if(addon_is_activated('refund_request')){
            $refund_check = 1;
         }else{
            $refund_check = 0;
         }
         $product_query_activation='';
         if(get_setting('product_query_activation')){
            $product_query_activation = 1;
         }else{
            $product_query_activation = 0;
         }
         $total_query = ProductQuery::where('product_id', $detailedProduct->id)->count();
        $product_queries = ProductQuery::where('product_id', $detailedProduct->id)->where('customer_id', '!=', $user->id)->latest('id')->paginate(10);
         return response()->json([$products,$shop_details,$relatedProducts,$topSellingProduct,$vendorActivation,$coversationSystem,$club_point,$affiliteCheck,$referral_code_url,$refund_check,$refund_sticker_image,$product_query_activation,$total_query,$product_queries]);


        // Pagination using Ajax
        if (request()->ajax()) {
            return Response::json(View::make('frontend.partials.product_query_pagination', array('product_queries' => $product_queries))->render());
        }
        // End of Pagination using Ajax

        if ($detailedProduct != null && $detailedProduct->published) {
            if ($request->has('product_referral_code') && addon_is_activated('affiliate_system')) {
                $affiliate_validation_time = AffiliateConfig::where('type', 'validation_time')->first();
                $cookie_minute = 30 * 24;
                if ($affiliate_validation_time) {
                    $cookie_minute = $affiliate_validation_time->value * 60;
                }
                Cookie::queue('product_referral_code', $request->product_referral_code, $cookie_minute);
                Cookie::queue('referred_product_id', $detailedProduct->id, $cookie_minute);

                $referred_by_user = User::where('referral_code', $request->product_referral_code)->first();

                $affiliateController = new AffiliateController;
                $affiliateController->processAffiliateStats($referred_by_user->id, 1, 0, 0, 0);
            }

            if(Auth::check()){
                $userClient = Auth::user()->id;
                session()->push('products'.$userClient, $detailedProduct->id);
            }

            if ($detailedProduct->digital == 1) {
                return response()->json(["detailedProduct"=>$detailedProduct,"product_queries"=>$product_queries,'total_query'=>$total_query]);
                // return view('frontend.digital_product_details', compact('detailedProduct', 'product_queries', 'total_query'));
            } else {
                return response()->json(["detailedProduct"=>$detailedProduct,"product_queries"=>$product_queries]);
                // return view('frontend.product_details', compact('detailedProduct', 'product_queries', 'total_query'));
            }
        }
        abort(404);
    }

    public function shop($slug)
    {

        $shop  = Shop::where('slug', $slug)->first();
        if ($shop != null) {
            if ($shop->verification_status != 0) {
                return view('frontend.seller_shop', compact('shop'));
            } else {
                return view('frontend.seller_shop_without_verification', compact('shop'));
            }
        }
        abort(404);
    }

    public function filter_shop($slug, $type)
    {
        $shop  = Shop::where('slug', $slug)->first();
        if ($shop != null && $type != null) {
            return view('frontend.seller_shop', compact('shop', 'type'));
        }
        abort(404);
    }

    public function all_categories(Request $request)
    {
        $categories = Category::where('level', 0)->orderBy('order_level', 'desc')->get();
        return view('frontend.all_category', compact('categories'));
    }

    public function all_brands(Request $request)
    {
        $categories = Category::all();
        return view('frontend.all_brand', compact('categories'));
    }

    public function home_settings(Request $request)
    {
        return view('home_settings.index');
    }

    public function top_10_settings(Request $request)
    {
        foreach (Category::all() as $key => $category) {
            if (is_array($request->top_categories) && in_array($category->id, $request->top_categories)) {
                $category->top = 1;
                $category->save();
            } else {
                $category->top = 0;
                $category->save();
            }
        }

        foreach (Brand::all() as $key => $brand) {
            if (is_array($request->top_brands) && in_array($brand->id, $request->top_brands)) {
                $brand->top = 1;
                $brand->save();
            } else {
                $brand->top = 0;
                $brand->save();
            }
        }

        flash(translate('Top 10 categories and brands have been updated successfully'))->success();
        return redirect()->route('home_settings.index');
    }

    public function variant_price(Request $request)
    {
        // return 'variant';
        $product = Product::find($request->id);
        $str = '';
        $quantity = 0;
        $tax = 0;
        $max_limit = 0;

        if ($request->has('color')) {
            $str = $request['color'];
        }

        if (json_decode($product->choice_options) != null) {
            foreach (json_decode($product->choice_options) as $key => $choice) {
                if ($str != null) {
                    $str .= '-' . str_replace(' ', '', $request['attribute_id_' . $choice->attribute_id]);
                } else {
                    $str .= str_replace(' ', '', $request['attribute_id_' . $choice->attribute_id]);
                }
            }
        }

        $product_stock = $product->stocks->where('variant', $str)->first();

        $price = $product_stock->price;


        if ($product->wholesale_product) {
            $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $request->quantity)->where('max_qty', '>=', $request->quantity)->first();
            if ($wholesalePrice) {
                $price = $wholesalePrice->price;
            }
        }

        $quantity = $product_stock->qty;
        $max_limit = $product_stock->qty;

        if ($quantity >= 1 && $product->min_qty <= $quantity) {
            $in_stock = 1;
        } else {
            $in_stock = 0;
        }

        //Product Stock Visibility
        if ($product->stock_visibility_state == 'text') {
            if ($quantity >= 1 && $product->min_qty < $quantity) {
                $quantity = translate('In Stock');
            } else {
                $quantity = translate('Out Of Stock');
            }
        }

        //discount calculation
        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }

        // taxes
        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }

        $price += $tax;

        return array(
            'price' => single_price($price * $request->quantity),
            'quantity' => $quantity,
            'digital' => $product->digital,
            'variation' => $str,
            'max_limit' => $max_limit,
            'in_stock' => $in_stock
        );
    }

    public function sellerpolicy()
    {
        $page =  Page::where('type', 'seller_policy_page')->first();
        return view("frontend.policies.sellerpolicy", compact('page'));
    }

    public function returnpolicy()
    {
        $page =  Page::where('type', 'return_policy_page')->first();
        return view("frontend.policies.returnpolicy", compact('page'));
    }

    public function supportpolicy()
    {
        $page =  Page::where('type', 'support_policy_page')->first();
        return view("frontend.policies.supportpolicy", compact('page'));
    }

    public function terms()
    {
        $page =  Page::where('type', 'terms_conditions_page')->first();
        return view("frontend.policies.terms", compact('page'));
    }

    public function privacypolicy()
    {
        $page =  Page::where('type', 'privacy_policy_page')->first();
        return view("frontend.policies.privacypolicy", compact('page'));
    }

    public function get_pick_up_points(Request $request)
    {
        $pick_up_points = PickupPoint::all();
        return view('frontend.partials.pick_up_points', compact('pick_up_points'));
    }

    public function get_category_items(Request $request)
    {
        $category = Category::findOrFail($request->id);
        // return view('frontend.partials.category_elements', compact('category'));

        $categoryUtilits = CategoryUtility::get_immediate_children_ids($category->id);

        $subCategorys = [];
        foreach($categoryUtilits as $key => $first_level_id){
            $subCategorys[$key] = [];
            $subCategorys[$key]['slug'] = Category::find($first_level_id)->slug;
            $subCategorys[$key]['name'] = Category::find($first_level_id)->getTranslation('name');
            $subCategorys[$key]['childCategorys'] = [];

            $subCategoryUtilits = CategoryUtility::get_immediate_children_ids($first_level_id);
            foreach($subCategoryUtilits as $keyd => $second_level_id){
                $subCategorys[$key]['childCategorys'][$keyd]['slug'] = Category::find($second_level_id)->slug;
                $subCategorys[$key]['childCategorys'][$keyd]['name'] = Category::find($second_level_id)->getTranslation('name');
            }
        }
        return response()->json(['subCategorys' => $subCategorys]);
    }

    public function premium_package_index()
    {
        $customer_packages = CustomerPackage::all();
        return view('frontend.user.customer_packages_lists', compact('customer_packages'));
    }

    // public function new_page()
    // {
    //     $user = User::where('user_type', 'admin')->first();
    //     auth()->login($user);
    //     return redirect()->route('admin.dashboard');

    // }


    // Ajax call
    public function new_verify(Request $request)
    {
        $email = $request->email;
        if (isUnique($email) == '0') {
            $response['status'] = 2;
            $response['message'] = 'Email already exists!';
            return json_encode($response);
        }

        $response = $this->send_email_change_verification_mail($request, $email);
        return json_encode($response);
    }


    // Form request
    public function update_email(Request $request)
    {
        $email = $request->email;
        if (isUnique($email)) {
            $this->send_email_change_verification_mail($request, $email);
            flash(translate('A verification mail has been sent to the mail you provided us with.'))->success();
            return back();
        }

        flash(translate('Email already exists!'))->warning();
        return back();
    }

    public function send_email_change_verification_mail($request, $email)
    {
        $response['status'] = 0;
        $response['message'] = 'Unknown';

        $verification_code = Str::random(32);

        $array['subject'] = 'Email Verification';
        $array['from'] = env('MAIL_FROM_ADDRESS');
        $array['content'] = 'Verify your account';
        $array['link'] = route('email_change.callback') . '?new_email_verificiation_code=' . $verification_code . '&email=' . $email;
        $array['sender'] = Auth::user()->name;
        $array['details'] = "Email Second";

        $user = Auth::user();
        $user->new_email_verificiation_code = $verification_code;
        $user->save();

        try {
            Mail::to($email)->queue(new SecondEmailVerifyMailManager($array));

            $response['status'] = 1;
            $response['message'] = translate("Your verification mail has been Sent to your email.");
        } catch (\Exception $e) {
            // return $e->getMessage();
            $response['status'] = 0;
            $response['message'] = $e->getMessage();
        }

        return $response;
    }

    public function email_change_callback(Request $request)
    {
        if ($request->has('new_email_verificiation_code') && $request->has('email')) {
            $verification_code_of_url_param =  $request->input('new_email_verificiation_code');
            $user = User::where('new_email_verificiation_code', $verification_code_of_url_param)->first();

            if ($user != null) {

                $user->email = $request->input('email');
                $user->new_email_verificiation_code = null;
                $user->save();

                auth()->login($user, true);

                flash(translate('Email Changed successfully'))->success();
                if ($user->user_type == 'seller') {
                    return redirect()->route('seller.dashboard');
                }
                return redirect()->route('dashboard');
            }
        }

        flash(translate('Email was not verified. Please resend your mail!'))->error();
        return redirect()->route('dashboard');
    }

    public function reset_password_with_code(Request $request)
    {

        if (($user = User::where('email', $request->email)->where('verification_code', $request->code)->first()) != null) {
            if ($request->password == $request->password_confirmation) {
                $user->password = Hash::make($request->password);
                $user->email_verified_at = date('Y-m-d h:m:s');
                $user->save();
                event(new PasswordReset($user));
                auth()->login($user, true);

                flash(translate('Password updated successfully'))->success();

                if (auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff') {
                    return redirect()->route('admin.dashboard');
                }
                return redirect()->route('home');
            } else {
                flash("Password and confirm password didn't match")->warning();
                return view('auth.passwords.reset');
            }
        } else {
            flash("Verification code mismatch")->error();
            return view('auth.passwords.reset');
        }
    }


    public function all_flash_deals()
    {
        $today = strtotime(date('Y-m-d H:i:s'));

        $data['all_flash_deals'] = FlashDeal::where('status', 1)
            ->where('start_date', "<=", $today)
            ->where('end_date', ">", $today)
            ->orderBy('created_at', 'desc')
            ->get()->take(8);

        return view("frontend.flash_deal.all_flash_deal_list", $data);
    }

    public function all_seller(Request $request)
    {
        $shops = Shop::whereIn('user_id', verified_sellers_id())
            ->paginate(15);

        return view('frontend.shop_listing', compact('shops'));
    }

    public function all_coupons(Request $request)
    {
       return $coupons = Coupon::where('start_date', '<=', strtotime(date('d-m-Y')))->where('end_date', '>=', strtotime(date('d-m-Y')))->paginate(15);
        return view('frontend.coupons', compact('coupons'));
    }

    public function inhouse_products(Request $request)
    {
        $products = filter_products(Product::where('added_by', 'admin'))->with('taxes')->paginate(12)->appends(request()->query());
        return view('frontend.inhouse_products', compact('products'));
    }
}
