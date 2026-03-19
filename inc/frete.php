<?php
/**
 * inc/frete.php — Cálculo de frete / taxa de entrega
 *
 * Responsabilidade:
 *   - Calcular o valor do frete com base em bairro, cidade ou
 *     faixa de valor do pedido
 *   - Verificar se o endereço está dentro da área de entrega
 *   - Retornar prazo estimado de entrega
 *   - Expor endpoint AJAX para consulta via fetch() no checkout
 *
 * Estratégia de cálculo (em ordem de prioridade):
 *   1. Por bairro  — tabela `frete_bairros` no banco (mais específico)
 *   2. Por cidade  — tabela `frete_cidades` no banco
 *   3. Por faixa de valor do pedido — fallback configurável
 *   4. Frete fixo padrão — último recurso
 *
 * Uso direto (incluído em checkout.php):
 *   require_once __DIR__ . '/../inc/frete.php';
 *   $resultado = calcularFrete($pdo, 'Centro', 'São Paulo', 89.90);
 *
 * Uso via AJAX (chamado por frete_ajax.php ou diretamente):
 *   POST bairro=Centro&cidade=SãoPaulo&valor_pedido=89.90
 */

// ── Configurações padrão ──────────────────────────────────────────────────────
// Podem ser sobrescritas por constantes em config.php

/** Frete fixo padrão quando nenhuma regra específica é encontrada (R$) */
define('FRETE_PADRAO',        defined('CFG_FRETE_PADRAO')        ? CFG_FRETE_PADRAO        : 8.00);

/** Valor mínimo do pedido para frete grátis (0 = desativado) */
define('FRETE_GRATIS_MINIMO', defined('CFG_FRETE_GRATIS_MINIMO') ? CFG_FRETE_GRATIS_MINIMO : 80.00);

/** Prazo padrão de entrega em minutos */
define('FRETE_PRAZO_PADRAO',  defined('CFG_FRETE_PRAZO_PADRAO')  ? CFG_FRETE_PRAZO_PADRAO  : 40);

