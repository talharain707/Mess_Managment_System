<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuEntryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(MenuEntry::query()->orderBy('served_on')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $menuEntry = MenuEntry::query()->create($request->validate([
            'served_on' => ['required', 'date', 'unique:menu_entries,served_on'],
            'meal_slot' => ['required', 'in:breakfast,lunch,dinner'],
            'dish_name' => ['required', 'string', 'max:255'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]));

        return response()->json($menuEntry, 201);
    }

    public function show(MenuEntry $menuEntry): JsonResponse
    {
        return response()->json($menuEntry);
    }

    public function update(Request $request, MenuEntry $menuEntry): JsonResponse
    {
        $menuEntry->update($request->validate([
            'served_on' => ['required', 'date', 'unique:menu_entries,served_on,'.$menuEntry->id],
            'meal_slot' => ['required', 'in:breakfast,lunch,dinner'],
            'dish_name' => ['required', 'string', 'max:255'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]));

        return response()->json($menuEntry);
    }

    public function destroy(MenuEntry $menuEntry): JsonResponse
    {
        $menuEntry->delete();

        return response()->json(['message' => 'Menu entry deleted successfully.']);
    }
}
