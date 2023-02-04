<?php

namespace App\Http\Controllers\Api\VUE;

use Illuminate\Http\Request;
use App\Models\Language;
use App\Http\Resources\VUE\LanguageCollection;
use Cache;

class LanguageController extends Controller
{
    public function getList(Request $request)
    {
        return Cache::rememberForever('app.languages', function () {
            return new LanguageCollection(Language::all());
        });
    }
}
