<?php
/**
 * Database connection helper.
 * Returns a PDO instance connected to kanika_trust.
 */

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host   = 'fist-o.com';
    $dbname = 'fisto_yaagam';
    $user   = 'fisto_yaagam';
    $pass   = 'FRBgy4drttdYvTZbdByF';

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}
