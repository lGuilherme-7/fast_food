<?php
// ============================================
// logout.php
// ============================================
session_start();
session_unset();
session_destroy();

require_once __DIR__ . '/../inc/config.php';  
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';

// Limpa o cookie de sessão
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $p["path"], $p["domain"],
        $p["secure"], $p["httponly"]
    );
}

// Redireciona para login após 3s (via meta) ou imediato via header
// Descomente a linha abaixo para redirecionar imediatamente sem página:
// header('Location: login.php'); exit;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="3;url=login.php">
    <title>Saindo — Sabor &amp; Cia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/public.css">
    
</head>
<body>

<div class="logout-page">
    <div class="logout-card">

        <div class="logout-logo">Sabor<span>&</span>Cia</div>

        <div class="logout-icone">
            <svg viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </div>

        <h1>Até logo!</h1>
        <p>Sua sessão foi encerrada com sucesso.<br>Redirecionando para o <strong>login</strong> em instantes...</p>

        <div class="progresso-wrap">
            <div class="progresso-bar"></div>
        </div>

        <div>
            <a href="login.php" class="btn-login">Entrar novamente</a>
            <a href="index.php" class="btn-home">Ir para o início</a>
        </div>

    </div>
</div>

</body>
</html>