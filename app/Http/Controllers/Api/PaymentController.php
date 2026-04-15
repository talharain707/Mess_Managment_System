<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Payment::query()->with('member')->latest('paid_on')->latest()->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'paid_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,bank,adjustment'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['received_by'] = $request->user()->id;

        return response()->json(
            Payment::query()->create($data)->load('member'),
            201
        );
    }

    public function show(Payment $payment): JsonResponse
    {
        return response()->json($payment->load('member'));
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        $payment->update($request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'paid_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,bank,adjustment'],
            'notes' => ['nullable', 'string'],
        ]));

        return response()->json($payment->load('member'));
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully.']);
    }
}
