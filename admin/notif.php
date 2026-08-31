<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
if (!current_admin()) { http_response_code(401); echo json_encode(['total' => 0, 'items' => []]); exit; }
echo json_encode(admin_notifications());
