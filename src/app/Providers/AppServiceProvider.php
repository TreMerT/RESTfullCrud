<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RedisDiscountRuleService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RedisDiscountRuleService::class, function ($app) {
            return new RedisDiscountRuleService();
        });
    }

    public function boot(): void
    {
        /* Varsayılan kuralları Redis'e yükle */
        $ruleService = app(RedisDiscountRuleService::class);
        if (!$ruleService->getRules()) {
            $ruleService->setDefaultRules();
        }
    }
}
