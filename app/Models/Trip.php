<?php

namespace App\Models;

use App\Models\Route;
use App\Models\Bus; 
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str; 

/**
 * Trip Model
 * Represents a single journey on a specific date and time.
 */
class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'bus_id',
        'trip_code',
        'departure_time',
        'available_seats'
    ];

    /**
     * A Trip belongs to a single Route.
     */
    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * A Trip belongs to a single Bus.
     */
    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    /**
     * A Trip has many Tickets.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Generates a unique trip code based on the route code.
     * The code will be formatted as `ROUTE_CODE001` (e.g., HRE-GLN-VIEW-001).
     *
     * @param string $routeCode The code of the associated route.
     * @return string The newly generated unique trip code.
     */
    public static function generateTripCode(string $routeCode): string
    {
        // Find the last trip with a code that starts with the given route code.
        // We order by the trip code to find the highest number.
        $lastTrip = self::where('trip_code', 'like', "{$routeCode}%")
                        ->orderByDesc('trip_code')
                        ->first();

        // Start the counter at 1.
        $nextNumber = 1;

        if ($lastTrip && $lastTrip->trip_code) {
            // If a last trip was found, extract the numeric part of the code.
            // Example: "HRE-GLN-VIEW-005" -> substr finds "-005"
            // The `(int)` cast handles converting this to a number, ignoring non-numeric parts.
            $lastIdNumber = (int) substr($lastTrip->trip_code, strlen($routeCode) + 1);
            
            // Increment the number for the new trip code.
            $nextNumber = $lastIdNumber + 1;
        }

        // Format the ID: route code + a hyphen + zero-padded number.
        // %03d ensures the number is padded with leading zeros to 3 digits.
        return sprintf('%s-%03d', $routeCode, $nextNumber);
    }
}
