<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the schedules with statistics.
     * Accessible by Admin, Staff, Operator
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Retrieve all schedules with their related route and bus data
        $schedules = Schedule::with(['route', 'bus'])->latest()->get();

        // Calculate statistics using the collection
        $statistics = [
            'total_schedules' => $schedules->count(),
            'status_counts' => $schedules->groupBy('status')->map(function ($items) {
                return $items->count();
            }),
            'latest_departure' => $schedules->first()->departure_time ?? null,
            'latest_arrival' => $schedules->first()->arrival_time ?? null,
        ];

        // Return a JSON response with both the schedules and the statistics
        return response()->json([
            'schedules' => $schedules,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Display the specified schedule.
     * Accessible by Admin, Staff, Operator
     */
    public function show(Schedule $schedule)
    {
        return response()->json($schedule->load(['route', 'bus']));
    }

    /**
     * Store a newly created schedule.
     * Accessible only by Admin
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'route_id'       => 'required|exists:routes,id',
            'bus_id'         => 'required|exists:buses,id',
            'departure_time' => 'required|date',
            'arrival_time'   => 'required|date|after:departure_time',
            // 'price'          => 'required|numeric|min:0',
            'status'         => 'in:scheduled,cancelled',
        ]);

        $schedule = Schedule::create($validated);
        return response()->json(['message' => 'Schedule created', 'data' => $schedule], 201);
    }

    /**
     * Update the specified schedule.
     * Accessible only by Admin
     */
    public function update(Request $request, Schedule $schedule)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'route_id'       => 'sometimes|exists:routes,id',
            'bus_id'         => 'sometimes|exists:buses,id',
            'departure_time' => 'sometimes|date',
            'arrival_time'   => 'sometimes|date|after:departure_time',
            // 'price'          => 'sometimes|numeric|min:0',
            'status'         => 'in:scheduled,cancelled',
        ]);

        $schedule->update($validated);
        return response()->json(['message' => 'Schedule updated', 'data' => $schedule]);
    }

    /**
     * Remove the specified schedule.
     * Accessible only by Admin
     */
    public function destroy(Schedule $schedule)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $schedule->delete();
        return response()->json(['message' => 'Schedule deleted']);
    }
}
