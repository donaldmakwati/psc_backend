<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Trip;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * Display a listing of all tickets for the authenticated user.
     * This method retrieves tickets owned by the current staff user.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the user's tickets.
     */
    public function myTickets()
    {
        try {
            // Get the currently authenticated user
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Authentication failed.'], 401);
            }

            // Load tickets for the specific user with their related trip and payment information.
            $tickets = Ticket::with(['trip.route', 'user', 'payment'])
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(20);

            return response()->json(['tickets' => $tickets], 200);
        } catch (\Exception $e) {
            Log::error("Error fetching user's tickets: " . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve your tickets.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display a listing of all tickets for administration.
     * This method is intended for administrative purposes.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of all tickets.
     */
    public function index()
    {
        try {
            // Check if the user has an admin role
            $user = Auth::user();
            if (!$user || !$user->isAdmin()) {
                return response()->json(['message' => 'Unauthorized. Only administrators can view all tickets.'], 403);
            }

            // Load all tickets with their related trip, user, and payment information.
            $tickets = Ticket::with(['trip.route', 'user', 'payment'])->latest()->paginate(20);
            return response()->json(['tickets' => $tickets], 200);
        } catch (\Exception $e) {
            Log::error("Error fetching tickets: " . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve tickets.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handles the generation of a ticket after a successful payment.
     *
     * @param Request $request The incoming request containing 'payment_id'.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating success or failure.
     */
    public function generateTicket(Request $request)
    {
        // 0. Authorization Check: Only allow 'admin' role to generate tickets.
        $user = Auth::user();

        if (!$user || !$user->isAdmin()) {
            Log::warning("Unauthorized attempt to generate ticket by user ID: " . ($user ? $user->id : 'Guest'));
            return response()->json(['message' => 'Unauthorized. Only administrators can generate tickets. 🛑'], 403);
        }

        $request->validate([
            'payment_id' => 'required|exists:payments,id',
        ]);

        $paymentId = $request->input('payment_id');

        try {
            DB::beginTransaction();

            $payment = Payment::with(['trip', 'user'])->find($paymentId);

            if (!$payment) {
                DB::rollBack();
                return response()->json(['message' => 'Payment not found.'], 404);
            }

            if ($payment->status !== 'completed') {
                DB::rollBack();
                Log::warning("Attempt to generate ticket for an uncompleted payment. Payment ID: {$payment->id}, Status: {$payment->status}");
                return response()->json(['message' => 'Payment not completed. Cannot generate ticket.'], 400);
            }

            if ($payment->ticket_id !== null) {
                DB::rollBack();
                Log::info("Ticket already generated for payment ID: {$payment->id}, existing Ticket ID: {$payment->ticket_id}");
                return response()->json(['message' => 'Ticket already generated for this payment.', 'ticket_id' => $payment->ticket_id], 409);
            }

            $trip = $payment->trip;
            $user = $payment->user;

            if (!$trip) {
                DB::rollBack();
                return response()->json(['message' => 'Associated trip not found for payment.'], 404);
            }
            if (!$user) {
                DB::rollBack();
                return response()->json(['message' => 'Associated user not found for payment.'], 404);
            }

            $routeCode = $trip->route_code ?? 'GENERIC';
            $ticketCode = Ticket::generateTicketCode($routeCode);

            while (Ticket::where('ticket_code', $ticketCode)->exists()) {
                $ticketCode = Ticket::generateTicketCode($routeCode);
            }

            $seatNumber = rand(1, 60);

            $ticket = Ticket::create([
                'trip_id' => $trip->id,
                'user_id' => $user->id,
                'ticket_code' => $ticketCode,
                'seat_number' => $seatNumber,
                'status' => 'confirmed',
            ]);

            $payment->ticket_id = $ticket->id;
            $payment->save();

            DB::commit();

            return response()->json([
                'message' => 'Ticket generated successfully! 🎉',
                'ticket' => $ticket,
                'payment' => $payment
            ], 201);

        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            Log::error("Error generating ticket: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Failed to generate ticket. 🚧', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display a specific ticket's details.
     *
     * @param int $id The ID of the ticket to retrieve.
     * @return \Illuminate\Http\JsonResponse A JSON response containing ticket details.
     */
    public function show($id)
    {
        $ticket = Ticket::with(['trip', 'user', 'payment'])->find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }
        return response()->json([
            'ticket' => $ticket,
            'trip_details' => $ticket->trip,
            'user_details' => $ticket->user,
            'payment_details' => $ticket->payment,
            'amount_paid' => $ticket->payment ? $ticket->payment->amount : 'N/A'
        ]);
    }

    /**
     * Remove the specified ticket from storage.
     *
     * @param int $id The ID of the ticket to delete.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result.
     */
    public function destroy($id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully.']);
    }
}