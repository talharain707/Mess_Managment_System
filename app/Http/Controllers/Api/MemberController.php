<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Member::query()->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $member = Member::query()->create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'room_no' => ['nullable', 'string', 'max:50'],
            'bed_no' => ['nullable', 'string', 'max:50'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'joined_on' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]));

        return response()->json($member, 201);
    }

    public function show(Member $member): JsonResponse
    {
        return response()->json($member);
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $member->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'room_no' => ['nullable', 'string', 'max:50'],
            'bed_no' => ['nullable', 'string', 'max:50'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'joined_on' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]));

        return response()->json($member);
    }

    public function destroy(Member $member): JsonResponse
    {
        $member->delete();

        return response()->json(['message' => 'Member deleted successfully.']);
    }
}
