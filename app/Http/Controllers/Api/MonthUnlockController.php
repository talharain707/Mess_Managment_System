<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MonthlyLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthUnlockController extends Controller
{
    public function __invoke(Request $request)
    {
        $monthInput = $request->input('month', now()->format('Y-m-01'));
        $monthString = Carbon::parse($monthInput)->format('Y-m');

        $ledgers = MonthlyLedger::where('month', $monthString)->get();

        if ($ledgers->isEmpty()) {
            return response()->json(['message' => 'This month is not closed.'], 400);
        }

        DB::transaction(function () use ($ledgers, $monthString) {
            foreach ($ledgers as $ledger) {
                Member::where('id', $ledger->member_id)->update([
                    'opening_balance' => $ledger->opening_balance
                ]);
            }

            MonthlyLedger::where('month', $monthString)->delete();
        });

        return response()->json(['message' => 'Month successfully unlocked.']);
    }
}
