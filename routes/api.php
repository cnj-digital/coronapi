<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', 'StatsController@all');
Route::get('/njiz', function() {
    return response()->json(Cache::get('njiz'));
});


Route::get('/{country}', 'StatsController@get');
