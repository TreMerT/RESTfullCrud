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
        $categories = $this->ruleService->getCategories();
        $allRules = $this->ruleService->getAllRules();

        $categoryGroups = $order->items->groupBy(function ($item) {
            return $item->product->category;
        });
        
        foreach ($categoryGroups as $categoryId => $items) { 
            foreach ($allRules as $rule) { 
                if (in_array($categoryId, $rule['applicable_categories'])) {
                    $discount = $this->applyRule(
                        $rule,
                        $items,
                        $currentTotal,
                        $categories[$categoryId]['name']
                    );
                  
                    if ($discount) {
                        $discounts[] = $discount;
                        $currentTotal = $discount['subtotal'];
                    }
                }
            }
        }  

        return [
            'discounts' => $discounts,
            'currentTotal' => $currentTotal
        ];
    }

    private function applyRule(array $rule, $items, float $currentTotal, string $categoryName): ?array
    {
        switch ($rule['type']) {
            case 'MULTI_ITEM_CHEAPEST_DISCOUNT':
                $totalQuantity = $items->sum('quantity');
                if ($totalQuantity >= 2) {
                    $cheapestItem = $items->sortBy('unit_price')->first();
                    $discountAmount = ($cheapestItem->unit_price * $cheapestItem->quantity) * ($rule['discount_percentage'] / 100);

                    return [
                        'discountReason' => "MULTI_ITEM_CHEAPEST_DISCOUNT",
                        'discountReasontr' => "{$categoryName} - {$rule['description']}",
                        'discountAmount' => number_format($discountAmount, 2, '.', ''),
                        'subtotal' => number_format($currentTotal - $discountAmount, 2, '.', '')
                    ];
                }
                break;

            case 'BUY_N_GET_1_FREE':
                $totalQuantity = $items->sum('quantity');
                if ($totalQuantity >= $rule['min_items']) {
                    $item = $items->first();
                    $discountAmount = $item->unit_price;

                    return [
                        'discountReason' => "BUY_N_GET_1_FREE",
                        'discountReasontr' => "{$categoryName} - {$rule['description']}",
                        'discountAmount' => number_format($discountAmount, 2, '.', ''),
                        'subtotal' => number_format($currentTotal - $discountAmount, 2, '.', '')
                    ];
                }
                break;
        }

        return null;
    }
}
