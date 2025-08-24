<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BusRouteController extends Controller
{
    /**
     * Display a listing of the routes with statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Retrieve all routes from the database
        $routes = Route::all();

        // Calculate statistics using the collection
        $statistics = [
            'total_routes' => $routes->count(),
            'total_distance_km' => $routes->sum('distance_km'),
            'average_distance_km' => $routes->avg('distance_km'),
        ];

        // Return a JSON response with both the list of routes and the statistics
        return response()->json([
            'routes' => $routes,
            'statistics' => $statistics,
        ]);
    }

    // View one route
    public function show($id)
    {
        $route = Route::findOrFail($id);
        return response()->json($route);
    }

    /**
     * Create a route (admin only)
     * Added 'route_code' validation
     */
    public function store(Request $request)
    {
        $user = Auth::user()->load('roles');

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
        }

        $validated = $request->validate([
            'route_code' => 'required|string|max:20|unique:routes,route_code',
            'origin' => 'required|string|max:100',
            'destination' => 'required|string|max:100',
            'distance_km' => 'required|numeric|min:0.1',
            'estimated_time' => 'required|string|max:50',
        ]);

        $route = Route::create($validated);

        return response()->json($route, 201);
    }

    /**
     * Update a route (admin only)
     * Added 'route_code' validation with an exception for the current route
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user()->load('roles');

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
        }

        $route = Route::findOrFail($id);

        $validated = $request->validate([
            // This rule makes the route_code unique, but ignores the current route's ID
            'route_code' => ['sometimes', 'string', 'max:20', Rule::unique('routes')->ignore($id)],
            'origin' => 'sometimes|string|max:100',
            'destination' => 'sometimes|string|max:100',
            'distance_km' => 'sometimes|numeric|min:0.1',
            'estimated_time' => 'sometimes|string|max:50',
        ]);

        $route->update($validated);

        return response()->json($route);
    }

    // Delete a route (admin only)
    public function destroy($id)
    {
        $user = Auth::user()->load('roles');

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
        }

        $route = Route::findOrFail($id);
        $route->delete();

        return response()->json(['message' => 'Route deleted successfully.']);
    }
}
