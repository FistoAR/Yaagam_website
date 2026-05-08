<?php
/**
 * POST /api/auth/logout.php
 * Deletes the current session token.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
    $db = getDB();
    $db->prepare("DELETE FROM auth_sessions WHERE token = :t")->execute([':t' => $m[1]]);
}

echo json_encode(['success' => true]);
