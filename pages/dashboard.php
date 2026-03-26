<?php
// admin/pages/dashboard.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// ── DATAS ────────────────────────────────────────────────────
$hoje  = date('Y-m-d');
$ontem = date('Y-m-d', strtotime('-1 day'));

// ── PEDIDOS HOJE / ONTEM ─────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM pedidos
    WHERE DATE(criado_em) = ? AND status != 'cancelado'
");
$stmt->execute([$hoje]);  $pedidos_hoje  = (int)$stmt->fetchColumn();
$stmt->execute([$ontem]); $pedidos_ontem = (int)$stmt->fetchColumn();

// ── FATURAMENTO HOJE / ONTEM ─────────────────────────────────
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total), 0) FROM pedidos
    WHERE DATE(criado_em) = ? AND status != 'cancelado'
");
$stmt->execute([$hoje]);  $fat_hoje  = (float)$stmt->fetchColumn();
$stmt->execute([$ontem]); $fat_ontem = (float)$stmt->fetchColumn();

// ── TICKET MÉDIO ─────────────────────────────────────────────
$ticket_hoje  = $pedidos_hoje  > 0 ? $fat_hoje  / $pedidos_hoje  : 0;
$ticket_ontem = $pedidos_ontem > 0 ? $fat_ontem / $pedidos_ontem : 0;

// ── CLIENTES ─────────────────────────────────────────────────
$total_clientes = (int)$pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE DATE(criado_em) = ?");
$stmt->execute([$hoje]);
$novos_hoje = (int)$stmt->fetchColumn();

// ── DELTA (%) ────────────────────────────────────────────────
function delta(float $atual, float $anterior): array {
    if ($anterior == 0) return ['pct' => 0, 'up' => true];
    $pct = (($atual - $anterior) / $anterior) * 100;
    return ['pct' => round(abs($pct)), 'up' => $pct >= 0];
}
$d_pedidos = delta($pedidos_hoje, $pedidos_ontem);
$d_fat     = delta($fat_hoje,     $fat_ontem);
$d_ticket  = delta($ticket_hoje,  $ticket_ontem);

