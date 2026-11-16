<?php

namespace App\Http\Controllers\Api;

use App\Application\Expenses\ApplyRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRuleRequest;
use App\Http\Resources\CategoryRuleResource;
use App\Models\CategoryRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryRuleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $rules = $request->user()->rules()
            ->with('category')
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        return CategoryRuleResource::collection($rules);
    }

    public function store(CategoryRuleRequest $request): JsonResponse
    {
        $rule = $request->user()->rules()->create([
            ...$request->validated(),
            'priority' => $request->validated('priority') ?? 100,
        ]);

        return (new CategoryRuleResource($rule->load('category')))->response()->setStatusCode(201);
    }

    public function update(CategoryRuleRequest $request, CategoryRule $rule): CategoryRuleResource
    {
        $this->authorize('update', $rule);

        $rule->update([
            ...$request->validated(),
            'priority' => $request->validated('priority') ?? $rule->priority,
        ]);

        return new CategoryRuleResource($rule->load('category'));
    }

    public function destroy(CategoryRule $rule): JsonResponse
    {
        $this->authorize('delete', $rule);

        $rule->delete();

        return response()->json(null, 204);
    }

    public function apply(Request $request, ApplyRules $apply): JsonResponse
    {
        return response()->json(['changed' => $apply->handle($request->user())]);
    }
}
