<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use PHPHtmlParser\Dom;

class Fetch extends Command
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
    protected $url = 'https://www.worldometers.info/coronavirus/';

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
    protected $signature = 'command:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches API data from worldometers.info/coronavirus';

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
        $rows = $dom->find('#main_table_countries_today > tbody:nth-child(2) > tr');

        $items = [];

        foreach ($rows as $row) {
            $dom->load($row);

            if ($row->find('td')->count() !== 0) {
                $items[] = $this->handleRow($countryRow = $row->find('td'));
            }

        }

        // dd('gone');

        // If we have any stats, cache them
        if (count($items)) {
            Cache::put('stats', $items, self::CACHE_TIME);
            Cache::put('last_fetch', time(), self::CACHE_TIME);
        }

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
     * @param $row
     * @return array
     */
    private function handleRow($row) {
        $country = $this->stripHtml($row[1]->innerHtml);

        // Api has changed, to keep backwards compatibiliy we rename World to Total
        if ($country === 'World') {
            $country = 'Total';
        }

        return [
            'country' => $country,
            'total_cases' => $this->formatNumber($row[2]->innerHtml),
            'new_cases' => $this->formatNumber($row[3]->innerHtml),
            'total_deaths' => $this->formatNumber($row[4]->innerHtml),
            'new_deaths' => $this->formatNumber($row[5]->innerHtml),
            'total_recovered' => $this->formatNumber($row[6]->innerHtml),
            'active_cases' => $this->formatNumber($row[7]->innerHtml),
            'serious_cases' => $this->formatNumber($row[8]->innerHtml),
            'cases_m_pop' => $this->stripHtml($row[9]->innerHtml)
        ];
    }

    /**
     * Strips the number tags, return 0 if not set and remove the ,
     *
     * @param $number
     * @return int
     */
    private function formatNumber($number) {
        $number = $this->stripHtml($number);

        if ($number === '') {
            return 0;
        }

        return intval(str_replace(',', '', $number));
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
