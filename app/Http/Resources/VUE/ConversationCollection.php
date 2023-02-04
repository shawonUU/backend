<?php

namespace App\Http\Resources\VUE;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\VUE\UserCollection;
use App\Models\User;

class ConversationCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                return [
                    'id' => $data->id,
                    // 'sender' => new UserCollection((user::find($data->sender_id))??new user),
                    // 'receiver' => new UserCollection((user::find($data->receiver_id))??new user),
                    'sender_id' => intval($data->sender_id),
                    'receiver_id' => intval($data->receiver_id) ,
                    'receiver_type'=> $data->receiver->user_type,
                    'receiverData' => $data->receiverData,
                    'senderData' => $data->senderData,
                    'messageData' => $data->messageData,
                    'shop_id' => $data->receiver->user_type == 'admin' ? 0 : $data->receiver->shop->id,
                    'shop_name' => $data->receiver->user_type == 'admin' ? 'In House Product' : $data->receiver->shop->name,
                    'shop_logo' => $data->receiver->user_type == 'admin' ? uploaded_asset(get_setting('header_logo'))  : uploaded_asset($data->receiver->shop->logo),
                    'title'=> $data->title,
                    'sender_viewed'=> intval($data->sender_viewed),
                    'receiver_viewed'=> intval($data->receiver_viewed),
                    'date'=> $data->updated_at,
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
