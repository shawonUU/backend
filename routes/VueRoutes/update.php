<?php
/*
|--------------------------------------------------------------------------
| Update Routes
|--------------------------------------------------------------------------
|
| This route is responsible for handling the intallation process
|
|
|
*/

use App\Http\Controllers\VueControllers\UpdateController;

Route::get('/', [UpdateController::class, 'step0']);
