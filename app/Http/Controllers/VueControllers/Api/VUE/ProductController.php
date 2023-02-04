<?php

namespace App\Http\Controllers\VueControllers\Api\VUE;

use Cache;
use App\Models\Shop;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Product;
use App\Models\Category;

use App\Models\FlashDeal;
use Illuminate\Http\Request;
use App\Utility\SearchUtility;
use App\Utility\CategoryUtility;
use App\Http\Resources\VUE\ProductCollection;
use App\Http\Resources\VUE\LoadmoreCollection;
use App\Http\Resources\VUE\FlashDealCollection;
use App\Http\Resources\VUE\DigitalProductDetails;
use App\Http\Resources\VUE\ProductMiniCollection;
use App\Http\Resources\VUE\ProductDetailCollection;
use App\Http\Resources\VUE\ShopDetailsCollection;
use App\Http\Resources\VUE\CategoryCollection;
class ProductController extends Controller
{


     public function all_products()
    {
        $products = Product::where('published',1)->get();
        return new ProductMiniCollection($products);
    }

      public function allpostapi()
    {
        return new LoadmoreCollection(Product::where('published',1)->where('auction_start_date',null)->latest()->paginate(6));
    }

        public function all_auction_products()
    {
         $products = \App\Models\Product::latest()->where('published', 1)->where('auction_product', 1);
                    if(get_setting('seller_auction_product') == 0){
                        $products = $products->where('added_by','admin');
                    }
                    $products = $products->where('auction_start_date','<=', strtotime("now"))->where('auction_end_date','>=', strtotime("now"))->get();
        return new ProductMiniCollection($products);
    }

       public function newproducts()
    {
        return new ProductMiniCollection(Product::where('published',1)->where('auction_start_date',null)->latest()->take(12)->get());
    }

    public function index()
    {
        return new ProductMiniCollection(Product::latest()->paginate(10));
    }

    public function show($slug)
    {
        $product=Product::where('slug', $slug)->first();
        if($product->digital==1){
            return new DigitalProductDetails(Product::where('slug', $slug)->get());
        }
        else{
            return new ProductDetailCollection(Product::where('slug', $slug)->get());
        }

    }

    public function admin()
    {
        return new ProductCollection(Product::where('added_by', 'admin')->latest()->paginate(10));
    }

    public function seller($slug, Request $request)
    {
        $shop = Shop::where('slug',$slug)->first();
        $products = Product::where('added_by', 'seller')->where('user_id', $shop->user_id);
        $sellerRroductCount = Product::where('added_by', 'seller')->where('user_id', $shop->user_id)->count();
        if ($request->name != "" || $request->name != null) {
           return $products = $products->where('name', 'like', '%' . $request->name . '%');
        }
        $products->where('published', 1);
        $shopDetails =  new ShopDetailsCollection($shop);
        $product = new ProductMiniCollection($products->latest()->paginate(18));
        return response()->json([$product,$shopDetails,$sellerRroductCount]);
    }

    public function category($slug, Request $request)
    {
        $category = Category::where('slug',$slug)->first();
        $categoryId = $category->id;
        $category_ids = CategoryUtility::children_ids($categoryId );
        $category_ids[] = $categoryId ;
        $products = Product::whereIn('category_id', $category_ids);
        if ($request->name != "" || $request->name != null) {
            return  $products = $products->where('name', 'like', '%' . $request->name . '%');
        }
        $products->where('published', 1);
        $cat_product= new ProductMiniCollection(filter_products($products)->latest()->paginate(100));

        return response()->json([$cat_product,$category]);
    }


    public function brand($slug, Request $request)
    {
        $brands = Brand::where('slug',$slug)->first();
        $products = Product::where('brand_id', $brands->id)->physical();
        if ($request->name != "" || $request->name != null) {
            $products = $products->where('name', 'like', '%' . $request->name . '%');
        }

        $products = new ProductMiniCollection(filter_products($products)->latest()->paginate(30));
        $brandName = $brands->name;
        return response()->json([$products,$brandName]);
    }

    public function todaysDeal()
    {
        return Cache::remember('app.todays_deal', 86400, function(){
            $products = Product::where('todays_deal', 1)->physical();
            return new ProductMiniCollection(filter_products($products)->limit(20)->latest()->get());
        });
    }

    public function flashDeal()
    {
        return Cache::remember('app.flash_deals', 86400, function(){
            $flash_deals = FlashDeal::where('status', 1)->where('featured', 1)->where('start_date', '<=', strtotime(date('d-m-Y')))->where('end_date', '>=', strtotime(date('d-m-Y')))->get();
            return new FlashDealCollection($flash_deals);
        });
    }

    public function featured()
    {
        $products = Product::where('featured', 1)->physical();
        return new ProductMiniCollection(filter_products($products)->latest()->paginate(10));
    }

