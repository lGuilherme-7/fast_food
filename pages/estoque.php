<?php
// admin/pages/estoque.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$mensagem = '';
$erro     = '';
$admin_id = $_SESSION['admin_id'] ?? null;

// ── AÇÕES POST ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $pid  = (int)($_POST['produto_id'] ?? 0);

    if (($acao === 'entrada' || $acao === 'saida') && $pid > 0) {
        $qtd    = (int)($_POST['qtd']    ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');

        if ($qtd <= 0) {
            $erro = 'Quantidade deve ser maior que zero.';
        } else {
            $stmt = $pdo->prepare("SELECT nome, estoque FROM produtos WHERE id = ?");
            $stmt->execute([$pid]);
            $prod = $stmt->fetch();

            if ($prod) {
                if ($acao === 'saida' && $qtd > (int)$prod['estoque']) {
                    $erro = 'Quantidade maior que o estoque disponível (' . (int)$prod['estoque'] . ' un.).';
                } else {
                    $op = $acao === 'entrada' ? '+' : '-';
                    $pdo->prepare("UPDATE produtos SET estoque = estoque $op ? WHERE id = ?")->execute([$qtd, $pid]);
                    $pdo->prepare("INSERT INTO estoque_historico (produto_id, tipo, quantidade, motivo, admin_id) VALUES (?,?,?,?,?)")
                        ->execute([$pid, $acao, $qtd, $motivo ?: ($acao === 'entrada' ? 'Entrada de estoque' : 'Saída de estoque'), $admin_id]);
                    $novo     = (int)$pdo->query("SELECT estoque FROM produtos WHERE id = $pid")->fetchColumn();
                    $sinal    = $acao === 'entrada' ? '+' : '-';
                    $mensagem = $sinal.$qtd.' un. em "'.$prod['nome'].'". Novo estoque: '.$novo.' un.';
                }
            }
        }
    }

    if ($acao === 'ajuste' && $pid > 0) {
        $novo_estoque = (int)($_POST['estoque'] ?? 0);
        $novo_minimo  = (int)($_POST['minimo']  ?? 0);
        $motivo       = trim($_POST['motivo']   ?? 'Ajuste manual');

        if ($novo_estoque < 0) {
            $erro = 'Estoque não pode ser negativo.';
        } else {
            $stmt = $pdo->prepare("SELECT nome, estoque FROM produtos WHERE id = ?");
            $stmt->execute([$pid]);
            $prod = $stmt->fetch();
            if ($prod) {
                $qtd_hist = abs($novo_estoque - (int)$prod['estoque']);
                $pdo->prepare("UPDATE produtos SET estoque = ?, estoque_min = ? WHERE id = ?")->execute([$novo_estoque, $novo_minimo, $pid]);
                if ($qtd_hist > 0) {
                    $pdo->prepare("INSERT INTO estoque_historico (produto_id, tipo, quantidade, motivo, admin_id) VALUES (?, 'ajuste', ?, ?, ?)")
                        ->execute([$pid, $qtd_hist, $motivo, $admin_id]);
                }
                $mensagem = 'Estoque de "'.$prod['nome'].'" ajustado para '.$novo_estoque.' unidades.';
            }
        }
    }
}

// ── FILTROS ───────────────────────────────────────────────────
$filtro_status = $_GET['status'] ?? 'todos';
$filtro_cat    = $_GET['cat']    ?? 'todas';
$filtro_busca  = trim($_GET['busca'] ?? '');

// ── QUERY PRINCIPAL (sempre do banco) ────────────────────────
$sql = "
    SELECT p.id, p.nome, p.estoque, p.estoque_min AS minimo,
           p.custo, p.ativo, p.imagem_url AS img,
           c.nome AS cat
    FROM produtos p
    JOIN categorias c ON c.id = p.categoria_id
    WHERE 1=1
