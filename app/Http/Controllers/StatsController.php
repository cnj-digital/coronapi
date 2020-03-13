<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    /**
     * Returns all stats from the Cache
     *
     * @return JsonResponse
     */
    public function all()
    {
        return response()->json([
            'data' => Cache::get('stats'),
            'last_fetch' =>  Cache::get('last_fetch')
        ]);
    }

    /**
     * Display the stats for the given country/ies
     *
     * @param  string
     * @return JsonResponse
     */
    public function get($country)
    {
        $stat = collect(Cache::get('stats'))->filter(function ($item) use ($country) {
           return mb_strtolower($item['country']) == mb_strtolower($country);
        })->first();

        return response()->json([
            'data' => $stat,
            'last_fetch' =>  Cache::get('last_fetch')
        ]);
    }
}
