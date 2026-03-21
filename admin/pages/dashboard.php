<?php
// admin/pages/dashboard.php
require_once '../includes/auth.php';

// ============================================
// DADOS SIMULADOS — substituir por banco depois
// ============================================
$stats = [
    'pedidos_hoje'  => 24,
    'faturamento'   => 1847.60,
    'ticket_medio'  => 38.49,
    'clientes'      => 312,
];

$pedidos_recentes = [
    ['id'=>1042,'cliente'=>'Ana Clara',  'itens'=>'Açaí Premium + Milkshake','total'=>35.80,'status'=>'entregue', 'hora'=>'14:32'],
    ['id'=>1041,'cliente'=>'Rafael M.',  'itens'=>'Combo Smash Duplo',        'total'=>42.90,'status'=>'preparo',  'hora'=>'14:18'],
    ['id'=>1040,'cliente'=>'Juliana P.', 'itens'=>'Bolo de Pote Ninho x2',    'total'=>29.80,'status'=>'entregue', 'hora'=>'13:55'],
    ['id'=>1039,'cliente'=>'Marcos T.',  'itens'=>'Hambúrguer Smash + Suco',  'total'=>39.80,'status'=>'cancelado','hora'=>'13:40'],
    ['id'=>1038,'cliente'=>'Carla S.',   'itens'=>'Açaí Tradicional',         'total'=>12.90,'status'=>'entregue', 'hora'=>'13:22'],
];

$produtos_populares = [
    ['nome'=>'Açaí Premium 500ml',  'vendas'=>87, 'receita'=>1649.43],
    ['nome'=>'Hambúrguer Smash',     'vendas'=>64, 'receita'=>1913.60],
    ['nome'=>'Combo Smash Duplo',    'vendas'=>41, 'receita'=>1758.90],
    ['nome'=>'Milkshake Oreo',       'vendas'=>38, 'receita'=>641.20],
    ['nome'=>'Bolo de Pote Ninho',   'vendas'=>35, 'receita'=>521.50],
];

$status_cores = [
    'entregue'  => ['bg'=>'#f0fdf4','cor'=>'#15803d','label'=>'Entregue'],
    'preparo'   => ['bg'=>'#fef9c3','cor'=>'#854d0e','label'=>'Em preparo'],
    'cancelado' => ['bg'=>'#fff0f4','cor'=>'#be185d','label'=>'Cancelado'],
    'pendente'  => ['bg'=>'#eff6ff','cor'=>'#1d4ed8','label'=>'Pendente'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Sabor&Cia Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/pages.css">
</head>
<body>

<div class="admin-wrap">

    <!-- ================================
         SIDEBAR
    ================================ -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-logo">
            <a href="../index.php">Sabor<span>&</span>Cia</a>
            <p>Painel administrativo</p>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Geral</div>

            <a href="dashboard.php" class="nav-item ativo">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="pedidos.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="11" y2="16"/></svg>
                Pedidos
            </a>
            <a href="vendas.php" class="nav-item">
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

    <!-- ================================
         MAIN
    ================================ -->
    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="topbar-esq">
                <button class="btn-menu" id="btnMenu" aria-label="Menu">
                    <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div>
                    <div class="topbar-titulo">Dashboard</div>
                    <div class="topbar-breadcrumb">Visão geral do dia</div>
                </div>
            </div>
            <div class="topbar-dir">
                <div class="topbar-data" id="dataAtual"></div>
                <a href="../../index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ver site
                </a>
            </div>
        </div>

        <!-- CONTEÚDO -->
        <div class="conteudo">

            <!-- STATS -->
            <div class="stats-grid">

                <div class="stat-card">
                    <div class="stat-icone">
                        <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>
                    </div>
                    <div class="stat-valor"><?= $stats['pedidos_hoje'] ?></div>
                    <div class="stat-label">Pedidos hoje</div>
                    <div class="stat-delta up">&#8593; 12% vs ontem</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icone">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="stat-valor">R$ <?= number_format($stats['faturamento'], 0, ',', '.') ?></div>
                    <div class="stat-label">Faturamento hoje</div>
                    <div class="stat-delta up">&#8593; 8% vs ontem</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icone">
                        <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <div class="stat-valor">R$ <?= number_format($stats['ticket_medio'], 2, ',', '.') ?></div>
                    <div class="stat-label">Ticket médio</div>
                    <div class="stat-delta down">&#8595; 2% vs ontem</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icone">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <div class="stat-valor"><?= $stats['clientes'] ?></div>
                    <div class="stat-label">Clientes totais</div>
                    <div class="stat-delta up">&#8593; 5 novos hoje</div>
                </div>

            </div>

            <!-- BOTTOM GRID -->
            <div class="bottom-grid">

                <!-- PEDIDOS RECENTES -->
                <div class="card">
                    <div class="card-head">
                        <h2>Pedidos recentes</h2>
                        <a href="pedidos.php">Ver todos</a>
                    </div>
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Itens</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos_recentes as $p):
                                $s = $status_cores[$p['status']] ?? $status_cores['pendente'];
                            ?>
                            <tr>
                                <td>
                                    <div class="pedido-id">#<?= $p['id'] ?></div>
                                    <div class="pedido-hora"><?= $p['hora'] ?></div>
                                </td>
                                <td><?= htmlspecialchars($p['cliente']) ?></td>
                                <td style="color:var(--cinza);font-size:.82rem;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($p['itens']) ?>
                                </td>
                                <td style="font-weight:600;">R$ <?= number_format($p['total'], 2, ',', '.') ?></td>
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

                <!-- MAIS VENDIDOS -->
                <div class="card">
                    <div class="card-head">
                        <h2>Mais vendidos</h2>
                        <a href="vendas.php">Ver relatório</a>
                    </div>
                    <div class="pop-lista">
                        <?php foreach ($produtos_populares as $i => $p): ?>
                        <div class="pop-item">
                            <div class="pop-rank"><?= $i + 1 ?></div>
                            <div class="pop-info">
                                <div class="pop-nome"><?= htmlspecialchars($p['nome']) ?></div>
                                <div class="pop-vendas"><?= $p['vendas'] ?> vendas</div>
                            </div>
                            <div class="pop-receita">R$ <?= number_format($p['receita'], 0, ',', '.') ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

        </div><!-- /conteudo -->

    </div><!-- /main -->

</div><!-- /admin-wrap -->

<!-- OVERLAY MOBILE -->
<div id="overlay" onclick="fecharMenu()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199;"></div>

<script>
    // Data atual na topbar
    var d = new Date();
    var dias   = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
    var meses  = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    document.getElementById('dataAtual').textContent =
        dias[d.getDay()] + ', ' + d.getDate() + ' ' + meses[d.getMonth()] + ' ' + d.getFullYear();

    // Menu mobile
    document.getElementById('btnMenu').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('aberta');
        document.getElementById('overlay').style.display =
            document.getElementById('sidebar').classList.contains('aberta') ? 'block' : 'none';
    });

    function fecharMenu() {
        document.getElementById('sidebar').classList.remove('aberta');
        document.getElementById('overlay').style.display = 'none';
    }
</script>

</body>
</html>