<?php

namespace App\Http\Controllers\Api\VUE;

use App\Http\Resources\VUE\SettingsCollection;
use App\Models\AppSettings;

class SettingsController extends Controller
{
    public function index()
    {
        return new SettingsCollection(AppSettings::all());
    }
}