";
$params = [];
if ($filtro_cat !== 'todas') { $sql .= " AND c.nome = ?";    $params[] = $filtro_cat; }
if ($filtro_busca !== '')    { $sql .= " AND p.nome LIKE ?"; $params[] = '%'.$filtro_busca.'%'; }
$sql .= " ORDER BY p.estoque ASC, p.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos_todos = $stmt->fetchAll();

// ── STATUS DO ESTOQUE ─────────────────────────────────────────
function statusEstoque(array $p): string {
    $e = (int)$p['estoque'];
    $m = (int)$p['minimo'];
    if ($e === 0)    return 'zero';
    if ($e <= $m)    return 'baixo';
    return 'ok';
}

$lista = $filtro_status !== 'todos'
    ? array_values(array_filter($produtos_todos, fn($p) => statusEstoque($p) === $filtro_status))
    : $produtos_todos;

$max_estq = !empty($lista) ? max(1, max(array_column($lista, 'estoque'))) : 1;

// ── STATS (do banco, sempre atualizado) ──────────────────────
$stats = $pdo->query("
    SELECT
        COUNT(*)                                          AS total,
        SUM(estoque = 0)                                  AS sem_estoque,
        SUM(estoque > 0 AND estoque <= estoque_min)       AS estoque_baixo,
        COALESCE(SUM(estoque * COALESCE(custo,0)), 0)     AS valor_total
    FROM produtos
")->fetch();

// ── CATEGORIAS PARA FILTRO ────────────────────────────────────
$cats = $pdo->query("
    SELECT DISTINCT c.nome
    FROM categorias c JOIN produtos p ON p.categoria_id = c.id
    ORDER BY c.nome
")->fetchAll(PDO::FETCH_COLUMN);

// ── CONTAGENS POR STATUS ──────────────────────────────────────
$qtd_zero  = count(array_filter($produtos_todos, fn($p) => statusEstoque($p) === 'zero'));
$qtd_baixo = count(array_filter($produtos_todos, fn($p) => statusEstoque($p) === 'baixo'));
$qtd_ok    = count(array_filter($produtos_todos, fn($p) => statusEstoque($p) === 'ok'));
$criticos  = array_values(array_filter($produtos_todos, fn($p) => statusEstoque($p) !== 'ok'));

// ── HISTÓRICO ─────────────────────────────────────────────────
$historico = $pdo->query("
    SELECT h.tipo, h.quantidade AS qtd, h.motivo,
           DATE_FORMAT(h.criado_em,'%d/%m %H:%i') AS data,
           p.nome AS produto
    FROM estoque_historico h
    JOIN produtos p ON p.id = h.produto_id
    ORDER BY h.criado_em DESC
    LIMIT 15
")->fetchAll();

// ── MODAL ─────────────────────────────────────────────────────
$modal_id   = (int)($_GET['mov'] ?? 0);
$modal_tipo = in_array($_GET['tipo'] ?? '', ['entrada','saida','ajuste']) ? $_GET['tipo'] : 'entrada';
$modal_prod = null;
if ($modal_id > 0) {
    $stmt = $pdo->prepare("SELECT p.id, p.nome, p.estoque, p.estoque_min AS minimo, p.custo, p.imagem_url AS img FROM produtos p WHERE p.id = ?");
    $stmt->execute([$modal_id]);
    $modal_prod = $stmt->fetch() ?: null;
}

$admin_nome = $_SESSION['admin_nome'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque — Sabor&Cia Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <style>
        .painel-grid { display:grid; grid-template-columns:1fr 270px; gap:20px; align-items:start; }
        .painel-dir  { display:flex; flex-direction:column; gap:16px; }

        .filtros-linha { display:flex; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
        .sel-cat { padding:9px 12px; border-radius:var(--r); border:1px solid var(--borda); background:var(--branco); font-family:var(--f-corpo); font-size:.85rem; color:var(--escuro); cursor:pointer; }

        .tab-status { padding:6px 14px; border-radius:50px; border:1px solid var(--borda); background:var(--branco); font-family:var(--f-corpo); font-size:.8rem; font-weight:600; color:var(--cinza); transition:all .2s; white-space:nowrap; text-decoration:none; display:inline-block; }
        .tab-status:hover { border-color:var(--rosa); color:var(--rosa); }
        .tab-status.ativo { background:var(--rosa); border-color:var(--rosa); color:#fff; }

        .tabela-scroll { overflow-x:auto; }
        .col-financeiro { white-space:nowrap; }

        .prod-cell { display:flex; align-items:center; gap:10px; }
        .prod-nome { font-weight:600; font-size:.88rem; }
        .prod-meta { display:flex; gap:4px; margin-top:3px; flex-wrap:wrap; }

        .estq-cell { display:flex; align-items:center; gap:8px; min-width:110px; }
        .estq-num  { font-weight:700; font-size:.9rem; min-width:28px; }
        .estq-num.zero  { color:#ef4444; }
        .estq-num.baixo { color:#f59e0b; }
        .estq-num.ok    { color:#16a34a; }
        .barra-wrap { flex:1; height:6px; background:var(--borda); border-radius:3px; overflow:hidden; }
        .barra-fill { height:100%; border-radius:3px; transition:width .4s; }
        .barra-fill.zero  { background:#ef4444; width:3px!important; }
        .barra-fill.baixo { background:#f59e0b; }
        .barra-fill.ok    { background:#16a34a; }

        .sub-info { display:flex; flex-direction:column; gap:2px; font-size:.78rem; color:var(--cinza); }
        .sub-info strong { color:var(--escuro); }

        .badge-zero  { background:#fff5f5; color:#ef4444; }
        .badge-baixo { background:#fefce8; color:#854d0e; }
        .badge-ok    { background:#f0fdf4; color:#15803d; }
        .badge-cat   { background:var(--rosa-claro); color:var(--rosa); font-size:.7rem; }

        .row-zero  td { background:#fff5f5; }
        .row-baixo td { background:#fefce8; }

        .btn-acao.entrada { color:#16a34a; border-color:#bbf7d0; background:#f0fdf4; }
        .btn-acao.entrada:hover { background:#16a34a; color:#fff; border-color:#16a34a; }
        .btn-acao.saida   { color:#f59e0b; border-color:#fde68a; background:#fefce8; }
        .btn-acao.saida:hover   { background:#f59e0b; color:#fff; border-color:#f59e0b; }

        .side-card { background:var(--branco); border:1px solid var(--borda); border-radius:var(--r); overflow:hidden; }
        .side-head { display:flex; align-items:center; justify-content:space-between; padding:13px 16px; border-bottom:1px solid var(--borda); }
        .side-head h3 { font-family:var(--f-titulo); font-size:.95rem; font-weight:700; }
        .contador { background:#ef4444; color:#fff; font-size:.72rem; font-weight:700; padding:2px 7px; border-radius:50px; }
        .side-vazio { padding:20px; text-align:center; color:var(--cinza); font-size:.82rem; }

        .alerta-item { display:flex; align-items:center; gap:10px; padding:11px 16px; border-bottom:1px solid var(--borda); }
        .alerta-item:last-child { border-bottom:none; }
        .alerta-dot  { width:9px; height:9px; border-radius:50%; flex-shrink:0; }
        .alerta-dot.zero  { background:#ef4444; }
        .alerta-dot.baixo { background:#f59e0b; }
        .alerta-info { flex:1; min-width:0; }
        .alerta-nome { font-size:.82rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .alerta-sub  { font-size:.74rem; color:var(--cinza); margin-top:2px; }
        .alerta-btn  { font-size:.74rem; font-weight:600; color:var(--rosa); white-space:nowrap; text-decoration:none; padding:4px 8px; border-radius:6px; background:var(--rosa-claro); transition:opacity .15s; }
        .alerta-btn:hover { opacity:.8; }

        .hist-item  { display:flex; align-items:flex-start; gap:10px; padding:10px 16px; border-bottom:1px solid var(--borda); }
        .hist-item:last-child { border-bottom:none; }
        .hist-icone { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .hist-icone.entrada { background:#f0fdf4; }
        .hist-icone.entrada svg { stroke:#16a34a; }
        .hist-icone.saida   { background:#fefce8; }
        .hist-icone.saida   svg { stroke:#f59e0b; }
        .hist-icone.ajuste  { background:var(--rosa-claro); }
        .hist-icone.ajuste  svg { stroke:var(--rosa); }
        .hist-icone.pedido  { background:#eff6ff; }
        .hist-icone.pedido  svg { stroke:#2563eb; }
        .hist-icone svg { width:12px; height:12px; fill:none; stroke-width:2.5; }
        .hist-info   { flex:1; min-width:0; }
        .hist-nome   { font-size:.8rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .hist-motivo { font-size:.74rem; color:var(--cinza); margin-top:1px; }
        .hist-meta   { display:flex; align-items:center; justify-content:space-between; margin-top:4px; }
        .hist-data   { font-size:.72rem; color:var(--cinza); }
        .hist-qtd    { font-size:.74rem; font-weight:700; padding:1px 6px; border-radius:4px; }
        .hist-qtd.entrada { background:#f0fdf4; color:#16a34a; }
        .hist-qtd.saida   { background:#fefce8; color:#f59e0b; }
        .hist-qtd.ajuste  { background:var(--rosa-claro); color:var(--rosa); }
        .hist-qtd.pedido  { background:#eff6ff; color:#2563eb; }

        .alerta-warn { background:#fefce8; border:1px solid #fde68a; color:#854d0e; border-radius:var(--r); padding:12px 16px; font-size:.85rem; font-weight:500; margin-bottom:18px; display:flex; align-items:center; gap:8px; }
        .alerta-warn svg { width:15px; height:15px; stroke:currentColor; fill:none; stroke-width:2; flex-shrink:0; }

        /* Modal */
        .tipo-tabs { display:flex; gap:8px; margin-bottom:18px; }
        .tipo-tab  { flex:1; padding:8px; border-radius:8px; border:1.5px solid var(--borda); background:var(--branco); font-family:var(--f-corpo); font-size:.82rem; font-weight:600; cursor:pointer; color:var(--cinza); transition:all .15s; }
        .tipo-tab.ativo.entrada { border-color:#22c55e; background:#f0fdf4; color:#16a34a; }
        .tipo-tab.ativo.saida   { border-color:#f59e0b; background:#fefce8; color:#854d0e; }
        .tipo-tab.ativo.ajuste  { border-color:var(--rosa); background:var(--rosa-claro); color:var(--rosa); }
        .modal-prod     { display:flex; align-items:center; gap:12px; background:var(--bg); border-radius:10px; padding:12px; margin-bottom:16px; }
        .modal-prod-img { width:44px; height:44px; border-radius:8px; overflow:hidden; flex-shrink:0; }
        .modal-prod-img img { width:100%; height:100%; object-fit:cover; }
        .modal-prod-nome { font-weight:600; font-size:.88rem; }
        .modal-prod-estq { font-size:.78rem; color:var(--cinza); margin-top:2px; }
        .campo-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

        @media (max-width:1024px) { .painel-grid { grid-template-columns:1fr; } }
        @media (max-width:640px)  { .col-financeiro { display:none; } }
    </style>
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
            <a href="dashboard.php"  class="nav-item"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
            <a href="pedidos.php"    class="nav-item"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="11" y2="16"/></svg>Pedidos</a>
            <a href="vendas.php"     class="nav-item"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Vendas</a>
            <div class="nav-label">Catálogo</div>
            <a href="produtos.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>Produtos</a>
            <a href="categorias.php" class="nav-item"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>Categorias</a>
            <a href="estoque.php"    class="nav-item ativo"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>Estoque</a>
            <div class="nav-label">Clientes</div>
            <a href="clientes.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Clientes</a>
            <a href="cupons.php"     class="nav-item"><svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>Cupons</a>
            <div class="nav-label">Sistema</div>
            <a href="configuracoes.php" class="nav-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M12 2v2M4.93 4.93l1.41 1.41M2 12h2M4.93 19.07l1.41-1.41M12 20v2M19.07 19.07l-1.41-1.41M22 12h-2"/></svg>Configurações</a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar"><?= mb_strtoupper(mb_substr($admin_nome,0,2)) ?></div>
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
                    <div class="topbar-titulo">Estoque</div>
                    <div class="topbar-breadcrumb">Entradas, saídas e alertas</div>
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

            <?php if ($mensagem): ?>
            <div class="alerta alerta-ok"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>
            <?php if ($erro): ?>
            <div class="alerta alerta-err"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ((int)$stats['sem_estoque'] > 0 || (int)$stats['estoque_baixo'] > 0): ?>
            <div class="alerta-warn">
                <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <?= (int)$stats['sem_estoque'] ?> produto<?= (int)$stats['sem_estoque']!==1?'s':'' ?> sem estoque
                <?= (int)$stats['estoque_baixo']>0 ? ' e '.(int)$stats['estoque_baixo'].' com estoque baixo' : '' ?>.
                Verifique os alertas.
            </div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-icone" style="background:var(--rosa-claro)">
                        <svg viewBox="0 0 24 24" style="stroke:var(--rosa)"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    </div>
                    <div class="stat-val"><?= (int)$stats['total'] ?></div>
                    <div class="stat-lbl">Produtos monitorados</div>
                </div>
                <div class="stat">
                    <div class="stat-icone" style="background:#fff5f5">
                        <svg viewBox="0 0 24 24" style="stroke:#ef4444"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div class="stat-val" style="color:<?= (int)$stats['sem_estoque']>0?'#ef4444':'var(--escuro)' ?>"><?= (int)$stats['sem_estoque'] ?></div>
                    <div class="stat-lbl">Sem estoque</div>
                </div>
                <div class="stat">
                    <div class="stat-icone" style="background:#fefce8">
                        <svg viewBox="0 0 24 24" style="stroke:#f59e0b"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div class="stat-val" style="color:<?= (int)$stats['estoque_baixo']>0?'#f59e0b':'var(--escuro)' ?>"><?= (int)$stats['estoque_baixo'] ?></div>
                    <div class="stat-lbl">Estoque baixo</div>
                </div>
                <div class="stat">
                    <div class="stat-icone" style="background:#f0fdf4">
                        <svg viewBox="0 0 24 24" style="stroke:#16a34a"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="stat-val">R$ <?= number_format((float)$stats['valor_total'],0,',','.') ?></div>
                    <div class="stat-lbl">Valor em estoque (custo)</div>
                </div>
            </div>

            <!-- DOIS PAINÉIS -->
            <div class="painel-grid">

                <!-- PAINEL ESQUERDO -->
                <div>
                    <!-- Tabs -->
                    <div class="toolbar">
                        <div class="toolbar-esq" style="flex-wrap:wrap;gap:6px">
                            <?php $tabs = ['todos'=>'Todos ('.count($produtos_todos).')','zero'=>'Sem estoque ('.$qtd_zero.')','baixo'=>'Baixo ('.$qtd_baixo.')','ok'=>'Normal ('.$qtd_ok.')']; ?>
                            <?php foreach ($tabs as $slug => $label): ?>
                            <a href="estoque.php?status=<?= $slug ?>&cat=<?= urlencode($filtro_cat) ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>"
                               class="tab-status <?= $filtro_status===$slug?'ativo':'' ?>"><?= $label ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <form method="GET" action="estoque.php" class="filtros-linha">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($filtro_status) ?>">
                        <select name="cat" class="sel-cat" onchange="this.form.submit()">
                            <option value="todas" <?= $filtro_cat==='todas'?'selected':'' ?>>Todas as categorias</option>
                            <?php foreach ($cats as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $filtro_cat===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="busca-wrap" style="flex:1;min-width:160px">
                            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" name="busca" class="busca-input" placeholder="Buscar produto..." value="<?= htmlspecialchars($filtro_busca) ?>" autocomplete="off">
                        </div>
                    </form>

                    <!-- Tabela -->
                    <div class="card">
                        <div class="tabela-scroll">
                            <table class="tabela">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Estoque atual</th>
                                        <th class="col-financeiro">Mín. / Custo</th>
                                        <th class="col-financeiro">Valor em estoque</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($lista)): ?>
                                <tr><td colspan="6">
                                    <div class="tabela-vazio">
                                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--borda)" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                        Nenhum produto encontrado.
                                    </div>
                                </td></tr>
                                <?php else: foreach ($lista as $p):
                                    $st   = statusEstoque($p);
                                    $perc = $st === 'zero' ? 0 : min(100, round(((int)$p['estoque'] / $max_estq) * 100));
                                    $row  = $st === 'zero' ? 'row-zero' : ($st === 'baixo' ? 'row-baixo' : '');
                                ?>
                                <tr class="<?= $row ?>">
                                    <td>
                                        <div class="prod-cell">
                                            <div class="prod-thumb">
                                                <img src="<?= htmlspecialchars($p['img']??'') ?>" alt="<?= htmlspecialchars($p['nome']) ?>">
                                            </div>
                                            <div>
                                                <div class="prod-nome"><?= htmlspecialchars($p['nome']) ?></div>
                                                <div class="prod-meta">
                                                    <span class="badge badge-cat"><?= htmlspecialchars($p['cat']) ?></span>
                                                    <?php if (!$p['ativo']): ?><span class="badge badge-inativo">Inativo</span><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="estq-cell">
                                            <span class="estq-num <?= $st ?>"><?= (int)$p['estoque'] ?></span>
                                            <div class="barra-wrap">
                                                <div class="barra-fill <?= $st ?>" style="width:<?= $perc ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-financeiro">
                                        <div class="sub-info">
                                            <span>Mín: <strong><?= (int)$p['minimo'] ?> un.</strong></span>
                                            <span>Custo: <strong>R$ <?= number_format((float)($p['custo']??0),2,',','.') ?></strong></span>
                                        </div>
                                    </td>
                                    <td class="col-financeiro">
                                        <strong style="font-size:.88rem">R$ <?= number_format((int)$p['estoque']*(float)($p['custo']??0),2,',','.') ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($st==='zero'): ?>
                                        <span class="badge badge-zero">Sem estoque</span>
                                        <?php elseif ($st==='baixo'): ?>
                                        <span class="badge badge-baixo">Baixo</span>
                                        <?php else: ?>
                                        <span class="badge badge-ok">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="acoes">
                                            <a href="estoque.php?mov=<?= $p['id'] ?>&tipo=entrada&status=<?= $filtro_status ?>&cat=<?= urlencode($filtro_cat) ?>">
                                                <button class="btn-acao entrada" title="Entrada">
                                                    <svg viewBox="0 0 24 24"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                                                </button>
                                            </a>
                                            <a href="estoque.php?mov=<?= $p['id'] ?>&tipo=saida&status=<?= $filtro_status ?>&cat=<?= urlencode($filtro_cat) ?>">
                                                <button class="btn-acao saida" title="Saída">
                                                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                                                </button>
                                            </a>
                                            <a href="estoque.php?mov=<?= $p['id'] ?>&tipo=ajuste&status=<?= $filtro_status ?>&cat=<?= urlencode($filtro_cat) ?>">
                                                <button class="btn-acao" title="Ajuste manual">
                                                    <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                </button>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PAINEL DIREITO -->
                <div class="painel-dir">

                    <!-- Alertas críticos -->
                    <div class="side-card">
                        <div class="side-head">
                            <h3>Alertas críticos</h3>
                            <?php if (count($criticos)>0): ?><span class="contador"><?= count($criticos) ?></span><?php endif; ?>
                        </div>
                        <?php if (empty($criticos)): ?>
                        <div class="side-vazio">✓ Tudo em ordem!</div>
                        <?php else: foreach ($criticos as $p): $st=statusEstoque($p); ?>
                        <div class="alerta-item">
                            <div class="alerta-dot <?= $st ?>"></div>
                            <div class="alerta-info">
                                <div class="alerta-nome"><?= htmlspecialchars($p['nome']) ?></div>
                                <div class="alerta-sub">
                                    <?= $st==='zero' ? 'Sem estoque' : (int)$p['estoque'].' un. (mín: '.(int)$p['minimo'].')' ?>
                                </div>
                            </div>
                            <a href="estoque.php?mov=<?= $p['id'] ?>&tipo=entrada" class="alerta-btn">+ Entrada</a>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <!-- Histórico -->
                    <div class="side-card">
                        <div class="side-head"><h3>Últimas movimentações</h3></div>
                        <?php if (empty($historico)): ?>
                        <div class="side-vazio">Nenhuma movimentação ainda.</div>
                        <?php else: foreach ($historico as $h): ?>
                        <div class="hist-item">
                            <div class="hist-icone <?= $h['tipo'] ?>">
                                <?php if ($h['tipo']==='entrada'): ?>
                                <svg viewBox="0 0 24 24"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                                <?php elseif ($h['tipo']==='saida'): ?>
                                <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                                <?php elseif ($h['tipo']==='pedido'): ?>
                                <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>
                                <?php else: ?>
                                <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="hist-info">
                                <div class="hist-nome"><?= htmlspecialchars($h['produto']) ?></div>
                                <div class="hist-motivo"><?= htmlspecialchars($h['motivo']??'') ?></div>
                                <div class="hist-meta">
                                    <span class="hist-data"><?= htmlspecialchars($h['data']) ?></span>
                                    <?php if ((int)$h['qtd']>0): ?>
                                    <span class="hist-qtd <?= $h['tipo'] ?>">
                                        <?= $h['tipo']==='entrada'?'+':($h['tipo']==='saida'||$h['tipo']==='pedido'?'-':'±') ?><?= (int)$h['qtd'] ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL -->
<?php if ($modal_prod): ?>
<div class="modal-overlay" id="modalMov">
    <div class="modal">
        <div class="modal-head">
            <h2>Movimentação de estoque</h2>
            <button class="modal-close" onclick="fecharModal()">&#x2715;</button>
        </div>
        <div class="modal-body">
            <div class="modal-prod">
                <div class="modal-prod-img">
                    <img src="<?= htmlspecialchars($modal_prod['img']??'') ?>" alt="<?= htmlspecialchars($modal_prod['nome']) ?>">
                </div>
                <div>
                    <div class="modal-prod-nome"><?= htmlspecialchars($modal_prod['nome']) ?></div>
                    <div class="modal-prod-estq">Estoque atual: <strong><?= (int)$modal_prod['estoque'] ?></strong> un. — mín. <?= (int)$modal_prod['minimo'] ?></div>
                </div>
            </div>

            <div class="tipo-tabs">
                <button type="button" class="tipo-tab entrada <?= $modal_tipo==='entrada'?'ativo':'' ?>" onclick="setTipo('entrada')">Entrada</button>
                <button type="button" class="tipo-tab saida   <?= $modal_tipo==='saida'  ?'ativo':'' ?>" onclick="setTipo('saida')">Saída</button>
                <button type="button" class="tipo-tab ajuste  <?= $modal_tipo==='ajuste' ?'ativo':'' ?>" onclick="setTipo('ajuste')">Ajuste</button>
            </div>

            <form method="POST" action="estoque.php?status=<?= $filtro_status ?>&cat=<?= urlencode($filtro_cat) ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>" id="formMov">
                <input type="hidden" name="produto_id" value="<?= $modal_prod['id'] ?>">
                <input type="hidden" name="acao" id="tipoAcao" value="<?= htmlspecialchars($modal_tipo) ?>">

                <div id="painelQtd" <?= $modal_tipo==='ajuste'?'style="display:none"':'' ?>>
                    <div class="campo">
                        <label>Quantidade</label>
                        <input type="number" name="qtd" min="1" placeholder="0">
                    </div>
                </div>

                <div id="painelAjuste" <?= $modal_tipo!=='ajuste'?'style="display:none"':'' ?>>
                    <div class="campo-2">
                        <div class="campo">
                            <label>Novo estoque</label>
                            <input type="number" name="estoque" min="0" value="<?= (int)$modal_prod['estoque'] ?>">
                        </div>
                        <div class="campo">
                            <label>Estoque mínimo</label>
                            <input type="number" name="minimo" min="0" value="<?= (int)$modal_prod['minimo'] ?>">
                        </div>
                    </div>
                </div>

                <div class="campo">
                    <label>Motivo / Observação</label>
                    <input type="text" name="motivo" placeholder="Ex: Reposição, produção diária, desperdício...">
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px">
                    <button type="button" class="btn btn-cinza" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn" id="btnSubmitMov"
                        style="background:<?= $modal_tipo==='entrada'?'#22c55e':($modal_tipo==='saida'?'#f59e0b':'var(--rosa)') ?>;color:#fff">
                        <svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:#fff;fill:none;stroke-width:2"><polyline points="20 6 9 17 4 12"/></svg>
                        <?= $modal_tipo==='entrada'?'Registrar entrada':($modal_tipo==='saida'?'Registrar saída':'Salvar ajuste') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="overlayMobile" onclick="fecharMenu()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199;"></div>

<script>
document.getElementById('btnMenu').addEventListener('click',function(){
    document.getElementById('sidebar').classList.toggle('aberta');
    document.getElementById('overlayMobile').style.display=document.getElementById('sidebar').classList.contains('aberta')?'block':'none';
});
function fecharMenu(){ document.getElementById('sidebar').classList.remove('aberta'); document.getElementById('overlayMobile').style.display='none'; }
function fecharModal(){
    var url=new URL(window.location.href);
    url.searchParams.delete('mov'); url.searchParams.delete('tipo');
    window.location.href=url.toString();
}
function setTipo(tipo){
    document.getElementById('tipoAcao').value=tipo;
    document.querySelectorAll('.tipo-tab').forEach(function(t){ t.classList.remove('ativo'); });
    document.querySelector('.tipo-tab.'+tipo).classList.add('ativo');
    var pQ=document.getElementById('painelQtd'), pA=document.getElementById('painelAjuste'), btn=document.getElementById('btnSubmitMov');
    var svg='<svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:#fff;fill:none;stroke-width:2"><polyline points="20 6 9 17 4 12"/></svg> ';
    if(tipo==='ajuste'){ pQ.style.display='none'; pA.style.display=''; btn.style.background='var(--rosa)'; btn.innerHTML=svg+'Salvar ajuste'; }
    else if(tipo==='entrada'){ pQ.style.display=''; pA.style.display='none'; btn.style.background='#22c55e'; btn.innerHTML=svg+'Registrar entrada'; }
    else { pQ.style.display=''; pA.style.display='none'; btn.style.background='#f59e0b'; btn.innerHTML=svg+'Registrar saída'; }
}
document.addEventListener('keydown',function(e){ if(e.key==='Escape') fecharModal(); });
</script>
</body>
</html>