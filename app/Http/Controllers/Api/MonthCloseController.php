<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MonthlyLedger;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthCloseController extends Controller
{
    public function __invoke(Request $request)
    {
        $monthInput = $request->input('month', now()->format('Y-m-01'));
        $monthStart = Carbon::parse($monthInput)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $monthString = $monthStart->format('Y-m');

        if (MonthlyLedger::where('month', $monthString)->exists()) {
            return response()->json(['message' => 'This month is already closed.'], 400);
        }

        DB::transaction(function () use ($monthStart, $monthEnd, $monthString) {
            $members = Member::where('is_active', true)->get();

            foreach ($members as $member) {
                $paid = Payment::where('member_id', $member->id)
                    ->whereBetween('paid_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->sum('amount');

                $liability = round(((float) $member->monthly_fee + (float) $member->opening_balance) - (float) $paid, 2);

                MonthlyLedger::create([
                    'member_id' => $member->id,
                    'month' => $monthString,
                    'opening_balance' => $member->opening_balance,
                    'monthly_fee' => $member->monthly_fee,
                    'paid_amount' => $paid,
                    'closing_liability' => $liability,
                ]);

                // Update the member's active opening balance for next month
                $member->update([
                    'opening_balance' => $liability
                ]);
            }
        });

        return response()->json(['message' => 'Month closed successfully and balances forwarded.']);
    }
}
