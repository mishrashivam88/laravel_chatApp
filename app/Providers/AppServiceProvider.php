<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->bind(BroadcastServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
   public function boot()
{
     Broadcast::routes(['middleware' => ['web']]);
}
}
