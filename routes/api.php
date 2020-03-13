<?php

use Illuminate\Support\Facades\Route;

Route::get('stats', 'StatsController@all');
Route::get('stats/{country}', 'StatsController@get');
