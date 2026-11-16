<?php

namespace App\Http\Controllers\Api;

use App\Application\Expenses\ExpenseData;
use App\Application\Expenses\SaveExpense;
use App\Application\Export\ExpenseCsvExport;
use App\Domain\Shared\Month;
use App\Events\SpendingChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'category_id' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $expenses = $request->user()->expenses()
            ->with('category')
            ->when($request->filled('month'), fn ($query) => $query->inMonth(Month::fromString($request->string('month'))))
            ->when($request->input('category_id') === 'none', fn ($query) => $query->whereNull('category_id'))
            ->when(ctype_digit((string) $request->input('category_id')), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn ($inner) => $inner->where('description', 'like', $term)->orWhere('merchant', 'like', $term));
            })
            ->orderByDesc('spent_on')
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 50), 200));

        return ExpenseResource::collection($expenses);
    }

    public function store(ExpenseRequest $request, SaveExpense $save): JsonResponse
    {
        $expense = $save->handle($request->user(), ExpenseData::fromArray($request->validated()));

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function update(ExpenseRequest $request, Expense $expense, SaveExpense $save): ExpenseResource
    {
        $this->authorize('update', $expense);

        return new ExpenseResource($save->handle($request->user(), ExpenseData::fromArray($request->validated()), $expense));
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);

        $month = Month::of($expense->spent_on)->key();
        $expense->delete();

        SpendingChanged::dispatch($request->user()->id, [$month]);

        return response()->json(null, 204);
    }

    public function export(Request $request, ExpenseCsvExport $export): StreamedResponse
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'category_id' => ['nullable', 'integer'],
        ]);

        return $export->download(
            $request->user(),
            $request->filled('month') ? Month::fromString($request->string('month')) : null,
            $request->filled('category_id') ? $request->integer('category_id') : null,
        );
    }
}
