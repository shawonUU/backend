<?php

use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Auction Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\VueControllers\Api\VUE\CartController;
use App\Http\Controllers\VueControllers\Api\VUE\HomeController;
use App\Http\Controllers\VueControllers\Api\VUE\SearchController;
use App\Http\Controllers\VueControllers\Api\VUE\AuctionProductController;
use App\Http\Controllers\VueControllers\Api\VUE\AuctionProductBidController;
//Some important Routes
// Route::get('get-pages',)
Route::get('get-important-pages','App\Http\Controllers\VueControllers\Api\VUE\SomeImportantInfoController@getImportantPages');
Route::get('header-category','App\Http\Controllers\VueControllers\Api\VUE\SomeImportantInfoController@getNavCategory');
Route::get('/blog','App\Http\Controllers\VueControllers\Api\VUE\SomeImportantInfoController@getBlog');
Route::get('/blogs/{slug}', 'App\Http\Controllers\VueControllers\Api\VUE\SomeImportantInfoController@blog_details');
Route::get('/all_brands','App\Http\Controllers\VueControllers\Api\VUE\SomeImportantInfoController@allBrands');
Route::get('/all_shops','App\Http\Controllers\VueControllers\Api\VUE\SomeImportantInfoController@allShops');
Route::get('/check-flash-deal','App\Http\Controllers\VueControllers\Api\VUE\SomeImportantInfoController@cheakFlashDeal');
Route::get('/flash-deal-product','App\Http\Controllers\VueControllers\Api\VUE\SomeImportantInfoController@flashDealProduct');

//Data for dahsboard

Route::get('data-userdashboard','App\Http\Controllers\VueControllers\Api\VUE\SomeImportantInfoController@getDataForUserDashboard');

