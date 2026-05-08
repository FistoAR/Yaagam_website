<?php
/**
 * GET /api/reports/list.php
 * Query: ?from=YYYY-MM-DD&to=YYYY-MM-DD&status=all|verified|pending
 * Returns QR data grouped by date for reporting.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
$db   = getDB();

$from   = $_GET['from']   ?? date('Y-m-01');
$to     = $_GET['to']     ?? date('Y-m-d');
$status = $_GET['status'] ?? 'all';

$where  = ["DATE(q.created_at) BETWEEN :from AND :to"];
$params = [':from' => $from, ':to' => $to];

if ($status === 'verified') {
    $where[] = "q.is_verified = 1";
} elseif ($status === 'pending') {
    $where[] = "q.is_verified = 0";
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Summary stats for the period
$summaryStmt = $db->prepare(
    "SELECT COUNT(*) as total,
            SUM(is_verified) as verified,
            COUNT(*) - SUM(is_verified) as pending
     FROM qr_codes q $whereSQL"
);
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();

// Daily breakdown
$dailyStmt = $db->prepare(
    "SELECT DATE(q.created_at) as date,
            COUNT(*) as total,
            SUM(q.is_verified) as verified
     FROM qr_codes q $whereSQL
     GROUP BY DATE(q.created_at)
     ORDER BY date DESC"
);
$dailyStmt->execute($params);
$daily = $dailyStmt->fetchAll();

// Yaagam breakdown
$yaagamStmt = $db->prepare(
    "SELECT q.yaagam_name, COUNT(*) as total, SUM(q.is_verified) as verified
     FROM qr_codes q $whereSQL
     GROUP BY q.yaagam_name ORDER BY total DESC"
);
$yaagamStmt->execute($params);
$byYaagam = $yaagamStmt->fetchAll();

// Full records
$recordsStmt = $db->prepare(
    "SELECT q.*, e.full_name as created_by_name
     FROM qr_codes q LEFT JOIN employees e ON e.id = q.created_by
     $whereSQL ORDER BY q.created_at DESC LIMIT 500"
);
$recordsStmt->execute($params);
$records = $recordsStmt->fetchAll();

echo json_encode([
    'success'   => true,
    'period'    => ['from' => $from, 'to' => $to],
    'summary'   => $summary,
    'daily'     => $daily,
    'by_yaagam' => $byYaagam,
    'records'   => $records,
]);
