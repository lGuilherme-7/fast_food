<?php
// ============================================================
// inc/config.php — Constantes e configurações globais
// Chamar com: require_once __DIR__ . '/../inc/config.php';
// SEMPRE incluir ANTES dos outros arquivos de inc/
// ============================================================

// ── AMBIENTE ─────────────────────────────────────────────────
define('AMBIENTE', 'desenvolvimento'); // 'desenvolvimento' ou 'producao'

if (AMBIENTE === 'desenvolvimento') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ── SESSÃO ────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // true em produção com HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── CAMINHOS ─────────────────────────────────────────────────
// Raiz do projeto no servidor
define('ROOT_PATH',   dirname(__DIR__));
define('INC_PATH',    ROOT_PATH . '/inc');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('ADMIN_PATH',  ROOT_PATH . '/admin');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

// URL base (detecta automaticamente)
$protocolo  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
$pasta      = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
// Sobe até a raiz do projeto (remove 'public' ou 'admin' do caminho)
$pasta_raiz = preg_replace('#/(public|admin|pages)(/.*)?$#', '', '/' . $pasta);
define('BASE_URL', $protocolo . '://' . $host . $pasta_raiz);

// ── WHATSAPP (fallback caso o banco não esteja disponível) ────
define('WPP_FALLBACK', '5581987028550');

// ── TIMEZONE ──────────────────────────────────────────────────
date_default_timezone_set('America/Recife');