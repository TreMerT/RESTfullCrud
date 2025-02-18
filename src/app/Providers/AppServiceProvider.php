<?php

namespace App\Providers;

use App\Services\DiscountCalculatorService;
use Illuminate\Support\ServiceProvider;
use App\Services\RedisDiscountRuleService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RedisDiscountRuleService::class, function ($app) {
            return new RedisDiscountRuleService();
        });
        $this->app->singleton(DiscountCalculatorService::class, function ($app) {
            return new DiscountCalculatorService($app->make(RedisDiscountRuleService::class));
        });
    }

    public function boot(): void
    {
        /* Varsayılan kuralları Redis'e yükle */
        $ruleService = app(RedisDiscountRuleService::class);

            $ruleService->setDefaultRules();

    }
}
