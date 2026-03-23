<?php
// includes/auth.php

require_once __DIR__ . '/../inc/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

// Timeout de 2 horas
if (isset($_SESSION['login_at']) && (time() - $_SESSION['login_at']) > 7200) {
    session_destroy();
    header('Location: ' . BASE_URL . '/admin/login.php?timeout=1');
    exit;
}

$_SESSION['login_at'] = time();