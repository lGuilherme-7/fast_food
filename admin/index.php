<?php
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