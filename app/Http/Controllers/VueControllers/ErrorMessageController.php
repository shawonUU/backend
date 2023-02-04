<?php

namespace App\Http\Controllers\VueControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ErrorMessageController extends Controller
{
    public static function middlewareErrorMessage(Request $request){
        return response()->json([$request->message], 401);
    }
}
