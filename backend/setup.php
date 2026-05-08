<?php
/**
 * Kanika Parameswari Trust - One-time Setup Script
 * Usage: php setup.php
 * Creates database, tables, and emergency admin account.
 */

$host     = 'fist-o.com';
$username = 'fisto_yaagam';
$password = 'FRBgy4drttdYvTZbdByF';  // Enter your DB password here
$dbname   = 'fisto_yaagam';

// Emergency admin credentials
$emergencyUser = 'emergency_admin';
$emergencyPass = 'KPT@Secure2026!';
$emergencyName = 'Emergency Administrator';

try {
    // 1. Connect to the database and run schema
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/setup.sql');
    $pdo->exec($sql);
    echo "✅ Tables created in database: $dbname\n";

    // 3. Seed emergency admin
    $hash = password_hash($emergencyPass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO employees (username, full_name, email, role, password_hash)
         VALUES (:u, :n, :e, 'super_admin', :p)
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)"
    );
    $stmt->execute([
        ':u' => $emergencyUser,
        ':n' => $emergencyName,
        ':e' => 'admin@kptrust.org',
        ':p' => $hash,
    ]);
    echo "✅ Emergency admin account ready.\n";
    echo "   Username : $emergencyUser\n";
    echo "   Password : $emergencyPass\n";

    // 4. Seed a default admin too
    $defaultPass = 'admin123';
    $hash2 = password_hash($defaultPass, PASSWORD_DEFAULT);
    $stmt2 = $pdo->prepare(
        "INSERT INTO employees (username, full_name, role, password_hash)
         VALUES (:u, :n, 'admin', :p)
         ON DUPLICATE KEY UPDATE id=id"
    );
    $stmt2->execute([':u' => 'admin', ':n' => 'Admin', ':p' => $hash2]);
    echo "✅ Default admin: admin / admin123\n";
    echo "\n🎉 Setup complete!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
