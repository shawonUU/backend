<?php

namespace App\Http\Controllers\Api\VUE;

use App\Http\Resources\VUE\ProductCollection;
use App\Http\Resources\VUE\ProductMiniCollection;
use App\Http\Resources\VUE\ShopCollection;
use App\Http\Resources\VUE\ShopDetailsCollection;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use App\Utility\SearchUtility;
use Cache;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $shop_r=array(112,181,184,186,187,190,195,196,197,198,201,203,204,207,212,213,215,216);
        $shop_query = Shop::query();
        $shop_query = $shop_query ->wherein('id',$shop_r)->get();

        if ($request->name != null && $request->name != "") {
            $shop_query->where("name", 'like', "%{$request->name}%");
            SearchUtility::store($request->name);
        }

        return new ShopCollection($shop_query->whereIn('user_id', verified_sellers_id()));

        //remove this , this is for testing
        //return new ShopCollection($shop_query->paginate(10));
    }

    public function info($id)
    {
        return new ShopDetailsCollection(Shop::where('id', $id)->first());
    }

    public function shopOfUser($id)
    {
        return new ShopCollection(Shop::where('user_id', $id)->get());
    }

    public function allProducts($id)
    {
        $shop = Shop::findOrFail($id);
        return new ProductCollection(Product::where('user_id', $shop->user_id)->where('published',1)->latest()->paginate(10));
    }

    public function topSellingProducts($id)
    {
        $shop = Shop::findOrFail($id);

        return Cache::remember("app.top_selling_products-$id", 86400, function () use ($shop){
            return new ProductMiniCollection(Product::where('user_id', $shop->user_id)->where('published',1)->orderBy('num_of_sale', 'desc')->limit(10)->get());
        });
    }

    public function featuredProducts($id)
    {
        $shop = Shop::findOrFail($id);

        return Cache::remember("app.featured_products-$id", 86400, function () use ($shop){
            return new ProductMiniCollection(Product::where(['user_id' => $shop->user_id, 'featured' => 1])->where('published',1)->latest()->limit(10)->get());
        });
    }

    public function newProducts($id)
    {
        $shop = Shop::findOrFail($id);

        return Cache::remember("app.new_products-$id", 86400, function () use ($shop){
            return new ProductMiniCollection(Product::where('user_id', $shop->user_id)->where('published',1)->orderBy('created_at', 'desc')->limit(10)->get());
        });
    }

    public function brands($id)
    {

    }
}
