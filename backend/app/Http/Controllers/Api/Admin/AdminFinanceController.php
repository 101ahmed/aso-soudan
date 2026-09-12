<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinanceExpenseResource;
use App\Http\Resources\FinanceRevenueResource;
use App\Models\Department;
use App\Models\FinanceBudget;
use App\Models\FinanceExpense;
use App\Models\FinanceRevenue;
use App\Services\MemberSubscriptionRevenueSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminFinanceController extends Controller
{
    public function overview(Request $request, string $code): JsonResponse
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.view');

        app(MemberSubscriptionRevenueSync::class)->syncAll($request->user()?->id);

        $year = $request->integer('year', (int) now()->year);
        $budget = FinanceBudget::query()->where('year', $year)->first();
        $approved = (float) ($budget?->amount ?? 0);
        $totalRevenues = (float) FinanceRevenue::query()->forYear($year)->sum('amount');
        $totalExpenses = (float) FinanceExpense::query()->forYear($year)->sum('amount');
        $operations = FinanceRevenue::query()->forYear($year)->count()
            + FinanceExpense::query()->forYear($year)->count();

        $revenuesBySource = FinanceRevenue::query()
            ->forYear($year)
            ->selectRaw('source, SUM(amount) as total, COUNT(*) as operations')
            ->groupBy('source')
            ->get()
            ->map(fn ($row) => [
                'source' => $row->source,
                'total' => (float) $row->total,
                'operations' => (int) $row->operations,
            ]);

        $expensesByCategory = FinanceExpense::query()
            ->forYear($year)
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as operations')
            ->groupBy('category')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'total' => (float) $row->total,
                'operations' => (int) $row->operations,
            ]);

        $grouped = FinanceExpense::query()
            ->forYear($year)
            ->selectRaw('department_id, SUM(amount) as total, COUNT(*) as operations')
            ->groupBy('department_id')
            ->get();
        $departments = Department::query()
            ->whereIn('id', $grouped->pluck('department_id')->filter()->all())
            ->get()
            ->keyBy('id');
        $expensesByDepartment = $grouped->map(function ($row) use ($departments) {
            $department = $row->department_id ? $departments->get($row->department_id) : null;

            return [
                'department_id' => $row->department_id,
                'department' => $department ? [
                    'id' => $department->id,
                    'code' => $department->code,
                    'name_ar' => $department->name_ar,
                    'name_fr' => $department->name_fr,
                ] : null,
                'total' => (float) $row->total,
                'operations' => (int) $row->operations,
            ];
        });

        return response()->json([
            'year' => $year,
            'total_revenues' => $totalRevenues,
            'total_expenses' => $totalExpenses,
            'current_balance' => $totalRevenues - $totalExpenses,
            'approved_budget' => $approved,
            'budget_remaining' => $approved - $totalExpenses,
            'spend_ratio' => $approved > 0 ? round(($totalExpenses / $approved) * 100, 1) : null,
            'operations_count' => $operations,
            'budget' => $budget ? [
                'id' => $budget->id,
                'year' => $budget->year,
                'amount' => (float) $budget->amount,
                'notes' => $budget->notes,
            ] : null,
            'revenues_by_source' => $revenuesBySource,
            'expenses_by_category' => $expensesByCategory,
            'expenses_by_department' => $expensesByDepartment,
        ]);
    }

    public function saveBudget(Request $request, string $code): JsonResponse
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.update');

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $budget = FinanceBudget::query()->updateOrCreate(
            ['year' => $data['year']],
            [
                'amount' => $data['amount'],
                'notes' => $data['notes'] ?? null,
                'updated_by' => $request->user()->id,
            ]
        );

        return response()->json([
            'id' => $budget->id,
            'year' => $budget->year,
            'amount' => (float) $budget->amount,
            'notes' => $budget->notes,
        ]);
    }

    public function revenuesIndex(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.view');

        app(MemberSubscriptionRevenueSync::class)->syncAll($request->user()?->id);

        $filters = $request->only(['year', 'source', 'search']);
        $query = FinanceRevenue::query()->with('member')->filtered($filters);

        return FinanceRevenueResource::collection(
            (clone $query)
                ->latest('occurred_on')
                ->latest('id')
                ->paginate($request->integer('per_page', 30))
        )->additional([
            'subscriptions_total' => (float) FinanceRevenue::query()
                ->filtered(['year' => $filters['year'] ?? now()->year, 'source' => 'membership'])
                ->sum('amount'),
            'year_total' => (float) (clone $query)->sum('amount'),
        ]);
    }

    public function revenuesStore(Request $request, string $code): JsonResponse
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.create');

        $item = FinanceRevenue::query()->create([
            ...$this->validatedRevenue($request),
            'recorded_by' => $request->user()->id,
        ]);

        return (new FinanceRevenueResource($item))->response()->setStatusCode(201);
    }

    public function revenuesUpdate(Request $request, string $code, FinanceRevenue $financeRevenue): FinanceRevenueResource
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.update');
        abort_if($financeRevenue->member_id, 422, 'Subscription revenues are updated from the members list.');
        $financeRevenue->update($this->validatedRevenue($request, $financeRevenue));

        return new FinanceRevenueResource($financeRevenue->fresh());
    }

    public function revenuesDestroy(Request $request, string $code, FinanceRevenue $financeRevenue): JsonResponse
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.delete');
        abort_if($financeRevenue->member_id, 422, 'Subscription revenues are removed from the members list.');
        $financeRevenue->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function expensesIndex(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.view');

        return FinanceExpenseResource::collection(
            FinanceExpense::query()
                ->with('department')
                ->filtered($request->only(['year', 'category', 'department_id', 'search']))
                ->latest('occurred_on')
                ->latest('id')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function expensesStore(Request $request, string $code): JsonResponse
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.create');

        $item = FinanceExpense::query()->create([
            ...$this->validatedExpense($request),
            'recorded_by' => $request->user()->id,
        ]);

        return (new FinanceExpenseResource($item->load('department')))->response()->setStatusCode(201);
    }

    public function expensesUpdate(Request $request, string $code, FinanceExpense $financeExpense): FinanceExpenseResource
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.update');
        $financeExpense->update($this->validatedExpense($request, $financeExpense));

        return new FinanceExpenseResource($financeExpense->fresh()->load('department'));
    }

    public function expensesDestroy(Request $request, string $code, FinanceExpense $financeExpense): JsonResponse
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.delete');
        $financeExpense->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validatedRevenue(Request $request, ?FinanceRevenue $item = null): array
    {
        return $request->validate([
            'occurred_on' => [$item ? 'sometimes' : 'required', 'date'],
            'amount' => [$item ? 'sometimes' : 'required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'source' => [$item ? 'sometimes' : 'required', Rule::in(FinanceRevenue::SOURCES)],
            'title_ar' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function validatedExpense(Request $request, ?FinanceExpense $item = null): array
    {
        $data = $request->validate([
            'occurred_on' => [$item ? 'sometimes' : 'required', 'date'],
            'amount' => [$item ? 'sometimes' : 'required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'category' => [$item ? 'sometimes' : 'required', Rule::in(FinanceExpense::CATEGORIES)],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'project_ar' => ['nullable', 'string', 'max:255'],
            'project_fr' => ['nullable', 'string', 'max:255'],
            'title_ar' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        if (array_key_exists('department_id', $data) && $data['department_id'] === '') {
            $data['department_id'] = null;
        }

        return $data;
    }

    private function assertFinance(string $code): void
    {
        abort_unless($code === 'finance', 404);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
