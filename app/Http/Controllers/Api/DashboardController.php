<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Member;
use App\Models\MenuEntry;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $month = Carbon::parse($request->query('month', now()->format('Y-m-01')));
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $members = Member::query()->where('is_active', true)->get();
        $expenses = Expense::query()
            ->with('category')
            ->whereBetween('spent_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();
        $payments = Payment::query()
            ->with('member')
            ->whereBetween('paid_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();

        $monthString = $monthStart->format('Y-m');
        $ledgers = \App\Models\MonthlyLedger::where('month', $monthString)->get()->keyBy('member_id');
        $isClosed = $ledgers->isNotEmpty();

        $futureLedgers = \App\Models\MonthlyLedger::where('month', '>', $monthString)
            ->orderBy('month', 'asc')
            ->get()
            ->groupBy('member_id');

        $memberLedger = $members->map(function (Member $member) use ($payments, $ledgers, $futureLedgers, $monthStart) {
            $memberPayments = (float) $payments
                ->where('member_id', $member->id)
                ->sum('amount');
            
            $ledger = $ledgers->get($member->id);

            if ($ledger) {
                $openingBalance = (float) $ledger->opening_balance;
                $monthlyFee = (float) $ledger->monthly_fee;
                $paid = (float) $ledger->paid_amount;
                $actualLiability = (float) $ledger->closing_liability;
            } else {
                $monthlyFee = (float) $member->monthly_fee;
                $paid = $memberPayments;

                $futureMemberLedgers = $futureLedgers->get($member->id);
                $anchorLedger = $futureMemberLedgers ? $futureMemberLedgers->first() : null;

                if ($anchorLedger) {
                    $anchorDate = Carbon::parse($anchorLedger->month . '-01');
                    $anchorOpeningBalance = (float) $anchorLedger->opening_balance;
                } else {
                    $anchorDate = now()->startOfMonth();
                    $anchorOpeningBalance = (float) $member->opening_balance;
                }

                if ($monthStart->lt($anchorDate)) {
                    $diffInMonths = $monthStart->diffInMonths($anchorDate);
                    
                    $paymentsBetween = \App\Models\Payment::where('member_id', $member->id)
                        ->whereBetween('paid_on', [$monthStart->toDateString(), $anchorDate->copy()->subDay()->toDateString()])
                        ->sum('amount');
                        
                    $openingBalance = $anchorOpeningBalance - ($diffInMonths * $monthlyFee) + $paymentsBetween;
                } else {
                    $openingBalance = (float) $member->opening_balance;
                }

                $actualLiability = round(($monthlyFee + $openingBalance) - $paid, 2);
            }

            return [
                'id' => $member->id,
                'name' => $member->name,
                'room_no' => $member->room_no,
                'monthly_fee' => $monthlyFee,
                'previous_balance' => $openingBalance,
                'paid' => $paid,
                'actual_liability' => $actualLiability,
            ];
        })->values();

        $totalRevenue = $memberLedger->sum('monthly_fee');
        $previousReceivables = $memberLedger->sum(fn ($m) => max($m['previous_balance'], 0));
        $previousCredits = $memberLedger->sum(fn ($m) => min($m['previous_balance'], 0));
        $totalExpenses = (float) $expenses->sum('amount');
        $paymentsReceived = $memberLedger->sum('paid');
        $balance = $paymentsReceived - $totalExpenses;
        $dailyAverage = round($totalExpenses / max($monthEnd->day, 1), 2);

        $weeklyExpense = collect($expenses)
            ->groupBy(fn (Expense $expense) => Carbon::parse($expense->spent_on)->weekOfMonth)
            ->map(fn ($group, $week) => [
                'label' => 'Week '.$week,
                'amount' => (float) $group->sum('amount'),
            ])
            ->values();

        $categoryBreakdown = ExpenseCategory::query()
            ->withSum(['expenses as month_total' => fn ($query) => $query->whereBetween('spent_on', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])], 'amount')
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (ExpenseCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'color' => $category->color,
                'month_total' => (float) ($category->month_total ?? 0),
            ]);

        $menuEntries = MenuEntry::query()
            ->whereBetween('served_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('served_on')
            ->get()
            ->map(fn (MenuEntry $entry) => [
                'id' => $entry->id,
                'served_on' => $entry->served_on?->format('Y-m-d'),
                'weekday' => $entry->served_on?->format('D'),
                'dish_name' => $entry->dish_name,
                'meal_slot' => $entry->meal_slot,
                'estimated_cost' => (float) $entry->estimated_cost,
            ]);

        return response()->json([
            'month' => $monthStart->format('F Y'),
            'stats' => [
                'is_closed' => $isClosed,
                'active_members' => $members->count(),
                'total_revenue' => $totalRevenue,
                'payments_received' => $paymentsReceived,
                'total_expenses' => $totalExpenses,
                'balance' => $balance,
                'daily_expense_average' => $dailyAverage,
                'previous_receivables' => $previousReceivables,
                'previous_credits' => $previousCredits,
            ],
            'member_ledger' => $memberLedger,
            'weekly_expense' => $weeklyExpense,
            'category_breakdown' => $categoryBreakdown,
            'menu_entries' => $menuEntries,
        ]);
    }
}
