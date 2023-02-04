<?php

namespace App\Http\Controllers\Api\VUE;

use App\Http\Resources\VUE\BrandCollection;
use App\Models\Brand;
use Illuminate\Http\Request;
use App\Utility\SearchUtility;
use Cache;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brand_r=array(292,29,303,304,308,312,25,313,315,322,328,346,760,823,824,833);
        $brand_query = Brand::query();
        $brand_query =  $brand_query->wherein('id',$brand_r)->get();
        if($request->name != "" || $request->name != null){
            $brand_query->where('name', 'like', '%'.$request->name.'%');
            SearchUtility::store($request->name);
        }
        return new BrandCollection($brand_query);
    }

    public function top()
    {
        return Cache::remember('app.top_brands', 86400, function(){
            return new BrandCollection(Brand::where('top', 1)->get());
        });
    }
}
