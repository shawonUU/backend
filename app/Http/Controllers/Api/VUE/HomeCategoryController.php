<?php

namespace App\Http\Controllers\Api\VUE;

use App\Http\Resources\VUE\HomeCategoryCollection;
use App\Models\HomeCategory;

class HomeCategoryController extends Controller
{
    public function index()
    {
        return new HomeCategoryCollection(HomeCategory::all());
    }
}
