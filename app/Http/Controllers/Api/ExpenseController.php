<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Expense::query()->with('category')->latest('spent_on')->latest()->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'spent_on' => ['required', 'date'],
            'item_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['created_by'] = $request->user()->id;

        return response()->json(
            Expense::query()->create($data)->load('category'),
            201
        );
    }

    public function show(Expense $expense): JsonResponse
    {
        return response()->json($expense->load('category'));
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $expense->update($request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'spent_on' => ['required', 'date'],
            'item_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]));

        return response()->json($expense->load('category'));
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully.']);
    }
}
