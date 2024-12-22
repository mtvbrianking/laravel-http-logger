<?php

namespace Bmatovu\HttpLogger;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class HttpLoggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->trackRequest();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/http-logger.php' => base_path('config/http-logger.php'),
            ], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/http-logger.php', 'http-logger');
    }

    protected function trackRequest(): void
    {
        $requestId = Str::random(10);

        $_SERVER['REQUEST_ID'] ??= $requestId;

        Log::shareContext(['request_id' => $requestId]);
    }
}
