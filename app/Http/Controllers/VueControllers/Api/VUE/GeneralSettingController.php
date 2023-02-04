<?php

namespace App\Http\Controllers\VueControllers\Api\VUE;

use App\Http\Resources\VUE\GeneralSettingCollection;
use App\Models\GeneralSetting;

class GeneralSettingController extends Controller
{
    public function index()
    {
        return new GeneralSettingCollection(GeneralSetting::all());
    }
}
