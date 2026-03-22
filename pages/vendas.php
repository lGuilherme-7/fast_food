<?php
// admin/pages/vendas.php
require_once '../includes/auth.php';
require_once '../../config/db.php';

// ============================================
// DADOS SIMULADOS — substituir por banco depois
// ============================================

// Período selecionado
$periodo = $_GET['periodo'] ?? '7dias';
$periodos = [
    'hoje'   => 'Hoje',
    '7dias'  => 'Últimos 7 dias',
    '30dias' => 'Últimos 30 dias',
    'mes'    => 'Este mês',
];

// Vendas por dia (últimos 7 dias)
$vendas_por_dia = [
    ['dia'=>'15/03','pedidos'=>18,'receita'=>692.40],
    ['dia'=>'16/03','pedidos'=>22,'receita'=>847.80],
    ['dia'=>'17/03','pedidos'=>15,'receita'=>576.50],
    ['dia'=>'18/03','pedidos'=>28,'receita'=>1078.20],
    ['dia'=>'19/03','pedidos'=>31,'receita'=>1192.90],
    ['dia'=>'20/03','pedidos'=>26,'receita'=>1001.60],
    ['dia'=>'21/03','pedidos'=>24,'receita'=>924.30],
];

// Totais do período
$total_pedidos  = array_sum(array_column($vendas_por_dia, 'pedidos'));
$total_receita  = array_sum(array_column($vendas_por_dia, 'receita'));
$ticket_medio   = $total_pedidos > 0 ? $total_receita / $total_pedidos : 0;
$max_receita    = max(array_column($vendas_por_dia, 'receita'));

// Vendas por categoria
$por_categoria = [
    ['cat'=>'Hambúrguer','pedidos'=>58,'receita'=>1742.20,'cor'=>'#f43f7a'],
    ['cat'=>'Açaí',      'pedidos'=>52,'receita'=>936.80, 'cor'=>'#7c3aed'],
    ['cat'=>'Bebidas',   'pedidos'=>38,'receita'=>487.40, 'cor'=>'#0ea5e9'],
    ['cat'=>'Doces',     'pedidos'=>16,'receita'=>146.90, 'cor'=>'#f59e0b'],
];
$total_cat_pedidos = array_sum(array_column($por_categoria, 'pedidos'));
$total_cat_receita = array_sum(array_column($por_categoria, 'receita'));

// Produtos mais vendidos
$top_produtos = [
    ['pos'=>1,'nome'=>'Hambúrguer Smash',       'cat'=>'Hambúrguer','qtd'=>64,'receita'=>1913.60,'ticket'=>29.90],
    ['pos'=>2,'nome'=>'Açaí Premium 500ml',     'cat'=>'Açaí',      'qtd'=>57,'receita'=>1077.30,'ticket'=>18.90],
    ['pos'=>3,'nome'=>'Combo Smash Duplo',      'cat'=>'Hambúrguer','qtd'=>41,'receita'=>1758.90,'ticket'=>42.90],
    ['pos'=>4,'nome'=>'Milkshake Oreo',         'cat'=>'Bebidas',   'qtd'=>38,'receita'=>641.20, 'ticket'=>16.90],
    ['pos'=>5,'nome'=>'Açaí com Morango 400ml', 'cat'=>'Açaí',      'qtd'=>35,'receita'=>556.50, 'ticket'=>15.90],
    ['pos'=>6,'nome'=>'Smash Bacon',            'cat'=>'Hambúrguer','qtd'=>28,'receita'=>977.20, 'ticket'=>34.90],
    ['pos'=>7,'nome'=>'Bolo de Pote Ninho',     'cat'=>'Doces',     'qtd'=>26,'receita'=>387.40, 'ticket'=>14.90],
    ['pos'=>8,'nome'=>'Suco de Laranja',        'cat'=>'Bebidas',   'qtd'=>24,'receita'=>237.60, 'ticket'=>9.90],
];
$max_qtd = max(array_column($top_produtos, 'qtd'));

// Formas de pagamento
$pagamentos = [
    ['metodo'=>'Pix',     'qtd'=>68,'receita'=>2614.80,'perc'=>41],
    ['metodo'=>'Cartão',  'qtd'=>52,'receita'=>2001.20,'perc'=>31],
    ['metodo'=>'Dinheiro','qtd'=>44,'receita'=>1697.30,'perc'=>27],
];

