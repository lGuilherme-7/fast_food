<?php
// admin/pages/estoque.php
require_once '../includes/auth.php';

// ============================================
// DADOS SIMULADOS — substituir por banco depois
// ============================================
$mensagem = '';
$erro     = '';

$produtos = [
    ['id'=>1, 'nome'=>'Açaí Premium 500ml',    'cat'=>'Açaí',       'estoque'=>48, 'minimo'=>10, 'custo'=>6.50,  'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=80&q=70'],
    ['id'=>2, 'nome'=>'Hambúrguer Smash',       'cat'=>'Hambúrguer', 'estoque'=>32, 'minimo'=>8,  'custo'=>11.20, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=80&q=70'],
    ['id'=>3, 'nome'=>'Bolo de Pote Ninho',     'cat'=>'Doces',      'estoque'=>20, 'minimo'=>5,  'custo'=>4.80,  'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=80&q=70'],
    ['id'=>4, 'nome'=>'Milkshake Oreo',         'cat'=>'Bebidas',    'estoque'=>15, 'minimo'=>8,  'custo'=>5.50,  'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=80&q=70'],
    ['id'=>5, 'nome'=>'Açaí Tradicional 300ml', 'cat'=>'Açaí',       'estoque'=>60, 'minimo'=>15, 'custo'=>4.20,  'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1511690743698-d9d85f2fbf38?w=80&q=70'],
    ['id'=>6, 'nome'=>'Combo Smash Duplo',      'cat'=>'Hambúrguer', 'estoque'=>7,  'minimo'=>8,  'custo'=>18.40, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=80&q=70'],
    ['id'=>7, 'nome'=>'Açaí com Morango 400ml', 'cat'=>'Açaí',       'estoque'=>0,  'minimo'=>10, 'custo'=>5.30,  'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=80&q=70'],
    ['id'=>8, 'nome'=>'Smash Bacon',            'cat'=>'Hambúrguer', 'estoque'=>22, 'minimo'=>8,  'custo'=>14.10, 'ativo'=>false, 'img'=>'https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?w=80&q=70'],
    ['id'=>9, 'nome'=>'Brigadeiro Gourmet',     'cat'=>'Doces',      'estoque'=>50, 'minimo'=>12, 'custo'=>1.80,  'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1611293388250-580b08c4a145?w=80&q=70'],
    ['id'=>10,'nome'=>'Suco de Laranja',        'cat'=>'Bebidas',    'estoque'=>0,  'minimo'=>6,  'custo'=>3.40,  'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?w=80&q=70'],
    ['id'=>11,'nome'=>'Bolo de Pote Oreo',      'cat'=>'Doces',      'estoque'=>12, 'minimo'=>5,  'custo'=>4.80,  'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=80&q=70'],
    ['id'=>12,'nome'=>'Refrigerante Lata',      'cat'=>'Bebidas',    'estoque'=>80, 'minimo'=>20, 'custo'=>2.10,  'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1581636625402-29b2a704ef13?w=80&q=70'],
];

// Histórico de movimentações simulado
$historico = [
    ['data'=>'21/03 14:30','produto'=>'Açaí Premium 500ml',    'tipo'=>'saida',  'qtd'=>3, 'motivo'=>'Pedido #1042', 'user'=>'Sistema'],
    ['data'=>'21/03 13:55','produto'=>'Bolo de Pote Ninho',     'tipo'=>'saida',  'qtd'=>2, 'motivo'=>'Pedido #1040', 'user'=>'Sistema'],
    ['data'=>'21/03 11:00','produto'=>'Açaí com Morango 400ml', 'tipo'=>'entrada','qtd'=>0, 'motivo'=>'Sem estoque — abastecimento necessário', 'user'=>'Admin'],
    ['data'=>'21/03 10:30','produto'=>'Refrigerante Lata',      'tipo'=>'entrada','qtd'=>40,'motivo'=>'Reposição de estoque',  'user'=>'Admin'],
    ['data'=>'20/03 18:00','produto'=>'Combo Smash Duplo',      'tipo'=>'saida',  'qtd'=>5, 'motivo'=>'Pedidos do dia',        'user'=>'Sistema'],
    ['data'=>'20/03 09:00','produto'=>'Brigadeiro Gourmet',     'tipo'=>'entrada','qtd'=>30,'motivo'=>'Produção diária',       'user'=>'Admin'],
];

// ============================================
// AÇÕES POST
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $pid  = (int)($_POST['produto_id'] ?? 0);

    if ($acao === 'ajuste') {
        $novo_estoque = (int)($_POST['estoque'] ?? 0);
        $novo_minimo  = (int)($_POST['minimo']  ?? 0);
        $motivo       = trim($_POST['motivo']   ?? 'Ajuste manual');

        if ($novo_estoque < 0) {
            $erro = 'Estoque não pode ser negativo.';
        } else {
            foreach ($produtos as &$p) {
                if ($p['id'] === $pid) {
                    $p['estoque'] = $novo_estoque;
                    $p['minimo']  = $novo_minimo;
                    $mensagem = 'Estoque de "' . $p['nome'] . '" atualizado para ' . $novo_estoque . ' unidades.';
                    break;
                }
            }
            unset($p);
            // TODO: UPDATE produtos SET estoque = ?, estoque_minimo = ? WHERE id = ?
            //       INSERT INTO estoque_historico (produto_id, tipo, qtd, motivo) VALUES (...)
        }
    }

    if ($acao === 'entrada' || $acao === 'saida') {
        $qtd    = (int)($_POST['qtd']    ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');

        if ($qtd <= 0) {
            $erro = 'Quantidade deve ser maior que zero.';
        } else {
            foreach ($produtos as &$p) {
                if ($p['id'] === $pid) {
                    if ($acao === 'entrada') {
                        $p['estoque'] += $qtd;
                        $mensagem = '+' . $qtd . ' unidades adicionadas em "' . $p['nome'] . '". Novo estoque: ' . $p['estoque'] . ' un.';
                    } else {
                        if ($qtd > $p['estoque']) {
                            $erro = 'Quantidade insuficiente em estoque.';
                        } else {
                            $p['estoque'] -= $qtd;
                            $mensagem = '-' . $qtd . ' unidades retiradas de "' . $p['nome'] . '". Novo estoque: ' . $p['estoque'] . ' un.';
                        }
                    }
                    break;
                }
            }
            unset($p);
            // TODO: UPDATE + INSERT historico
        }
    }
}

// Classificar status de estoque
function statusEstoque($p) {
    if ($p['estoque'] === 0)               return 'zero';
    if ($p['estoque'] <= $p['minimo'])     return 'baixo';
    return 'ok';
}

// Filtros GET
$filtro_status = $_GET['status'] ?? 'todos';
$filtro_cat    = $_GET['cat']    ?? 'todas';
$filtro_busca  = trim($_GET['busca'] ?? '');

$lista = array_filter($produtos, function($p) use ($filtro_status, $filtro_cat, $filtro_busca) {
    $s   = statusEstoque($p);
    $ok_s = ($filtro_status === 'todos' || $s === $filtro_status);
    $ok_c = ($filtro_cat === 'todas'    || $p['cat'] === $filtro_cat);
    $ok_b = empty($filtro_busca) || stripos($p['nome'], $filtro_busca) !== false;
    return $ok_s && $ok_c && $ok_b;
});

// Produto para modal de movimentação
$modal_id  = (int)($_GET['mov'] ?? 0);
$modal_prod = null;
$modal_tipo = $_GET['tipo'] ?? 'entrada'; // entrada | saida | ajuste
foreach ($produtos as $p) {
    if ($p['id'] === $modal_id) { $modal_prod = $p; break; }
}
$abrir_modal = $modal_prod !== null;

// Stats
$total_items  = count($produtos);
$sem_estoque  = count(array_filter($produtos, fn($p) => $p['estoque'] === 0));
$estoque_baixo = count(array_filter($produtos, fn($p) => statusEstoque($p) === 'baixo'));
$valor_total  = array_sum(array_map(fn($p) => $p['estoque'] * $p['custo'], $produtos));
$cats = array_unique(array_column($produtos, 'cat'));
sort($cats);
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
            <a href="estoque.php" class="nav-item ativo">
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
        <div class="topbar">
            <div class="topbar-esq">
                <button class="btn-menu" id="btnMenu">
                    <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div>
                    <div class="topbar-titulo">Estoque</div>
                    <div class="topbar-breadcrumb">Controle de entradas, saídas e alertas</div>
                </div>
            </div>
            <div class="topbar-dir">
                <a href="../../index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ver site
                </a>
            </div>
        </div>

        <div class="conteudo">

            <?php if ($mensagem): ?>
            <div class="alerta alerta-ok">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <?= htmlspecialchars($mensagem) ?>
            </div>
            <?php endif; ?>

            <?php if ($erro): ?>
            <div class="alerta alerta-err">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= htmlspecialchars($erro) ?>
            </div>
            <?php endif; ?>

            <?php if ($sem_estoque > 0 || $estoque_baixo > 0): ?>
            <div class="alerta alerta-warn">
                <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <?= $sem_estoque ?> produto<?= $sem_estoque>1?'s':'' ?> sem estoque
                <?= $estoque_baixo > 0 ? 'e ' . $estoque_baixo . ' com estoque baixo.' : '.' ?>
                Verifique os alertas.
            </div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-icone" style="background:var(--rosa-claro)">
                        <svg viewBox="0 0 24 24" style="stroke:var(--rosa)"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    </div>
                    <div class="stat-val"><?= $total_items ?></div>
                    <div class="stat-lbl">Produtos monitorados</div>
                </div>
                <div class="stat <?= $sem_estoque > 0 ? 'alerta-stat' : '' ?>">
                    <div class="stat-icone" style="background:#fff5f5">
                        <svg viewBox="0 0 24 24" style="stroke:#ef4444"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div class="stat-val" style="color:<?= $sem_estoque > 0 ? '#ef4444' : 'var(--escuro)' ?>"><?= $sem_estoque ?></div>
                    <div class="stat-lbl">Sem estoque</div>
                </div>
                <div class="stat <?= $estoque_baixo > 0 ? 'warn-stat' : '' ?>">
                    <div class="stat-icone" style="background:#fefce8">
                        <svg viewBox="0 0 24 24" style="stroke:#f59e0b"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div class="stat-val" style="color:<?= $estoque_baixo > 0 ? '#f59e0b' : 'var(--escuro)' ?>"><?= $estoque_baixo ?></div>
                    <div class="stat-lbl">Estoque baixo</div>
                </div>
                <div class="stat">
                    <div class="stat-icone" style="background:#f0fdf4">
                        <svg viewBox="0 0 24 24" style="stroke:#16a34a"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="stat-val">R$ <?= number_format($valor_total, 0, ',', '.') ?></div>
                    <div class="stat-lbl">Valor em estoque (custo)</div>
                </div>
            </div>

            <!-- DOIS PAINÉIS -->
            <div class="painel-grid">

                <!-- ESQUERDO: TABELA -->
                <div>
                    <!-- TOOLBAR -->
                    <div class="toolbar">
                        <div class="toolbar-esq">
                            <?php
                            $qtd_zero  = count(array_filter($produtos, fn($p) => statusEstoque($p) === 'zero'));
                            $qtd_baixo = count(array_filter($produtos, fn($p) => statusEstoque($p) === 'baixo'));
                            $qtd_ok    = count(array_filter($produtos, fn($p) => statusEstoque($p) === 'ok'));
                            $tabs = [
                                'todos' => ['label'=>'Todos (' . $total_items . ')','cls'=>''],
                                'zero'  => ['label'=>'Sem estoque (' . $qtd_zero . ')','cls'=>'danger'],
                                'baixo' => ['label'=>'Baixo (' . $qtd_baixo . ')','cls'=>'warn'],
                                'ok'    => ['label'=>'Normal (' . $qtd_ok . ')','cls'=>''],
                            ];
                            foreach ($tabs as $slug => $t):
                            ?>
                            <a href="estoque.php?status=<?= $slug ?>&cat=<?= urlencode($filtro_cat) ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>"
                               class="tab-filtro <?= $t['cls'] ?> <?= $filtro_status === $slug ? 'ativo' : '' ?>">
                                <?= $t['label'] ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="toolbar-dir">
                            <form method="GET" action="estoque.php" style="display:flex;gap:8px;align-items:center">
                                <?php if ($filtro_status !== 'todos'): ?><input type="hidden" name="status" value="<?= $filtro_status ?>"><?php endif; ?>
                                <select name="cat" class="sel-cat" onchange="this.form.submit()">
                                    <option value="todas" <?= $filtro_cat==='todas'?'selected':'' ?>>Todas as cats.</option>
                                    <?php foreach ($cats as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>" <?= $filtro_cat===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="busca-wrap">
                                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                    <input type="text" name="busca" class="busca-input" placeholder="Buscar..."
                                        value="<?= htmlspecialchars($filtro_busca) ?>" autocomplete="off">
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- TABELA -->
                    <div class="card">
                    <div class="tabela-scroll">
                        <table class="tabela">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Estoque atual</th>
                                    <th>Mínimo</th>
                                    <th>Custo unit.</th>
                                    <th>Valor total</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lista)): ?>
                                <tr><td colspan="7">
                                    <div class="tabela-vazio">
                                        <svg width="36" height="36" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                        Nenhum produto encontrado.
                                    </div>
                                </td></tr>
                                <?php else: ?>
                                <?php foreach ($lista as $p):
                                    $st   = statusEstoque($p);
                                    $perc = $p['minimo'] > 0 ? min(100, ($p['estoque'] / ($p['minimo'] * 2)) * 100) : ($p['estoque'] > 0 ? 100 : 0);
                                    $row  = $st === 'zero' ? 'row-zero' : ($st === 'baixo' ? 'row-baixo' : '');
                                ?>
                                <tr class="<?= $row ?>">
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div class="prod-thumb"><img src="<?= $p['img'] ?>" alt="<?= htmlspecialchars($p['nome']) ?>"></div>
                                            <div>
                                                <div style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($p['nome']) ?></div>
                                                <span class="badge badge-cat"><?= htmlspecialchars($p['cat']) ?></span>
                                                <?php if (!$p['ativo']): ?><span class="badge badge-inativo" style="margin-left:4px">Inativo</span><?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="estoque-barra-wrap">
                                            <span class="estoque-qtd <?= $st ?>"><?= $p['estoque'] ?></span>
                                            <div class="barra-estq">
                                                <div class="barra-fill <?= $st ?>" style="width:<?= $perc ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-size:.85rem;color:var(--cinza)"><?= $p['minimo'] ?> un.</td>
                                    <td style="font-size:.85rem;color:var(--cinza)">R$ <?= number_format($p['custo'], 2, ',', '.') ?></td>
                                    <td style="font-weight:600;font-size:.88rem;">R$ <?= number_format($p['estoque'] * $p['custo'], 2, ',', '.') ?></td>
                                    <td>
                                        <?php if ($st === 'zero'): ?>
                                        <span class="badge badge-zero">Sem estoque</span>
                                        <?php elseif ($st === 'baixo'): ?>
                                        <span class="badge badge-baixo">Estoque baixo</span>
                                        <?php else: ?>
                                        <span class="badge badge-ok">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="acoes">
                                            <a href="estoque.php?mov=<?= $p['id'] ?>&tipo=entrada&status=<?= $filtro_status ?>&cat=<?= urlencode($filtro_cat) ?>">
                                                <button class="btn-acao entrada" title="Entrada de estoque">
                                                    <svg viewBox="0 0 24 24"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                                                </button>
                                            </a>
                                            <a href="estoque.php?mov=<?= $p['id'] ?>&tipo=saida&status=<?= $filtro_status ?>&cat=<?= urlencode($filtro_cat) ?>">
                                                <button class="btn-acao saida" title="Saída de estoque">
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
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- DIREITO: ALERTAS + HISTÓRICO -->
                <div class="painel-dir">

                    <!-- ALERTAS CRÍTICOS -->
                    <?php $criticos = array_filter($produtos, fn($p) => statusEstoque($p) !== 'ok'); ?>
                    <div class="alertas-card">
                        <div class="alertas-head">
                            <h3>Alertas</h3>
                            <?php if (count($criticos) > 0): ?>
                            <span class="contador"><?= count($criticos) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($criticos)): ?>
                        <div style="padding:20px;text-align:center;color:var(--cinza);font-size:.85rem;">
                            Tudo em ordem!
                        </div>
                        <?php else: ?>
                        <?php foreach ($criticos as $p):
                            $st = statusEstoque($p);
                        ?>
                        <div class="alerta-item">
                            <div class="alerta-dot <?= $st ?>"></div>
                            <div class="alerta-info">
                                <div class="alerta-nome"><?= htmlspecialchars($p['nome']) ?></div>
                                <div class="alerta-sub">
                                    <?= $st === 'zero' ? 'Sem estoque' : $p['estoque'] . ' un. (mín: ' . $p['minimo'] . ')' ?>
                                </div>
                            </div>
                            <a href="estoque.php?mov=<?= $p['id'] ?>&tipo=entrada" class="alerta-btn">+ Entrada</a>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- HISTÓRICO -->
                    <div class="hist-card">
                        <div class="hist-head">
                            <h3>Últimas movimentações</h3>
                        </div>
                        <?php foreach ($historico as $h): ?>
                        <div class="hist-item">
                            <div class="hist-icone <?= $h['tipo'] ?>">
                                <?php if ($h['tipo'] === 'entrada'): ?>
                                <svg viewBox="0 0 24 24"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                                <?php else: ?>
                                <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="hist-info">
                                <div class="hist-nome"><?= htmlspecialchars($h['produto']) ?></div>
                                <div class="hist-motivo"><?= htmlspecialchars($h['motivo']) ?></div>
                                <div class="hist-meta">
                                    <span class="hist-data"><?= $h['data'] ?> — <?= $h['user'] ?></span>
                                    <?php if ($h['qtd'] > 0): ?>
                                    <span class="hist-qtd <?= $h['tipo'] ?>"><?= $h['tipo'] === 'entrada' ? '+' : '-' ?><?= $h['qtd'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div>

            </div><!-- /painel-grid -->
        </div><!-- /conteudo -->
    </div><!-- /main -->
</div><!-- /admin-wrap -->

<!-- ================================
     MODAL MOVIMENTAÇÃO
================================ -->
<?php if ($abrir_modal && $modal_prod): ?>
<div class="modal-overlay" id="modalMov">
    <div class="modal">
        <div class="modal-head">
            <h2>Movimentação de estoque</h2>
            <button class="modal-close" onclick="fecharModal()">&#x2715;</button>
        </div>

        <div class="modal-body">

            <!-- Produto resumo -->
            <div class="modal-prod">
                <div class="modal-prod-img">
                    <img src="<?= htmlspecialchars($modal_prod['img']) ?>" alt="<?= htmlspecialchars($modal_prod['nome']) ?>">
                </div>
                <div>
                    <div class="modal-prod-nome"><?= htmlspecialchars($modal_prod['nome']) ?></div>
                    <div class="modal-prod-estq">Estoque atual: <strong><?= $modal_prod['estoque'] ?></strong> unidades</div>
                </div>
            </div>

            <!-- Tabs de tipo -->
            <div class="tipo-tabs">
                <button type="button" class="tipo-tab entrada <?= $modal_tipo === 'entrada' ? 'ativo' : '' ?>"
                    onclick="setTipo('entrada')">Entrada</button>
                <button type="button" class="tipo-tab saida <?= $modal_tipo === 'saida' ? 'ativo' : '' ?>"
                    onclick="setTipo('saida')">Saída</button>
                <button type="button" class="tipo-tab ajuste <?= $modal_tipo === 'ajuste' ? 'ativo' : '' ?>"
                    onclick="setTipo('ajuste')">Ajuste manual</button>
            </div>

            <!-- Form Entrada / Saída -->
            <form method="POST" action="estoque.php?status=<?= $filtro_status ?>&cat=<?= urlencode($filtro_cat) ?>" id="formMov">
                <input type="hidden" name="produto_id" value="<?= $modal_prod['id'] ?>">
                <input type="hidden" name="acao" id="tipoAcao" value="<?= $modal_tipo ?>">

                <!-- Painel entrada/saida -->
                <div id="painelQtd" <?= $modal_tipo === 'ajuste' ? 'style="display:none"' : '' ?>>
                    <div class="campo">
                        <label for="f-qtd">Quantidade</label>
                        <input type="number" id="f-qtd" name="qtd" min="1" placeholder="0" required>
                    </div>
                </div>

                <!-- Painel ajuste -->
                <div id="painelAjuste" <?= $modal_tipo !== 'ajuste' ? 'style="display:none"' : '' ?>>
                    <div class="campo-2">
                        <div class="campo">
                            <label for="f-estoque">Novo estoque</label>
                            <input type="number" id="f-estoque" name="estoque" min="0"
                                value="<?= $modal_prod['estoque'] ?>">
                        </div>
                        <div class="campo">
                            <label for="f-minimo">Estoque mínimo</label>
                            <input type="number" id="f-minimo" name="minimo" min="0"
                                value="<?= $modal_prod['minimo'] ?>">
                        </div>
                    </div>
                </div>

                <div class="campo">
                    <label for="f-motivo">Motivo / Observação</label>
                    <input type="text" id="f-motivo" name="motivo"
                        placeholder="Ex: Reposição de estoque, desperdício...">
                </div>

                <div class="modal-foot" style="padding:0;border:none;margin-top:4px">
                    <button type="button" class="btn btn-cinza" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn" id="btnSubmitMov"
                        style="background:<?= $modal_tipo === 'entrada' ? '#22c55e' : ($modal_tipo === 'saida' ? '#f59e0b' : 'var(--rosa)') ?>;color:#fff">
                        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        <?= $modal_tipo === 'entrada' ? 'Registrar entrada' : ($modal_tipo === 'saida' ? 'Registrar saída' : 'Salvar ajuste') ?>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
<?php endif; ?>

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

    // Fechar modal
    function fecharModal() {
        var url = new URL(window.location.href);
        url.searchParams.delete('mov');
        url.searchParams.delete('tipo');
        window.location.href = url.toString();
    }

    // Trocar tipo de movimentação
    function setTipo(tipo) {
        document.getElementById('tipoAcao').value = tipo;

        document.querySelectorAll('.tipo-tab').forEach(function(t) { t.classList.remove('ativo'); });
        document.querySelector('.tipo-tab.' + tipo).classList.add('ativo');

        var painelQtd    = document.getElementById('painelQtd');
        var painelAjuste = document.getElementById('painelAjuste');
        var btn          = document.getElementById('btnSubmitMov');

        if (tipo === 'ajuste') {
            painelQtd.style.display    = 'none';
            painelAjuste.style.display = '';
            btn.style.background       = 'var(--rosa)';
            btn.innerHTML              = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:#fff;fill:none;stroke-width:2"><polyline points="20 6 9 17 4 12"/></svg> Salvar ajuste';
        } else if (tipo === 'entrada') {
            painelQtd.style.display    = '';
            painelAjuste.style.display = 'none';
            btn.style.background       = '#22c55e';
            btn.innerHTML              = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:#fff;fill:none;stroke-width:2"><polyline points="20 6 9 17 4 12"/></svg> Registrar entrada';
        } else {
            painelQtd.style.display    = '';
            painelAjuste.style.display = 'none';
            btn.style.background       = '#f59e0b';
            btn.innerHTML              = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:#fff;fill:none;stroke-width:2"><polyline points="20 6 9 17 4 12"/></svg> Registrar saída';
        }
    }

    // ESC fecha modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') fecharModal();
    });
</script>

</body>
</html>