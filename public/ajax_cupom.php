<?php
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

// Busca o cupom direto para pegar tipo e valor bruto
$stmt = $pdo->prepare("
    SELECT * FROM cupons
    WHERE codigo = ? AND ativo = 1
      AND (validade IS NULL OR validade >= CURDATE())
      AND (limite = 0 OR usos < limite)
");
$stmt->execute([$codigo]);
$cupom = $stmt->fetch();

$resultado = cupom_aplicar($pdo, $codigo, $subtotal);

echo json_encode([
    'valido'   => $resultado['valido'],
    'mensagem' => $resultado['mensagem'],
    'desconto' => $resultado['desconto'],
    'cupom_id' => $resultado['cupom_id'],
    'tipo'     => $cupom ? $cupom['tipo']          : '',   // 'percentual' ou 'fixo'
    'valor'    => $cupom ? (float)$cupom['valor']  : 0,   // valor bruto do cupom
]);