// Últimas transações
$transacoes = [
    ['id'=>1042,'cliente'=>'Ana Clara',  'produto'=>'Açaí Premium + Milkshake','total'=>35.80,'pagto'=>'pix',    'status'=>'entregue','hora'=>'14:32'],
    ['id'=>1041,'cliente'=>'Rafael M.',  'produto'=>'Combo Smash Duplo',        'total'=>42.90,'pagto'=>'cartao', 'status'=>'preparo', 'hora'=>'14:18'],
    ['id'=>1040,'cliente'=>'Juliana P.', 'produto'=>'Bolo de Pote Ninho x2',    'total'=>29.80,'pagto'=>'dinheiro','status'=>'entregue','hora'=>'13:55'],
    ['id'=>1039,'cliente'=>'Marcos T.',  'produto'=>'Hambúrguer Smash + Suco',  'total'=>39.80,'pagto'=>'pix',    'status'=>'cancelado','hora'=>'13:40'],
    ['id'=>1038,'cliente'=>'Carla S.',   'produto'=>'Açaí Tradicional',         'total'=>12.90,'pagto'=>'dinheiro','status'=>'entregue','hora'=>'13:22'],
    ['id'=>1037,'cliente'=>'Bruno L.',   'produto'=>'Smash Bacon + 2x Refri',   'total'=>46.90,'pagto'=>'cartao', 'status'=>'entregue','hora'=>'13:10'],
];

