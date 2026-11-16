<?php

namespace App\Http\Controllers\Api;

use App\Application\Budget\MonthlySummary;
use App\Domain\Shared\Money;
use App\Domain\Shared\Month;
use App\Events\SpendingChanged;
use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetAlertResource;
use App\Http\Resources\UserResource;
use App\Models\BudgetAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BudgetController extends Controller
{
    public function summary(Request $request, MonthlySummary $summary): JsonResponse
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);

        $month = $request->filled('month') ? Month::fromString($request->string('month')) : Month::current();

        return response()->json($summary->handle($request->user(), $month));
    }

    public function updateLimit(Request $request): UserResource
    {
        $request->validate(
            ['monthly_limit' => ['nullable', 'regex:/^\s*\d{1,9}([.,]\d{1,2})?\s*$/']],
            ['monthly_limit.regex' => 'Podaj limit, np. 4000.'],
        );

        $user = $request->user();
        $limit = $request->input('monthly_limit');
        $user->update(['monthly_limit' => blank($limit) ? null : Money::parse($limit)->cents]);

        SpendingChanged::dispatch($user->id, [Month::current()->key()]);

        return new UserResource($user);
    }

    public function alerts(Request $request): AnonymousResourceCollection
    {
        $alerts = $request->user()->budgetAlerts()
            ->with('category')
            ->latest('id')
            ->limit(30)
            ->get();

        return BudgetAlertResource::collection($alerts)->additional([
            'unread' => $request->user()->budgetAlerts()->whereNull('read_at')->count(),
        ]);
    }

    public function readAlert(BudgetAlert $alert): BudgetAlertResource
    {
        $this->authorize('update', $alert);

        $alert->update(['read_at' => $alert->read_at ?? now()]);

        return new BudgetAlertResource($alert->load('category'));
    }

    public function readAllAlerts(Request $request): JsonResponse
    {
        $request->user()->budgetAlerts()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(null, 204);
    }
}
