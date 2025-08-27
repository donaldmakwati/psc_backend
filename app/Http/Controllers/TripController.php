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
            'user_id' => 'required|exists:users,id', // 👈 Add validation for user_id
            'departure_time' => 'required|date_format:Y-m-d H:i:s|after:now',
            'available_seats' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        // Check if the assigned user has the 'operator' role
        $user = \App\Models\User::findOrFail($request->user_id);
        if (!$user->isOperator()) {
            return response()->json(['message' => 'The assigned user does not have the "operator" role. 🛑'], 400);
        }
        
        // Find the bus and check its status before any other logic
        $bus = Bus::findOrFail($request->bus_id);
        if ($bus->status === 'under_maintenance') {
            return response()->json(['message' => 'The selected bus is currently under maintenance and cannot be scheduled. 🛠️'], 400);
        }
        
        // Find the route to get its code
        $route = Route::findOrFail($request->route_id);

        // 🕒 CHECK: Scheduling Conflict for the bus
        $departureTime = new \DateTime($request->departure_time);
        $busStartTime = (clone $departureTime)->modify('-3 hours')->format('Y-m-d H:i:s');
        $busEndTime = (clone $departureTime)->modify('+3 hours')->format('Y-m-d H:i:s');

        // Check if the bus is already scheduled for another trip within the 3-hour window.
        $existingBusTrip = Trip::where('bus_id', $request->bus_id)
                            ->whereBetween('departure_time', [$busStartTime, $busEndTime])
                            ->exists();

        if ($existingBusTrip) {
            return response()->json(['message' => 'This bus is already scheduled for another trip within 3 hours of the requested departure time. Please select a different time or bus. 🚫'], 400);
        }
        
        // 🕒 NEW CHECK: Scheduling Conflict for the operator (2-hour window)
        $operatorStartTime = (clone $departureTime)->modify('-1 hour')->format('Y-m-d H:i:s');
        $operatorEndTime = (clone $departureTime)->modify('+1 hour')->format('Y-m-d H:i:s');
        
        $existingOperatorTrip = Trip::where('user_id', $request->user_id)
                                    ->whereBetween('departure_time', [$operatorStartTime, $operatorEndTime])
                                    ->exists();

        if ($existingOperatorTrip) {
            return response()->json(['message' => 'An operator has already been assigned a trip within the next two hours. Please select a different operator or time. 🚫'], 400);
        }
        
        try {
            $tripCode = Trip::generateTripCode($route->route_code);

            $trip = Trip::create([
                'route_id' => $request->route_id,
                'bus_id' => $request->bus_id,
                'user_id' => $request->user_id, // 👈 Add user_id to the trip creation
                'trip_code' => $tripCode,
                'departure_time' => $request->departure_time,
                'available_seats' => $request->available_seats,
            ]);

            $trip->load(['route', 'bus', 'user']); // Load the new 'user' relationship

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
        $trip = Trip::with(['route', 'bus', 'user'])->findOrFail($id); // Load the 'user' relationship
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
            'user_id' => 'sometimes|required|exists:users,id', // 👈 Add validation for user_id on update
            'departure_time' => 'sometimes|required|date_format:Y-m-d H:i:s|after:now',
            'available_seats' => 'sometimes|required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        try {
            $trip->update($request->all());
            $trip->load(['route', 'bus', 'user']); // Load the 'user' relationship
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