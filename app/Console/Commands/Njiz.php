<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use PHPHtmlParser\Dom;

class Njiz extends Command
{
    /**
     * How long do we cache the data
     */
    const CACHE_TIME = 60 * 60 * 24 * 7;  // 1 week

    /**
     * Scrape endpoint
     *
     * @var string
     */
    protected $url = 'https://www.nijz.si/sl/dnevno-spremljanje-okuzb-s-sars-cov-2-covid-19';

    /**
     * User agent string for curl
     *
     * @var string
     */
    protected $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:njiz';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches API data from NJIZ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param Dom $dom
     * @return void
     */
    public function handle(Dom $dom)
    {
        $result = $this->fetch($this->url, $this->agent);

        if (!$result) {
            return;
        }

         $dom->load($result);

        // Find the first table (sometimes we have 2, only interested in the first one)
        $content = $dom->find('div.content > div.field.field-name-body.field-type-text-with-summary.field-label-hidden > div > div > *');

        // Content is the whole list of items, we are interested in the first table, and the paragraph before the first table
        $table_index = collect($content->toArray())->search(function ($item, $key) {
            return $item->tag->name() === 'table';
        });

        // If table index not found or the table is at the front (we don't have a date description) return
        if ($table_index === false || $table_index <= 0) {
            return;
        }

        $output = [
            'description' => $this->stripHtml($content->toArray()[$table_index - 1]->innerHtml),
            'total_tests' => '/',
            'confirmed_cases' => '/',
            'daily_tests' => '/',
            'daily_cases' => '/',
        ];

        $rows = collect($content->toArray()[$table_index]->find('tbody > tr')->toArray());

        // Number of tests in the previous day
        $tests_match = 'opravljenih testov v preteklem dnevu';
        $tests_index = $rows->search(function($item, $key) use ($tests_match) {
            return strpos($item->innerHtml, $tests_match);
        });
        if ($tests_index !== false) {
            $output['daily_tests'] = $this->stripHtml($rows->get($tests_index)->firstChild()->innerHtml);
        }

        // We are fetching number of new cases in the previous day
        $cases_match = 'potrjenih primerov v preteklem dnevu';
        $cases_index = $rows->search(function($item, $key) use ($cases_match) {
            return strpos($item->innerHtml, $cases_match);
        });
        if ($cases_index !== false) {
            $output['daily_cases'] = $this->stripHtml($rows->get($cases_index)->firstChild()->innerHtml);
        }

        $total_tests_match = 'opravljenih testov';
        $total_tests_index = $rows->search(function($item, $key) use ($total_tests_match) {
            return strpos($item->innerHtml, $total_tests_match);
        });
        if ($total_tests_index !== false) {
            $output['total_tests'] = $this->stripHtml($rows->get($total_tests_index)->firstChild()->innerHtml);
        }

        // We are fetching number of new cases in the previous day
        $confirmed_cases_match = 'potrjenih primerov';
        $confirmed_cases_index = $rows->search(function($item, $key) use ($confirmed_cases_match) {
            return strpos($item->innerHtml, $confirmed_cases_match);
        });
        if ($confirmed_cases_index !== false) {
            $output['confirmed_cases'] = $this->stripHtml($rows->get($confirmed_cases_index)->firstChild()->innerHtml);
        }

        Cache::put('njiz', $output, self::CACHE_TIME);

        return;
    }

    /**
     * Fetches the website from the given url
     * Returns false on failure
     *
     * @return bool|string
     */
    private function fetch($url, $agent) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);

        $info = curl_getinfo($ch);

        // If not 200, there was an error
        if ($info["http_code"] !== 200) {
            return false;
        }

        return $result;
    }

    /**
     * Strips html tags and trims the output
     *
     * @param $input
     * @return string
     */
    private function stripHtml($input) {
        return trim(strip_tags($input));
    }
}
