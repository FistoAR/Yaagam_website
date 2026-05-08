<?php
/**
 * POST /api/auth/register.php
 * Body: { "username","full_name","email","phone","password","role" }
 * Requires: admin auth
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$admin = requireAdmin();
$input = json_decode(file_get_contents('php://input'), true);

$username  = trim($input['username']  ?? '');
$fullName  = trim($input['full_name'] ?? '');
$email     = trim($input['email']     ?? '');
$phone     = trim($input['phone']     ?? '');
$password  = $input['password']       ?? '';
$role      = $input['role']           ?? 'employee';

if ($username === '' || $fullName === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'username, full_name, and password are required']);
    exit;
}

if (!in_array($role, ['admin', 'employee'])) {
    $role = 'employee';
}

$db = getDB();

// Check duplicate
$stmt = $db->prepare("SELECT id FROM employees WHERE username = :u");
$stmt->execute([':u' => $username]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Username already exists']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare(
    "INSERT INTO employees (username, full_name, email, phone, password_hash, role)
     VALUES (:u, :n, :e, :ph, :pw, :r)"
);
$stmt->execute([
    ':u'  => $username,
    ':n'  => $fullName,
    ':e'  => $email ?: null,
    ':ph' => $phone ?: null,
    ':pw' => $hash,
    ':r'  => $role,
]);

$newId = $db->lastInsertId();

// Log
$db->prepare("INSERT INTO activity_log (employee_id,action,details) VALUES (:a,'register_employee',:d)")
   ->execute([':a' => $admin['id'], ':d' => "Registered user: $username (id:$newId)"]);

echo json_encode([
    'success' => true,
    'employee' => [
        'id'        => (int)$newId,
        'username'  => $username,
        'full_name' => $fullName,
        'email'     => $email,
        'phone'     => $phone,
        'role'      => $role,
    ],
]);
