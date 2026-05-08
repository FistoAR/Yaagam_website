<?php
/**
 * GET  /api/employees/list.php         → List all employees
 * PUT  /api/employees/list.php         → Update an employee
 *      Body: { "id", "full_name", "email", "phone", "role", "is_active", "password"(optional) }
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/auth.php';

$admin = requireAdmin();
$db    = getDB();

// ─── GET: list ───
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $db->query(
        "SELECT id, username, full_name, email, phone, role, is_active, created_at, updated_at
         FROM employees ORDER BY created_at DESC"
    )->fetchAll();

    echo json_encode(['success' => true, 'employees' => $rows]);
    exit;
}

// ─── PUT: update ───
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Employee id is required']);
        exit;
    }

    $fields = [];
    $params = [':id' => $id];

    if (isset($input['full_name']) && trim($input['full_name']) !== '') {
        $fields[] = "full_name = :fn";
        $params[':fn'] = trim($input['full_name']);
    }
    if (array_key_exists('email', $input)) {
        $fields[] = "email = :em";
        $params[':em'] = trim($input['email']) ?: null;
    }
    if (array_key_exists('phone', $input)) {
        $fields[] = "phone = :ph";
        $params[':ph'] = trim($input['phone']) ?: null;
    }
    if (isset($input['role']) && in_array($input['role'], ['admin','employee'])) {
        $fields[] = "role = :rl";
        $params[':rl'] = $input['role'];
    }
    if (isset($input['is_active'])) {
        $fields[] = "is_active = :ia";
        $params[':ia'] = $input['is_active'] ? 1 : 0;
    }
    if (isset($input['password']) && $input['password'] !== '') {
        $fields[] = "password_hash = :pw";
        $params[':pw'] = password_hash($input['password'], PASSWORD_DEFAULT);
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        exit;
    }

    $sql = "UPDATE employees SET " . implode(', ', $fields) . " WHERE id = :id";
    $db->prepare($sql)->execute($params);

    echo json_encode(['success' => true, 'message' => 'Employee updated']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
