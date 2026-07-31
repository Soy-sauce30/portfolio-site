<?php
$secret = 'sawyer2026';
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');

if (hash_equals('sha256=' . hash_hmac('sha256', $payload, $secret), $signature)) {
    shell_exec('cd /home/sawyerabrahani.com/public_html && git fetch origin && git reset --hard origin/main 2>&1');
    echo 'Deployed!';
} else {
    http_response_code(403);
    echo 'Forbidden';
}
