<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{


  /**
   * This namespace is applied to your controller routes.
   *
   * In addition, it is set as the URL generator's root namespace.
   *
   * @var string
   */
  public const HOME = '/home';
  protected $namespace = null;

  /**
   * Define your route model bindings, pattern filters, etc.
   *
   * @return void
   */
  public function boot()
  {
    //

    parent::boot();

    $this->configureRateLimiting();
  }

  /**
   * Define the routes for the application.
   *
   * @return void
   */
  public function map()
  {
     $this->mapApiRoutes();

     $this->mapApiSellerRoutes();

     $this->mapAdminRoutes();

     $this->mapSellerRoutes();

     $this->mapAffiliateRoutes();

     $this->mapRefundRoutes();

     $this->mapClubPointsRoutes();

     $this->mapOtpRoutes();

     $this->mapOfflinePaymentRoutes();

     $this->mapAfricanPaymentGatewayRoutes();

     $this->mapPaytmRoutes();

     $this->mapPosRoutes();

     $this->mapSellerPackageRoutes();

     $this->mapDeliveryBoyRoutes();

     $this->mapAuctionRoutes();

     $this->mapWholesaleRoutes();

     //Vue Application Routes start here
     $this->mapVueRoutes();
     $this->mapWebVueRoutes();
     $this->mapWholesaleVueRoutes();
     $this->mapDeliveryBoyVueRoutes();
     $this->mapAuctionVueRoutes();
     $this->mapSellerPackageVueRoutes();
     $this->mapAffiliateVueRoutes();
     $this->mapOfflinePaymentVueRoutes();
     $this->mapPaytmVueRoutes();
     $this->mapAfricanPaymentGatewayVueRoutes();
     $this->mapRefundVueRoutes();
     $this->mapClubPointsVueRoutes();
     $this->mapOtpVueRoutes();
     $this->mapPosVueRoutes();
     $this->mapUpdateVueRoutes();
     $this->mapInstallVueRoutes();
     $this->mapAdminVueRoutes();
     $this->mapSellerVueRoutes();
     $this->mapApiSellerVueRoutes();
     //Vue Application Routes end here

     $this->mapWebRoutes();





    // $this->mapInstallRoutes();

    //$this->mapUpdateRoutes();
  }

  /**
   * Define the "b2b" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapWholesaleRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/wholesale.php'));
  }

  /**
   * Define the "delivery boy" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapDeliveryBoyRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/delivery_boy.php'));
  }

    /**
   * Define the "auction" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAuctionRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/auction.php'));
  }

  /**
   * Define the "seller package" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapSellerPackageRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/seller_package.php'));
  }

  /**
   * Define the "affiliate" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAffiliateRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/affiliate.php'));
  }

  /**
   * Define the "offline payment" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapOfflinePaymentRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/offline_payment.php'));
  }


  /**
   * Define the "offline payment" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapPaytmRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/paytm.php'));
  }

  /**
   * Define the "offline payment" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAfricanPaymentGatewayRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/african_pg.php'));
  }

  /**
   * Define the "refund" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapRefundRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/refund_request.php'));
  }

  /**
   * Define the "club points" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapClubPointsRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/club_points.php'));
  }

  /**
   * Define the "OTP System" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapOtpRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/otp.php'));
  }

  /**
   * Define the "POS System" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapPosRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/pos.php'));
  }

  /**
   * Define the "updating" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapUpdateRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/update.php'));
  }

  /**
   * Define the "installation" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapInstallRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/install.php'));
  }

  /**
   * Define the "web" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapWebRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/web.php'));
  }

  /**
   * Define the "admin" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAdminRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/admin.php'));
  }

  /**
   * Define the "seller" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapSellerRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/seller.php'));
  }

  /**
   * Define the "api" routes for the application.
   *
   * These routes are typically stateless.
   *
   * @return void
   */
  protected function mapApiSellerRoutes()
  {
    Route::prefix('api')
       ->middleware('api')
       ->namespace($this->namespace)
       ->group(base_path('routes/api_seller.php'));
  }

  /**
   * Define the "api" routes for the application.
   *
   * These routes are typically stateless.
   *
   * @return void
   */
  protected function mapApiRoutes()
  {
    Route::prefix('api')
       ->middleware('api')
       ->namespace($this->namespace)
       ->group(base_path('routes/api.php'));
  }









  //For vue application start here

  protected function mapVueRoutes()
  {
    Route::prefix('vue')
       ->middleware('api')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/vue.php'));
  }

  protected function mapWebVueRoutes()
  {
    Route::prefix('vueweb')
       ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/web.php'));
  }



  protected function mapWholesaleVueRoutes()
  {
    Route::prefix('vuewholesale')
       ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/wholesale.php'));
  }

  /**
   * Define the "delivery boy" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapDeliveryBoyVueRoutes()
  {
    Route::prefix('vuedelivery_boy')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/delivery_boy.php'));
  }

    /**
   * Define the "auction" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAuctionVueRoutes()
  {
    Route::prefix('vueauction')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/auction.php'));
  }

  /**
   * Define the "seller package" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapSellerPackageVueRoutes()
  {
    Route::prefix('vueseller_package')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/seller_package.php'));
  }

  /**
   * Define the "affiliate" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAffiliateVueRoutes()
  {
    Route::prefix('vueaffiliate')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/affiliate.php'));
  }

  /**
   * Define the "offline payment" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapOfflinePaymentVueRoutes()
  {
    Route::prefix('vueoffline_payment')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/offline_payment.php'));
  }


  /**
   * Define the "offline payment" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapPaytmVueRoutes()
  {
    Route::prefix('vuepaytm')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/paytm.php'));
  }

  /**
   * Define the "offline payment" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAfricanPaymentGatewayVueRoutes()
  {
    Route::prefix('vueafrican_pg')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/african_pg.php'));
  }

  /**
   * Define the "refund" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapRefundVueRoutes()
  {
    Route::prefix('vuerefund_request')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/refund_request.php'));
  }

  /**
   * Define the "club points" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapClubPointsVueRoutes()
  {
    Route::prefix('vueclub_points')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/club_points.php'));
  }

  /**
   * Define the "OTP System" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapOtpVueRoutes()
  {
    Route::prefix('vueotp')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/otp.php'));
  }

  /**
   * Define the "POS System" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapPosVueRoutes()
  {
    Route::prefix('vuepos')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/pos.php'));
  }

  /**
   * Define the "updating" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapUpdateVueRoutes()
  {
    Route::prefix('vueupdate')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/update.php'));
  }

  /**
   * Define the "installation" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapInstallVueRoutes()
  {
    Route::prefix('vueinstall')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/install.php'));
  }

  /**
   * Define the "web" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */

  /**
   * Define the "admin" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAdminVueRoutes()
  {
    Route::prefix('vueadmin')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/admin.php'));
  }

  /**
   * Define the "seller" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapSellerVueRoutes()
  {
    Route::prefix('vueseller')
        ->middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/seller.php'));
  }

  /**
   * Define the "api" routes for the application.
   *
   * These routes are typically stateless.
   *
   * @return void
   */
  protected function mapApiSellerVueRoutes()
  {
    Route::prefix('vueapi_seller')
       ->middleware('api')
       ->namespace($this->namespace)
       ->group(base_path('routes/VueRoutes/api_seller.php'));
  }

//For vue application end here





  /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(600)->by(optional($request->user())->id ?: $request->ip());
        });
    }

}
