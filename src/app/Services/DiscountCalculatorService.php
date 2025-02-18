<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Discounts\CategoryDiscountStrategy;
use App\Services\Discounts\TotalAmountDiscountStrategy;

class DiscountCalculatorService
{
    private array $discountStrategies;
    private RedisDiscountRuleService $redisDiscountRuleService;

    public function __construct(RedisDiscountRuleService $redisDiscountRuleService)
    {
        $this->redisDiscountRuleService = $redisDiscountRuleService;
        $this->discountStrategies = [
            new CategoryDiscountStrategy($redisDiscountRuleService),
            new TotalAmountDiscountStrategy(),
        ];
    }

    public function calculateDiscounts(Order $order)
    {
        $currentTotal = $order->total;
        $allDiscounts = [];

        foreach ($this->discountStrategies as $strategy) {
            $result = $strategy->calculate($order, $currentTotal);

            if (!empty($result['discounts'])) {
                $allDiscounts = array_merge($allDiscounts, $result['discounts']);
                $currentTotal = $result['currentTotal'];
            }
        }

        return [
            'orderId' => $order->id,
            'discounts' => $allDiscounts,
            'totalDiscount' => number_format(collect($allDiscounts)->sum('discountAmount'), 2, '.', ''),
            'discountedTotal' => number_format($currentTotal, 2, '.', '')
        ];
    }
}
