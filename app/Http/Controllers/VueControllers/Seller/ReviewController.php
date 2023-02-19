<?php

namespace App\Http\Controllers\VueControllers\Seller;

use DB;
use Auth;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = DB::table('reviews')
                    ->orderBy('id', 'desc')
                    ->join('products', 'reviews.product_id', '=', 'products.id')
                    ->where('products.user_id', Auth::user()->id)
                    ->select('reviews.id','products.id as product_id','reviews.user_id')
                    ->distinct()
                    ->paginate(9);

        $review = [];
        foreach ($reviews as $key => $value) {
            $review[$key] = \App\Models\Review::find($value->id);
            $review[$key]->viewed = 1;
            $review[$key]->product = Product::where('id',$value->product_id)->select('name','slug','digital')->first();
            $review[$key]->user = User::where('id',$value->user_id)->first('name');
        }
        return response()->json(['reviews'=>$review]);
        // return view('seller.reviews', compact('reviews'));
    }

}
