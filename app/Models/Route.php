<?php

namespace App\Models;

use App\Models\Stop;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
        {
        protected $fillable = [
        'origin',
        'destination',
        'distance_km',
        'estimated_time',
        'route_code'
        ];

        public function stops()
        {
        return $this->hasMany(Stop::class, 'route_id');
        }

        public function schedules()
        {
        return $this->hasMany(Schedule::class);
        }

}
