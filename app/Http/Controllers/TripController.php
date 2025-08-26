<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Route; 
use App\Models\Bus;   
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator; 

class TripController extends Controller
{
    /**
     * Display a listing of all trips.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $trips = Trip::with(['route', 'bus'])->latest()->paginate(10); 

        return response()->json([
            'message' => 'Trips retrieved successfully. 🚌',
            'trips' => $trips
        ]);
    }

    /**
     * Store a newly created trip in storage with advanced validation.
     * Only accessible by 'admin' role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // 🔐 Authorization Check: Only allow admins to perform this action.
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only administrators can perform this action. 🛑'], 403);
        }

        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'route_id' => 'required|exists:routes,id',
            'bus_id' => 'required|exists:buses,id',
            'departure_time' => 'required|date_format:Y-m-d H:i:s|after:now',
            'available_seats' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }
        
        // Find the bus and check its status before any other logic
        $bus = Bus::findOrFail($request->bus_id);
        if ($bus->status === 'under_maintenance') {
            return response()->json(['message' => 'The selected bus is currently under maintenance and cannot be scheduled. 🛠️'], 400);
        }
        
        // Find the route to get its code
        $route = Route::findOrFail($request->route_id);

        // 🕒 NEW CHECK: Scheduling Conflict
        $departureTime = new \DateTime($request->departure_time);
        $startTime = (clone $departureTime)->modify('-3 hours')->format('Y-m-d H:i:s');
        $endTime = (clone $departureTime)->modify('+3 hours')->format('Y-m-d H:i:s');

        // Check if the bus is already scheduled for another trip within the 3-hour window.
        $existingTrip = Trip::where('bus_id', $request->bus_id)
                            ->whereBetween('departure_time', [$startTime, $endTime])
                            ->exists();

        if ($existingTrip) {
            return response()->json(['message' => 'This bus is already scheduled for another trip within 3 hours of the requested departure time. Please select a different time or bus. 🚫'], 400);
        }

        try {
            $tripCode = Trip::generateTripCode($route->route_code);

            $trip = Trip::create([
                'route_id' => $request->route_id,
                'bus_id' => $request->bus_id,
                'trip_code' => $tripCode,
                'departure_time' => $request->departure_time,
                'available_seats' => $request->available_seats,
            ]);

            $trip->load(['route', 'bus']);

            return response()->json([
                'message' => 'Trip created successfully! ✨',
                'trip' => $trip
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error creating trip: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create trip. 🚧', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified trip.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $trip = Trip::with(['route', 'bus'])->findOrFail($id);
        return response()->json(['message' => 'Trip details retrieved successfully. ℹ️', 'trip' => $trip]);
    }

    /**
     * Update the specified trip in storage.
     * Only accessible by 'admin' role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only administrators can perform this action. 🛑'], 403);
        }

        $trip = Trip::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'route_id' => 'sometimes|required|exists:routes,id',
            'bus_id' => 'sometimes|required|exists:buses,id',
            'departure_time' => 'sometimes|required|date_format:Y-m-d H:i:s|after:now',
            'available_seats' => 'sometimes|required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        try {
            $trip->update($request->all());
            $trip->load(['route', 'bus']);
            return response()->json(['message' => 'Trip updated successfully! ✅', 'trip' => $trip]);

        } catch (\Exception $e) {
            Log::error("Error updating trip: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update trip. 🚧', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified trip from storage.
     * Only accessible by 'admin' role.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only administrators can perform this action. 🛑'], 403);
        }

        $trip = Trip::findOrFail($id);

        try {
            $trip->delete();
            return response()->json(['message' => 'Trip deleted successfully! 🗑️'], 200);

        } catch (\Exception $e) {
            Log::error("Error deleting trip: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete trip. 🚧', 'error' => $e->getMessage()], 500);
        }
    }
}