Route::controller(SearchController::class)->group(function () {
    Route::get('/search', 'index')->name('search');
    Route::get('/search?keyword={search}', 'index')->name('suggestion.search');
    Route::get('/ajax-search', 'ajax_search')->name('search.ajax');
    Route::get('/category/{category_slug}', 'listingByCategory')->name('products.category');
    Route::get('/brand/{brand_slug}', 'listingByBrand')->name('products.brand');
});
Route::get('products/allpost', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@allpostapi')->name('allpostapi');
Route::get('products/newproducts', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@newproducts')->name('newproduct');
Route::get('v3/jobs', 'App\Http\Controllers\VueControllers\Api\VUE\JobController@index')->name('alljobs');
Route::get('v3/job/{slug}', 'App\Http\Controllers\VueControllers\Api\VUE\JobController@show')->name('jobshow');
Route::get('v3/jobapplication', 'App\Http\Controllers\VueControllers\Api\VUE\JobController@job_apply');
Route::post('v3/productstore','App\Http\Controllers\VueControllers\Api\VUE\Seller\ProductController@productstore');
Route::post('v3/productupdate/{id}','App\Http\Controllers\VueControllers\Api\VUE\Seller\ProductController@productupdate');

// Route::post('v2/sellerproductdetails/{id}', function(){
//     return "hello";
// });
Route::get('v2/sellerproductdetails/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\Seller\ProductController@seller_product_details');

Route::get('dropdowncategories', 'App\Http\Controllers\VueControllers\Api\VUE\CategoryController@dropdowncategories');

//Auction


//Admin
Route::group(['prefix' =>'admin', 'middleware' => ['auth', 'admin']], function(){
    // Auction product lists
    Route::controller(AuctionProductController::class)->group(function () {
        Route::get('auction/all-products', 'all_auction_product_list')->name('auction.all_products');
        Route::get('auction/inhouse-products', 'inhouse_auction_products')->name('auction.inhouse_products');
        Route::get('auction/seller-products', 'seller_auction_products')->name('auction.seller_products');

        Route::get('/auction-product/create', 'product_create_admin')->name('auction_product_create.admin');
        Route::post('/auction-product/store', 'product_store_admin')->name('auction_product_store.admin');
        Route::get('/auction_products/edit/{id}', 'product_edit_admin')->name('auction_product_edit.admin');
        Route::post('/auction_products/update/{id}', 'product_update_admin')->name('auction_product_update.admin');
        Route::get('/auction_products/destroy/{id}', 'product_destroy_admin')->name('auction_product_destroy.admin');

        // Sales
        Route::get('/auction_products-orders', 'admin_auction_product_orders')->name('auction_products_orders');
    });
    Route::controller(AuctionProductBidController::class)->group(function () {
        Route::get('/product-bids/{id}', 'product_bids_admin')->name('product_bids.admin');
        Route::get('/product-bids/destroy/{id}', 'bid_destroy_admin')->name('product_bids_destroy.admin');
    });
});

Route::group(['prefix' => 'seller', 'middleware' => ['seller', 'verified', 'user']], function() {
    Route::controller(AuctionProductController::class)->group(function () {
        Route::get('/auction_products', 'auction_product_list_seller')->name('auction_products.seller.index');

        Route::get('/auction-product/create', 'product_create_seller')->name('auction_product_create.seller');
        Route::post('/auction-product/store', 'product_store_seller')->name('auction_product_store.seller');
        Route::get('/auction_products/edit/{id}', 'product_edit_seller')->name('auction_product_edit.seller');
        Route::post('/auction_products/update/{id}', 'product_update_seller')->name('auction_product_update.seller');
        Route::get('/auction_products/destroy/{id}', 'product_destroy_seller')->name('auction_product_destroy.seller');

        Route::get('/auction_products-orders', 'seller_auction_product_orders')->name('auction_products_orders.seller');
    });
    Route::controller(AuctionProductBidController::class)->group(function () {
        Route::get('/product-bids/{id}', 'product_bids_seller')->name('product_bids.seller');
        Route::get('/product-bids/destroy/{id}', 'bid_destroy_seller')->name('product_bids_destroy.seller');
    });
});

Route::group(['middleware' => ['auth']], function() {
    Route::resource('auction_product_bids', AuctionProductBidController::class);

    Route::post('/auction/cart/show-cart-modal', [CartController::class, 'showCartModalAuction'])->name('auction.cart.showCartModal');
    Route::get('/auction/purchase_history', [AuctionProductController::class, 'purchase_history_user'])->name('auction_product.purchase_history');
});

Route::post('/home/section/auction_products', [HomeController::class, 'load_auction_products_section'])->name('home.section.auction_products');

Route::controller(AuctionProductController::class)->group(function () {
    Route::get('/auction-product/{slug}', 'auction_product_details')->name('auction-product');
    Route::get('/auction-products', 'all_auction_products')->name('auction_products.all');
});


Route::group(['prefix' => 'v3/auth', 'middleware' => ['app_language']], function() {
    Route::get('login', 'App\Http\Controllers\VueControllers\Api\VUE\AuthController@login');
    Route::get('signup', 'App\Http\Controllers\VueControllers\Api\VUE\AuthController@signup');
    Route::post('social-login', 'App\Http\Controllers\VueControllers\Api\VUE\AuthController@socialLogin');
    Route::post('password/forget_request', 'App\Http\Controllers\VueControllers\Api\VUE\PasswordResetController@forgetRequest');
    Route::post('password/confirm_reset', 'App\Http\Controllers\VueControllers\Api\VUE\PasswordResetController@confirmReset');
    Route::post('password/resend_code', 'App\Http\Controllers\VueControllers\Api\VUE\PasswordResetController@resendCode');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('logout', 'App\Http\Controllers\VueControllers\Api\VUE\AuthController@logout');
        // Route::get('user', 'App\Http\Controllers\VueControllers\Api\VUE\AuthController@user');
    });
    Route::middleware('auth:sanctum')->get('/', function (Request $request) {

    });
    Route::get('get_nav_data', 'App\Http\Controllers\VueControllers\HomeController@getNaveData');

    Route::post('resend_code', 'App\Http\Controllers\VueControllers\Api\VUE\AuthController@resendCode');
    Route::post('confirm_code', 'App\Http\Controllers\VueControllers\Api\VUE\AuthController@confirmCode');
});

