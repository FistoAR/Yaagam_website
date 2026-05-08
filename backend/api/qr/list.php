<?php
/**
 * GET /api/qr/list.php
 * Query params: ?page=1&limit=20&search=&status=all|verified|pending
 * Returns paginated QR code list.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
$db   = getDB();

$page   = max(1, (int)($_GET['page']   ?? 1));
$limit  = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "(customer_name LIKE :s OR phone_number LIKE :s2 OR yaagam_name LIKE :s3 OR verification_key LIKE :s4)";
    $params[':s']  = "%$search%";
    $params[':s2'] = "%$search%";
    $params[':s3'] = "%$search%";
    $params[':s4'] = "%$search%";
}

if ($status === 'verified') {
    $where[] = "is_verified = 1";
} elseif ($status === 'pending') {
    $where[] = "is_verified = 0";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM qr_codes $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Fetch
$sql = "SELECT q.*, e.full_name AS created_by_name
        FROM qr_codes q
        LEFT JOIN employees e ON e.id = q.created_by
        $whereSQL
        ORDER BY q.created_at DESC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

echo json_encode([
    'success'    => true,
    'qr_codes'   => $rows,
    'pagination' => [
        'page'       => $page,
        'limit'      => $limit,
        'total'      => $total,
        'total_pages'=> (int)ceil($total / $limit),
    ],
]);
