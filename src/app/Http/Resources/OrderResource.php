<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'customerId' => $this->customer_id,
            'items' => $this->items->map(function ($item) {
                return [
                    'productId' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unitPrice' => number_format($item->unit_price, 2, '.', ''),
                    'total' => number_format($item->total, 2, '.', '')
                ];
            }),
            'total' => number_format($this->total, 2, '.', '')
        ];
    }
}
