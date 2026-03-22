<?php
// ============================================================
// inc/pedidos.php — Criação e consulta de pedidos
// Chamar com: require_once __DIR__ . '/../inc/pedidos.php';
// ============================================================

/**
 * Cria um novo pedido completo com seus itens.
 * Retorna o ID do pedido criado ou false em caso de erro.
 *
 * $dados = [
 *   'cliente_id'   => int|null,
 *   'cliente_nome' => string,
 *   'cliente_tel'  => string,
 *   'tipo_entrega' => 'entrega' | 'retirada',
 *   'endereco'     => string,
 *   'bairro'       => string,
 *   'complemento'  => string,
 *   'referencia'   => string,
 *   'pagamento'    => 'dinheiro' | 'cartao' | 'pix',
 *   'troco_para'   => float|null,
 *   'observacao'   => string,
 *   'cupom_id'     => int|null,
 * ]
 *
 * $itens = [
 *   ['produto_id' => int, 'produto_nome' => string,
 *    'produto_preco' => float, 'quantidade' => int,
 *    'adicionais' => string, 'obs' => string],
 *   ...
 * ]
 */
function pedido_criar(PDO $pdo, array $dados, array $itens, float $taxa_entrega = 0.00, float $desconto = 0.00): int|false {
    try {
        $pdo->beginTransaction();

        // Calcula subtotal
        $subtotal = 0.0;
        foreach ($itens as $it) {
            $subtotal += (float)$it['produto_preco'] * (int)$it['quantidade'];
        }
        $total = $subtotal + $taxa_entrega - $desconto;

        // Insere pedido
        $stmt = $pdo->prepare("
            INSERT INTO pedidos
                (cliente_id, cliente_nome, cliente_tel,
                 tipo_entrega, endereco, bairro, complemento, referencia,
                 pagamento, troco_para, subtotal, taxa_entrega, desconto, total,
                 cupom_id, observacao, status)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')
        ");
        $stmt->execute([
            $dados['cliente_id']   ?? null,
            $dados['cliente_nome'] ?? '',
            $dados['cliente_tel']  ?? '',
            $dados['tipo_entrega'] ?? 'entrega',
            $dados['endereco']     ?? '',
            $dados['bairro']       ?? '',
            $dados['complemento']  ?? '',
            $dados['referencia']   ?? '',
            $dados['pagamento']    ?? 'pix',
            $dados['troco_para']   ?? null,
            $subtotal,
            $taxa_entrega,
            $desconto,
            $total,
            $dados['cupom_id']     ?? null,
            $dados['observacao']   ?? '',
        ]);

        $pedido_id = (int)$pdo->lastInsertId();

        // Insere itens
        $stmtItem = $pdo->prepare("
            INSERT INTO pedido_itens
                (pedido_id, produto_id, produto_nome, produto_preco, quantidade, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($itens as $it) {
            $sub = (float)$it['produto_preco'] * (int)$it['quantidade'];
            $stmtItem->execute([
                $pedido_id,
                $it['produto_id']    ?? null,
                $it['produto_nome'],
                $it['produto_preco'],
                $it['quantidade'],
                $sub,
            ]);
        }

        // Incrementa uso do cupom
        if (!empty($dados['cupom_id'])) {
            $pdo->prepare("UPDATE cupons SET usos = usos + 1 WHERE id = ?")->execute([$dados['cupom_id']]);
        }

        $pdo->commit();
        return $pedido_id;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('[Pedido] Erro ao criar: ' . $e->getMessage());
        return false;
    }
}

/**
 * Retorna pedidos de um cliente específico.
 */
function pedidos_do_cliente(PDO $pdo, int $cliente_id, int $limite = 20): array {
    $stmt = $pdo->prepare("
        SELECT id, status, total, tipo_entrega, pagamento, criado_em
        FROM pedidos
        WHERE cliente_id = ?
        ORDER BY criado_em DESC
        LIMIT ?
    ");
    $stmt->execute([$cliente_id, $limite]);
    return $stmt->fetchAll();
}

/**
 * Retorna um pedido completo com seus itens.
 */
function pedido_detalhe(PDO $pdo, int $pedido_id, ?int $cliente_id = null): ?array {
    $sql = "SELECT * FROM pedidos WHERE id = ?";
    $params = [$pedido_id];

    // Se cliente_id informado, garante que o pedido pertence ao cliente
    if ($cliente_id !== null) {
        $sql .= " AND cliente_id = ?";
        $params[] = $cliente_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedido = $stmt->fetch();
    if (!$pedido) return null;

    // Busca itens
    $stmt = $pdo->prepare("
        SELECT * FROM pedido_itens WHERE pedido_id = ? ORDER BY id
    ");
    $stmt->execute([$pedido_id]);
    $pedido['itens'] = $stmt->fetchAll();

    return $pedido;
}

/**
 * Monta a mensagem formatada do pedido para WhatsApp.
 */
function pedido_mensagem_wpp(array $pedido, array $itens, string $whatsapp): string {
    $linhas = [];
    $linhas[] = '*Novo pedido — Sabor & Cia*';
    $linhas[] = '';
    $linhas[] = '*Cliente:* ' . $pedido['cliente_nome'];
    $linhas[] = '*Telefone:* ' . $pedido['cliente_tel'];
    $linhas[] = '';

    if ($pedido['tipo_entrega'] === 'entrega') {
        $linhas[] = '*Entrega em:*';
        $linhas[] = $pedido['endereco'] . ($pedido['complemento'] ? ', ' . $pedido['complemento'] : '');
        if ($pedido['bairro']) $linhas[] = 'Bairro: ' . $pedido['bairro'];
        if ($pedido['referencia']) $linhas[] = 'Ref: ' . $pedido['referencia'];
    } else {
        $linhas[] = '*Retirada no local*';
    }

    $linhas[] = '';
    $linhas[] = '*Itens:*';
    foreach ($itens as $it) {
        $linha = $it['quantidade'] . 'x ' . $it['produto_nome'];
        $linha .= ' — R$ ' . number_format($it['subtotal'], 2, ',', '.');
        $linhas[] = $linha;
    }

    $linhas[] = '';
    if ((float)$pedido['desconto'] > 0) {
        $linhas[] = 'Desconto: -R$ ' . number_format($pedido['desconto'], 2, ',', '.');
    }
    if ((float)$pedido['taxa_entrega'] > 0) {
        $linhas[] = 'Taxa de entrega: R$ ' . number_format($pedido['taxa_entrega'], 2, ',', '.');
    }
    $linhas[] = '*Total: R$ ' . number_format($pedido['total'], 2, ',', '.') . '*';
    $linhas[] = '';
    $linhas[] = '*Pagamento:* ' . ucfirst($pedido['pagamento']);
    if (!empty($pedido['troco_para'])) {
        $linhas[] = 'Troco para: R$ ' . number_format($pedido['troco_para'], 2, ',', '.');
    }
    if (!empty($pedido['observacao'])) {
        $linhas[] = '';
        $linhas[] = '*Obs:* ' . $pedido['observacao'];
    }

    $mensagem = implode("\n", $linhas);
    return 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode($mensagem);
}

/**
 * Retorna os status com label e cor para exibição.
 */
function pedido_status_info(string $status): array {
    $mapa = [
        'pendente'  => ['label' => 'Pendente',   'cor' => '#f59e0b'],
        'preparo'   => ['label' => 'Em preparo',  'cor' => '#3b82f6'],
        'entregue'  => ['label' => 'Entregue',    'cor' => '#22c55e'],
        'cancelado' => ['label' => 'Cancelado',   'cor' => '#ef4444'],
    ];
    return $mapa[$status] ?? ['label' => $status, 'cor' => '#9ca3af'];
}