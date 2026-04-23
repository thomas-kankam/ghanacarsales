<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dealer/admin tokens use the personal_access grant; TTL is controlled here (not tokensExpireIn).
        $years = (int) env('PASSPORT_PERSONAL_ACCESS_TOKEN_TTL_YEARS', 100);

        Passport::personalAccessTokensExpireIn(now()->addYears(max(1, $years)));
    }
}
