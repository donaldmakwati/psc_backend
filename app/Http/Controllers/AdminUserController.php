<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\StaffIdGenerator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function storeAdmin(Request $request)
    {
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'address'=>  'required|string|max:255',
            'phone'=> 'required|string|max:255'
       
        ]);

        $role = Role::where('name', 'admin')->first();
        if (!$role) return response()->json(['message' => 'Admin role not found.'], 400);

        $staffId = StaffIdGenerator::generateId('admin');
        if (!$staffId) return response()->json(['message' => 'Failed to generate ID.'], 500);

        $user = User::create([
            'name' => $validated['name'],
            'surname' => $validated['surname'],
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'staff_id' => $staffId,
            'gender' => 'male', 
        ]);

        $user->roles()->attach($role);

        return response()->json([
            'message' => 'Admin created successfully.',
            'user' => $user->only(['id', 'name', 'surname', 'email','phone','address', 'staff_id']),
        ], 201);
    }

    /**
     * 📋 List staff/operator users (paginated) with statistics.
     */
    public function index()
    {
        $roleIds = Role::whereIn('name', ['staff', 'operator'])->pluck('id');
        
        // Fetch all staff and operator users to calculate statistics
        $allUsers = User::whereHas('roles', fn($q) => $q->whereIn('role_id', $roleIds))
            ->get();

        // Calculate statistics
        $statistics = [
            'total_users' => $allUsers->count(),
            'role_counts' => $allUsers->groupBy(fn($user) => $user->roles->first()->name)
                               ->map(fn($items) => $items->count()),
            'gender_counts' => $allUsers->groupBy('gender')->map(fn($items) => $items->count()),
        ];

        // Get paginated users for the main table/list
        $paginatedUsers = User::whereHas('roles', fn($q) => $q->whereIn('role_id', $roleIds))
            ->select('id', 'name', 'surname', 'email', 'address', 'phone', 'gender', 'staff_id')
            ->with('roles:id,name')
            ->paginate(10);
            
        return response()->json([
            'users' => $paginatedUsers,
            'statistics' => $statistics,
        ]);
    }

    /**
     * ➕ Store a new staff or operator user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => ['required', Rule::in(['male', 'female'])],
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(['staff', 'operator'])],
        ]);

        $role = Role::where('name', $validated['role'])->firstOrFail();
        //if (!$role) return response()->json(['message' => 'Invalid role.'], 400);

        $staffId = StaffIdGenerator::generateId($validated['role']);
        if (!$staffId) return response()->json(['message' => 'Failed to generate ID.'], 500);

        $user = User::create([
            'name' => $validated['name'],
            'surname' => $validated['surname'],
            'email' => $validated['email'],
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'gender' => $validated['gender'],
            'password' => Hash::make($validated['password']),
            'staff_id' => $staffId,
        ]);

        $user->roles()->attach($role);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user->only(['id', 'name', 'surname', 'email', 'address', 'phone', 'gender', 'staff_id']),
        ], 201);
    }

    /**
     * 🔍 Show a specific user with roles.
     */
    public function show(User $user)
    {
        $user->load('roles:id,name');
        return response()->json($user->only(['id', 'name', 'surname', 'email', 'address', 'phone', 'gender', 'staff_id', 'roles']));
    }

    /**
     * 🖊️ Update a user (staff or operator).
     */
    public function update(Request $request, User $user)
    {
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Cannot update admin users here.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => ['required', Rule::in(['male', 'female'])],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(['staff', 'operator'])],
        ]);

        $userData = collect($validated)->except('role')->toArray();

        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        $role = Role::where('name', $validated['role'])->first();
        if ($role) $user->roles()->sync([$role->id]);

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user->fresh('roles'),
        ]);
    }

    /**
     * ❌ Delete a user (except admin).
     */
    public function destroy(User $user)
    {
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Cannot delete admin users here.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
