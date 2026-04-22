<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('workpulse:staging:status', function () {
    $defaultConnection = Config::get('database.default');
    $connection = Config::get("database.connections.$defaultConnection", []);
    $host = $connection['host'] ?? 'sqlite';
    $database = $connection['database'] ?? '';
    $appEnv = (string) config('app.env');
    $auth2BaseUrl = (string) config('services.auth2.base_url');

    $this->info('WorkPulse staging status');
    $this->newLine();
    $this->line('APP_ENV: '.$appEnv);
    $this->line('DB_CONNECTION: '.$defaultConnection);
    $this->line('DB_HOST: '.$host);
    $this->line('DB_DATABASE: '.$database);
    $this->line('AUTH2_BASE_URL: '.$auth2BaseUrl);
    $this->newLine();

    if ($appEnv === 'production') {
        $this->warn('APP_ENV is production. This is not recommended for staging-copy work.');
    }

    if ($defaultConnection !== 'sqlite' && ! in_array((string) $host, ['127.0.0.1', 'localhost'], true)) {
        $this->warn('Database host is not local. Confirm this is a safe staging target before testing writes.');
    }

    if ($defaultConnection === 'sqlite') {
        $this->warn('Laravel is still pointed at SQLite. Switch .env to the staging MySQL copy when ready.');
    } else {
        $this->info('Laravel is configured to use a non-SQLite database connection.');
    }
})->purpose('Show the current Laravel DB target and staging safety hints.');
