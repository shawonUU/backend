<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SellerProductDetailsCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                    

                return [
                    'id' => $data->id,
                    'name' => $data->getTranslation('name'),
                    'discount'=>$data->discount,
                    'unit_price'=>$data->unit_price,
                    'current_stock'=>$data->current_stock,
                    'digital' => (integer) $data->digital,
                    'description' => $data->description,
                
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
