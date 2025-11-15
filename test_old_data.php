<?php

require __DIR__ . '/vendor/autoload.php';

use bitoliveira\Approval\Tests\Fixtures\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Create a simple test to verify old_data and data behavior
$employee = Employee::query()->create([
    'name' => 'Test User',
    'salary' => 5000
]);

$approval = $employee->requestApproval('update_field', [
    'name' => 'Test User',  // Same value, should not be in data
    'salary' => 6000,       // Different value, should be in data
    'department' => 'IT'    // New field, should be in data
], userId: 1);

echo "Old Data:\n";
print_r($approval->old_data);
echo "\nChanged Data (only different fields):\n";
print_r($approval->data);

// Verify expectations
$hasOldData = !empty($approval->old_data);
$dataOnlyHasChanges = !isset($approval->data['name']) && isset($approval->data['salary']) && isset($approval->data['department']);

echo "\n\nTest Results:\n";
echo "✓ old_data is captured: " . ($hasOldData ? "PASS" : "FAIL") . "\n";
echo "✓ data only contains changed fields: " . ($dataOnlyHasChanges ? "PASS" : "FAIL") . "\n";