// ── PEDIDOS RECENTES ─────────────────────────────────────────
$stmt = $pdo->query("
    SELECT id, cliente_nome AS cliente, total, status,
           TIME_FORMAT(criado_em, '%H:%i') AS hora
    FROM pedidos
    ORDER BY criado_em DESC
    LIMIT 5
");
$pedidos_raw = $stmt->fetchAll();

// Busca itens de cada pedido em uma só query
$pedidos_recentes = [];
if (!empty($pedidos_raw)) {
    $ids   = array_column($pedidos_raw, 'id');
    $marks = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT pedido_id,
               GROUP_CONCAT(produto_nome ORDER BY id SEPARATOR ', ') AS itens
        FROM pedido_itens
        WHERE pedido_id IN ($marks)
        GROUP BY pedido_id
    ");
    $stmt->execute($ids);
    $itens_map = [];
    foreach ($stmt->fetchAll() as $r) {
        $itens_map[$r['pedido_id']] = $r['itens'];
    }
    foreach ($pedidos_raw as $p) {
        $p['itens']       = $itens_map[$p['id']] ?? '—';
        $pedidos_recentes[] = $p;
    }
}

// ── MAIS VENDIDOS (30 dias) ──────────────────────────────────
$stmt = $pdo->query("
    SELECT
        pi.produto_nome          AS nome,
        SUM(pi.quantidade)       AS vendas,
        SUM(pi.subtotal)         AS receita
    FROM pedido_itens pi
    JOIN pedidos p ON p.id = pi.pedido_id
    WHERE p.status != 'cancelado'
      AND p.criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY pi.produto_nome
    ORDER BY vendas DESC
    LIMIT 5
");
$produtos_populares = $stmt->fetchAll();

// ── STATUS → CORES ───────────────────────────────────────────
$status_cores = [
    'entregue'  => ['bg' => '#f0fdf4', 'cor' => '#15803d', 'label' => 'Entregue'],
    'preparo'   => ['bg' => '#fef9c3', 'cor' => '#854d0e', 'label' => 'Em preparo'],
    'cancelado' => ['bg' => '#fff0f4', 'cor' => '#be185d', 'label' => 'Cancelado'],
    'pendente'  => ['bg' => '#eff6ff', 'cor' => '#1d4ed8', 'label' => 'Pendente'],
];

// Nome do admin da sessão
$admin_nome = $_SESSION['admin_nome'] ?? 'Administrador';
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

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <a href="../public/index.php">Sabor<span>&</span>Cia</a>
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
                <div class="user-avatar"><?= mb_strtoupper(mb_substr($admin_nome, 0, 2)) ?></div>
                <div class="user-info">
                    <div class="user-nome"><?= htmlspecialchars($admin_nome) ?></div>
                    <div class="user-role">admin</div>
                </div>
            </div>
            <a href="../public/index.php">
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
                <button class="btn-menu" id="btnMenu" aria-label="Menu">
                    <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div>
                    <div class="topbar-titulo">Dashboard</div>
                    <div class="topbar-breadcrumb">Visão geral — <?= date('d/m/Y') ?></div>
                </div>
            </div>
            <div class="topbar-dir">
                <div class="topbar-data" id="dataAtual"></div>
                <a href="../../public/index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;">
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
                    <div class="stat-valor"><?= $pedidos_hoje ?></div>
                    <div class="stat-label">Pedidos hoje</div>
                    <div class="stat-delta <?= $d_pedidos['up'] ? 'up' : 'down' ?>">
                        <?= $d_pedidos['up'] ? '&#8593;' : '&#8595;' ?> <?= $d_pedidos['pct'] ?>% vs ontem
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icone">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="stat-valor">R$ <?= number_format($fat_hoje, 0, ',', '.') ?></div>
                    <div class="stat-label">Faturamento hoje</div>
                    <div class="stat-delta <?= $d_fat['up'] ? 'up' : 'down' ?>">
                        <?= $d_fat['up'] ? '&#8593;' : '&#8595;' ?> <?= $d_fat['pct'] ?>% vs ontem
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icone">
                        <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <div class="stat-valor">R$ <?= number_format($ticket_hoje, 2, ',', '.') ?></div>
                    <div class="stat-label">Ticket médio</div>
                    <div class="stat-delta <?= $d_ticket['up'] ? 'up' : 'down' ?>">
                        <?= $d_ticket['up'] ? '&#8593;' : '&#8595;' ?> <?= $d_ticket['pct'] ?>% vs ontem
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icone">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <div class="stat-valor"><?= $total_clientes ?></div>
                    <div class="stat-label">Clientes totais</div>
                    <div class="stat-delta up">
                        &#8593; <?= $novos_hoje ?> novo<?= $novos_hoje !== 1 ? 's' : '' ?> hoje
                    </div>
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

                    <?php if (empty($pedidos_recentes)): ?>
                    <p style="padding:32px 20px;text-align:center;color:var(--cinza);font-size:.85rem;">
                        Nenhum pedido registrado ainda.
                    </p>
                    <?php else: ?>
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
                                    <div class="pedido-hora"><?= htmlspecialchars($p['hora']) ?></div>
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
                    <?php endif; ?>
                </div>

                <!-- MAIS VENDIDOS -->
                <div class="card">
                    <div class="card-head">
                        <h2>Mais vendidos (30 dias)</h2>
                        <a href="vendas.php">Ver relatório</a>
                    </div>

                    <?php if (empty($produtos_populares)): ?>
                    <p style="padding:32px 20px;text-align:center;color:var(--cinza);font-size:.85rem;">
                        Nenhuma venda registrada ainda.
                    </p>
                    <?php else: ?>
                    <div class="pop-lista">
                        <?php foreach ($produtos_populares as $i => $p): ?>
                        <div class="pop-item">
                            <div class="pop-rank"><?= $i + 1 ?></div>
                            <div class="pop-info">
                                <div class="pop-nome"><?= htmlspecialchars($p['nome']) ?></div>
                                <div class="pop-vendas"><?= (int)$p['vendas'] ?> vendas</div>
                            </div>
                            <div class="pop-receita">R$ <?= number_format($p['receita'], 0, ',', '.') ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </div>

        </div><!-- /conteudo -->
    </div><!-- /main -->
</div><!-- /admin-wrap -->

<div id="overlay" onclick="fecharMenu()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199;"></div>

<script>
    var d = new Date();
    var dias  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
    var meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    document.getElementById('dataAtual').textContent =
        dias[d.getDay()] + ', ' + d.getDate() + ' ' + meses[d.getMonth()] + ' ' + d.getFullYear();

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