<?php

namespace App\Http\Resources\VUE;

use Illuminate\Http\Resources\Json\ResourceCollection;
use \App\Models\Product;
class ProductMiniDetailsCollection extends ResourceCollection
{
    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'name' => $this->title,
            'slug' => $this->slug,
            'thumbnail_image' => uploaded_asset($this->thumbnail_image),
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
