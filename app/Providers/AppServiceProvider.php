<?php

namespace App\Providers;

use App\Http\SlackClient\HttpSlackClient;
use App\Http\SlackClient\SlackClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SlackClient::class, function () {
            return new HttpSlackClient(
                clientId: env('SLACK_CLIENT_ID'),
                clientSecret: env('SLACK_CLIENT_SECRET'),
                redirectUrl: env('SLACK_REDIRECT_URI'),
                endpoint: 'https://slack.com/api/'
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
