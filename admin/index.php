<?php
// admin/index.php
// Ponto de entrada do painel — redireciona para dashboard
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

header('Location: pages/dashboard.php');
exit;