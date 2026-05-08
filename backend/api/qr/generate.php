<?php
/**
 * POST /api/qr/generate.php
 * Body: { "customer_name","phone_number","yaagam_name","event_datetime","notes" }
 * Generates a unique verification key and stores the QR record.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user  = requireAuth();
$input = json_decode(file_get_contents('php://input'), true);

$customerName  = trim($input['customer_name']  ?? '');
$phoneNumber   = trim($input['phone_number']   ?? '');
$yaagamName    = trim($input['yaagam_name']     ?? '');
$eventDatetime = trim($input['event_datetime']  ?? '');
$notes         = trim($input['notes']           ?? '');

if ($customerName === '' || $phoneNumber === '' || $yaagamName === '' || $eventDatetime === '') {
    http_response_code(400);
    echo json_encode(['error' => 'customer_name, phone_number, yaagam_name, and event_datetime are required']);
    exit;
}

$db = getDB();

// Generate a unique verification key
$verificationKey = strtoupper(bin2hex(random_bytes(16)));  // 32-char hex string

$stmt = $db->prepare(
    "INSERT INTO qr_codes (customer_name, phone_number, yaagam_name, event_datetime, verification_key, notes, created_by)
     VALUES (:cn, :pn, :yn, :ed, :vk, :nt, :cb)"
);
$stmt->execute([
    ':cn' => $customerName,
    ':pn' => $phoneNumber,
    ':yn' => $yaagamName,
    ':ed' => $eventDatetime,
    ':vk' => $verificationKey,
    ':nt' => $notes ?: null,
    ':cb' => $user['id'],
]);

$newId = $db->lastInsertId();

// Log activity
$db->prepare("INSERT INTO activity_log (employee_id,action,details) VALUES (:a,'generate_qr',:d)")
   ->execute([':a' => $user['id'], ':d' => "QR #$newId for $customerName ($yaagamName)"]);

echo json_encode([
    'success' => true,
    'qr_code' => [
        'id'               => (int)$newId,
        'customer_name'    => $customerName,
        'phone_number'     => $phoneNumber,
        'yaagam_name'      => $yaagamName,
        'event_datetime'   => $eventDatetime,
        'verification_key' => $verificationKey,
        'is_verified'      => false,
        'notes'            => $notes,
    ],
]);
