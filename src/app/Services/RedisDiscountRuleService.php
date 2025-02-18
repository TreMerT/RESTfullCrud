<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RedisDiscountRuleService
{
    private const REDIS_KEY = 'category_discount_rules';

    public function setDefaultRules(): void
    {
        $rules = [
            1 => [
                'type' => 'MULTI_ITEM_CHEAPEST_DISCOUNT',
                'min_items' => 2,
                'discount_percentage' => 20,
                'description' => 'İki veya daha fazla ürün alındığında en ucuza %20 indirim'
            ],
            2 => [
                'type' => 'BUY_N_GET_1_FREE',
                'min_items' => 6,
                'free_items' => 1,
                'description' => '6 adet alındığında 1 tanesi bedava'
            ],
            3 => [
                'type' => 'BULK_PURCHASE_DISCOUNT',
                'min_items' => 3,
                'discount_percentage' => 15,
                'description' => '3 veya daha fazla ürün alındığında %15 indirim'
            ]
        ];

        Redis::set(self::REDIS_KEY, json_encode($rules));
    }

    public function getRules(): array
    {
        $rules = Redis::get(self::REDIS_KEY);
        return $rules ? json_decode($rules, true) : [];
    }

    public function setRule(int $categoryId, array $rule): void
    {
        $rules = $this->getRules();
        $rules[$categoryId] = $rule;
        Redis::set(self::REDIS_KEY, json_encode($rules));
    }

    public function deleteRule(int $categoryId): void
    {
        $rules = $this->getRules();
        unset($rules[$categoryId]);
        Redis::set(self::REDIS_KEY, json_encode($rules));
    }
} 