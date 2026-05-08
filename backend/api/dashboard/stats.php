<?php
/**
 * GET /api/dashboard/stats.php
 * Returns aggregate counts for the dashboard cards.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
$db   = getDB();

$totalEmployees = $db->query("SELECT COUNT(*) FROM employees WHERE is_active=1")->fetchColumn();
$totalQR        = $db->query("SELECT COUNT(*) FROM qr_codes")->fetchColumn();
$verifiedQR     = $db->query("SELECT COUNT(*) FROM qr_codes WHERE is_verified=1")->fetchColumn();
$pendingQR      = $db->query("SELECT COUNT(*) FROM qr_codes WHERE is_verified=0")->fetchColumn();

$todayQR = $db->query(
    "SELECT COUNT(*) FROM qr_codes WHERE DATE(created_at) = CURDATE()"
)->fetchColumn();

$upcomingEvents = $db->query(
    "SELECT COUNT(*) FROM qr_codes WHERE event_datetime >= NOW() AND is_verified=0"
)->fetchColumn();

// Recent QR codes
$recent = $db->query(
    "SELECT id, customer_name, yaagam_name, event_datetime, is_verified, created_at
     FROM qr_codes ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// Recent activity
$activity = $db->query(
    "SELECT a.action, a.details, a.created_at, e.full_name
     FROM activity_log a LEFT JOIN employees e ON e.id = a.employee_id
     ORDER BY a.created_at DESC LIMIT 10"
)->fetchAll();

echo json_encode([
    'success' => true,
    'stats' => [
        'total_employees'  => (int)$totalEmployees,
        'total_qr'         => (int)$totalQR,
        'verified_qr'      => (int)$verifiedQR,
        'pending_qr'       => (int)$pendingQR,
        'today_qr'         => (int)$todayQR,
        'upcoming_events'  => (int)$upcomingEvents,
    ],
    'recent_qr'   => $recent,
    'activity_log' => $activity,
]);
