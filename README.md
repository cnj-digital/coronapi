# Corona API

Laravel based API for Corona stats.

Scrapes data from https://www.worldometers.info/coronavirus/.

Tries to hide the fact of being a scraper to avoid being IP blocked, by presenting itself as a Mac Chrome browser.

# Setup

Laravel basic setup with the additional cronjob entry as it fetches data every 10 minutes via the Laravel Scheduler

https://laravel.com/docs/7.x/scheduling

``* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
``

# Endpoints

`/api/stats`

`/api/stats/{country}`

Returns wrapped data with additional `last_fetched` field (UNIX Timestamp)
