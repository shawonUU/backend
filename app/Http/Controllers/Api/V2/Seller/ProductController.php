<?php

namespace App\Http\Controllers\Api\V2\Seller;

use App\Http\Resources\V2\ReviewCollection;
use App\Http\Resources\V2\Seller\ProductCollection;
use App\Http\Resources\V2\SellerProductDetailsCollection;
use App\Http\Resources\V2\Seller\ProductResource;
use App\Http\Resources\V2\Seller\ProductReviewCollection;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductTax;
use App\Models\Review;
use App\Models\User;
use App\Services\ProductStockService;
use App\Services\ProductService;
use App\Services\ProductTaxService;
use Artisan;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->where('user_id', auth()->user()->id)->paginate(10);
        return new ProductCollection($products);
    }

    public function edit()
    {
        $product = Product::where('user_id', auth()->user()->id)->first();
        return new ProductResource($product);
    }

    public function change_status(Request $request)
    {
        $product = Product::where('user_id', auth()->user()->id)
            ->where('id', $request->id)
            ->update([
                'published' => $request->status
            ]);

        if ($product == 0) {
            return $this->failed(translate('This product is not yours'));
        }
        return ($request->status == 1) ?
            $this->success(translate('Product has been published successfully')) :
            $this->success(translate('Product has been unpublished successfully'));
    }

    public function change_featured_status(Request $request)
    {
        $product = Product::where('user_id', auth()->user()->id)
            ->where('id', $request->id)
            ->update([
                'seller_featured' => $request->featured_status
            ]);

        if ($product == 0) {
          return  $this->failed(translate('This product is not yours'));
        }

        return ($request->featured_status == 1) ?
            $this->success(translate('Product has been featured successfully')) :
            $this->success(translate('Product has been unfeatured successfully'));
    }

    public function duplicate($id)
    {
        $product = Product::findOrFail($id);

        if (auth()->user()->id != $product->user_id) {
            return $this->failed(translate('This product is not yours'));
        }
        if (addon_is_activated('seller_subscription')) {
            if (!seller_package_validity_check(auth()->user()->id)) {
                return $this->failed(translate('Please upgrade your package'));
            }
        }

        if (auth()->user()->id == $product->user_id) {
            $product_new = $product->replicate();
            $product_new->slug = $product_new->slug . '-' . Str::random(5);
            $product_new->save();

            //Store in Product Stock Table
            (new ProductStockService)->product_duplicate_store($product->stocks, $product_new);

            //Store in Product Tax Table
            (new ProductTaxService)->product_duplicate_store($product->taxes, $product_new);

            return $this->success(translate('Product has been duplicated successfully'));
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if (auth()->user()->id != $product->user_id) {
            return $this->failed(translate('This product is not yours'));
        }

        $product->product_translations()->delete();
        $product->stocks()->delete();
        $product->taxes()->delete();

        if (Product::destroy($id)) {
            Cart::where('product_id', $id)->delete();

            return $this->success(translate('Product has been deleted successfully'));

            Artisan::call('view:clear');
            Artisan::call('cache:clear');
        }
    }

    public function product_reviews()
    {
        $reviews = Review::orderBy('id', 'desc')
            ->join('products', 'reviews.product_id', '=', 'products.id')
            ->join('users','reviews.user_id','=','users.id')
            ->where('products.user_id', auth()->user()->id)
            ->select('reviews.id','reviews.rating','reviews.comment','reviews.status','reviews.updated_at','products.name as product_name','users.id as user_id','users.name','users.avatar')
            ->distinct()
            ->paginate(1);

       return new ProductReviewCollection($reviews);
    }

    public function remainingUploads(){

        $remaining_uploads=(max(0, auth()->user()->shop->product_upload_limit - auth()->user()->products()->count()) );
        return response()->json([
            'ramaining_product'=> $remaining_uploads,
        ]);
    }

       public function productstore(Request $request){
        $product= new Product;
        $product->name = $request->name;
        $product->category_id = $request->category_id;
        $product->unit = $request->unit ;
        $product->min_qty = $request->min_qty ;
        $product->unit_price = $request->unit_price ;
        $product->discount = $request->discount ;
        $product->current_stock = $request->current_stock ;
        $product->description = $request->description;
         $product->variant_product=0;
         $product->choice_options='[]';
         $product->colors='[]';
         $product->cash_on_delivery=1;
         $product->low_stock_quantity=1;
         $product->discount_type='ammount';




        $product->added_by='seller';

        $product->user_id=  $request->seller_id;
        $product->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', strtolower($request->name))).'-'.Str::random(5);
         if($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0){
            $product->colors = json_encode($request->colors);
        }
        else {
            $colors = array();
            $product->colors = json_encode($colors);
        }
       $product1= $product->save();



       $product_stock= new ProductStock;
       $product_stock->product_id= $product->id;
       $product_stock->price= $request->unit_price;
       $product_stock->qty= $request->current_stock;
       $product_stock->variant= '';
       $product_stock->sku= '';

       $product_stock->save();

        // return $product1->id;


        //   $product = $this->productService->store($request->except([
        //     '_token', 'sku', 'choice', 'tax_id', 'tax', 'tax_type', 'flash_deal_id', 'flash_discount', 'flash_discount_type'
        // ]));
        // $request->merge(['product_id' => $product->id]);



        return response()->json([
            'result' => true,
            'message' => 'successfully saved'

        ]);

    }
    public function productupdate(Request $request,$id){
         $product = Product::findOrFail($id);
        $product->name = $request->name;
        if($request->category_id!="null"){
             $product->category_id = $request->category_id;
        }
        $product->unit = $request->unit ;
        $product->unit_price = $request->unit_price ;
        $product->current_stock = $request->current_stock ;


         if($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0){
            $product->colors = json_encode($request->colors);
        }
        else {
            $colors = array();
            $product->colors = json_encode($colors);
        }
        if($product->save())
        return response()->json([
            'result' => true,
            'message' => 'successfully saved'

        ]);

    }


   public function seller_product_details($id){
        //  $product=Product::where('id', $id)->first();

        return new SellerProductDetailsCollection(Product::where('id', $id)->get());

    }




}
