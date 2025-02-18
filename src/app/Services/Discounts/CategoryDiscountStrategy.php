<?php

namespace App\Services\Discounts;

use App\Models\Order;
use App\Services\RedisDiscountRuleService;

class CategoryDiscountStrategy implements DiscountStrategy
{
    private $ruleService;

    public function __construct(RedisDiscountRuleService $ruleService)
    {
        $this->ruleService = $ruleService;
    }

    public function calculate(Order $order, float $currentTotal): array
    {
        $discounts = [];
        $categoryRules = $this->ruleService->getRules();

        $categoryGroups = $order->items->groupBy(function ($item) {
            return $item->product->category;
        });

        foreach ($categoryGroups as $categoryId => $items) {
            if (isset($categoryRules[$categoryId])) {
                $rule = $categoryRules[$categoryId];
                $discount = $this->applyRule($rule, $items, $categoryId);
                
                if ($discount) {
                    $discounts[] = $discount;
                    $currentTotal -= $discount['discountAmount'];
                }
            }
        }

        return [
            'discounts' => $discounts,
            'currentTotal' => $currentTotal
        ];
    }

    // ... diğer metodlar aynı kalacak ...
} 