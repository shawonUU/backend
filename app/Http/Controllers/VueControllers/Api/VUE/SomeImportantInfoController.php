<?php

namespace App\Http\Controllers\VueControllers\Api\VUE;

use App\Models\Blog;
use App\Models\Shop;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\VueControllers\Controller;
use App\Http\Resources\VUE\BlogCllection;
use App\Http\Resources\VUE\ShopCollection;
use App\Http\Resources\VUE\BrandCollection;
use App\Http\Resources\VUE\BlogDetailsCollection;
use App\Http\Resources\VUE\ProductMiniCollection;

class SomeImportantInfoController extends Controller
{
    public function getImportantPages(){

        if( get_setting('widget_one_labels',null,App::getLocale()) !=  null ){
            $pages = json_decode( get_setting('widget_one_labels',null,App::getLocale()), true);
            $links =json_decode( get_setting('widget_one_links'), true);
        }
         $title = get_setting('widget_one',null,App::getLocale());
        return response()->json(['title'=>  $title, 'pages'=>$pages,'links'=>$links]);

    }
    public function getNavCategory(){
        $navCategoryName = json_decode(get_setting('header_menu_labels'), true);
        $navCategoryLinks = json_decode( get_setting('header_menu_links'), true);
        return response()->json(['name'=>$navCategoryName, 'links'=>$navCategoryLinks]);
    }

    public function getBlog(){
        $blogs = Blog::where('status', 1)->orderBy('created_at', 'desc')->paginate(12);
        // return $blogs;
        return new BlogCllection($blogs);
    }

    public function blog_details($slug){
        $blogs=Blog::where('slug',$slug)->first();
        return new BlogDetailsCollection($blogs);
    }

    public function allBrands(){
         $brands = Brand::all();
         return new BrandCollection($brands);
    }

    public function allShops(){
        $shops = Shop::whereIn('user_id', verified_sellers_id())
        ->where('user_id','!=',null)
        ->paginate(20);
        return new ShopCollection($shops);
   }

   public function cheakFlashDeal(){
         $flash_deal = \App\Models\FlashDeal::where('status', 1)->where('featured', 1)->first();
         $flashDealIs = '';
        if($flash_deal != null && strtotime(date('Y-m-d H:i:s')) >= $flash_deal->start_date && strtotime(date('Y-m-d H:i:s')) <= $flash_deal->end_date){
            $flashDealIs=1;
        }else{
            $flashDealIs=0;
        }

        return $flashDealIs;
   }

   public function flashDealProduct(){
    $flash_deal = \App\Models\FlashDeal::where('status', 1)->where('featured', 1)->first();
    $flashDealProduct = [];
   if($flash_deal != null && strtotime(date('Y-m-d H:i:s')) >= $flash_deal->start_date && strtotime(date('Y-m-d H:i:s')) <= $flash_deal->end_date)
   {
        foreach ($flash_deal->flash_deal_products->take(8) as $key => $flash_deal_product){
           $flashDealProduct[] = \App\Models\Product::find($flash_deal_product->product_id);
        }
   }
   return new ProductMiniCollection($flashDealProduct);
   }

}
