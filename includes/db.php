<?php
// ============================================================
// config/db.php — Conexão PDO com MySQL
//
// COMO USAR em qualquer página:
//   require_once __DIR__ . '/../config/db.php';
//   $stmt = $pdo->prepare("SELECT * FROM produtos WHERE ativo = 1");
//   $stmt->execute();
//   $produtos = $stmt->fetchAll();
// ============================================================

// ─── CONFIGURAÇÕES ───────────────────────────────────────────
// XAMPP local (padrão):
define('DB_HOST', 'localhost');
define('DB_NAME', 'saborcia');
define('DB_USER', 'root');
define('DB_PASS', '');         
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

// ─── CONEXÃO PDO ─────────────────────────────────────────────
function conectar(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo; // reutiliza conexão já aberta
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    $opcoes = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lança exceções em erro
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // retorna arrays associativos
        PDO::ATTR_EMULATE_PREPARES   => false,                     // prepared statements reais
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
    } catch (PDOException $e) {
        // Em produção: logar o erro, nunca expor ao usuário
        error_log('[DB] Falha na conexão: ' . $e->getMessage());
        http_response_code(503);
        die('Serviço temporariamente indisponível. Tente novamente em instantes.');
    }

    return $pdo;
}

// Atalho global
$pdo = conectar();


// ============================================================
// EXEMPLOS DE USO — DELETE após entender
// ============================================================

/*
// ── BUSCAR TODOS ────────────────────────────────────────────
$stmt = $pdo->query("SELECT * FROM produtos WHERE ativo = 1 ORDER BY nome");
$produtos = $stmt->fetchAll();

// ── BUSCAR UM REGISTRO ──────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch();

// ── INSERIR ─────────────────────────────────────────────────
$stmt = $pdo->prepare("
    INSERT INTO produtos (categoria_id, nome, preco, estoque, ativo)
    VALUES (?, ?, ?, ?, 1)
");
$stmt->execute([$categoria_id, $nome, $preco, $estoque]);
$novo_id = $pdo->lastInsertId();

// ── ATUALIZAR ───────────────────────────────────────────────
$stmt = $pdo->prepare("
    UPDATE produtos SET nome = ?, preco = ?, estoque = ?
    WHERE id = ?
");
$stmt->execute([$nome, $preco, $estoque, $id]);

// ── EXCLUIR ─────────────────────────────────────────────────
$stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
$stmt->execute([$id]);

// ── CONTAR ──────────────────────────────────────────────────
$total = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'pendente'")->fetchColumn();

// ── TRANSAÇÃO (ex: salvar pedido + itens juntos) ────────────
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO pedidos (...) VALUES (...)");
    $stmt->execute([...]);
    $pedido_id = $pdo->lastInsertId();

    foreach ($itens as $item) {
        $stmt2 = $pdo->prepare("INSERT INTO pedido_itens (...) VALUES (...)");
        $stmt2->execute([...]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('[Pedido] Erro: ' . $e->getMessage());
}
*/