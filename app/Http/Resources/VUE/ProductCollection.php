<?php

namespace App\Http\Resources\VUE;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
               'data' => $this->collection->map(function($data) {
                $photos = explode(',', $data->photos);
                $photoLink = [];
                foreach($photos as $photo){
                    $photoLink[] = uploaded_asset($photo);
                }
                return [
                    'id' => $data->id,
                    'name' => $data->getTranslation('name'),
                    'digital'=>$data->digital,
                    'slug' => $data->slug,
                    'photos' => $photoLink,
                    'thumbnail_image' => uploaded_asset($data->thumbnail_img),
                    'base_price' => (double) home_base_price($data, false),
                    'base_discounted_price' => (double) home_discounted_base_price($data, false),
                    'todays_deal' => (integer) $data->todays_deal,
                    'featured' =>(integer) $data->featured,
                    'unit' => $data->unit,
                    'discount' => (double)$data->discount,
                    'discount_in_percentage'=> "-".discount_in_percentage($data)."%",
                    'digital' => (integer) $data->digital,
                    'discount_type' => $data->discount_type,
                    'rating' => (double) $data->rating,
                    'earn_point' => (double)$data->earn_point,
                    'sales' => (integer) $data->num_of_sale,
                    'wholesale_product' => (integer)$data->wholesale_product,
                    'links' => [
                        'details' => route('products.show', $data->id),
                        'reviews' => route('api.reviews.index', $data->id),
                        'related' => route('products.related', $data->id),
                        // 'top_from_seller' => route('products.topFromSeller', $data->id)
                    ]

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
}