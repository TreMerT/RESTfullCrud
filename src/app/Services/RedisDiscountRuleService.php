<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RedisDiscountRuleService
{
    private const RULES_KEY = 'discount_rules';
    private const CATEGORIES_KEY = 'categories';

    public function setDefaultRules(): void
    {
        // Kategorileri tanımla
        $categories = [
            1 => ['id' => 1, 'name' => 'A Kategori'],
            2 => ['id' => 2, 'name' => 'B Kategori']
        ];

        // İndirim kurallarını tanımla
        $rules = [
            'MULTI_ITEM_CHEAPEST' => [
                'id' => 'MULTI_ITEM_CHEAPEST',
                'type' => 'MULTI_ITEM_CHEAPEST_DISCOUNT',
                'min_items' => 2,
                'discount_percentage' => 20,
                'description' => 'İki veya daha fazla ürün alındığında en ucuza %20 indirim',
                'applicable_categories' => [1] // Sadece 1. kategoride geçerli
            ],
            'BUY_N_GET_1_FREE' => [
                'id' => 'BUY_N_GET_1_FREE',
                'type' => 'BUY_N_GET_1_FREE',
                'min_items' => 6,
                'free_items' => 1,
                'description' => '6 adet alındığında 1 tanesi bedava',
                'applicable_categories' => [2] // Sadece 2. kategoride de geçerli
            ]
        ];

        // Redis'e kaydet
        Redis::set(self::RULES_KEY, json_encode($rules));
        Redis::set(self::CATEGORIES_KEY, json_encode($categories));
    }

    public function getAllRules(): array
    {
        $rules = Redis::get(self::RULES_KEY);
        return $rules ? json_decode($rules, true) : [];
    }

    public function getCategories(): array
    {
        $categories = Redis::get(self::CATEGORIES_KEY);
        return $categories ? json_decode($categories, true) : [];
    }

    public function getRulesForCategory(int $categoryId): array
    {
        $allRules = $this->getAllRules();
        return array_filter($allRules, function($rule) use ($categoryId) {
            return in_array($categoryId, $rule['applicable_categories']);
        });
    }

    public function addRule(array $rule): void
    {
        $rules = $this->getAllRules();
        $rules[$rule['id']] = $rule;
        Redis::set(self::RULES_KEY, json_encode($rules));
    }

    public function updateRule(string $ruleId, array $rule): void
    {
        $rules = $this->getAllRules();
        $rules[$ruleId] = $rule;
        Redis::set(self::RULES_KEY, json_encode($rules));
    }

    public function deleteRule(string $ruleId): void
    {
        $rules = $this->getAllRules();
        unset($rules[$ruleId]);
        Redis::set(self::RULES_KEY, json_encode($rules));
    }
}