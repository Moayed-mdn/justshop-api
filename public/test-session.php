<?php
// Simple session test - bypasses Laravel completely
session_start();

// Set a test value
if (!isset($_SESSION['test_count'])) {
    $_SESSION['test_count'] = 1;
} else {
    $_SESSION['test_count']++;
}

header('Content-Type: application/json');
echo json_encode([
    'session_id' => session_id(),
    'test_count' => $_SESSION['test_count'],
    'cookies_sent' => $_COOKIE,
    'session_data' => $_SESSION,
    'message' => 'Reload this page - the count should increase'
]);
