<?php
/**
 * Auth middleware – validates the Bearer token and returns the employee row.
 * Usage:  $user = requireAuth();
 */

require_once __DIR__ . '/database.php';

function requireAuth(): array {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing or invalid Authorization header']);
        exit;
    }

    $token = $m[1];
    $db = getDB();

    $stmt = $db->prepare(
        "SELECT e.* FROM auth_sessions s
         JOIN employees e ON e.id = s.employee_id
         WHERE s.token = :token AND s.expires_at > NOW() AND e.is_active = 1"
    );
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Session expired or invalid']);
        exit;
    }

    unset($user['password_hash']);
    return $user;
}

function requireAdmin(): array {
    $user = requireAuth();
    if (!in_array($user['role'], ['admin', 'super_admin'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
    return $user;
}