Route::group(['prefix' => 'v3'], function() {
    Route::prefix('delivery-boy')->group(function () {
        Route::get('dashboard-summary/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@dashboard_summary')->middleware('auth:sanctum');
        Route::get('deliveries/completed/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@completed_delivery')->middleware('auth:sanctum');
        Route::get('deliveries/cancelled/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@cancelled_delivery')->middleware('auth:sanctum');
        Route::get('deliveries/on_the_way/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@on_the_way_delivery')->middleware('auth:sanctum');
        Route::get('deliveries/picked_up/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@picked_up_delivery')->middleware('auth:sanctum');
        Route::get('deliveries/assigned/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@assigned_delivery')->middleware('auth:sanctum');
        Route::get('collection-summary/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@collection_summary')->middleware('auth:sanctum');
        Route::get('earning-summary/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@earning_summary')->middleware('auth:sanctum');
        Route::get('collection/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@collection')->middleware('auth:sanctum');
        Route::get('earning/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@earning')->middleware('auth:sanctum');
        Route::get('cancel-request/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@cancel_request')->middleware('auth:sanctum');
        Route::post('change-delivery-status', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@change_delivery_status')->middleware('auth:sanctum');
        //Delivery Boy Order
        Route::get('purchase-history-details/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@details')->middleware('auth:sanctum');
        Route::get('purchase-history-items/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\DeliveryBoyController@items')->middleware('auth:sanctum');
    });

    Route::get('get-search-suggestions', 'App\Http\Controllers\VueControllers\Api\VUE\SearchSuggestionController@getList');
    Route::get('languages', 'App\Http\Controllers\VueControllers\Api\VUE\LanguageController@getList');

    Route::get('chat/conversations', 'App\Http\Controllers\VueControllers\Api\VUE\ChatController@conversations')->middleware('auth:sanctum');
    Route::get('chat/messages/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ChatController@messages')->middleware('auth:sanctum');
    Route::post('chat/insert-message', 'App\Http\Controllers\VueControllers\Api\VUE\ChatController@insert_message')->middleware('auth:sanctum');
    Route::get('chat/get-new-messages/{conversation_id}/{last_message_id}', 'App\Http\Controllers\VueControllers\Api\VUE\ChatController@get_new_messages')->middleware('auth:sanctum');
    Route::post('chat/create-conversation', 'App\Http\Controllers\VueControllers\Api\VUE\ChatController@create_conversation')->middleware('auth:sanctum');

    Route::apiResource('banners', 'App\Http\Controllers\VueControllers\Api\VUE\BannerController')->only('index');

    Route::get('brands/top', 'App\Http\Controllers\VueControllers\Api\VUE\BrandController@top');
    Route::apiResource('brands', 'App\Http\Controllers\VueControllers\Api\VUE\BrandController')->only('index');

    Route::apiResource('business-settings', 'App\Http\Controllers\VueControllers\Api\VUE\BusinessSettingController')->only('index');

    Route::get('categories/featured', 'App\Http\Controllers\VueControllers\Api\VUE\CategoryController@featured');
    Route::get('categories/home', 'App\Http\Controllers\VueControllers\Api\VUE\CategoryController@home');
    Route::get('categories/top', 'App\Http\Controllers\VueControllers\Api\VUE\CategoryController@top');
    Route::apiResource('categories', 'App\Http\Controllers\VueControllers\Api\VUE\CategoryController')->only('index');
    Route::get('sub-categories/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\SubCategoryController@index')->name('subCategories.index');

    Route::apiResource('colors', 'App\Http\Controllers\VueControllers\Api\VUE\ColorController')->only('index');

    Route::apiResource('currencies', 'App\Http\Controllers\VueControllers\Api\VUE\CurrencyController')->only('index');

    Route::apiResource('customers', 'App\Http\Controllers\VueControllers\Api\VUE\CustomerController')->only('show');

    Route::apiResource('general-settings', 'App\Http\Controllers\VueControllers\Api\VUE\GeneralSettingController')->only('index');

    Route::apiResource('home-categories', 'App\Http\Controllers\VueControllers\Api\VUE\HomeCategoryController')->only('index');

    //Route::get('purchase-history/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\PurchaseHistoryController@index')->middleware('auth:sanctum');
    //Route::get('purchase-history-details/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\PurchaseHistoryDetailController@index')->name('purchaseHistory.details')->middleware('auth:sanctum');

    Route::get('purchase-history', 'App\Http\Controllers\VueControllers\Api\VUE\PurchaseHistoryController@index')->middleware('auth:sanctum');
    Route::get('purchase-history-details/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\PurchaseHistoryController@details')->middleware('auth:sanctum');
    Route::get('purchase-history-items/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\PurchaseHistoryController@items')->middleware('auth:sanctum');

    Route::get('filter/categories', 'App\Http\Controllers\VueControllers\Api\VUE\FilterController@categories');
    Route::get('filter/brands', 'App\Http\Controllers\VueControllers\Api\VUE\FilterController@brands');

    Route::get('products/admin', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@admin');
    Route::get('products/seller/{slug}', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@seller');
    Route::get('products/category/{slug}', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@category')->name('api.products.category');
    Route::get('products/sub-category/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@subCategory')->name('products.subCategory');
    Route::get('products/sub-sub-category/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@subSubCategory')->name('products.subSubCategory');
    Route::get('products/brand/{slug}', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@brand')->name('api.products.brand');
    Route::get('products/todays-deal', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@todaysDeal');
    Route::get('products/featured', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@featured');
    Route::get('products/best-seller', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@bestSeller');
    Route::get('products/allproductsm','App\Http\Controllers\VueControllers\Api\VUE\ProductController@all_products');
    Route::get('products/all_auction_products','App\Http\Controllers\VueControllers\Api\VUE\ProductController@all_auction_products');
    Route::get('products/top-from-seller/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@topFromSeller');
    Route::get('products/related/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@related')->name('products.related');
    Route::get('products/featured-from-seller/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@newFromSeller')->name('products.featuredromSeller');
    Route::get('products/search', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@search');
    Route::get('products/variant/price', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@variantPrice');
    Route::get('products/home', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController@home');
    Route::apiResource('products', 'App\Http\Controllers\VueControllers\Api\VUE\ProductController')->except(['store', 'update', 'destroy']);
    Route::get('home-category-wise-product','App\Http\Controllers\VueControllers\Api\VUE\ProductController@homeCategoriesProduct');
    // //Product with slug
    // Route::get('/product/{slug}', 'product')->name('product');
    Route::get('cart-summary', 'App\Http\Controllers\VueControllers\Api\VUE\CartController@summary')->middleware('auth:sanctum');
    Route::get('cart-count', 'App\Http\Controllers\VueControllers\Api\VUE\CartController@count')->middleware('auth:sanctum');
    Route::post('carts/process', 'App\Http\Controllers\VueControllers\Api\VUE\CartController@process')->middleware('auth:sanctum');
    Route::post('carts/add', 'App\Http\Controllers\VueControllers\Api\VUE\CartController@add')->middleware('auth:sanctum');
    Route::post('carts/change-quantity', 'App\Http\Controllers\VueControllers\Api\VUE\CartController@changeQuantity')->middleware('auth:sanctum');
    Route::apiResource('carts', 'App\Http\Controllers\VueControllers\Api\VUE\CartController')->only('destroy')->middleware('auth:sanctum');
    Route::post('carts', 'App\Http\Controllers\VueControllers\Api\VUE\CartController@getList')->middleware('auth:sanctum');
    Route::get('delivery-info', 'App\Http\Controllers\VueControllers\Api\VUE\ShippingController@getDeliveryInfo')->middleware('auth:sanctum');


    Route::post('coupon-apply', 'App\Http\Controllers\VueControllers\Api\VUE\CheckoutController@apply_coupon_code')->middleware('auth:sanctum');
    Route::post('coupon-remove', 'App\Http\Controllers\VueControllers\Api\VUE\CheckoutController@remove_coupon_code')->middleware('auth:sanctum');

    Route::post('update-address-in-cart', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@updateAddressInCart')->middleware('auth:sanctum');
    Route::post('update-shipping-type-in-cart', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@updateShippingTypeInCart')->middleware('auth:sanctum');
    Route::get('get-home-delivery-address', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@getShippingInCart')->middleware('auth:sanctum');
    Route::post('shipping_cost', 'App\Http\Controllers\VueControllers\Api\VUE\ShippingController@shipping_cost')->middleware('auth:sanctum');
    Route::post('carriers', 'App\Http\Controllers\VueControllers\Api\VUE\CarrierController@index')->middleware('auth:sanctum');



    Route::get('payment-types', 'App\Http\Controllers\VueControllers\Api\VUE\PaymentTypesController@getList');

    Route::get('reviews/product/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ReviewController@index')->name('api.reviews.index');
    Route::post('reviews/submit', 'App\Http\Controllers\VueControllers\Api\VUE\ReviewController@submit')->name('api.reviews.submit')->middleware('auth:sanctum');

    Route::get('shop/user/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ShopController@shopOfUser')->middleware('auth:sanctum');
    Route::get('shops/details/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ShopController@info')->name('shops.info');
    Route::get('shops/products/all/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ShopController@allProducts')->name('shops.allProducts');
    Route::get('shops/products/top/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ShopController@topSellingProducts')->name('shops.topSellingProducts');
    Route::get('shops/products/featured/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ShopController@featuredProducts')->name('shops.featuredProducts');
    Route::get('shops/products/new/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ShopController@newProducts')->name('shops.newProducts');
    Route::get('shops/brands/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\ShopController@brands')->name('shops.brands');
    Route::apiResource('shops', 'App\Http\Controllers\VueControllers\Api\VUE\ShopController')->only('index');

    Route::get('sliders', 'App\Http\Controllers\VueControllers\Api\VUE\SliderController@sliders');
    Route::get('banners-one', 'App\Http\Controllers\VueControllers\Api\VUE\SliderController@bannerOne');
    Route::get('banners-two', 'App\Http\Controllers\VueControllers\Api\VUE\SliderController@bannerTwo');
    Route::get('banners-three', 'App\Http\Controllers\VueControllers\Api\VUE\SliderController@bannerThree');


    Route::get('wishlists-check-product', 'App\Http\Controllers\VueControllers\Api\VUE\WishlistController@isProductInWishlist')->middleware('auth:sanctum');
    Route::get('wishlists-add-product', 'App\Http\Controllers\VueControllers\Api\VUE\WishlistController@add')->middleware('auth:sanctum');
    Route::get('wishlists-remove-product', 'App\Http\Controllers\VueControllers\Api\VUE\WishlistController@remove')->middleware('auth:sanctum');
    Route::get('wishlists', 'App\Http\Controllers\VueControllers\Api\VUE\WishlistController@index')->middleware('auth:sanctum');
    Route::apiResource('wishlists', 'App\Http\Controllers\VueControllers\Api\VUE\WishlistController')->except(['index', 'update', 'show']);

    Route::get('policies/seller', 'App\Http\Controllers\VueControllers\Api\VUE\PolicyController@sellerPolicy')->name('policies.seller');
    Route::get('policies/support', 'App\Http\Controllers\VueControllers\Api\VUE\PolicyController@supportPolicy')->name('policies.support');
    Route::get('policies/return', 'App\Http\Controllers\VueControllers\Api\VUE\PolicyController@returnPolicy')->name('policies.return');

    // Route::get('user/info/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\UserController@info')->middleware('auth:sanctum');
    // Route::post('user/info/update', 'App\Http\Controllers\VueControllers\Api\VUE\UserController@updateName')->middleware('auth:sanctum');
    Route::get('user/shipping/address', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@addresses')->middleware('auth:sanctum');
    Route::post('user/shipping/create', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@createShippingAddress')->middleware('auth:sanctum');
    Route::post('user/shipping/update', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@updateShippingAddress')->middleware('auth:sanctum');
    Route::post('user/shipping/update-location', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@updateShippingAddressLocation')->middleware('auth:sanctum');
    Route::post('user/shipping/make_default', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@makeShippingAddressDefault')->middleware('auth:sanctum');
    Route::get('user/shipping/delete/{address_id}', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@deleteShippingAddress')->middleware('auth:sanctum');

    Route::get('clubpoint/get-list', 'App\Http\Controllers\VueControllers\Api\VUE\ClubpointController@get_list')->middleware('auth:sanctum');
    Route::post('clubpoint/convert-into-wallet', 'App\Http\Controllers\VueControllers\Api\VUE\ClubpointController@convert_into_wallet')->middleware('auth:sanctum');

    Route::get('refund-request/get-list', 'App\Http\Controllers\VueControllers\Api\VUE\RefundRequestController@get_list')->middleware('auth:sanctum');
    Route::post('refund-request/send', 'App\Http\Controllers\VueControllers\Api\VUE\RefundRequestController@send')->middleware('auth:sanctum');

    Route::post('get-user-by-access_token', 'App\Http\Controllers\VueControllers\Api\VUE\UserController@getUserInfoByAccessToken');

    Route::get('cities', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@getCities');
    Route::get('states', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@getStates');
    Route::get('countries', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@getCountries');

    Route::get('cities-by-state/{state_id}', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@getCitiesByState');
    Route::get('states-by-country/{country_id}', 'App\Http\Controllers\VueControllers\Api\VUE\AddressController@getStatesByCountry');


    // Route::post('coupon/apply', 'App\Http\Controllers\VueControllers\Api\VUE\CouponController@apply')->middleware('auth:sanctum');


    Route::any('stripe', 'App\Http\Controllers\VueControllers\Api\VUE\StripeController@stripe');
    Route::any('/stripe/create-checkout-session', 'App\Http\Controllers\VueControllers\Api\VUE\StripeController@create_checkout_session')->name('api.stripe.get_token');
    Route::any('/stripe/payment/callback', 'App\Http\Controllers\VueControllers\Api\VUE\StripeController@callback')->name('api.stripe.callback');
    Route::any('/stripe/success', 'App\Http\Controllers\VueControllers\Api\VUE\StripeController@success')->name('api.stripe.success');
    Route::any('/stripe/cancel', 'App\Http\Controllers\VueControllers\Api\VUE\StripeController@cancel')->name('api.stripe.cancel');

    Route::any('paypal/payment/url', 'App\Http\Controllers\VueControllers\Api\VUE\PaypalController@getUrl')->name('api.paypal.url');
    Route::any('paypal/payment/done', 'App\Http\Controllers\VueControllers\Api\VUE\PaypalController@getDone')->name('api.paypal.done');
    Route::any('paypal/payment/cancel', 'App\Http\Controllers\VueControllers\Api\VUE\PaypalController@getCancel')->name('api.paypal.cancel');

    Route::any('razorpay/pay-with-razorpay', 'App\Http\Controllers\VueControllers\Api\VUE\RazorpayController@payWithRazorpay')->name('api.razorpay.payment');
    Route::any('razorpay/payment', 'App\Http\Controllers\VueControllers\Api\VUE\RazorpayController@payment')->name('api.razorpay.payment');
    Route::post('razorpay/success', 'App\Http\Controllers\VueControllers\Api\VUE\RazorpayController@success')->name('api.razorpay.success');

    Route::any('paystack/init', 'App\Http\Controllers\VueControllers\Api\VUE\PaystackController@init')->name('api.paystack.init');
    Route::post('paystack/success', 'App\Http\Controllers\VueControllers\Api\VUE\PaystackController@success')->name('api.paystack.success');

    Route::any('iyzico/init', 'App\Http\Controllers\VueControllers\Api\VUE\IyzicoController@init')->name('api.iyzico.init');
    Route::any('iyzico/callback', 'App\Http\Controllers\VueControllers\Api\VUE\IyzicoController@callback')->name('api.iyzico.callback');
    Route::post('iyzico/success', 'App\Http\Controllers\VueControllers\Api\VUE\IyzicoController@success')->name('api.iyzico.success');


    Route::group(['middleware' => 'web'], function () {
    Route::get('bkash/begin', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@begin')->middleware('auth:sanctum');
    Route::get('bkash/api/webpage/{token}/{amount}', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@webpage')->name('api.bkash.webpage');
    Route::any('bkash/api/checkout/{token}/{amount}', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@checkout')->name('api.bkash.checkout');
    Route::any('bkash/api/execute/{token}', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@execute')->name('api.bkash.execute');
    Route::any('bkash/api/fail', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@fail')->name('api.bkash.fail');
    Route::any('bkash/api/success', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@success')->name('api.bkash.success');
    Route::post('bkash/api/process', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@process')->name('api.bkash.process');
});

    // Route::get('bkash/begin', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@begin')->middleware('auth:sanctum');
    // Route::get('bkash/api/webpage/{token}/{amount}', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@webpage')->name('api.bkash.webpage');
    // Route::any('bkash/api/checkout/{token}/{amount}', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@checkout')->name('api.bkash.checkout');
    // Route::any('bkash/api/execute/{token}', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@execute')->name('api.bkash.execute');
    // Route::any('bkash/api/fail', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@fail')->name('api.bkash.fail');
    // Route::any('bkash/api/success', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@success')->name('api.bkash.success');
    // Route::post('bkash/api/process', 'App\Http\Controllers\VueControllers\Api\VUE\BkashController@process')->name('api.bkash.process');

    Route::get('nagad/begin', 'App\Http\Controllers\VueControllers\Api\VUE\NagadController@begin')->middleware('auth:sanctum');
    Route::any('nagad/verify/{payment_type}', 'App\Http\Controllers\VueControllers\Api\VUE\NagadController@verify')->name('app.nagad.callback_url');
    Route::post('nagad/process', 'App\Http\Controllers\VueControllers\Api\VUE\NagadController@process');

    Route::get('sslcommerz/begin', 'App\Http\Controllers\VueControllers\Api\VUE\SslCommerzController@begin');
    Route::post('sslcommerz/success', 'App\Http\Controllers\VueControllers\Api\VUE\SslCommerzController@payment_success');
    Route::post('sslcommerz/fail', 'App\Http\Controllers\VueControllers\Api\VUE\SslCommerzController@payment_fail');
    Route::post('sslcommerz/cancel', 'App\Http\Controllers\VueControllers\Api\VUE\SslCommerzController@payment_cancel');

    Route::any('flutterwave/payment/url', 'App\Http\Controllers\VueControllers\Api\VUE\FlutterwaveController@getUrl')->name('api.flutterwave.url');
    Route::any('flutterwave/payment/callback', 'App\Http\Controllers\VueControllers\Api\VUE\FlutterwaveController@callback')->name('api.flutterwave.callback');

    Route::any('paytm/payment/pay', 'App\Http\Controllers\VueControllers\Api\VUE\PaytmController@pay')->name('api.paytm.pay');
    Route::any('paytm/payment/callback', 'App\Http\Controllers\VueControllers\Api\VUE\PaytmController@callback')->name('api.paytm.callback');

    Route::post('payments/pay/wallet', 'App\Http\Controllers\VueControllers\Api\VUE\WalletController@processPayment')->middleware('auth:sanctum');
    Route::post('payments/pay/cod', 'App\Http\Controllers\VueControllers\Api\VUE\PaymentController@cashOnDelivery')->middleware('auth:sanctum');
    Route::post('payments/pay/manual', 'App\Http\Controllers\VueControllers\Api\VUE\PaymentController@manualPayment')->middleware('auth:sanctum');

    Route::post('offline/payment/submit', 'App\Http\Controllers\VueControllers\Api\VUE\OfflinePaymentController@submit')->name('api.offline.payment.submit');

    Route::post('order/store', 'App\Http\Controllers\VueControllers\Api\VUE\OrderController@store')->middleware('auth:sanctum');

    Route::get('profile/counters', 'App\Http\Controllers\VueControllers\Api\VUE\ProfileController@counters')->middleware('auth:sanctum');

    Route::post('profile/update', 'App\Http\Controllers\VueControllers\Api\VUE\ProfileController@update')->middleware('auth:sanctum');

    Route::post('profile/update-device-token', 'App\Http\Controllers\VueControllers\Api\VUE\ProfileController@update_device_token')->middleware('auth:sanctum');
    Route::post('profile/update-image', 'App\Http\Controllers\VueControllers\Api\VUE\ProfileController@updateImage')->middleware('auth:sanctum');
    Route::post('profile/image-upload', 'App\Http\Controllers\VueControllers\Api\VUE\ProfileController@imageUpload')->middleware('auth:sanctum');
    Route::post('profile/check-phone-and-email', 'App\Http\Controllers\VueControllers\Api\VUE\ProfileController@checkIfPhoneAndEmailAvailable')->middleware('auth:sanctum');

    Route::post('file/image-upload', 'App\Http\Controllers\VueControllers\Api\VUE\FileController@imageUpload')->middleware('auth:sanctum');
    Route::get('file-all', 'App\Http\Controllers\VueControllers\Api\VUE\FileController@index')->middleware('auth:sanctum');

    Route::get('wallet/balance', 'App\Http\Controllers\VueControllers\Api\VUE\WalletController@balance')->middleware('auth:sanctum');
    Route::get('wallet/history', 'App\Http\Controllers\VueControllers\Api\VUE\WalletController@walletRechargeHistory')->middleware('auth:sanctum');
    Route::post('wallet/offline-recharge', 'App\Http\Controllers\VueControllers\Api\VUE\WalletController@offline_recharge')->middleware('auth:sanctum');

    Route::get('flash-deals', 'App\Http\Controllers\VueControllers\Api\VUE\FlashDealController@index');
    Route::get('flash-deal-products/{id}', 'App\Http\Controllers\VueControllers\Api\VUE\FlashDealController@products');

    //Addon list
    Route::get('addon-list', 'App\Http\Controllers\VueControllers\Api\VUE\ConfigController@addon_list');
    //Activated social login list
    Route::get('activated-social-login', 'App\Http\Controllers\VueControllers\Api\VUE\ConfigController@activated_social_login');

    //Business Sttings list
    Route::post('business-settings', 'App\Http\Controllers\VueControllers\Api\VUE\ConfigController@business_settings');
    //Pickup Point list
    Route::get('pickup-list', 'App\Http\Controllers\VueControllers\Api\VUE\ShippingController@pickup_list');
});

Route::fallback(function() {
    return response()->json([
        'data' => [],
        'success' => false,
        'status' => 404,
        'message' => 'Invalid Route'
    ]);
});
