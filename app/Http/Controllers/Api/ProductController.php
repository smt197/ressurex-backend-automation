<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // TODO: Implement index method
        return response()->json([
            'message' => 'List of resources',
            'data' => [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // TODO: Implement store method
        return response()->json([
            'message' => 'Resource created successfully',
            'data' => [],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        // TODO: Implement show method
        return response()->json([
            'message' => 'Resource details',
            'data' => [],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // TODO: Implement update method
        return response()->json([
            'message' => 'Resource updated successfully',
            'data' => [],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        // TODO: Implement destroy method
        return response()->json([
            'message' => 'Resource deleted successfully',
        ]);
    }
}
