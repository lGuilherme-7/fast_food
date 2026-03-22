<?php
// ============================================================
// inc/frete.php — Cálculo de taxa de entrega e validação
// Chamar com: require_once __DIR__ . '/../inc/frete.php';
// ============================================================

/**
 * Carrega as configurações de entrega do banco.
 * Retorna array com as chaves de configuração.
 */
function frete_config(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT chave, valor FROM configuracoes
        WHERE chave IN (
            'entrega_ativa', 'entrega_taxa', 'entrega_gratis',
            'entrega_tempo', 'entrega_raio',
            'retirada_ativa', 'retirada_tempo'
        )
    ");
    $cfg = [];
    foreach ($stmt->fetchAll() as $row) {
        $cfg[$row['chave']] = $row['valor'];
    }

    // Valores padrão caso não existam no banco
    return array_merge([
        'entrega_ativa'  => '1',
        'entrega_taxa'   => '5.00',
        'entrega_gratis' => '50.00',
        'entrega_tempo'  => '40',
        'entrega_raio'   => '5',
        'retirada_ativa' => '1',
        'retirada_tempo' => '15',
    ], $cfg);
}

/**
 * Calcula a taxa de entrega com base no subtotal do carrinho.
 *
 * Retorna:
 *   'taxa'       => float  (0.00 se grátis)
 *   'gratis'     => bool
 *   'falta'      => float  (quanto falta para entrega grátis, 0 se já grátis)
 *   'disponivel' => bool   (se entrega está ativa)
 */
function calcular_frete(PDO $pdo, float $subtotal, string $tipo = 'entrega'): array {
    $cfg = frete_config($pdo);

    if ($tipo === 'retirada') {
        return [
            'taxa'       => 0.00,
            'gratis'     => true,
            'falta'      => 0.00,
            'disponivel' => (bool)(int)$cfg['retirada_ativa'],
            'tempo'      => (int)$cfg['retirada_tempo'],
            'tipo'       => 'retirada',
        ];
    }

    $ativo        = (bool)(int)$cfg['entrega_ativa'];
    $taxa         = (float)$cfg['entrega_taxa'];
    $gratis_acima = (float)$cfg['entrega_gratis'];

    $gratis = ($gratis_acima > 0 && $subtotal >= $gratis_acima) || $taxa === 0.00;
    $falta  = $gratis ? 0.00 : max(0, $gratis_acima - $subtotal);

    return [
        'taxa'       => $gratis ? 0.00 : $taxa,
        'gratis'     => $gratis,
        'falta'      => $falta,
        'disponivel' => $ativo,
        'tempo'      => (int)$cfg['entrega_tempo'],
        'raio'       => (int)$cfg['entrega_raio'],
        'tipo'       => 'entrega',
    ];
}

/**
 * Retorna o HTML do bloco de informação de frete para o checkout.
 * Ex: "Faltam R$ 10,00 para entrega grátis" ou "Entrega grátis!"
 */
function frete_html_info(array $frete): string {
    if (!$frete['disponivel']) {
        return '<div style="color:#ef4444;font-size:.82rem;font-weight:500;">Entrega indisponível no momento.</div>';
    }
    if ($frete['gratis']) {
        return '<div style="color:#16a34a;font-size:.82rem;font-weight:600;">✓ Entrega grátis!</div>';
    }
    if ($frete['falta'] > 0) {
        $faltam = 'R$ ' . number_format($frete['falta'], 2, ',', '.');
        $taxa   = 'R$ ' . number_format($frete['taxa'],  2, ',', '.');
        return '<div style="color:#9ca3af;font-size:.82rem;">Taxa de entrega: <strong style="color:#1a1014;">' . $taxa . '</strong> — Faltam <strong style="color:#f43f7a;">' . $faltam . '</strong> para grátis.</div>';
    }
    $taxa = 'R$ ' . number_format($frete['taxa'], 2, ',', '.');
    return '<div style="color:#9ca3af;font-size:.82rem;">Taxa de entrega: <strong style="color:#1a1014;">' . $taxa . '</strong></div>';
}