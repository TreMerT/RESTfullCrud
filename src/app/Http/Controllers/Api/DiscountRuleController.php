<?php

namespace App\Http\Controllers\Api;     

use App\Services\RedisDiscountRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountRuleController extends Controller
{
    private $ruleService;

    public function __construct(RedisDiscountRuleService $ruleService)
    {
        $this->ruleService = $ruleService;
    }

    public function index()
    {
        return response()->json($this->ruleService->getRules());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|integer|min:1',
            'type' => 'required|string|in:MULTI_ITEM_CHEAPEST_DISCOUNT,BUY_N_GET_1_FREE,BULK_PURCHASE_DISCOUNT',
            'min_items' => 'required|integer|min:1',
            'discount_percentage' => 'required_if:type,MULTI_ITEM_CHEAPEST_DISCOUNT,BULK_PURCHASE_DISCOUNT|numeric|min:0|max:100',
            'free_items' => 'required_if:type,BUY_N_GET_1_FREE|integer|min:1',
            'description' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->ruleService->setRule($request->category_id, [
            'type' => $request->type,
            'min_items' => $request->min_items,
            'discount_percentage' => $request->discount_percentage,
            'free_items' => $request->free_items,
            'description' => $request->description
        ]);

        return response()->json(['message' => 'İndirim kuralı başarıyla eklendi']);
    }

    public function destroy($categoryId)
    {
        $this->ruleService->deleteRule($categoryId);
        return response()->json(['message' => 'İndirim kuralı başarıyla silindi']);
    }
} 