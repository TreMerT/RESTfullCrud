<?php

namespace App\Services\Discounts;

use App\Models\Order;

interface DiscountStrategy
{
    public function calculate(Order $order, float $currentTotal): ?array;
} 