<?php

namespace App\Http\Controllers\Api;

use App\Domain\Shared\Money;
use App\Domain\Shared\Month;
use App\Events\SpendingChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = $request->user()->categories()
            ->withCount(['expenses', 'rules'])
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $request->user()->categories()->create($this->attributes($request));

        $this->limitsChanged($request);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorize('update', $category);

        $category->update($this->attributes($request));

        if ($category->wasChanged('monthly_limit')) {
            $this->limitsChanged($request);
        }

        return new CategoryResource($category);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(CategoryRequest $request): array
    {
        $limit = $request->validated('monthly_limit');

        return [
            'name' => $request->validated('name'),
            'color' => strtolower($request->validated('color')),
            'monthly_limit' => blank($limit) ? null : Money::parse($limit)->cents,
        ];
    }

    private function limitsChanged(Request $request): void
    {
        SpendingChanged::dispatch($request->user()->id, [Month::current()->key()]);
    }
}
