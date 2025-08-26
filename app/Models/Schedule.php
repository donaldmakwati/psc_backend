<?php

namespace App\Models;

use App\Models\Bus;
use App\Models\Route;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
     
     protected $fillable = [
        'route_id',
        'bus_id',
        'departure_time',
        'arrival_time',
        // 'price',
        'status',
    ];

    
    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}
