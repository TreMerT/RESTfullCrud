<?php

namespace App\Services\Discounts;

use App\Models\Order;

class TotalAmountDiscountStrategy implements DiscountStrategy
{
    public function calculate(Order $order, float $currentTotal): ?array
    { 
        if ($currentTotal >= 1000) {
            $discountAmount = $currentTotal * 0.10;
        
            return [
                'discounts' => [[
                    'discountReason' => '10_PERCENT_OVER_1000',
                    'discountReasontr' => 'Genel Fiyata 10% İndirim',
                    'discountAmount' => number_format($discountAmount, 2, '.', ''),
                    'subtotal' => number_format($currentTotal - $discountAmount, 2, '.', '')
                ]],
                'currentTotal' => number_format($currentTotal - $discountAmount, 2, '.', '')
            ];
        }
        return null;
    }
} 