<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

function require_admin() {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
}