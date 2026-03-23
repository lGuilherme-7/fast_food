<?php
// ============================================================
// public/ajax_cupom.php — Endpoint AJAX para validar cupom
// Chamado pelo checkout.php via fetch()
// ============================================================
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/carrinho.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valido' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

$codigo   = strtoupper(trim($_POST['codigo']   ?? ''));
$subtotal = (float)($_POST['subtotal'] ?? 0);

if (empty($codigo)) {
    echo json_encode(['valido' => false, 'mensagem' => 'Digite um código.']);
    exit;
}

$resultado = cupom_aplicar($pdo, $codigo, $subtotal);

echo json_encode([
    'valido'    => $resultado['valido'],
    'mensagem'  => $resultado['mensagem'],
    'desconto'  => $resultado['desconto'],
    'cupom_id'  => $resultado['cupom_id'],
    'tipo'      => '', // retornado do banco se necessário
    'valor'     => $resultado['desconto'],
]);