<?php
/**
 * FIXMATE - Logout Controller
 */
require_once __DIR__ . '/includes/auth.php';
setFlash('info', 'You have been signed out safely.');
logoutUser('./');
