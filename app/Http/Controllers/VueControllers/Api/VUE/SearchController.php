<?php

namespace App\Http\Controllers\VueControllers\Api\VUE;

use Illuminate\Http\Request;
use App\Models\Search;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Shop;
use App\Models\Attribute;
use App\Models\AttributeCategory;
use App\Utility\CategoryUtility;
use App\Http\Resources\VUE\ProductCollection;
use App\Http\Resources\VUE\LoadmoreCollection;
use App\Http\Resources\VUE\FlashDealCollection;
use App\Http\Resources\VUE\DigitalProductDetails;
use App\Http\Resources\VUE\ProductMiniCollection;
use App\Http\Resources\VUE\ProductDetailCollection;
use App\Http\Resources\VUE\ShopDetailsCollection;
use App\Http\Resources\VUE\CategoryCollection;
use App\Http\Resources\VUE\ShopCollection;

use Illuminate\Support\Facades\Route;

class SearchController extends Controller
{
    public function index(Request $request, $category_id = null, $brand_id = null)
    {
        // return $request;

        $query = $request->keyword;
        $sort_by = $request->sort_by;
        $min_price = $request->min_price;
        $max_price = $request->max_price;
        $seller_id = $request->seller_id;
        $attributes = null;
        $selected_attribute_values = array();
        $colors = Color::all();
        $selected_color = null;

        $conditions = ['published' => 1];

        if ($brand_id != null) {
            $conditions = array_merge($conditions, ['brand_id' => $brand_id]);
        } elseif ($request->brand != null) {
            $brand_id = (Brand::where('slug', $request->brand)->first() != null) ? Brand::where('slug', $request->brand)->first()->id : null;
            $conditions = array_merge($conditions, ['brand_id' => $brand_id]);
            $attributes = Attribute::all();
        }


        // if ($seller_id != null) {
        //     $conditions = array_merge($conditions, ['user_id' => Seller::findOrFail($seller_id)->user->id]);
        // }

        $products = Product::where($conditions);

        if ($category_id != null) {
            $category_ids = CategoryUtility::children_ids($category_id);
            $category_ids[] = $category_id;

            $products->whereIn('category_id', $category_ids);

            $attribute_ids = AttributeCategory::whereIn('category_id', $category_ids)->pluck('attribute_id')->toArray();
            $attributes = Attribute::whereIn('id', $attribute_ids)->get();

        } else {
            // if ($query != null) {
            //     foreach (explode(' ', trim($query)) as $word) {
            //         $ids = Category::where('name', 'like', '%'.$word.'%')->pluck('id')->toArray();
            //         if (count($ids) > 0) {
            //             foreach ($ids as $id) {
            //                 $category_ids[] = $id;
            //                 array_merge($category_ids, CategoryUtility::children_ids($id));
            //             }
            //         }
            //     }
            //     $attribute_ids = AttributeCategory::whereIn('category_id', $category_ids)->pluck('attribute_id')->toArray();
            //     $attributes = Attribute::whereIn('id', $attribute_ids)->get();
            // }
        }

        if ($min_price != null && $max_price != null) {
            $products->where('unit_price', '>=', $min_price)->where('unit_price', '<=', $max_price);
        }

        if ($query != null) {
            // $searchController = new SearchController;
            // $searchController->store($request);
            $this->store($request);

            $products->where(function ($q) use ($query) {
                foreach (explode(' ', trim($query)) as $word) {
                    $q->where('name', 'like', '%' . $word . '%')
                        ->orWhere('tags', 'like', '%' . $word . '%')
                        ;
                }
            });
        }

        switch ($sort_by) {
            case 'newest':
                $products->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $products->orderBy('created_at', 'asc');
                break;
            case 'price-asc':
                $products->orderBy('unit_price', 'asc');
                break;
            case 'price-desc':
                $products->orderBy('unit_price', 'desc');
                break;
            default:
                $products->orderBy('id', 'desc');
                break;
        }

        if ($request->has('color') && $request->color != null) {
            $str = '"' . $request->color . '"';
            $products->where('colors', 'like', '%' . $str . '%');
            $selected_color = $request->color;
        }

        if ($request->has('selected_attribute_values') && $request->selected_attribute_values != null) {
            $selected_attribute_values = $request->selected_attribute_values;
            $products->where(function ($query) use($selected_attribute_values) {
                foreach ($selected_attribute_values as $key => $value) {
                    $str = '"' . $value . '"';

                    $query->orWhere('choice_options', 'like', '%' . $str . '%');
                }
            });
        }

       $productsCount = $products->count();
       $products = filter_products($products)->with('taxes')->paginate(24)->appends(request()->query());
       $products = new ProductCollection($products);


        $color_filter_activation = get_setting('color_filter_activation');
        $category_name = null;
        $auto_description = null;
        $category = [];
        $parentCategory = [];
        if($category_id){
             $category_name = \App\Models\Category::find($category_id)->getTranslation('name');
             $auto_description= \App\Models\Category::find($category_id)->auto_cat_description;
             $category = \App\Models\Category::find($category_id);
             if($category->parent_id != 0){
                $parentCategory = \App\Models\Category::find($category->parent_id);
             }
        }
        $addon_is_activated = addon_is_activated('club_point');
        $currentRouteName = Route::currentRouteName();
        $cetegoryLevelZero = \App\Models\Category::where('level', 0)->get();

        $ids = \App\Utility\CategoryUtility::get_immediate_children_ids($category_id);

        $categories = \App\Models\Category::whereIn('id', $ids)->get();

       $brands = \App\Models\Brand::select('id','slug','name as text')->get();
        if($attributes){
            foreach($attributes as $key => $attribute){
                $attributes[$key]->values =  $attribute->attribute_values;
            }
        }


        //return view('frontend.product_listing', compact('products', 'query', 'category_id', 'brand_id', 'sort_by', 'seller_id', 'min_price', 'max_price', 'attributes', 'selected_attribute_values', 'colors', 'selected_color'));
        return response()->json([
            'products' => $products,
            'query' => $query,
            'category_id' => $category_id,
            'brand_id' => $brand_id,
            'sort_by' => $sort_by,
            'seller_id' => $seller_id,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'attributes' => $attributes,
            'selected_attribute_values' => $selected_attribute_values,
            'colors' => $colors,
            'selected_color' => $selected_color,
            'color_filter_activation' => $color_filter_activation,
            'category_name' => $category_name,
            'addon_is_activated' => $addon_is_activated,
            'currentRouteName' => $currentRouteName,
            'auto_description' =>$auto_description,
            'cetegoryLevelZero' => $cetegoryLevelZero,
            'category' => $category,
            'parentCategory' => $parentCategory,
            'categories' => $categories,
            'brands' => $brands,
            'productsCount'=>$productsCount
        ]);

    }

