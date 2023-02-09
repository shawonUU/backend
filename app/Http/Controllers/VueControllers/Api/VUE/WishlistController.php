<?php

namespace App\Http\Controllers\VueControllers\Api\VUE;

use App\Http\Resources\VUE\WishlistCollection;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{

    public function index()
    {
        $wistlistProduct = Wishlist::join('products', 'products.id', '=', 'wishlists.product_id')
        ->where('wishlists.user_id', auth()->user()->id)
        ->select('wishlists.id as wishlist_id','wishlists.product_id', 'products.*')
        ->latest()->get();
        $wishlist  = new WishlistCollection($wistlistProduct);
        $nowishlist_image =  asset('public/assets/img/nothing.svg');//https://localhost/backend/public/assets/img/nothing.svg
        return ['wishlist' => $wishlist, 'nowishlist_image' => $nowishlist_image];


        // $existing_product_ids = Product::whereIn('id', $product_ids)->pluck("id")->toArray();
        // $existing_products = Product::whereIn('id', $product_ids)->get()->toArray();

        //  $query = Wishlist::query();
        // $wistlistProduct =  $query->where('user_id', auth()->user()->id)->whereIn("product_id", $existing_product_ids)->latest()->get();
        //  $wistlistProduct;
        // return new WishlistCollection($query->latest()->get());
    }

    public function store(Request $request)
    {
        Wishlist::updateOrCreate(
            ['user_id' => $request->user_id, 'product_id' => $request->product_id]
        );
        return response()->json(['message' => translate('Product is successfully added to your wishlist')], 201);
    }

    public function destroy($id)
    {
        try {
            Wishlist::destroy($id);
            return response()->json(['result' => true, 'message' => translate('Product is successfully removed from your wishlist')], 200);
        } catch (\Exception $e) {
            return response()->json(['result' => false, 'message' => $e->getMessage()], 200);
        }

    }

    public function add(Request $request)
    {
        $product = Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id])->count();
        if ($product > 0) {
            return response()->json([
                'message' => translate('Product present in wishlist'),
                'is_in_wishlist' => true,
                'product_id' => (integer)$request->product_id,
                'wishlist_id' => (integer)Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id])->first()->id
            ], 200);
        } else {
            Wishlist::create(
                ['user_id' =>auth()->user()->id, 'product_id' => $request->product_id]
            );

            return response()->json([
                'message' => translate('Product added to wishlist'),
                'is_in_wishlist' => true,
                'product_id' => (integer)$request->product_id,
                'wishlist_id' => (integer)Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id])->first()->id
            ], 200);
        }

    }

    public function remove(Request $request)
    {
        $product = Wishlist::where(['product_id' => $request->product_id, 'user_id' =>  auth()->user()->id])->count();
        if ($product == 0) {
            return response()->json([
                'message' => translate('Product in not in wishlist'),
                'is_in_wishlist' => false,
                'product_id' => (integer)$request->product_id,
                'wishlist_id' => 0
            ], 200);
        } else {
            Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id])->delete();

            return response()->json([
                'message' => translate('Product is removed from wishlist'),
                'is_in_wishlist' => false,
                'product_id' => (integer)$request->product_id,
                'wishlist_id' => 0
            ], 200);
        }
    }

    public function isProductInWishlist(Request $request)
    {
        $product = Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id])->count();
        if ($product > 0)
            return response()->json([
                'message' => translate('Product present in wishlist'),
                'is_in_wishlist' => true,
                'product_id' => (integer)$request->product_id,
                'wishlist_id' => (integer)Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id])->first()->id
            ], 200);

        return response()->json([
            'message' => translate('Product is not present in wishlist'),
            'is_in_wishlist' => false,
            'product_id' => (integer)$request->product_id,
            'wishlist_id' => 0
        ], 200);
    }
}
