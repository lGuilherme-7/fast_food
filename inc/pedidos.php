<?php
// inc/pedidos.php

function pedido_criar(PDO $pdo, array $dados, array $itens, float $taxa_entrega = 0.00, float $desconto = 0.00): int|false {
    try {
        $pdo->beginTransaction();

        $subtotal = 0.0;
        foreach ($itens as $it) {
            $subtotal += (float)$it['produto_preco'] * (int)$it['quantidade'];
        }
        $total = $subtotal + $taxa_entrega - $desconto;

        $stmt = $pdo->prepare("
            INSERT INTO pedidos
                (cliente_id, cliente_nome, cliente_tel,
                 tipo_entrega, endereco, bairro, complemento, referencia,
                 pagamento, troco_para, subtotal, taxa_entrega, desconto, total,
                 cupom_id, observacao, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pendente')
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

        // Garante colunas adicionais/obs (retrocompatibilidade)
        try { $pdo->query("SELECT adicionais FROM pedido_itens LIMIT 1"); }
        catch (PDOException $e) {
            $pdo->exec("ALTER TABLE pedido_itens ADD COLUMN adicionais TEXT DEFAULT NULL AFTER subtotal");
            $pdo->exec("ALTER TABLE pedido_itens ADD COLUMN obs TEXT DEFAULT NULL AFTER adicionais");
        }

        $stmtItem = $pdo->prepare("
            INSERT INTO pedido_itens
                (pedido_id, produto_id, produto_nome, produto_preco, quantidade, subtotal, adicionais, obs)
            VALUES (?,?,?,?,?,?,?,?)
        ");

        // ── DESCONTAR ESTOQUE AUTOMATICAMENTE ────────────────
        $stmtEstoque = $pdo->prepare("
            UPDATE produtos
            SET estoque = GREATEST(0, estoque - ?)
            WHERE id = ?
        ");
        $stmtHist = $pdo->prepare("
            INSERT INTO estoque_historico
                (produto_id, tipo, quantidade, motivo, admin_id)
            VALUES (?, 'pedido', ?, ?, NULL)
        ");

        foreach ($itens as $it) {
            $sub  = (float)$it['produto_preco'] * (int)$it['quantidade'];
            $adds = is_array($it['adicionais'] ?? null)
                ? implode(', ', $it['adicionais'])
                : ($it['adicionais'] ?? '');

            $stmtItem->execute([
                $pedido_id,
                $it['produto_id']    ?? null,
                $it['produto_nome'],
                $it['produto_preco'],
                $it['quantidade'],
                $sub,
                $adds ?: null,
                $it['obs'] ?? null,
            ]);

            // Desconta estoque apenas se tiver produto_id válido
            if (!empty($it['produto_id'])) {
                $stmtEstoque->execute([$it['quantidade'], $it['produto_id']]);

                // Registra no histórico como "pedido"
                $stmtHist->execute([
                    $it['produto_id'],
                    (int)$it['quantidade'],
                    'Pedido #' . $pedido_id . ' — ' . $it['produto_nome'],
                ]);
            }
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

function pedidos_do_cliente(PDO $pdo, int $cliente_id, int $limite = 20): array {
    $stmt = $pdo->prepare("
        SELECT id, status, total, tipo_entrega, pagamento, criado_em
        FROM pedidos WHERE cliente_id = ?
        ORDER BY criado_em DESC LIMIT ?
    ");
    $stmt->execute([$cliente_id, $limite]);
    return $stmt->fetchAll();
}

function pedido_detalhe(PDO $pdo, int $pedido_id, ?int $cliente_id = null): ?array {
    $sql    = "SELECT * FROM pedidos WHERE id = ?";
    $params = [$pedido_id];
    if ($cliente_id !== null) { $sql .= " AND cliente_id = ?"; $params[] = $cliente_id; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedido = $stmt->fetch();
    if (!$pedido) return null;

    $stmt = $pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id = ? ORDER BY id");
    $stmt->execute([$pedido_id]);
    $pedido['itens'] = $stmt->fetchAll();
    return $pedido;
}

function pedido_mensagem_wpp(array $pedido, array $itens, string $whatsapp): string {
    $linhas   = [];
    $linhas[] = '*Novo pedido — Sabor & Cia*';
    $linhas[] = '';
    $linhas[] = '*Cliente:* ' . $pedido['cliente_nome'];
    $linhas[] = '*Telefone:* ' . $pedido['cliente_tel'];
    $linhas[] = '';

    $tipo = $pedido['tipo_entrega'] ?? 'entrega';
    if ($tipo === 'entrega') {
        $linhas[] = '*Entrega em:*';
        $linhas[] = $pedido['endereco'] . (!empty($pedido['complemento']) ? ', '.$pedido['complemento'] : '');
        if (!empty($pedido['bairro']))     $linhas[] = 'Bairro: ' . $pedido['bairro'];
        if (!empty($pedido['referencia'])) $linhas[] = 'Ref: '   . $pedido['referencia'];
    } elseif ($tipo === 'local') {
        $linhas[] = '*Comer no local*';
        if (!empty($pedido['referencia'])) $linhas[] = $pedido['referencia'];
    } else {
        $linhas[] = '*Retirada no local*';
    }

    $linhas[] = '';
    $linhas[] = '*Itens:*';
    foreach ($itens as $it) {
        $linhas[] = (int)$it['quantidade'] . 'x ' . $it['produto_nome']
                  . ' — R$ ' . number_format((float)$it['subtotal'], 2, ',', '.');
        if (!empty($it['adicionais'])) $linhas[] = '  ↳ ' . $it['adicionais'];
        if (!empty($it['obs']))        $linhas[] = '  📝 ' . $it['obs'];
    }

    $linhas[] = '';
    if ((float)($pedido['desconto'] ?? 0) > 0)
        $linhas[] = 'Desconto: -R$ ' . number_format($pedido['desconto'], 2, ',', '.');
    if ((float)($pedido['taxa_entrega'] ?? 0) > 0)
        $linhas[] = 'Taxa de entrega: R$ ' . number_format($pedido['taxa_entrega'], 2, ',', '.');
    $linhas[] = '*Total: R$ ' . number_format($pedido['total'], 2, ',', '.') . '*';
    $linhas[] = '';
    $linhas[] = '*Pagamento:* ' . ucfirst($pedido['pagamento'] ?? '');
    if (!empty($pedido['troco_para']))
        $linhas[] = 'Troco para: R$ ' . number_format($pedido['troco_para'], 2, ',', '.');
    if (!empty($pedido['observacao'])) {
        $linhas[] = '';
        $linhas[] = '*Obs geral:* ' . $pedido['observacao'];
    }

    return 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode(implode("\n", $linhas));
}

function pedido_status_info(string $status): array {
    $mapa = [
        'pendente'  => ['label' => 'Pendente',   'cor' => '#f59e0b'],
        'preparo'   => ['label' => 'Em preparo',  'cor' => '#3b82f6'],
        'entregue'  => ['label' => 'Entregue',    'cor' => '#22c55e'],
        'cancelado' => ['label' => 'Cancelado',   'cor' => '#ef4444'],
    ];
    return $mapa[$status] ?? ['label' => $status, 'cor' => '#9ca3af'];
}