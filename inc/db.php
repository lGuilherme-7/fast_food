<?php
// ============================================================
// inc/db.php — Conexão PDO com MySQL
// Chamar com: require_once __DIR__ . '/../inc/db.php';
// ============================================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'saborecia');
define('DB_USER',    'root');
define('DB_PASS',    '');        // XAMPP: vazio por padrão
define('DB_PORT',    '3306');
define('DB_CHARSET', 'utf8mb4');

function conectar(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
    } catch (PDOException $e) {
        error_log('[DB] Falha na conexão: ' . $e->getMessage());
        http_response_code(503);
        die(json_encode(['erro' => 'Serviço indisponível. Tente novamente.']));
    }

    return $pdo;
}

// Variável global pronta para usar em qualquer arquivo
$pdo = conectar();