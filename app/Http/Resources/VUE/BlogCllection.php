<?php

namespace App\Http\Resources\VUE;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BlogCllection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                return [
                    'id' => $data->id,
                    'name' => $data->title,
                    'slug' => $data->slug,
                    'short_description' => $data->short_description,
                    'category'=>$data->category->category_name,
                    'banner' => uploaded_asset($data->banner),
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
