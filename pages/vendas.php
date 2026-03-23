<?php
// admin/pages/vendas.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// ── PERÍODO ──────────────────────────────────────────────────
$periodo  = $_GET['periodo'] ?? '7dias';
$periodos = [
    'hoje'   => 'Hoje',
    '7dias'  => 'Últimos 7 dias',
    '30dias' => 'Últimos 30 dias',
    'mes'    => 'Este mês',
];

// Intervalo SQL para cada período
switch ($periodo) {
    case 'hoje':
        $where_data  = "DATE(p.criado_em) = CURDATE()";
        $where_ant   = "DATE(p.criado_em) = CURDATE() - INTERVAL 1 DAY";
        $dias_graf   = 1;
        break;
    case '30dias':
        $where_data  = "p.criado_em >= CURDATE() - INTERVAL 30 DAY";
        $where_ant   = "p.criado_em >= CURDATE() - INTERVAL 60 DAY AND p.criado_em < CURDATE() - INTERVAL 30 DAY";
        $dias_graf   = 30;
        break;
    case 'mes':
        $where_data  = "YEAR(p.criado_em) = YEAR(NOW()) AND MONTH(p.criado_em) = MONTH(NOW())";
        $where_ant   = "YEAR(p.criado_em) = YEAR(NOW() - INTERVAL 1 MONTH) AND MONTH(p.criado_em) = MONTH(NOW() - INTERVAL 1 MONTH)";
        $dias_graf   = (int)date('t');
        break;
    default: // 7dias
        $where_data  = "p.criado_em >= CURDATE() - INTERVAL 7 DAY";
        $where_ant   = "p.criado_em >= CURDATE() - INTERVAL 14 DAY AND p.criado_em < CURDATE() - INTERVAL 7 DAY";
        $dias_graf   = 7;
        break;
}
$filtro_nao_cancelado = "AND p.status != 'cancelado'";

