<?php

namespace App\Models;

use App\Models\Trip;
use App\Models\Payment;
use App\Models\User; // <-- Added this import
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'user_id', 'ticket_code', 'seat_number', 'status'];

    /**
     * A Ticket belongs to a single Trip.
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
    
    /**
     * A Ticket belongs to a single User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A Ticket has one Payment.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Generate a unique ticket code based on the route and a random string.
     * @param string $routeCode A short code for the route, e.g., 'HRE-BYO'
     * @return string
     */
    public static function generateTicketCode($routeCode)
    {
        // Generate a random 8-character uppercase alphanumeric string
        $randomPart = strtoupper(Str::random(8));

        // Combine the route code with the random part
        return "{$routeCode}-{$randomPart}";
    }
}
