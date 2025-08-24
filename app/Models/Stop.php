<?php

namespace App\Models;

use App\Models\Route;
use Illuminate\Database\Eloquent\Model;

class Stop extends Model
{
     protected $fillable = [
        'route_id',
        'stop_name',
        'stop_order',
    ];

    // Define the relationship to Route
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }
}
