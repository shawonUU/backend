<?php

namespace App\Http\Resources\VUE;

use Illuminate\Http\Resources\Json\ResourceCollection;

class WishlistCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                return [
                    'id' => (integer) $data->wishlist_id,
                    'product_id' => $data->id,
                    'name' => $data->name,
                    'digital'=> $data->digital,
                    'slug' => $data->slug,
                    'thumbnail_image' => uploaded_asset($data->thumbnail_img),
                    'base_price' => home_base_price($data, false),
                    'base_discounted_price' => (double) home_discounted_base_price($data, false),
                    'rating' => (double) $data->rating,
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
