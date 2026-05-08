<?php
/**
 * GET /api/auth/check.php
 * Validates current token and returns user info.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
echo json_encode(['success' => true, 'user' => $user]);
