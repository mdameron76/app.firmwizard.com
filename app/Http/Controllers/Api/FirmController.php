<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FirmController extends Controller
{
    /**
     * Display a listing of firms for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $firms = $request->user()->firms()->with('profile')->get();

        return response()->json([
            'success' => true,
            'data' => $firms,
        ]);
    }

    /**
     * Store a newly created firm
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website_url' => 'nullable|url|max:255',
            'primary_email' => 'nullable|email|max:255',
            'primary_phone' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:50',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Ensure slug is unique
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Firm::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter;
            $counter++;
        }

        $firm = Firm::create($validated);

        // Attach current user as owner
        $firm->users()->attach($request->user()->id, ['role' => 'owner']);

        return response()->json([
            'success' => true,
            'message' => 'Firm created successfully',
            'data' => $firm,
        ], 201);
    }

    /**
     * Display the specified firm
     */
    public function show(Firm $firm): JsonResponse
    {
        $firm->load('profile', 'activeAiFirmProfile');

        return response()->json([
            'success' => true,
            'data' => $firm,
        ]);
    }

    /**
     * Update the specified firm
     */
    public function update(Request $request, Firm $firm): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'website_url' => 'nullable|url|max:255',
            'primary_email' => 'nullable|email|max:255',
            'primary_phone' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:50',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $firm->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $firm->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Firm updated successfully',
            'data' => $firm->fresh(),
        ]);
    }

    /**
     * Remove the specified firm
     */
    public function destroy(Firm $firm): JsonResponse
    {
        $firm->delete();

        return response()->json([
            'success' => true,
            'message' => 'Firm deleted successfully',
        ]);
    }
}
