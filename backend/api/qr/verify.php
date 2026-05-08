<?php
/**
 * QR Verification endpoint (used by mobile app).
 *
 * GET  /api/qr/verify.php?key=XXXXX   → Returns QR details + status
 * POST /api/qr/verify.php             → Marks QR as verified
 *       Body: { "key": "XXXXX", "verified_by": "Scanner Name" }
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

$db = getDB();

// ─── GET: Fetch QR details by key ───
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $key = trim($_GET['key'] ?? '');
    if ($key === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Verification key is required']);
        exit;
    }

    $stmt = $db->prepare(
        "SELECT id, customer_name, phone_number, yaagam_name, event_datetime,
                verification_key, is_verified, verified_at, verified_by, notes, created_at
         FROM qr_codes WHERE verification_key = :k"
    );
    $stmt->execute([':k' => $key]);
    $qr = $stmt->fetch();

    if (!$qr) {
        http_response_code(404);
        echo json_encode(['error' => 'QR code not found', 'valid' => false]);
        exit;
    }

    echo json_encode([
        'success'     => true,
        'valid'       => true,
        'is_verified' => (bool)$qr['is_verified'],
        'qr_code'     => $qr,
    ]);
    exit;
}

// ─── POST: Mark QR as verified ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $key        = trim($input['key']         ?? '');
    $verifiedBy = trim($input['verified_by'] ?? 'Mobile App');

    if ($key === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Verification key is required']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, is_verified, event_datetime, customer_name, yaagam_name FROM qr_codes WHERE verification_key = :k");
    $stmt->execute([':k' => $key]);
    $qr = $stmt->fetch();

    if (!$qr) {
        http_response_code(404);
        echo json_encode(['error' => 'QR code not found', 'valid' => false]);
        exit;
    }

    // Check if the QR code is expired (scanned > 30 mins after event_datetime)
    $scannedTime = isset($input['scanned_time']) ? strtotime($input['scanned_time']) : time();
    $eventTime = strtotime($qr['event_datetime']);
    
    // Allow up to 30 minutes late (30 * 60 seconds)
    $maxAllowedTime = $eventTime + (30 * 60);

    if ($scannedTime > $maxAllowedTime) {
        http_response_code(403);
        echo json_encode([
            'error'       => 'This QR code has expired. The event time has passed by more than 30 minutes.',
            'valid'       => false,
            'is_verified' => (bool)$qr['is_verified'],
            'event_date'  => $qr['event_datetime']
        ]);
        exit;
    }

    if ((bool)$qr['is_verified']) {
        http_response_code(409);
        echo json_encode([
            'error'       => 'QR code has already been verified/scanned',
            'is_verified' => true,
            'valid'       => false,
            'customer'    => $qr['customer_name']
        ]);
        exit;
    }

    // Mark as verified
    $stmt = $db->prepare(
        "UPDATE qr_codes SET is_verified = 1, verified_at = NOW(), verified_by = :vb WHERE id = :id"
    );
    $stmt->execute([':vb' => $verifiedBy, ':id' => $qr['id']]);

    // Log
    $db->prepare("INSERT INTO activity_log (action, details) VALUES ('qr_verified', :d)")
       ->execute([':d' => "QR #" . $qr['id'] . " verified by $verifiedBy"]);

    echo json_encode([
        'success'     => true,
        'message'     => 'QR code verified successfully',
        'is_verified' => true,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
