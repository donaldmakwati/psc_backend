<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Ticket;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Display a listing of all payments.
     */
    public function viewAllPayments()
    {
        $payments = Payment::with('ticket.trip.route', 'user')->get();
        return response()->json(['payments' => $payments]);
    }

    /**
     * Handles online payments (e.g., from an external payment gateway).
     * This method assumes the ticket has already been generated.
     */
    public function makeOnlinePayment(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string|in:ecocash,zb,cbz,usd,zig',
        ]);

        try {
            DB::beginTransaction();
            $payment = Payment::create([
                'ticket_id' => $request->ticket_id,
                'amount' => $request->amount,
                'method' => $request->method,
                'status' => 'completed',
                'transaction_id' => 'MOCK_' . \Illuminate\Support\Str::uuid(),
            ]);

            DB::commit();
            return response()->json(['message' => 'Online payment successful!', 'payment' => $payment], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred during online payment.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Handles a payment made by a staff member on behalf of a passenger.
     * This method creates a payment record and then generates the ticket.
     */
    public function makeStaffPayment(Request $request)
    {
        // First, load the authenticated user's roles.
        $user = Auth::user()->load('roles');

        // Check if the authenticated user is a staff member or an admin using the hasRole() method.
        if (!$user->hasRole('staff') && !$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string|in:ecocash,zb,cbz,usd,zig',
        ]);

        try {
            DB::beginTransaction();

            // ✅ CRITICAL FIX: The logic has been changed to use Bus capacity and Trip tickets.
            // Load the trip with its bus and tickets to check for available seats.
            $trip = Trip::with(['bus', 'tickets'])->findOrFail($request->trip_id);
            
            // Calculate available seats by subtracting sold tickets from bus capacity.
            $availableSeats = $trip->bus->capacity - $trip->tickets->count();

            if ($availableSeats <= 0) {
                DB::rollBack();
                return response()->json(['error' => 'No available seats on this trip.'], 400);
            }

            // Create a payment record for the staff payment.
            $payment = new Payment([
                'amount' => $request->amount,
                'method' => $request->method,
                'status' => 'completed',
                'transaction_id' => 'MOCK_' . \Illuminate\Support\Str::uuid(),
                'user_id' => Auth::id(), // Link the payment to the authenticated staff user
                'trip_id' => $request->trip_id, // Link the payment to the selected trip
            ]);

            $payment->save();

            DB::commit();

            return response()->json([
                'message' => 'Staff payment successful!',
                'payment_id' => $payment->id,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred during staff payment.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Updates the status of an existing payment.
     */
    public function updatePaymentStatus(Request $request, $paymentId)
    {
        $request->validate(['status' => 'required|in:pending,completed,failed']);
        try {
            $payment = Payment::findOrFail($paymentId);
            $payment->status = $request->status;
            $payment->save();
            return response()->json(['message' => 'Payment status updated successfully!', 'payment' => $payment]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update payment status.', 'details' => $e->getMessage()], 500);
        }
    }
}
