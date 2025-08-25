<?php

namespace App\Models;

use App\Models\Role;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Services\StaffIdGenerator;
use Tymon\JWTAuth\Contracts\JWTSubject; // ✅ Required for JWT

class User extends Authenticatable implements JWTSubject // ✅ Implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'surname',    
        'email',
        'address',    
        'phone',      
        'gender',     
        'password',
        'staff_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {            
        });
    }

    /**
     * The roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $roleName The name of the role to check.
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    /**
     * Check if the user is an Admin.
     *
     * @return bool
     */

    // app/Models/User.php
    

   public function isAdmin(): bool
{
    return $this->hasRole('admin');
}


    /**
     * Check if the user is an Operator.
     *
     * @return bool
     */
    public function isOperator(): bool
    {
        return $this->hasRole('operator');
    }

    /**
     * Check if the user is staff.
     *
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    // --- JWT Authentication Methods ---

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Returns the user's primary key (usually 'id')
    }

    /**
     * Return a key-value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
       
        return [];
    }
}