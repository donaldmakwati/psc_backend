<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusController extends Controller
{
    /**
     * Display a listing of the buses with statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Retrieve all buses from the database
        $buses = Bus::all();

        // Calculate statistics using the collection
        $statistics = [
            'total_buses' => $buses->count(),
            'status_counts' => $buses->groupBy('status')->map(function ($items) {
                return $items->count();
            }),
            'type_counts' => $buses->groupBy('type')->map(function ($items) {
                return $items->count();
            }),
        ];

        // Return a JSON response with both the list of buses and the statistics
        return response()->json([
            'buses' => $buses,
            'statistics' => $statistics,
        ]);
    }

    public function show($id)
    {
        $bus = Bus::findOrFail($id);
        return response()->json($bus);
    }

    public function store(Request $request)
    {
        $user = Auth::user()->load('roles');

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
        }

        $validated = $request->validate([
            'bus_number' => 'required|unique:buses|max:50',
            'type' => 'required|in:AC,Non-AC,Sleeper,Seater',
            'capacity' => 'required|integer|min:1',
            'status' => 'in:active,maintenance',
        ]);

        $bus = Bus::create($validated);

        return response()->json($bus, 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user()->load('roles');

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
        }

        $bus = Bus::findOrFail($id);

        $validated = $request->validate([
            'bus_number' => 'sometimes|unique:buses,bus_number,' . $bus->id,
            'type' => 'sometimes|in:AC,Non-AC,Sleeper,Seater',
            'capacity' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:active,maintenance',
        ]);

        $bus->update($validated);

        return response()->json($bus);
    }

    public function destroy($id)
    {
        $user = Auth::user()->load('roles');

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
        }

        $bus = Bus::findOrFail($id);
        $bus->delete();

        return response()->json(['message' => 'Bus deleted successfully.']);
    }
}
