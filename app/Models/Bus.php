<?php

namespace App\Models;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_number',
        'type',
        'capacity',
        'status',
    ];

    /**
     * Get the schedules for the bus.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
