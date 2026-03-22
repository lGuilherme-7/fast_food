<?php
// ============================================================
// inc/carrinho.php — Funções auxiliares do carrinho (lado servidor)
//
// O carrinho PRINCIPAL fica no localStorage do navegador (JS).
// Este arquivo serve para o CHECKOUT — quando o formulário é
// enviado, os itens chegam via POST como JSON e são
// processados aqui antes de salvar o pedido.
//
// Chamar com: require_once __DIR__ . '/../inc/carrinho.php';
// ============================================================

/**
 * Decodifica o JSON do carrinho enviado pelo formulário do checkout.
 * Retorna array de itens validados ou array vazio.
 *
 * Espera receber $_POST['carrinho_json'] com o formato:
 * [
 *   { "id": 1, "nome": "Açaí Premium", "preco": 20.90,
 *     "qtd": 2, "adicionais": ["Banana","Granola"], "obs": "" },
 *   ...
 * ]
 */
function carrinho_do_post(): array {
    $json = $_POST['carrinho_json'] ?? '[]';
    $itens = json_decode($json, true);

    if (!is_array($itens) || empty($itens)) return [];

    $validos = [];
    foreach ($itens as $it) {
        // Campos obrigatórios
        if (empty($it['nome']) || !isset($it['preco']) || empty($it['qtd'])) continue;

        $validos[] = [
            'produto_id'    => isset($it['id']) ? (int)$it['id'] : null,
            'produto_nome'  => substr(strip_tags($it['nome']), 0, 150),
            'produto_preco' => round((float)$it['preco'], 2),
            'quantidade'    => max(1, (int)$it['qtd']),
            'adicionais'    => isset($it['adicionais']) && is_array($it['adicionais'])
                                ? implode(', ', array_map('strip_tags', $it['adicionais']))
                                : '',
            'obs'           => substr(strip_tags($it['obs'] ?? ''), 0, 255),
        ];
    }

    return $validos;
}

/**
 * Calcula o subtotal de um array de itens (já validados).
 */
function carrinho_subtotal(array $itens): float {
    $total = 0.0;
    foreach ($itens as $it) {
        $total += (float)$it['produto_preco'] * (int)$it['quantidade'];
    }
    return round($total, 2);
}

/**
 * Valida e aplica um cupom de desconto.
 *
 * Retorna array com:
 *   'valido'    => bool
 *   'cupom_id'  => int|null
 *   'desconto'  => float
 *   'mensagem'  => string   (erro ou confirmação)
 */
function cupom_aplicar(PDO $pdo, string $codigo, float $subtotal): array {
    if (empty($codigo)) {
        return ['valido' => false, 'cupom_id' => null, 'desconto' => 0.0, 'mensagem' => ''];
    }

    $stmt = $pdo->prepare("
        SELECT * FROM cupons
        WHERE codigo = ? AND ativo = 1
          AND (validade IS NULL OR validade >= CURDATE())
          AND (limite = 0 OR usos < limite)
    ");
    $stmt->execute([strtoupper(trim($codigo))]);
    $cupom = $stmt->fetch();

    if (!$cupom) {
        return ['valido' => false, 'cupom_id' => null, 'desconto' => 0.0, 'mensagem' => 'Cupom inválido ou expirado.'];
    }

    // Verifica valor mínimo
    if ((float)$cupom['minimo'] > 0 && $subtotal < (float)$cupom['minimo']) {
        $min = 'R$ ' . number_format($cupom['minimo'], 2, ',', '.');
        return ['valido' => false, 'cupom_id' => null, 'desconto' => 0.0, 'mensagem' => 'Pedido mínimo de ' . $min . ' para usar este cupom.'];
    }

    // Calcula desconto
    if ($cupom['tipo'] === 'percentual') {
        $desconto = round($subtotal * ((float)$cupom['valor'] / 100), 2);
        $label    = $cupom['valor'] . '% de desconto';
    } else {
        $desconto = min((float)$cupom['valor'], $subtotal);
        $label    = 'R$ ' . number_format($desconto, 2, ',', '.') . ' de desconto';
    }

    return [
        'valido'    => true,
        'cupom_id'  => (int)$cupom['id'],
        'desconto'  => $desconto,
        'mensagem'  => 'Cupom aplicado! ' . $label . '.',
    ];
}

/**
 * Monta array de itens formatado para a mensagem do WhatsApp.
 * Retorna string multi-linha.
 */
function carrinho_texto_wpp(array $itens): string {
    $linhas = [];
    foreach ($itens as $it) {
        $sub   = 'R$ ' . number_format($it['produto_preco'] * $it['quantidade'], 2, ',', '.');
        $linha = $it['quantidade'] . 'x ' . $it['produto_nome'] . ' — ' . $sub;
        if (!empty($it['adicionais'])) $linha .= ' (' . $it['adicionais'] . ')';
        if (!empty($it['obs']))        $linha .= ' [obs: ' . $it['obs'] . ']';
        $linhas[] = $linha;
    }
    return implode("\n", $linhas);
}