// ── TOTAIS DO PERÍODO ─────────────────────────────────────────
$stmt = $pdo->query("
    SELECT
        COUNT(*)                    AS total_pedidos,
        COALESCE(SUM(p.total), 0)   AS total_receita
    FROM pedidos p
    WHERE $where_data $filtro_nao_cancelado
");
$totais = $stmt->fetch();
$total_pedidos = (int)$totais['total_pedidos'];
$total_receita = (float)$totais['total_receita'];
$ticket_medio  = $total_pedidos > 0 ? $total_receita / $total_pedidos : 0;

// ── TOTAIS DO PERÍODO ANTERIOR (delta) ────────────────────────
$stmt = $pdo->query("
    SELECT
        COUNT(*)                    AS total_pedidos,
        COALESCE(SUM(p.total), 0)   AS total_receita
    FROM pedidos p
    WHERE $where_ant $filtro_nao_cancelado
");
$ant = $stmt->fetch();
$ant_pedidos = (int)$ant['total_pedidos'];
$ant_receita = (float)$ant['total_receita'];
$ant_ticket  = $ant_pedidos > 0 ? $ant_receita / $ant_pedidos : 0;
$media_dia   = $dias_graf > 0 ? round($total_pedidos / $dias_graf, 1) : 0;
$ant_media   = $dias_graf > 0 ? round($ant_pedidos   / $dias_graf, 1) : 0;

// Função delta
function delta_pct(float $atual, float $ant): array {
    if ($ant == 0) return ['pct' => 0, 'up' => true];
    $pct = (($atual - $ant) / $ant) * 100;
    return ['pct' => round(abs($pct)), 'up' => $pct >= 0];
}
$d_receita = delta_pct($total_receita, $ant_receita);
$d_pedidos = delta_pct($total_pedidos, $ant_pedidos);
$d_ticket  = delta_pct($ticket_medio,  $ant_ticket);
$d_media   = delta_pct($media_dia,     $ant_media);

// ── VENDAS POR DIA (gráfico de barras) ───────────────────────
$stmt = $pdo->query("
    SELECT
        DATE_FORMAT(p.criado_em, '%d/%m') AS dia,
        COUNT(*)                          AS pedidos,
        COALESCE(SUM(p.total), 0)         AS receita
    FROM pedidos p
    WHERE $where_data $filtro_nao_cancelado
    GROUP BY DATE(p.criado_em)
    ORDER BY DATE(p.criado_em) ASC
");
$vendas_por_dia = $stmt->fetchAll();
$max_receita    = !empty($vendas_por_dia) ? max(array_column($vendas_por_dia, 'receita')) : 1;

// ── VENDAS POR CATEGORIA ──────────────────────────────────────
$stmt = $pdo->query("
    SELECT
        c.nome AS cat,
        c.cor,
        COUNT(DISTINCT p.id)      AS pedidos,
        COALESCE(SUM(pi.subtotal), 0) AS receita
    FROM pedido_itens pi
    JOIN pedidos p ON p.id = pi.pedido_id
    JOIN produtos pr ON pr.id = pi.produto_id
    JOIN categorias c ON c.id = pr.categoria_id
    WHERE $where_data $filtro_nao_cancelado
    GROUP BY c.id
    ORDER BY receita DESC
");
$por_categoria     = $stmt->fetchAll();
$total_cat_pedidos = array_sum(array_column($por_categoria, 'pedidos'));
$total_cat_receita = array_sum(array_column($por_categoria, 'receita'));

// ── FORMAS DE PAGAMENTO ───────────────────────────────────────
$stmt = $pdo->query("
    SELECT
        p.pagamento                 AS metodo,
        COUNT(*)                    AS qtd,
        COALESCE(SUM(p.total), 0)   AS receita
    FROM pedidos p
    WHERE $where_data $filtro_nao_cancelado
    GROUP BY p.pagamento
    ORDER BY receita DESC
");
$pagamentos_raw    = $stmt->fetchAll();
$total_pag_pedidos = array_sum(array_column($pagamentos_raw, 'qtd'));
$pagamentos        = array_map(function($pg) use ($total_pag_pedidos) {
    $pg['perc'] = $total_pag_pedidos > 0 ? round(($pg['qtd'] / $total_pag_pedidos) * 100) : 0;
    return $pg;
}, $pagamentos_raw);

// ── TOP PRODUTOS ──────────────────────────────────────────────
$stmt = $pdo->query("
    SELECT
        pi.produto_nome                        AS nome,
        c.nome                                 AS cat,
        SUM(pi.quantidade)                     AS qtd,
        COALESCE(SUM(pi.subtotal), 0)          AS receita,
        COALESCE(SUM(pi.subtotal) / NULLIF(SUM(pi.quantidade), 0), 0) AS ticket
    FROM pedido_itens pi
    JOIN pedidos p  ON p.id  = pi.pedido_id
    LEFT JOIN produtos pr ON pr.id = pi.produto_id
    LEFT JOIN categorias c ON c.id = pr.categoria_id
    WHERE $where_data $filtro_nao_cancelado
    GROUP BY pi.produto_nome
    ORDER BY qtd DESC
    LIMIT 8
");
$top_produtos = $stmt->fetchAll();
$max_qtd      = !empty($top_produtos) ? max(array_column($top_produtos, 'qtd')) : 1;

// ── ÚLTIMAS TRANSAÇÕES ────────────────────────────────────────
$stmt = $pdo->query("
    SELECT
        p.id,
        p.cliente_nome  AS cliente,
        p.total,
        p.pagamento     AS pagto,
        p.status,
        TIME_FORMAT(p.criado_em, '%H:%i') AS hora
    FROM pedidos p
    WHERE $where_data
    ORDER BY p.criado_em DESC
    LIMIT 6
");
$transacoes_raw = $stmt->fetchAll();

// Resumo dos itens por transação
$transacoes = [];
if (!empty($transacoes_raw)) {
    $ids   = array_column($transacoes_raw, 'id');
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt  = $pdo->prepare("
        SELECT pedido_id,
               GROUP_CONCAT(quantidade, 'x ', produto_nome ORDER BY id SEPARATOR ', ') AS resumo
        FROM pedido_itens WHERE pedido_id IN ($marks) GROUP BY pedido_id
    ");
    $stmt->execute($ids);
    $itens_map = [];
    foreach ($stmt->fetchAll() as $r) $itens_map[$r['pedido_id']] = $r['resumo'];
    foreach ($transacoes_raw as $t) {
        $t['produto'] = $itens_map[$t['id']] ?? '—';
        $transacoes[] = $t;
    }
}

// ── HELPERS ──────────────────────────────────────────────────
$status_cfg  = [
    'entregue'  => ['bg' => '#f0fdf4', 'cor' => '#15803d', 'label' => 'Entregue'],
    'preparo'   => ['bg' => '#fef9c3', 'cor' => '#854d0e', 'label' => 'Em preparo'],
    'cancelado' => ['bg' => '#fff0f4', 'cor' => '#be185d', 'label' => 'Cancelado'],
    'pendente'  => ['bg' => '#eff6ff', 'cor' => '#1d4ed8', 'label' => 'Pendente'],
];
$pagto_label = ['pix' => 'Pix', 'cartao' => 'Cartão', 'dinheiro' => 'Dinheiro'];
$pagto_icone = [
    'pix'      => '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>',
    'cartao'   => '<svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
    'dinheiro' => '<svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>',
];
$admin_nome  = $_SESSION['admin_nome'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendas — Sabor&Cia Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/pages.css">
</head>
<body>
<div class="admin-wrap">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <a href="../index.php">Sabor<span>&</span>Cia</a>
            <p>Painel administrativo</p>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Geral</div>
            <a href="dashboard.php" class="nav-item"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
            <a href="pedidos.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="11" y2="16"/></svg>Pedidos</a>
            <a href="vendas.php"    class="nav-item ativo"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Vendas</a>
            <div class="nav-label">Catálogo</div>
            <a href="produtos.php"  class="nav-item"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>Produtos</a>
            <a href="categorias.php"class="nav-item"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>Categorias</a>
            <a href="estoque.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>Estoque</a>
            <div class="nav-label">Clientes</div>
            <a href="clientes.php"  class="nav-item"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Clientes</a>
            <a href="cupons.php"    class="nav-item"><svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>Cupons</a>
            <div class="nav-label">Sistema</div>
            <a href="configuracoes.php" class="nav-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M12 2v2M4.93 4.93l1.41 1.41M2 12h2M4.93 19.07l1.41-1.41M12 20v2M19.07 19.07l-1.41-1.41M22 12h-2"/></svg>Configurações</a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar"><?= mb_strtoupper(mb_substr($admin_nome, 0, 2)) ?></div>
                <div class="user-info">
                    <div class="user-nome"><?= htmlspecialchars($admin_nome) ?></div>
                    <div class="user-role">admin</div>
                </div>
            </div>
            <a href="../logout.php"><button class="btn-logout"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Sair do painel</button></a>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main">
        <div class="topbar">
            <div class="topbar-esq">
                <button class="btn-menu" id="btnMenu"><svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
                <div>
                    <div class="topbar-titulo">Vendas</div>
                    <div class="topbar-breadcrumb">Relatório — <?= $periodos[$periodo] ?></div>
                </div>
            </div>
            <div class="topbar-dir">
                <a href="../../public/index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ver site
                </a>
            </div>
        </div>

        <div class="conteudo">

            <!-- FILTRO PERÍODO -->
            <div class="periodo-bar">
                <div class="periodo-tabs">
                    <?php foreach ($periodos as $slug => $label): ?>
                    <a href="vendas.php?periodo=<?= $slug ?>"
                       class="tab-periodo <?= $periodo === $slug ? 'ativo' : '' ?>">
                        <?= $label ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <button class="btn-exportar" onclick="exportarCSV()">
                    <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar CSV
                </button>
            </div>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                    <div class="stat-val">R$ <?= number_format($total_receita, 0, ',', '.') ?></div>
                    <div class="stat-lbl">Faturamento total</div>
                    <div class="stat-delta <?= $d_receita['up'] ? 'up' : 'down' ?>">
                        <?= $d_receita['up'] ? '&#8593;' : '&#8595;' ?> <?= $d_receita['pct'] ?>% vs período anterior
                    </div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg></div>
                    <div class="stat-val"><?= $total_pedidos ?></div>
                    <div class="stat-lbl">Pedidos no período</div>
                    <div class="stat-delta <?= $d_pedidos['up'] ? 'up' : 'down' ?>">
                        <?= $d_pedidos['up'] ? '&#8593;' : '&#8595;' ?> <?= $d_pedidos['pct'] ?>% vs período anterior
                    </div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                    <div class="stat-val">R$ <?= number_format($ticket_medio, 2, ',', '.') ?></div>
                    <div class="stat-lbl">Ticket médio</div>
                    <div class="stat-delta <?= $d_ticket['up'] ? 'up' : 'down' ?>">
                        <?= $d_ticket['up'] ? '&#8593;' : '&#8595;' ?> <?= $d_ticket['pct'] ?>% vs período anterior
                    </div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                    <div class="stat-val"><?= $media_dia ?></div>
                    <div class="stat-lbl">Média de pedidos/dia</div>
                    <div class="stat-delta <?= $d_media['up'] ? 'up' : 'down' ?>">
                        <?= $d_media['up'] ? '&#8593;' : '&#8595;' ?> <?= $d_media['pct'] ?>% vs período anterior
                    </div>
                </div>
            </div>

            <!-- GRÁFICO DE BARRAS -->
            <div class="grafico-card">
                <div class="grafico-head">
                    <div>
                        <h2>Faturamento por dia</h2>
                        <p><?= $periodos[$periodo] ?></p>
                    </div>
                    <div class="grafico-legenda">
                        <div class="legenda-item"><div class="legenda-dot" style="background:var(--rosa)"></div>Receita diária</div>
                    </div>
                </div>
                <?php if (empty($vendas_por_dia)): ?>
                <div style="padding:40px;text-align:center;color:var(--cinza);font-size:.85rem;">Nenhuma venda no período.</div>
                <?php else: ?>
               <div class="barras-wrap">
                    <?php foreach ($vendas_por_dia as $d):
                        $altura = $max_receita > 0 ? round(($d['receita'] / $max_receita) * 140) : 4;
                    ?>
                    <div class="barra-col">
                        <div class="barra-val">R$ <?= number_format($d['receita'], 0, ',', '.') ?></div>
                        <div class="barra" style="height:<?= $altura ?>px; width:100%"></div>
                        <div class="barra-label"><?= $d['dia'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- CATEGORIAS + PAGAMENTOS -->
            <div class="meio-grid">

                <!-- POR CATEGORIA -->
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h2>Vendas por categoria</h2>
                            <p><?= $total_cat_pedidos ?> pedidos — R$ <?= number_format($total_cat_receita, 2, ',', '.') ?></p>
                        </div>
                    </div>
                    <?php if (empty($por_categoria)): ?>
                    <div style="padding:32px;text-align:center;color:var(--cinza);font-size:.85rem;">Nenhum dado no período.</div>
                    <?php else: ?>
                    <div class="cat-lista">
                        <?php foreach ($por_categoria as $c):
                            $perc = $total_cat_receita > 0 ? ($c['receita'] / $total_cat_receita) * 100 : 0;
                            $cor  = !empty($c['cor']) ? $c['cor'] : '#f43f7a';
                        ?>
                        <div class="cat-item">
                            <div class="cat-dot" style="background:<?= htmlspecialchars($cor) ?>"></div>
                            <div class="cat-info">
                                <div class="cat-nome"><?= htmlspecialchars($c['cat']) ?></div>
                                <div class="cat-barra-wrap">
                                    <div class="cat-barra-fill" style="width:<?= round($perc) ?>%;background:<?= htmlspecialchars($cor) ?>"></div>
                                </div>
                            </div>
                            <div class="cat-nums">
                                <div class="cat-receita">R$ <?= number_format($c['receita'], 0, ',', '.') ?></div>
                                <div class="cat-pedidos"><?= (int)$c['pedidos'] ?> pedidos</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- FORMAS DE PAGAMENTO -->
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h2>Formas de pagamento</h2>
                            <p>Distribuição no período</p>
                        </div>
                    </div>
                    <?php if (empty($pagamentos)): ?>
                    <div style="padding:32px;text-align:center;color:var(--cinza);font-size:.85rem;">Nenhum dado no período.</div>
                    <?php else: ?>
                    <div class="pagto-lista">
                        <?php foreach ($pagamentos as $pg):
                            $icone = $pagto_icone[$pg['metodo']] ?? $pagto_icone['pix'];
                            $label = $pagto_label[$pg['metodo']] ?? ucfirst($pg['metodo']);
                        ?>
                        <div class="pagto-item">
                            <div class="pagto-icone"><?= $icone ?></div>
                            <div class="pagto-info">
                                <div class="pagto-nome"><?= $label ?> — <?= (int)$pg['qtd'] ?> pedidos</div>
                                <div class="pagto-barra-wrap">
                                    <div class="pagto-barra-fill" style="width:<?= $pg['perc'] ?>%"></div>
                                </div>
                            </div>
                            <div class="pagto-nums">
                                <div class="pagto-perc"><?= $pg['perc'] ?>%</div>
                                <div class="pagto-receita">R$ <?= number_format($pg['receita'], 0, ',', '.') ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- RANKING PRODUTOS -->
            <div class="ranking-card">
                <div class="card-head" style="padding:18px 20px;border-bottom:1px solid var(--borda);display:flex;align-items:flex-start;justify-content:space-between;">
                    <div>
                        <h2 style="font-family:var(--f-titulo);font-size:1rem;font-weight:700;">Ranking de produtos</h2>
                        <p style="font-size:.76rem;color:var(--cinza);margin-top:2px;">Por quantidade vendida no período</p>
                    </div>
                </div>
                <?php if (empty($top_produtos)): ?>
                <div style="padding:40px;text-align:center;color:var(--cinza);font-size:.85rem;">Nenhum produto vendido no período.</div>
                <?php else: ?>
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Qtd vendida</th>
                            <th>Ticket médio</th>
                            <th>Receita total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_produtos as $i => $p): ?>
                        <tr>
                            <td>
                                <span class="rank-num <?= $i < 3 ? 'top' : '' ?>"><?= $i + 1 ?></span>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($p['nome']) ?></div>
                                <div class="prod-barra-wrap">
                                    <div class="prod-barra-fill" style="width:<?= $max_qtd > 0 ? round(($p['qtd']/$max_qtd)*100) : 0 ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background:var(--rosa-claro);color:var(--rosa);">
                                    <?= htmlspecialchars($p['cat'] ?? '—') ?>
                                </span>
                            </td>
                            <td style="font-weight:700"><?= (int)$p['qtd'] ?> un.</td>
                            <td style="color:var(--cinza)">R$ <?= number_format($p['ticket'], 2, ',', '.') ?></td>
                            <td>
                                <span style="font-family:var(--f-titulo);font-size:.95rem;color:var(--rosa);font-weight:700;">
                                    R$ <?= number_format($p['receita'], 2, ',', '.') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- ÚLTIMAS TRANSAÇÕES -->
            <div class="trans-card">
                <div style="padding:18px 20px;border-bottom:1px solid var(--borda);display:flex;align-items:flex-start;justify-content:space-between;">
                    <div>
                        <h2 style="font-family:var(--f-titulo);font-size:1rem;font-weight:700;">Últimas transações</h2>
                        <p style="font-size:.76rem;color:var(--cinza);margin-top:2px;">Pedidos mais recentes do período</p>
                    </div>
                    <a href="pedidos.php" style="font-size:.78rem;color:var(--rosa);font-weight:500;">Ver todos</a>
                </div>
                <?php if (empty($transacoes)): ?>
                <div style="padding:40px;text-align:center;color:var(--cinza);font-size:.85rem;">Nenhuma transação no período.</div>
                <?php else: ?>
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Itens</th>
                            <th>Pagamento</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transacoes as $t):
                            $s = $status_cfg[$t['status']] ?? $status_cfg['pendente'];
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:700;color:var(--rosa);font-size:.88rem;">
                                    <a href="pedidos.php?ver=<?= $t['id'] ?>" style="color:var(--rosa)">#<?= $t['id'] ?></a>
                                </div>
                                <div style="font-size:.72rem;color:var(--cinza)"><?= htmlspecialchars($t['hora']) ?></div>
                            </td>
                            <td style="font-weight:500"><?= htmlspecialchars($t['cliente']) ?></td>
                            <td style="color:var(--cinza);font-size:.82rem;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars($t['produto']) ?>
                            </td>
                            <td style="font-size:.82rem;color:var(--cinza)">
                                <?= $pagto_label[$t['pagto']] ?? htmlspecialchars($t['pagto']) ?>
                            </td>
                            <td style="font-weight:700">R$ <?= number_format($t['total'], 2, ',', '.') ?></td>
                            <td>
                                <span class="badge" style="background:<?= $s['bg'] ?>;color:<?= $s['cor'] ?>;">
                                    <?= $s['label'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div><!-- /conteudo -->
    </div><!-- /main -->
</div><!-- /admin-wrap -->

<div id="overlayMobile" onclick="fecharMenu()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199;"></div>

<script>
    document.getElementById('btnMenu').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('aberta');
        document.getElementById('overlayMobile').style.display =
            document.getElementById('sidebar').classList.contains('aberta') ? 'block' : 'none';
    });
    function fecharMenu() {
        document.getElementById('sidebar').classList.remove('aberta');
        document.getElementById('overlayMobile').style.display = 'none';
    }

    // Exportar CSV com dados reais do PHP
    function exportarCSV() {
        var linhas = [['Dia','Pedidos','Receita (R$)']];
        <?php foreach ($vendas_por_dia as $d): ?>
        linhas.push(['<?= addslashes($d['dia']) ?>','<?= (int)$d['pedidos'] ?>','<?= number_format($d['receita'],2,'.',',') ?>']);
        <?php endforeach; ?>
        var csv  = linhas.map(function(r){ return r.join(';'); }).join('\n');
        var blob = new Blob(['\uFEFF'+csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'vendas_<?= $periodo ?>_<?= date('Y-m-d') ?>.csv';
        link.click();
    }
</script>
</body>
</html>