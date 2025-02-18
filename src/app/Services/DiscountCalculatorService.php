<?php

namespace App\Services;

use App\Models\Order;

class DiscountCalculatorService
{
    private array $discountStrategies;
    
    public function __construct()
    {
        $this->discountStrategies = [
            new TotalAmountDiscountStrategy(),
            new CategoryTwoDiscountStrategy(),
            new CategoryOneDiscountStrategy(),
        ];
    }

    public function calculateDiscounts(Order $order)
    {
        $discounts = [];
        $currentTotal = $order->total;

        foreach ($this->discountStrategies as $strategy) {
            $discount = $strategy->calculate($order, $currentTotal);
            if ($discount) {
                $discounts[] = $discount;
                $currentTotal = $discount['subtotal'];
            }
        }

        return [
            'orderId' => $order->id,
            'discounts' => $discounts,
            'totalDiscount' => collect($discounts)->sum('discountAmount'),
            'discountedTotal' => $currentTotal
        ];
    }
} 