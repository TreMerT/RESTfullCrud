<?php

namespace App\Http\Controllers;

use App\Services\RedisDiscountRuleService;
use Illuminate\Http\Request;

class DiscountRuleController extends Controller
{
    private $ruleService;

    public function __construct(RedisDiscountRuleService $ruleService)
    {
        $this->ruleService = $ruleService;
    }

    public function index()
    {
        return response()->json([
            'categories' => $this->ruleService->getCategories(),
            'rules' => $this->ruleService->getAllRules()
        ]);
    }

    public function getRulesByCategory($categoryId)
    {
        return response()->json([
            'category' => $this->ruleService->getCategories()[$categoryId] ?? null,
            'rules' => $this->ruleService->getRulesForCategory($categoryId)
        ]);
    }

    public function resetDefaults()
    {
        $this->ruleService->setDefaultRules();
        return response()->json(['message' => 'Varsayılan kurallar yüklendi']);
    }
}
