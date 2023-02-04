<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VueHelperController extends Controller
{
    public function uploadAsset(Request $request){
        return uploaded_asset($request->banner);
    }
}