$status_cfg = [
    'entregue'  => ['bg'=>'#f0fdf4','cor'=>'#15803d','label'=>'Entregue'],
    'preparo'   => ['bg'=>'#fef9c3','cor'=>'#854d0e','label'=>'Em preparo'],
    'cancelado' => ['bg'=>'#fff0f4','cor'=>'#be185d','label'=>'Cancelado'],
    'pendente'  => ['bg'=>'#eff6ff','cor'=>'#1d4ed8','label'=>'Pendente'],
];
$pagto_label = ['pix'=>'Pix','cartao'=>'Cartão','dinheiro'=>'Dinheiro'];
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
            <a href="dashboard.php" class="nav-item">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="pedidos.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="11" y2="16"/></svg>
                Pedidos
            </a>
            <a href="vendas.php" class="nav-item ativo">
                <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Vendas
            </a>
            <div class="nav-label">Catálogo</div>
            <a href="produtos.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Produtos
            </a>
            <a href="categorias.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                Categorias
            </a>
            <a href="estoque.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Estoque
            </a>
            <div class="nav-label">Clientes</div>
            <a href="clientes.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Clientes
            </a>
            <a href="cupons.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                Cupons
            </a>
            <div class="nav-label">Sistema</div>
            <a href="configuracoes.php" class="nav-item">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M12 2v2M4.93 4.93l1.41 1.41M2 12h2M4.93 19.07l1.41-1.41M12 20v2M19.07 19.07l-1.41-1.41M22 12h-2"/></svg>
                Configurações
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar">AD</div>
                <div class="user-info">
                    <div class="user-nome">Administrador</div>
                    <div class="user-role">admin</div>
                </div>
            </div>
            <a href="../logout.php">
                <button class="btn-logout">
                    <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Sair do painel
                </button>
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="topbar-esq">
                <button class="btn-menu" id="btnMenu">
                    <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div>
                    <div class="topbar-titulo">Vendas</div>
                    <div class="topbar-breadcrumb">Relatório e análise de desempenho</div>
                </div>
            </div>
            <div class="topbar-dir">
                <a href="../../index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ver site
                </a>
            </div>
        </div>

        <!-- CONTEÚDO -->
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
                    <div class="stat-delta up">&#8593; 14% vs período anterior</div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg></div>
                    <div class="stat-val"><?= $total_pedidos ?></div>
                    <div class="stat-lbl">Pedidos no período</div>
                    <div class="stat-delta up">&#8593; 8% vs período anterior</div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                    <div class="stat-val">R$ <?= number_format($ticket_medio, 2, ',', '.') ?></div>
                    <div class="stat-lbl">Ticket médio</div>
                    <div class="stat-delta down">&#8595; 2% vs período anterior</div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                    <div class="stat-val"><?= round($total_pedidos / count($vendas_por_dia), 1) ?></div>
                    <div class="stat-lbl">Média de pedidos/dia</div>
                    <div class="stat-delta up">&#8593; 3% vs período anterior</div>
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
                        <div class="legenda-item">
                            <div class="legenda-dot" style="background:var(--rosa)"></div>
                            Receita diária
                        </div>
                    </div>
                </div>
                <div class="barras-wrap">
                    <?php foreach ($vendas_por_dia as $d):
                        $altura = $max_receita > 0 ? ($d['receita'] / $max_receita) * 100 : 0;
                    ?>
                    <div class="barra-col">
                        <div class="barra-val">R$ <?= number_format($d['receita'], 0, ',', '.') ?></div>
                        <div class="barra" style="height:<?= $altura ?>%" title="<?= $d['dia'] ?>: R$ <?= number_format($d['receita'], 2, ',', '.') ?>"></div>
                        <div class="barra-label"><?= $d['dia'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- MEIO: CATEGORIAS + PAGAMENTOS -->
            <div class="meio-grid">

                <!-- VENDAS POR CATEGORIA -->
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h2>Vendas por categoria</h2>
                            <p><?= $total_cat_pedidos ?> pedidos — R$ <?= number_format($total_cat_receita, 2, ',', '.') ?></p>
                        </div>
                    </div>
                    <div class="cat-lista">
                        <?php foreach ($por_categoria as $c):
                            $perc = $total_cat_receita > 0 ? ($c['receita'] / $total_cat_receita) * 100 : 0;
                        ?>
                        <div class="cat-item">
                            <div class="cat-dot" style="background:<?= $c['cor'] ?>"></div>
                            <div class="cat-info">
                                <div class="cat-nome"><?= $c['cat'] ?></div>
                                <div class="cat-barra-wrap">
                                    <div class="cat-barra-fill" style="width:<?= $perc ?>%;background:<?= $c['cor'] ?>"></div>
                                </div>
                            </div>
                            <div class="cat-nums">
                                <div class="cat-receita">R$ <?= number_format($c['receita'], 0, ',', '.') ?></div>
                                <div class="cat-pedidos"><?= $c['pedidos'] ?> pedidos</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- FORMAS DE PAGAMENTO -->
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h2>Formas de pagamento</h2>
                            <p>Distribuição no período</p>
                        </div>
                    </div>
                    <div class="pagto-lista">
                        <?php foreach ($pagamentos as $pg): ?>
                        <div class="pagto-item">
                            <div class="pagto-icone">
                                <?php if ($pg['metodo'] === 'Pix'): ?>
                                <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                <?php elseif ($pg['metodo'] === 'Cartão'): ?>
                                <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                <?php else: ?>
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="pagto-info">
                                <div class="pagto-nome"><?= $pg['metodo'] ?> — <?= $pg['qtd'] ?> pedidos</div>
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
                        <?php foreach ($top_produtos as $p): ?>
                        <tr>
                            <td>
                                <span class="rank-num <?= $p['pos'] <= 3 ? 'top' : '' ?>"><?= $p['pos'] ?></span>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($p['nome']) ?></div>
                                <div class="prod-barra-wrap">
                                    <div class="prod-barra-fill" style="width:<?= $max_qtd > 0 ? ($p['qtd']/$max_qtd)*100 : 0 ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background:var(--rosa-claro);color:var(--rosa);">
                                    <?= htmlspecialchars($p['cat']) ?>
                                </span>
                            </td>
                            <td style="font-weight:700;"><?= $p['qtd'] ?> un.</td>
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
            </div>

            <!-- ÚLTIMAS TRANSAÇÕES -->
            <div class="trans-card">
                <div style="padding:18px 20px;border-bottom:1px solid var(--borda);display:flex;align-items:flex-start;justify-content:space-between;">
                    <div>
                        <h2 style="font-family:var(--f-titulo);font-size:1rem;font-weight:700;">Últimas transações</h2>
                        <p style="font-size:.76rem;color:var(--cinza);margin-top:2px;">Pedidos mais recentes</p>
                    </div>
                    <a href="pedidos.php" style="font-size:.78rem;color:var(--rosa);font-weight:500;">Ver todos</a>
                </div>
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
                                <div style="font-weight:700;color:var(--rosa);font-size:.88rem;">#<?= $t['id'] ?></div>
                                <div style="font-size:.72rem;color:var(--cinza)"><?= $t['hora'] ?></div>
                            </td>
                            <td style="font-weight:500"><?= htmlspecialchars($t['cliente']) ?></td>
                            <td style="color:var(--cinza);font-size:.82rem;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars($t['produto']) ?>
                            </td>
                            <td style="font-size:.82rem;color:var(--cinza)"><?= $pagto_label[$t['pagto']] ?></td>
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
            </div>

        </div><!-- /conteudo -->
    </div><!-- /main -->
</div><!-- /admin-wrap -->

<!-- OVERLAY MOBILE -->
<div id="overlayMobile" onclick="fecharMenu()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199;"></div>

<script>
    // Menu mobile
    document.getElementById('btnMenu').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('aberta');
        document.getElementById('overlayMobile').style.display =
            document.getElementById('sidebar').classList.contains('aberta') ? 'block' : 'none';
    });
    function fecharMenu() {
        document.getElementById('sidebar').classList.remove('aberta');
        document.getElementById('overlayMobile').style.display = 'none';
    }

    // Exportar CSV simulado
    function exportarCSV() {
        var linhas = [
            ['Dia', 'Pedidos', 'Receita (R$)'],
            <?php foreach ($vendas_por_dia as $d): ?>
            ['<?= $d['dia'] ?>', '<?= $d['pedidos'] ?>', '<?= number_format($d['receita'], 2, '.', '') ?>'],
            <?php endforeach; ?>
        ];
        var csv = linhas.map(function(r) { return r.join(';'); }).join('\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'vendas_<?= $periodo ?>_<?= date('Y-m-d') ?>.csv';
        link.click();
    }
</script>

</body>
</html>