<?php
// Admin Authorization
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Ensure user is logged in
requireLogin();

// Fetch current user
$user = currentUser();

// Check if user is an admin
if (!$user || $user['role'] !== 'admin') {
    // Redirect non-admins to admin login
    header('Location: /admin/login');
    exit;
}
