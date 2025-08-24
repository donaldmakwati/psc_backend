<?php

namespace App\Services;

use App\Models\User;

class StaffIdGenerator
{
    /**
     * Defines the prefix for each role.
     * You can customize these prefixes.
     * @var array<string, string>
     */
    protected static array $rolePrefixes = [
        'admin'    => 'ADM',  // Admin ID will start with ADM (e.g., ADM001)
        'staff'    => 'PSC',  // Staff ID will start with PSC (e.g., PSC100, PSC101)
        'operator' => 'OPR',  // Operator ID will start with OPR (e.g., OPR200, OPR201)
    ];

    /**
     * Defines the starting number for each role's ID sequence.
     * This allows different roles to have different number ranges.
     * @var array<string, int>
     */
    protected static array $roleStartNumbers = [
        'admin'    => 100,
        'staff'    => 100,
        'operator' => 200,
    ];

    /**
     * Generates a unique staff ID based on the user's role.
     *
     * @param string $roleName The name of the role (e.g., 'admin', 'staff', 'operator').
     * @return string|null The generated staff ID, or null if the role is not defined.
     */
    public static function generateId(string $roleName): ?string
    {
        // Check if the role has a defined prefix
        if (!isset(self::$rolePrefixes[$roleName])) {
            // Log an error or throw an exception if an undefined role is passed
            \Log::warning("Attempted to generate staff ID for undefined role: {$roleName}");
            return null;
        }

        $prefix = self::$rolePrefixes[$roleName];
        $startNumber = self::$roleStartNumbers[$roleName] ?? 1; // Default to 1 if no specific start number is set

        // Find the last user with a staff_id matching the current role's prefix.
        // We order by staff_id in descending order to get the highest numeric part.
        $lastUser = User::where('staff_id', 'like', "{$prefix}%")
                        ->orderByDesc('staff_id')
                        ->first();

        $nextNumber = $startNumber;

        if ($lastUser && $lastUser->staff_id) {
            // Extract the numeric part from the last staff_id.
            // Example: if staff_id is 'ADM005', substr will get '005', then (int) converts to 5.
            $lastIdNumber = (int) substr($lastUser->staff_id, strlen($prefix));

            // Ensure the next number is at least the defined start number for the role.
            // This handles cases where the last ID's number might be lower than the start number
            // (e.g., if you manually deleted higher IDs or changed the start number).
            $nextNumber = max($lastIdNumber + 1, $startNumber);
        }

        // Format the ID: prefix + zero-padded number (e.g., ADM001, PSC100, OPR200)
        // %03d ensures the number is padded with leading zeros to 3 digits.
        // Adjust '03d' if you need more digits (e.g., '%04d' for ADM0001).
        return sprintf('%s%03d', $prefix, $nextNumber);
    }
}