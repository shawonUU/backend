<?php

namespace App\Http\Controllers\Api\VUE;

use App\Http\Resources\VUE\ColorCollection;
use App\Models\Color;

class ColorController extends Controller
{
    public function index()
    {
        return new ColorCollection(Color::all());
    }
}
