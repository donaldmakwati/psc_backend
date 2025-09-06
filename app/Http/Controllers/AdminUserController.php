<?php
namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\StaffIdGenerator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    public function storeAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3',
                'regex:/^[a-zA-Z]+$/'
            ],
            'surname' => [
                'required',
                'string',
                'max:255',
                'min:3',
                'regex:/^[a-zA-Z]+$/'
            ],
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'address' => 'required|string|max:255',
            'phone' => [
                'nullable',
                'string',
                'max:255',
                'unique:users',
                'regex:/^(\+263|0)(7[0-9]|8[6-8])[0-9]{7}$/'
            ],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
        ], [
            'name.regex' => 'This is not a name',
            'name.min' => 'Enter proper name',
            'surname.regex' => 'This is not a name',
            'surname.min' => 'Enter proper name',
            'phone.regex' => 'Please enter a valid Zimbabwean phone number',
        ]);

        $role = Role::where('name', 'admin')->first();
        if (!$role) return response()->json(['message' => 'Admin role not found.'], 400);

        $staffId = StaffIdGenerator::generateId('admin');
        if (!$staffId) return response()->json(['message' => 'Failed to generate ID.'], 500);

        // Store plain password before hashing
        $plainPassword = $validated['password'];

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'surname' => $validated['surname'],
                'address' => $validated['address'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'staff_id' => $staffId,
                'gender' => $validated['gender'] ?? null,
            ]);

            $user->roles()->attach($role);

            // Send credentials email
            $user->sendCredentialsEmail($plainPassword);

            DB::commit();

            return response()->json([
                'message' => 'Admin created successfully. Credentials have been sent to their email.',
                'user' => $user->only(['id', 'name', 'surname', 'email', 'phone', 'address', 'staff_id']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin creation failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to create admin. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📋 List staff/operator users (paginated) with statistics.
     */
    public function index()
    {
        $roleIds = Role::whereIn('name', ['staff', 'operator'])->pluck('id');

        // Fetch all staff and operator users to calculate statistics
        $allUsers = User::whereHas('roles', fn ($q) => $q->whereIn('role_id', $roleIds))
            ->get();

        // Calculate statistics
        $statistics = [
            'total_users' => $allUsers->count(),
            'role_counts' => $allUsers->groupBy(fn ($user) => $user->roles->first()->name)
                ->map(fn ($items) => $items->count()),
            'gender_counts' => $allUsers->groupBy('gender')->map(fn ($items) => $items->count()),
        ];

        // Get paginated users for the main table/list
        $paginatedUsers = User::whereHas('roles', fn ($q) => $q->whereIn('role_id', $roleIds))
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
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3',
                'regex:/^[a-zA-Z]+$/'
            ],
            'surname' => [
                'required',
                'string',
                'max:255',
                'min:3',
                'regex:/^[a-zA-Z]+$/'
            ],
            'email' => 'required|string|email|max:255|unique:users',
            'address' => 'nullable|string|max:255',
            'phone' => [
                'nullable',
                'string',
                'min:9',
                'max:11',
                'unique:users',
                'regex:/^(\+263|0)((7[0-9]|8[6-8])[0-9]{7}|(2[0-9]|4|8)[0-9]{5,6})$/'
            ],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(['staff', 'operator'])],
        ], [
            'name.regex' => 'This is not a name',
            'name.min' => 'Enter proper name',
            'surname.regex' => 'This is not a name',
            'surname.min' => 'Enter proper name',
            'phone.regex' => 'Please enter a valid Zimbabwean phone number',
        ]);

        $role = Role::where('name', $validated['role'])->firstOrFail();
        $staffId = StaffIdGenerator::generateId($validated['role']);
        
        if (!$staffId) return response()->json(['message' => 'Failed to generate ID.'], 500);

        // Store plain password before hashing
        $plainPassword = $validated['password'];

        try {
            DB::beginTransaction();

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

            // Send credentials email
            $user->sendCredentialsEmail($plainPassword);

            DB::commit();

            return response()->json([
                'message' => 'User created successfully. Credentials have been sent to their email.',
                'user' => $user->only(['id', 'name', 'surname', 'email', 'address', 'phone', 'gender', 'staff_id']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User creation failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to create user. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
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
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3',
                'regex:/^[a-zA-Z]+$/'
            ],
            'surname' => [
                'required',
                'string',
                'max:255',
                'min:3',
                'regex:/^[a-zA-Z]+$/'
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'address' => 'nullable|string|max:255',
            'phone' => [
                'nullable',
                'string',
                'min:9',
                'max:11',
                Rule::unique('users')->ignore($user->id),
                'regex:/^(\+263|0)((7[0-9]|8[6-8])[0-9]{7}|(2[0-9]|4|8)[0-9]{5,6})$/'
            ],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(['staff', 'operator'])],
        ], [
            'name.regex' => 'This is not a name',
            'name.min' => 'Enter proper name',
            'surname.regex' => 'This is not a name',
            'surname.min' => 'Enter proper name',
            'phone.regex' => 'Please enter a valid Zimbabwean phone number',
        ]);

        try {
            DB::beginTransaction();

            // Handle password update
            if ($request->filled('password')) {
                $validated['password'] = Hash::make($validated['password']);
                
                // If password is being updated, send new credentials email
                $user->sendCredentialsEmail($request->input('password'));
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            $role = Role::where('name', $validated['role'])->first();
            if ($role) $user->roles()->sync([$role->id]);

            DB::commit();

            return response()->json([
                'message' => 'User updated successfully.' . ($request->filled('password') ? ' New credentials sent to email.' : ''),
                'user' => $user->fresh('roles'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User update failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to update user. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
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