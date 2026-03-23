<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['admin']) ||
    $_SESSION['admin'] !== true ||
    !isset($_SESSION['admin_id'])
) {
    header('Location: login.php');
    exit;
}

// opcional (nível mais profissional)
$stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ? AND ativo = 1");
$stmt->execute([$_SESSION['admin_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

header('Location: ' . BASE_URL . '/pages/dashboard.php');
exit;