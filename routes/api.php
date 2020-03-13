<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'StatsController@all');
Route::get('/{country}', 'StatsController@get');
