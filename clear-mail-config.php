<?php
/**
 * Clear Mail Configuration Cache
 * 
 * This script clears Laravel's configuration cache to ensure
 * the new Brevo email configuration is loaded properly.
 */

echo "🧹 Clearing Mail Configuration Cache...\n";
echo "=====================================\n\n";

// Clear configuration cache
echo "1. Clearing configuration cache...\n";
$output = shell_exec('php artisan config:clear 2>&1');
echo $output . "\n";

// Clear application cache
echo "2. Clearing application cache...\n";
$output = shell_exec('php artisan cache:clear 2>&1');
echo $output . "\n";

// Clear route cache
echo "3. Clearing route cache...\n";
$output = shell_exec('php artisan route:clear 2>&1');
echo $output . "\n";

// Clear view cache
echo "4. Clearing view cache...\n";
$output = shell_exec('php artisan view:clear 2>&1');
echo $output . "\n";

// Optimize configuration
echo "5. Optimizing configuration...\n";
$output = shell_exec('php artisan config:cache 2>&1');
echo $output . "\n";

echo "\n✅ Mail configuration cache cleared successfully!\n";
echo "The application will now use the Brevo SMTP configuration.\n\n";

echo "📧 Current Mail Configuration:\n";
echo "MAIL_MAILER: " . env('MAIL_MAILER', 'smtp') . "\n";
echo "MAIL_HOST: " . env('MAIL_HOST', 'smtp-relay.brevo.com') . "\n";
echo "MAIL_PORT: " . env('MAIL_PORT', '587') . "\n";
echo "MAIL_USERNAME: " . env('MAIL_USERNAME', '98f98d001@smtp-brevo.com') . "\n";
echo "MAIL_FROM_ADDRESS: " . env('MAIL_FROM_ADDRESS', 'johnninonavares7@gmail.com') . "\n";
echo "MAIL_FROM_NAME: " . env('MAIL_FROM_NAME', 'SkillsXchange') . "\n";
