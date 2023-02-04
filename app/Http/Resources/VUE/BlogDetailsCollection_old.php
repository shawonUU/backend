<?php

namespace App\Http\Resources\VUE;

use Illuminate\Http\Resources\Json\ResourceCollection;
use \App\Models\Blog;
class BlogDetailsCollection extends ResourceCollection
{
    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'name' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'category'=>$this->category->category_name,
            'meta_img' => uploaded_asset($this->banner),
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
