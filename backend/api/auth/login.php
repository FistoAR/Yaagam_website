<?php
/**
 * POST /api/auth/login.php
 * Body: { "username": "...", "password": "..." }
 * Returns: { "token": "...", "user": {...} }
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password are required']);
    exit;
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM employees WHERE username = :u AND is_active = 1");
$stmt->execute([':u' => $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// Generate session token
$token = bin2hex(random_bytes(48));
$expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

$stmt = $db->prepare(
    "INSERT INTO auth_sessions (employee_id, token, expires_at, ip_address)
     VALUES (:eid, :token, :exp, :ip)"
);
$stmt->execute([
    ':eid'   => $user['id'],
    ':token' => $token,
    ':exp'   => $expiresAt,
    ':ip'    => $ip,
]);

// Log activity
$stmt = $db->prepare(
    "INSERT INTO activity_log (employee_id, action, ip_address) VALUES (:eid, 'login', :ip)"
);
$stmt->execute([':eid' => $user['id'], ':ip' => $ip]);

unset($user['password_hash']);

echo json_encode([
    'success' => true,
    'token'   => $token,
    'user'    => $user,
]);
