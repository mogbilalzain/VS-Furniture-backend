<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Resetting Admin Password ===\n";

// Update admin password
$admin = App\Models\User::where('email', 'admin@vsfurniture.com')->first();
if ($admin) {
    $admin->password = Hash::make('admin123');
    $admin->save();
    echo "✅ Password reset for admin@vsfurniture.com\n";
} else {
    echo "❌ Admin user not found\n";
}

// Clear all old tokens
DB::table('personal_access_tokens')->delete();
echo "✅ All tokens cleared\n";

echo "\nYou can now login with:\n";
echo "Email: admin@vsfurniture.com\n";
echo "Password: admin123\n";
