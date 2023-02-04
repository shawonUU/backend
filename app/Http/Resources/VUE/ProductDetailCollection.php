<?php

namespace App\Http\Resources\VUE;

use App\Models\User;
use App\Models\Review;
use App\Models\Attribute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\ResourceCollection;


class ProductDetailCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data) {
                $precision = 2;
                $calculable_price = home_discounted_base_price($data, false);
                $calculable_price = number_format($calculable_price, $precision, '.', '');
                $calculable_price = floatval($calculable_price);
                // $calculable_price = round($calculable_price, 2);
                $photo_paths = get_images_path($data->photos);

                $photos = [];


                if (!empty($photo_paths)) {
                    for ($i = 0; $i < count($photo_paths); $i++) {
                        if ($photo_paths[$i] != "" ) {
                            $item = array();
                            $item['variant'] = "";
                            $item['path'] = $photo_paths[$i];
                            $photos[]= $item;
                        }

                    }

                }

                // $stocks = [];
                foreach ($data->stocks as $stockItem){
                    if($stockItem->image != null && $stockItem->image != ""){
                        $item = array();
                        $item['variant'] = $stockItem->variant;
                        $item['path'] = uploaded_asset($stockItem->image) ;

                        // $item1 = array();
                        // $item1['variant'] = $stockItem->variant;
                        // $item1['path'] = uploaded_asset($stockItem->image) ;
                        // $item1['qty'] = $stockItem->qty;
                        // $photos[]= $item;

                        // $stocks[] = $item1;
                    }
                }

                $brand = [
                    'id'=> 0,
                    'name'=> "",
                    'logo'=> "",
                ];

                if($data->brand != null) {
                    $brand = [
                        'id'=> $data->brand->id,
                        'slug'=> $data->brand->slug,
                        'name'=> $data->brand->getTranslation('name'),
                        'logo'=> uploaded_asset($data->brand->logo),
                    ];
                }

                $stockQty = 0;
                foreach ($data->stocks as $key => $stock) {
                    $stockQty += $stock->qty;
                }
                $vedioLink = '';
                if($data->video_provider == 'youtube' && isset(explode('=', $data->video_link)[1])){
                    $vedioLink = explode('=', $data->video_link)[1];
                }else if($data->video_provider == 'dailymotion' && isset(explode('video/', $data->video_link)[1])){
                    $vedioLink = explode('video/', $data->video_link)[1];
                }else if($data->video_provider == 'vimeo' && isset(explode('vimeo.com/', $data->video_link)[1])){
                    $vedioLink = explode('vimeo.com/', $data->video_link)[1];
                }

                foreach( $data->reviews as $key => $review ){
                    $review->image = uploaded_asset($review->image);
                    $review->user_name =User::find($review->user_id)->name;
                    $review->created = date('d-m-Y', strtotime($review->created_at));
                    $review->updated= date('d-m-Y', strtotime($review->updated_at));
                    $data->reviews[$key]=$review;
                }

                return [
                    'id' => (integer)$data->id,
                    'name' => $data->getTranslation('name'),
                    'home_discounted_price' => $data->home_discounted_price,
                    'home_price' => $data->home_price,
                    // 'stocks' => $stocks,
                    'addon_is_activated' => $data->addon_is_activated,
                    'stocksQty' => $data->stocksQty,
                    'stock_visibility_state' => $data->stock_visibility_state,
                    'digital' => $data->digital,
                    'external_link' => $data->external_link,
                    'min_qty' => $data->min_qty,

                    'added_by' => $data->added_by,
                    'seller_id' => $data->user->id,
                    'shop_id' => $data->added_by == 'admin' ? 0 : $data->user->shop->id,
                    'shop_name' => $data->added_by == 'admin' ? translate('In House Product') : $data->user->shop->name,
                    'shop_logo' => $data->added_by == 'admin' ? uploaded_asset(get_setting('header_logo')) : uploaded_asset($data->user->shop->logo)??"",
                    'photos' => $photos,
                    'thumbnail_image' => uploaded_asset($data->thumbnail_img),
                    'tags' => explode(',', $data->tsag),
                    'price_high_low' => (double)explode('-', home_discounted_base_price($data, false))[0] == (double)explode('-', home_discounted_price($data, false))[1] ? format_price((double)explode('-', home_discounted_price($data, false))[0]) : "From " . format_price((double)explode('-', home_discounted_price($data, false))[0]) . " to " . format_price((double)explode('-', home_discounted_price($data, false))[1]),
                    'choice_options' => $this->convertToChoiceOptions(json_decode($data->choice_options)),
                    'colors' => json_decode($data->colors),
                    'stock_visibility_state' => $data->stock_visibility_state,
                    'stocks' => $stockQty,
                    'has_discount' => home_base_price($data, false) != home_discounted_base_price($data, false),
                    'discount'=> "-".discount_in_percentage($data)."%",
                    'stroked_price' => home_base_price($data),
                    'main_price' => home_discounted_base_price($data),
                    'calculable_price' => $calculable_price,
                    'currency_symbol' => currency_symbol(),
                    'est_shipping_days' =>  $data->est_shipping_days,
                    'wholesalePrices' =>$data->stocks->first()->wholesalePrices,
                    'home_price'      => home_price($data),
                    'home_discounted_price'      => home_discounted_price($data),
                    // 'current_stock' => (integer)$data->stocks->first()->qty,
                    'unit' => $data->unit,
                    'rating' => (double)$data->rating,
                    'rating_count' => (integer)Review::where(['product_id' => $data->id])->count(),
                    'reviews' => $data->reviews->count(),
                    'reviewsDatas' => $data->reviews,
                    'earn_point' => (double)$data->earn_point,
                    'external_link' => $data->external_link,
                    'external_link_btn' => $data->external_link_btn,
                    'description' => $data->getTranslation('description'),
                    'video_link' => $data->video_link != null ?  $data->video_link : null,
                    'video_url' => $vedioLink,
                    'pdf' => $data->video_link != null ?  $data->video_link : null,
                    'pdfLink' =>  uploaded_asset($data->pdf),
                    'brand' => $brand,
                    'link' => route('product', $data->slug)
                ];
            })
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }

    protected function convertToChoiceOptions($data)
    {
        $result = array();
       if($data) {
        foreach ($data as $key => $choice) {
            $item['id'] = $choice->attribute_id;
            $item['name'] = $choice->attribute_id;
            $item['title'] = Attribute::find($choice->attribute_id)->getTranslation('name');
            $item['options'] = $choice->values;
            array_push($result, $item);
        }
       }
        return $result;
    }

    protected function convertPhotos($data)
    {
        $result = array();
        foreach ($data as $key => $item) {
            array_push($result, uploaded_asset($item));
        }
        return $result;
    }
}
