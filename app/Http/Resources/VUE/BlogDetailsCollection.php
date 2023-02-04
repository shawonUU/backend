<?php

namespace App\Http\Resources\VUE;

use Illuminate\Http\Resources\Json\JsonResource;

class BlogDetailsCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // return "Hello";
        return [
            'id' => $this->id,
            'name' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'category'=>$this->category->category_name,
            'meta_img' => uploaded_asset($this->meta_img),
        ];
    }
}
