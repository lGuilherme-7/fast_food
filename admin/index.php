<?php
require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../../inc/db.php';

// admin/index.php
// Ponto de entrada do painel — redireciona conforme sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header('Location: pages/dashboard.php');
} else {
    header('Location: login.php');
}
exit;