    public function listing(Request $request)
    {
        return $this->index($request);
    }

    // public function listingByCategory(Request $request)
    // {

    //     $category_slug = $request->category_slug;
    //     $brand_slug = $request->selectedBrand;

    //     $category_id = null;
    //     $brand_id = null;
    //     $category = Category::where('slug', $category_slug)->first();
    //     $brand = Brand::where('slug', $brand_slug)->first();

    //     if($category){$category_id = $category->id;}
    //     if($brand){$brand_id = $brand->id;}

    //     return $this->index($request, $category_id, $brand_id);
    // }

    public function listingByCategory(Request $request, $category_slug)
    {
        $category = Category::where('slug', $category_slug)->first();
        if ($category != null) {
            return $this->index($request, $category->id);
        }
        abort(404);
    }

    public function listingByBrand(Request $request, $brand_slug)
    {
        $brand = Brand::where('slug', $brand_slug)->first();

        if ($brand != null) {
            return $this->index($request, null, $brand->id);
        }

        abort(404);
    }

    //Suggestional Search
    public function ajax_search(Request $request)
    {
        // return get_setting('vendor_system_activation');
        $keywords = array();
        $query = $request->search;
        $products = Product::where('published', 1)->where('tags', 'like', '%' . $query . '%')->get();
        foreach ($products as $key => $product) {
            foreach (explode(',', $product->tags) as $key => $tag) {
                if (stripos($tag, $query) !== false) {
                    if (sizeof($keywords) > 5) {
                        break;
                    } else {
                        if (!in_array(strtolower($tag), $keywords)) {
                            array_push($keywords, strtolower($tag));
                        }
                    }
                }
            }
        }

        $products = filter_products(Product::query());

        $products = $products->where('published', 1)
            ->where(function ($q) use ($query) {
                foreach (explode(' ', trim($query)) as $word) {
                    $q->where('name', 'like', '%' . $word . '%')
                        ->orWhere('tags', 'like', '%' . $word . '%')
                        ->orWhereHas('product_translations', function ($q) use ($word) {
                            $q->where('name', 'like', '%' . $word . '%');
                        })
                        ->orWhereHas('stocks', function ($q) use ($word) {
                            $q->where('sku', 'like', '%' . $word . '%');
                        });
                }
            })
            ->limit(3)
            ->get();

        $products = new ProductCollection($products);

        $categories = Category::where('name', 'like', '%' . $query . '%')->get()->take(3);
        $categories = new CategoryCollection($categories);

        $shops = Shop::whereIn('user_id', verified_sellers_id())->where('name', 'like', '%' . $query . '%')->get()->take(3);
        $shops = new ShopCollection($shops);
        $vendorSystemActivation = get_setting('vendor_system_activation');
        if (sizeof($keywords) > 0 || sizeof($categories) > 0 || sizeof($products) > 0 || sizeof($shops) > 0) {
            // return view('frontend.partials.search_content', compact('products', 'categories', 'keywords', 'shops'));
            return response()->json(['products' => $products, 'categories' => $categories, 'keywords' => $keywords, 'shops' => $shops, 'vendorSystemActivation' => $vendorSystemActivation]);
        }
        return '0';
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $search = Search::where('query', $request->keyword)->first();
        if ($search != null) {
            $search->count = $search->count + 1;
            $search->save();
        } else {
            $search = new Search;
            $search->query = $request->keyword;
            $search->save();
        }
    }
}