    public function bestSeller()
    {
        return Cache::remember('app.best_selling_products', 86400, function(){
            $products = Product::orderBy('num_of_sale', 'desc')->physical();
            return new ProductMiniCollection(filter_products($products)->limit(20)->get());
        });
    }

    public function related($id)
    {
        return Cache::remember("app.related_products-$id", 86400, function() use ($id){
            $product = Product::find($id);
            $products = Product::where('category_id', $product->category_id)->where('id', '!=', $id)->physical();
            return new ProductMiniCollection(filter_products($products)->limit(10)->get());
        });
    }

    public function topFromSeller($id)
    {
        return Cache::remember("app.top_from_this_seller_products-$id", 86400, function() use ($id){
            $product = Product::find($id);
            $products = Product::where('user_id', $product->user_id)->orderBy('num_of_sale', 'desc')->physical();

            return new ProductMiniCollection(filter_products($products)->limit(10)->get());
        });
    }


    public function search(Request $request)
    {
        $category_ids = [];
        $brand_ids = [];

        if ($request->categories != null && $request->categories != "") {
            $category_ids = explode(',', $request->categories);
        }

        if ($request->brands != null && $request->brands != "") {
            $brand_ids = explode(',', $request->brands);
        }

        $sort_by = $request->sort_key;
        $name = $request->name;
        $min = $request->min;
        $max = $request->max;


        $products = Product::query();

        $products->where('published', 1)->physical();

        if (!empty($brand_ids)) {
            $products->whereIn('brand_id', $brand_ids);
        }

        if (!empty($category_ids)) {
            $n_cid = [];
            foreach ($category_ids as $cid) {
                $n_cid = array_merge($n_cid, CategoryUtility::children_ids($cid));
            }

            if (!empty($n_cid)) {
                $category_ids = array_merge($category_ids, $n_cid);
            }

            $products->whereIn('category_id', $category_ids);
        }

        if ($name != null && $name != "") {
            $products->where(function ($query) use ($name) {
                foreach (explode(' ', trim($name)) as $word) {
                    $query->where('name', 'like', '%'.$word.'%')->orWhere('tags', 'like', '%'.$word.'%')->orWhereHas('product_translations', function($query) use ($word){
                        $query->where('name', 'like', '%'.$word.'%');
                    });
                }
            });
            SearchUtility::store($name);
        }

        if ($min != null && $min != "" && is_numeric($min)) {
            $products->where('unit_price', '>=', $min);
        }

        if ($max != null && $max != "" && is_numeric($max)) {
            $products->where('unit_price', '<=', $max);
        }

        switch ($sort_by) {
            case 'price_low_to_high':
                $products->orderBy('unit_price', 'asc');
                break;

            case 'price_high_to_low':
                $products->orderBy('unit_price', 'desc');
                break;

            case 'new_arrival':
                $products->orderBy('created_at', 'desc');
                break;

            case 'popularity':
                $products->orderBy('num_of_sale', 'desc');
                break;

            case 'top_rated':
                $products->orderBy('rating', 'desc');
                break;

            default:
                $products->orderBy('created_at', 'desc');
                break;
        }

        return new ProductMiniCollection(filter_products($products)->paginate(30));
    }

    public function variantPrice(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $str = '';
        $tax = 0;

        if ($request->has('color') && $request->color != "") {
            $str = Color::where('code', '#' . $request->color)->first()->name;
        }

        $var_str = str_replace(',', '-', $request->variants);
        $var_str = str_replace(' ', '', $var_str);

        if ($var_str != "") {
            $temp_str = $str == "" ? $var_str : '-' . $var_str;
            $str .= $temp_str;
        }


        $product_stock = $product->stocks->where('variant', $str)->first();
        $price = $product_stock->price;
        $stockQuantity = $product_stock->qty;


        //discount calculation
        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        $price += $tax;

        return response()->json([
            'product_id' => $product->id,
            'variant' => $str,
            'price' => (double)convert_price($price),
            'price_string' => format_price(convert_price($price)),
            'stock' => intval($stockQuantity),
            'image' => $product_stock->image == null ? "" : uploaded_asset($product_stock->image)
        ]);
    }

    public function home()
    {
        return new ProductCollection(Product::inRandomOrder()->physical()->take(50)->get());
    }

    public function homeCategoriesProduct(){
        get_setting('home_categories');
        $home_categories = json_decode(get_setting('home_categories'));
        $products = [];
        $categoryInfo = [];
        foreach($home_categories as $key => $value){
            $category = Category::find($value);
            $categoryInfo[$key] = $category;
            $products[$key] = new ProductCollection(get_cached_products($category->id));
        }

        return response()->json([$products,$categoryInfo]);

    }
}
