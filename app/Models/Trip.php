<?php

namespace App\Models;

use App\Models\Bus; 
use App\Models\User;
use App\Models\Route;
use App\Models\Ticket;
use Illuminate\Support\Str; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'user_id',
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
     * A Trip belongs to a single User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    /**
     * A Trip has many Tickets.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    
    public static function generateTripCode(string $routeCode): string
    {
        $lastTrip = self::where('trip_code', 'like', "{$routeCode}%")
                        ->orderByDesc('trip_code')
                        ->first();

        // Start the counter at 1.
        $nextNumber = 1;

        if ($lastTrip && $lastTrip->trip_code) {
            $lastIdNumber = (int) substr($lastTrip->trip_code, strlen($routeCode) + 1);
            $nextNumber = $lastIdNumber + 1;
        }

        
        return sprintf('%s-%03d', $routeCode, $nextNumber);
    }
}
