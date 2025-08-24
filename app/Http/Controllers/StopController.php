<?php

namespace App\Http\Controllers;

use App\Models\Stop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StopController extends Controller
{
    // ✅ List all stops (with route info)
    public function index()
    {
        return response()->json(
            Stop::with('route')->orderBy('route_id')->orderBy('stop_order')->get()
        );
    }

    // ✅ View a single stop
    public function show($id)
    {
        $stop = Stop::with('route')->findOrFail($id);
        return response()->json($stop);
    }

    // ✅ Admin creates a new stop
    public function store(Request $request)
    {
        $user = Auth::user()->load('roles');

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
        }
        $validated = $request->validate([
            'route_id' => 'required|exists:routes,id',
            'stop_name' => 'required|string|max:100',
            'stop_order' => 'required|integer|min:1',
        ]);

        $stop = Stop::create($validated);

        return response()->json([
            'message' => 'Stop created successfully.',
            'data' => $stop
        ], 201);
    }

    // ✅ Admin updates an existing stop
    public function update(Request $request, $id)
    {
                $user = Auth::user()->load('roles');

                if (!$user->isAdmin()) {
                    return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
                }

                $stop = Stop::findOrFail($id);
                $validated = $request->validate([
                    'route_id' => 'sometimes|exists:routes,id',
                    'stop_name' => 'sometimes|string|max:100',
                    'stop_order' => 'sometimes|integer|min:1',
                ]);

                $stop->update($validated);

                return response()->json([
                    'message' => 'Stop updated successfully.',
                    'data' => $stop
                ]);
            }

    // ✅ Admin deletes a stop
    public function destroy($id)
    {

            $user = Auth::user()->load('roles');

            if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
            }
            $stop = Stop::findOrFail($id);
            $stop->delete();

            return response()->json(['message' => 'Stop deleted successfully.']);
    }
}
