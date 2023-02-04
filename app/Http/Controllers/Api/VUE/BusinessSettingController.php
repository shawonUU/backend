<?php

namespace App\Http\Controllers\Api\VUE;

use App\Http\Resources\VUE\BusinessSettingCollection;
use App\Models\BusinessSetting;

class BusinessSettingController extends Controller
{
    public function index()
    {
        return new BusinessSettingCollection(BusinessSetting::all());
    }
}
