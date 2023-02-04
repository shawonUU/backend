<?php

namespace App\Http\Resources\VUE;

use Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class AuctuionDetails extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $photos = explode(',', $this->photos);
        $photosUrl = [];
        foreach($photos  as $photo){
            $photosUrl[] = uploaded_asset($photo);
        }
        $highest_bid = $this->bids->max('amount');
        $biddingAccessCheck = "";
        if($this->auction_end_date >= strtotime("now")){
            if(Auth::check() && $this->user_id == Auth::user()->id){
                 $biddingAccessCheck = 'own_product';
            }else{
                if(Auth::check() && Auth::user()->product_bids->where('product_id',$this->id)->first() != null){
                  $biddingAccessCheck = 'change_bid';
                }else{
                  $biddingAccessCheck = 'place_bid';
                }
            }
        }
        $vedioLink = '';
        if($this->video_provider == 'youtube' && isset(explode('=', $this->video_link)[1])){
            $vedioLink = explode('=', $this->video_link)[1];
        }else if($this->video_provider == 'dailymotion' && isset(explode('video/', $this->video_link)[1])){
            $vedioLink = explode('video/', $this->video_link)[1];
        }else if($this->video_provider == 'vimeo' && isset(explode('vimeo.com/', $this->video_link)[1])){
            $vedioLink = explode('vimeo.com/', $this->video_link)[1];
        }

        $reviewUserAvatar = [];
        foreach($this->reviews as $key=>$review){
            if($review->user->avatar_original !=null){
                $reviewUserAvatar[]= uploaded_asset($review->user->avatar_original);
            }else{
                $reviewUserAvatar='assets/img/placeholder.jpg';
            }

        }
        $commentable = '';
        if(Auth::check()){
                 $commentable = false;
            foreach($this->orderDetails as $key => $orderDetail){
                if($orderDetail->order != null && $orderDetail->order->user_id == Auth::user()->id &&
                $orderDetail->delivery_status == 'delivered' &&
                \App\Models\Review::where('user_id', Auth::user()->id)->where('product_id', $this->id)->first() == null){
                    $commentable = true;
                }
            }
        }

        return [
            'name' => $this->name,
            'est_shipping_day' => $this->est_shipping_day,
            'id' => (integer)$this->id,
            'name' => $this->getTranslation('name'),
            'name' => $this->getTranslation('name'),
            'added_by' => $this->added_by,
            'seller_id' => $this->user->id,
            'shop_id' => $this->added_by == 'admin' ? 0 : $this->user->shop->id,
            'shop_name' => $this->added_by == 'admin' ? translate('In House Product') : $this->user->shop->name,
            'shop_slug' => $this->user->shop->slug,
            'shop_logo' => $this->added_by == 'admin' ? uploaded_asset(get_setting('header_logo')) : uploaded_asset($this->user->shop->logo)??"",
            'photos' =>  $photosUrl,
            'thumbnail_image' => uploaded_asset($this->thumbnail_img),
            'auction_end_date' => $this->auction_end_date,
            'starting_bid' => $this->starting_bid,
            'unit' => $this->unit,
            'highest_bid' =>  $highest_bid,
            'min_bid_amount' => $highest_bid != null ? $highest_bid+1 : $this->starting_bid,
            'video_link' => $this->video_link,
            'video_url' => $vedioLink,
            'description'=>$this->description,
            'pdf' => $this->pdf,
            'auction_product'=> $this->auction_product,
            'reviewCount' => count($this->reviews),
            'commentable' => $commentable,
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