// ──────────────────────────────────────────────────────────────────────────────
// calcularFrete()
//
// Função principal. Retorna array com todas as informações de entrega.
//
// @param PDO    $pdo
// @param string $bairro       — bairro do endereço de entrega
// @param string $cidade       — cidade do endereço de entrega
// @param float  $valorPedido  — subtotal dos produtos (sem frete)
//
// @return array {
//   disponivel  bool    — se entrega nessa área
//   valor       float   — valor do frete em reais
//   gratis      bool    — se frete é grátis
//   prazo_min   int     — prazo mínimo em minutos
//   prazo_max   int     — prazo máximo em minutos
//   prazo_texto string  — ex: "30 a 50 min"
//   regra       string  — origem da regra: 'bairro'|'cidade'|'faixa'|'padrao'
//   mensagem    string  — texto amigável para exibir ao usuário
// }
// ──────────────────────────────────────────────────────────────────────────────
function calcularFrete(PDO $pdo, string $bairro, string $cidade, float $valorPedido = 0.0): array
{
    $bairro = trim($bairro);
    $cidade = trim($cidade);

    // ── 1. Tenta regra por bairro ─────────────────────────────────────────────
    $regraB = buscarFretePorBairro($pdo, $bairro, $cidade);
    if ($regraB !== null) {
        return montarResultado($regraB, $valorPedido, 'bairro');
    }

    // ── 2. Tenta regra por cidade ─────────────────────────────────────────────
    $regraC = buscarFretePorCidade($pdo, $cidade);
    if ($regraC !== null) {
        return montarResultado($regraC, $valorPedido, 'cidade');
    }

    // ── 3. Tenta regra por faixa de valor ─────────────────────────────────────
    $regraF = buscarFretePorFaixa($pdo, $valorPedido);
    if ($regraF !== null) {
        return montarResultado($regraF, $valorPedido, 'faixa');
    }

    // ── 4. Frete padrão (fallback) ────────────────────────────────────────────
    return montarResultadoPadrao($valorPedido);
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarFretePorBairro()
//
// Busca na tabela `frete_bairros` — pesquisa case-insensitive,
// ignora acentuação com COLLATE.
//
// Estrutura esperada da tabela:
//   CREATE TABLE frete_bairros (
//     id         INT AUTO_INCREMENT PRIMARY KEY,
//     bairro     VARCHAR(100) NOT NULL,
//     cidade     VARCHAR(100) NOT NULL,
//     valor      DECIMAL(8,2) NOT NULL DEFAULT 0.00,
//     prazo_min  SMALLINT     NOT NULL DEFAULT 30,
//     prazo_max  SMALLINT     NOT NULL DEFAULT 50,
//     disponivel TINYINT(1)   NOT NULL DEFAULT 1,
//     ativo      TINYINT(1)   NOT NULL DEFAULT 1
//   );
//
// @return array|null
// ──────────────────────────────────────────────────────────────────────────────
function buscarFretePorBairro(PDO $pdo, string $bairro, string $cidade): ?array
{
    if (empty($bairro)) return null;

    $stmt = $pdo->prepare("
        SELECT valor, prazo_min, prazo_max, disponivel
        FROM frete_bairros
        WHERE bairro = :bairro
          AND cidade = :cidade
          AND ativo  = 1
        LIMIT 1
    ");
    $stmt->execute([
        ':bairro' => $bairro,
        ':cidade' => $cidade,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarFretePorCidade()
//
// Estrutura esperada da tabela:
//   CREATE TABLE frete_cidades (
//     id         INT AUTO_INCREMENT PRIMARY KEY,
//     cidade     VARCHAR(100) NOT NULL,
//     estado     CHAR(2)      NOT NULL,
//     valor      DECIMAL(8,2) NOT NULL DEFAULT 0.00,
//     prazo_min  SMALLINT     NOT NULL DEFAULT 40,
//     prazo_max  SMALLINT     NOT NULL DEFAULT 70,
//     disponivel TINYINT(1)   NOT NULL DEFAULT 1,
//     ativo      TINYINT(1)   NOT NULL DEFAULT 1
//   );
//
// @return array|null
// ──────────────────────────────────────────────────────────────────────────────
function buscarFretePorCidade(PDO $pdo, string $cidade): ?array
{
    if (empty($cidade)) return null;

    $stmt = $pdo->prepare("
        SELECT valor, prazo_min, prazo_max, disponivel
        FROM frete_cidades
        WHERE cidade = :cidade
          AND ativo  = 1
        LIMIT 1
    ");
    $stmt->execute([':cidade' => $cidade]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarFretePorFaixa()
//
// Retorna a regra de frete cuja faixa de valor do pedido cobre $valorPedido.
//
// Estrutura esperada da tabela:
//   CREATE TABLE frete_faixas (
//     id              INT AUTO_INCREMENT PRIMARY KEY,
//     valor_minimo    DECIMAL(8,2) NOT NULL DEFAULT 0.00,
//     valor_maximo    DECIMAL(8,2) NOT NULL DEFAULT 9999.00,
//     valor_frete     DECIMAL(8,2) NOT NULL DEFAULT 0.00,
//     prazo_min       SMALLINT     NOT NULL DEFAULT 30,
//     prazo_max       SMALLINT     NOT NULL DEFAULT 50,
//     disponivel      TINYINT(1)   NOT NULL DEFAULT 1,
//     ativo           TINYINT(1)   NOT NULL DEFAULT 1
//   );
//
// Exemplo de dados:
//   (0.00,  29.99,  12.00, 40, 60, 1, 1)  → pedidos até R$29,99
//   (30.00, 59.99,   8.00, 35, 55, 1, 1)  → pedidos R$30 a R$59,99
//   (60.00, 79.99,   5.00, 30, 45, 1, 1)  → pedidos R$60 a R$79,99
//   (80.00, 9999.00, 0.00, 25, 40, 1, 1)  → acima de R$80 — grátis
//
// @return array|null
// ──────────────────────────────────────────────────────────────────────────────
function buscarFretePorFaixa(PDO $pdo, float $valorPedido): ?array
{
    $stmt = $pdo->prepare("
        SELECT valor_frete AS valor, prazo_min, prazo_max, disponivel
        FROM frete_faixas
        WHERE :valor BETWEEN valor_minimo AND valor_maximo
          AND ativo = 1
        ORDER BY valor_minimo ASC
        LIMIT 1
    ");
    $stmt->execute([':valor' => $valorPedido]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// ──────────────────────────────────────────────────────────────────────────────
// montarResultado()
//
// Transforma uma linha do banco em um array padronizado de resposta,
// aplicando a regra de frete grátis por valor mínimo.
//
// @param array  $regra       — linha retornada do banco
// @param float  $valorPedido
// @param string $origem      — 'bairro'|'cidade'|'faixa'
//
// @return array
// ──────────────────────────────────────────────────────────────────────────────
function montarResultado(array $regra, float $valorPedido, string $origem): array
{
    $disponivel = (bool) $regra['disponivel'];

    // Frete grátis por valor do pedido (sobrepõe regra do banco)
    $gratis = false;
    $valor  = (float) $regra['valor'];

    if (FRETE_GRATIS_MINIMO > 0 && $valorPedido >= FRETE_GRATIS_MINIMO) {
        $gratis = true;
        $valor  = 0.00;
    } elseif ($valor === 0.00) {
        $gratis = true;
    }

    $prazoMin = (int) ($regra['prazo_min'] ?? FRETE_PRAZO_PADRAO);
    $prazoMax = (int) ($regra['prazo_max'] ?? FRETE_PRAZO_PADRAO + 15);

    return [
        'disponivel'  => $disponivel,
        'valor'       => $valor,
        'gratis'      => $gratis,
        'prazo_min'   => $prazoMin,
        'prazo_max'   => $prazoMax,
        'prazo_texto' => formatarPrazo($prazoMin, $prazoMax),
        'regra'       => $origem,
        'mensagem'    => montarMensagem($disponivel, $gratis, $valor, $prazoMin, $prazoMax, $valorPedido),
    ];
}

// ──────────────────────────────────────────────────────────────────────────────
// montarResultadoPadrao()
//
// Fallback quando nenhuma regra do banco cobre o endereço informado.
// Usa FRETE_PADRAO e FRETE_PRAZO_PADRAO.
//
// @return array
// ──────────────────────────────────────────────────────────────────────────────
function montarResultadoPadrao(float $valorPedido): array
{
    $gratis = FRETE_GRATIS_MINIMO > 0 && $valorPedido >= FRETE_GRATIS_MINIMO;
    $valor  = $gratis ? 0.00 : FRETE_PADRAO;
    $min    = FRETE_PRAZO_PADRAO;
    $max    = FRETE_PRAZO_PADRAO + 15;

    return [
        'disponivel'  => true,
        'valor'       => $valor,
        'gratis'      => $gratis,
        'prazo_min'   => $min,
        'prazo_max'   => $max,
        'prazo_texto' => formatarPrazo($min, $max),
        'regra'       => 'padrao',
        'mensagem'    => montarMensagem(true, $gratis, $valor, $min, $max, $valorPedido),
    ];
}

// ──────────────────────────────────────────────────────────────────────────────
// formatarPrazo()
//
// Gera texto amigável do prazo de entrega.
//
// @param int $min
// @param int $max
//
// @return string  ex: "30 a 45 min" | "1h a 1h20"
// ──────────────────────────────────────────────────────────────────────────────
function formatarPrazo(int $min, int $max): string
{
    $fmt = function (int $m): string {
        if ($m < 60) return $m . ' min';
        $h   = intdiv($m, 60);
        $rem = $m % 60;
        return $rem > 0 ? "{$h}h{$rem}" : "{$h}h";
    };

    if ($min === $max) return $fmt($min);
    return $fmt($min) . ' – ' . $fmt($max);
}

// ──────────────────────────────────────────────────────────────────────────────
// montarMensagem()
//
// Gera texto amigável para exibir no checkout.
// ──────────────────────────────────────────────────────────────────────────────
function montarMensagem(
    bool   $disponivel,
    bool   $gratis,
    float  $valor,
    int    $min,
    int    $max,
    float  $valorPedido
): string {
    if (!$disponivel) {
        return 'Infelizmente não entregamos nessa área ainda.';
    }

    $prazo = formatarPrazo($min, $max);

    if ($gratis) {
        return "🎉 Frete grátis! Entrega em {$prazo}.";
    }

    // Mostra quanto falta para frete grátis
    $faltaTexto = '';
    if (FRETE_GRATIS_MINIMO > 0 && $valorPedido < FRETE_GRATIS_MINIMO) {
        $falta = FRETE_GRATIS_MINIMO - $valorPedido;
        $faltaTexto = ' (adicione R$ ' . number_format($falta, 2, ',', '.') . ' para frete grátis)';
    }

    return 'Frete: R$ ' . number_format($valor, 2, ',', '.') . ' · Entrega em ' . $prazo . $faltaTexto;
}

// ──────────────────────────────────────────────────────────────────────────────
// listarBairrosDisponiveis()
//
// Retorna todos os bairros ativos para popular um <select> ou autocomplete.
//
// @param PDO    $pdo
// @param string $cidade  — filtra por cidade (opcional)
//
// @return array [ ['bairro'=>..., 'cidade'=>..., 'valor'=>...], ... ]
// ──────────────────────────────────────────────────────────────────────────────
function listarBairrosDisponiveis(PDO $pdo, string $cidade = ''): array
{
    if ($cidade !== '') {
        $stmt = $pdo->prepare("
            SELECT bairro, cidade, valor, prazo_min, prazo_max
            FROM frete_bairros
            WHERE cidade    = :cidade
              AND disponivel = 1
              AND ativo      = 1
            ORDER BY bairro ASC
        ");
        $stmt->execute([':cidade' => $cidade]);
    } else {
        $stmt = $pdo->query("
            SELECT bairro, cidade, valor, prazo_min, prazo_max
            FROM frete_bairros
            WHERE disponivel = 1 AND ativo = 1
            ORDER BY cidade ASC, bairro ASC
        ");
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ──────────────────────────────────────────────────────────────────────────────
// listarCidadesDisponiveis()
//
// Retorna todas as cidades atendidas (para autocomplete no checkout).
//
// @param PDO $pdo
// @return array
// ──────────────────────────────────────────────────────────────────────────────
function listarCidadesDisponiveis(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT cidade, estado, valor, prazo_min, prazo_max
        FROM frete_cidades
        WHERE disponivel = 1 AND ativo = 1
        ORDER BY cidade ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ──────────────────────────────────────────────────────────────────────────────
// calcularFreteAjax()
//
// Handler para chamadas AJAX vindas do checkout.
// Lê dados do POST, calcula e retorna JSON.
//
// Chamado por: public/frete_ajax.php (ou diretamente se este arquivo
// for incluído e a flag estiver presente).
//
// Resposta JSON:
// {
//   "sucesso": true,
//   "disponivel": true,
//   "valor": 8.00,
//   "valor_formatado": "R$ 8,00",
//   "gratis": false,
//   "prazo_texto": "30 – 45 min",
//   "mensagem": "Frete: R$ 8,00 · Entrega em 30 – 45 min (...)"
// }
// ──────────────────────────────────────────────────────────────────────────────
function calcularFreteAjax(PDO $pdo): void
{
    header('Content-Type: application/json; charset=utf-8');

    $bairro      = trim($_POST['bairro']       ?? '');
    $cidade      = trim($_POST['cidade']       ?? '');
    $valorPedido = (float) ($_POST['valor_pedido'] ?? 0);

    if (empty($cidade)) {
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => 'Informe a cidade para calcular o frete.',
        ]);
        exit;
    }

    $resultado = calcularFrete($pdo, $bairro, $cidade, $valorPedido);

    echo json_encode([
        'sucesso'         => true,
        'disponivel'      => $resultado['disponivel'],
        'valor'           => $resultado['valor'],
        'valor_formatado' => 'R$ ' . number_format($resultado['valor'], 2, ',', '.'),
        'gratis'          => $resultado['gratis'],
        'prazo_min'       => $resultado['prazo_min'],
        'prazo_max'       => $resultado['prazo_max'],
        'prazo_texto'     => $resultado['prazo_texto'],
        'regra'           => $resultado['regra'],
        'mensagem'        => $resultado['mensagem'],
    ]);
    exit;
}

// ── Modo AJAX direto ──────────────────────────────────────────────────────────
// Se este arquivo for acessado via POST com o campo 'acao=calcular',
// responde como endpoint AJAX sem precisar de arquivo separado.
if (
    php_sapi_name() !== 'cli'
    && $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['acao'] ?? '') === 'calcular_frete'
    && basename($_SERVER['PHP_SELF']) === 'frete.php'
) {
    // Garante que $pdo existe
    if (!isset($pdo)) {
        require_once __DIR__ . '/db.php';
    }
    calcularFreteAjax($pdo);
}