<?php

namespace App\Http\Controllers\VueControllers\Api\VUE;

use App\Http\Resources\VUE\CurrencyCollection;
use App\Models\Currency;

class CurrencyController extends Controller
{
    public function index()
    {
        return new CurrencyCollection(Currency::all());
    }
}
