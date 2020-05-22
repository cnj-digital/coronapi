# Corona API

Laravel based API for Corona stats.

Scrapes data from https://www.worldometers.info/coronavirus/.

It presents itself as a Chrome browser on MacOS.

It's available here: https://api.protikoroni.si/

# Setup

Laravel basic setup with the additional cronjob entry as it fetches data every 10 minutes using the Laravel Scheduler

https://laravel.com/docs/7.x/scheduling

``* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
``

Make sure you select a proper Cache Driver.

# API Endpoints

## `/`

Home endpoint gives info about all coutnries. All data is wrapped in a data object.
`last_fetched` is a UNIX timestamp of when the data was fetched.

```json
{
    "data": [
        {
            "country": "Total",
            "total_cases": 5234140,
            "new_cases": 43644,
            "total_deaths": 335730,
            "new_deaths": 1557,
            "total_recovered": 2112123,
            "active_cases": 2786287,
            "serious_cases": 45499,
            "cases_m_pop": "671"
        },
        {
            "country": "USA",
            "total_cases": 1625039,
            "new_cases": 4137,
            "total_deaths": 96526,
            "new_deaths": 172,
            "total_recovered": 382987,
            "active_cases": 1145526,
            "serious_cases": 17907,
            "cases_m_pop": "4,913"
        },
        ...
    ],
    "last_fetched": 1590159351
}
```

## `/{country}`

Single 'country' endpoint (all names under country are valid, including `Total`).

```json
{
    "data": {
        "country": "Slovenia",
        "total_cases": 1468,
        "new_cases": 0,
        "total_deaths": 106,
        "new_deaths": 0,
        "total_recovered": 1340,
        "active_cases": 22,
        "serious_cases": 3,
        "cases_m_pop": "706"
    },
    "last_fetch": 1590159351
}
```

## Credits

This package was built by [CNJ Digital](https://www.cnj.si/).

## License

This project is licensed under the MIT License.

