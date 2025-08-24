<?php

namespace App\Models;

use App\Models\Trip;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Payment Model
 * Represents a payment transaction for a ticket.
 */
class Payment extends Model
{
    use HasFactory;

    // Corrected fillable fields to include trip_id and user_id
    protected $fillable = ['ticket_id', 'trip_id', 'user_id', 'amount', 'method', 'status', 'transaction_id'];

    /**
     * A Payment belongs to a single Ticket.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * A Payment belongs to a single Trip.
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * A Payment belongs to a single User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
