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
    protected $url = 'https://www.gov.si/teme/koronavirus-sars-cov-2/';

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
    protected $description = 'Fetches API data from NJIZ/GOV';

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

        $daily_tests = $this->stripDots($dom->find('#e91362 > div > div > ul > li:nth-child(1) > p')->text);
        preg_match('/^PCR:(?P<cases>\d+)/', $daily_tests, $matches);

        if (\array_key_exists('cases', $matches)) {
            $daily_tests = $matches['cases'];
        } else {
            $daily_tests = 0;
        }

        $output = [
            'description' => $dom->find('#e91362 > div > div > div.remark > p')->innerHtml,
            'total_tests' => '/',
            'confirmed_cases' => '/',
            'daily_tests' => $daily_tests,
            'daily_cases' => $this->stripDots($dom->find('#e91362 > div > div > ul > li:nth-child(2) > div.values.red > p.val')->text),
            'daily_deaths' => $this->stripDots($dom->find('#e91362 > div > div > ul > li:nth-child(7) > div.values.black > p.val')->text)
        ];

        Cache::put('njiz', $output, self::CACHE_TIME);

        return;
    }

    /**
     * Fetches the website from the given url
     * Returns false on failure
     *
     * @return bool|string
     */
    private function fetch($url, $agent)
    {
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
    private function stripHtml($input)
    {
        return trim(strip_tags($input));
    }

    /**
     * Strips html tags and trims the output
     *
     * @param $input
     * @return string
     */
    private function stripDots($input)
    {
        return str_replace(['.', ' '], '', $input);
    }
}
