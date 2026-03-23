<?php
// admin/pages/pedidos.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$mensagem = '';
$erro     = '';

// ── AÇÕES POST ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $pid  = (int)($_POST['pedido_id'] ?? 0);
    $acao = $_POST['acao'];

    if ($acao === 'status' && isset($_POST['novo_status'])) {
        $validos = ['pendente','preparo','entregue','cancelado'];
        $novo    = $_POST['novo_status'];
        if ($pid > 0 && in_array($novo, $validos, true)) {
            $pdo->prepare("UPDATE pedidos SET status=? WHERE id=?")->execute([$novo, $pid]);
            $mensagem = 'Status do pedido #'.$pid.' atualizado para '.ucfirst($novo).'.';
        }
    }

    if ($acao === 'excluir' && $pid > 0) {
        $pdo->prepare("DELETE FROM pedido_itens WHERE pedido_id=?")->execute([$pid]);
        $pdo->prepare("DELETE FROM pedidos WHERE id=?")->execute([$pid]);
        $mensagem = 'Pedido #'.$pid.' removido.';
    }
}

// ── FILTROS ───────────────────────────────────────────────────
$filtro_status = $_GET['status'] ?? 'todos';
$filtro_busca  = trim($_GET['busca'] ?? '');

// ── QUERY PRINCIPAL ───────────────────────────────────────────
$sql = "
    SELECT p.id, p.cliente_nome, p.cliente_tel, p.total, p.subtotal,
           p.taxa_entrega, p.desconto, p.status, p.pagamento,
           p.tipo_entrega, p.endereco, p.bairro, p.complemento,
           p.referencia, p.observacao, p.troco_para,
           DATE_FORMAT(p.criado_em,'%d/%m/%Y') AS data,
           TIME_FORMAT(p.criado_em,'%H:%i')    AS hora
    FROM pedidos p
    WHERE 1=1
";
$params = [];
if ($filtro_status !== 'todos') {
    $sql .= " AND p.status=?";
    $params[] = $filtro_status;
}
if ($filtro_busca !== '') {
    $sql .= " AND (p.cliente_nome LIKE ? OR CAST(p.id AS CHAR) LIKE ?)";
    $like = '%'.$filtro_busca.'%';
    $params[] = $like;
    $params[] = $like;
}
$sql .= " ORDER BY p.criado_em DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lista = $stmt->fetchAll();

// ── ITENS RESUMIDOS POR PEDIDO (na tabela) ────────────────────
$itens_map = [];
if (!empty($lista)) {
    $ids   = array_column($lista, 'id');
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt  = $pdo->prepare("
        SELECT pedido_id,
               GROUP_CONCAT(quantidade,'x ',produto_nome ORDER BY id SEPARATOR ', ') AS resumo
        FROM pedido_itens
        WHERE pedido_id IN ($marks)
        GROUP BY pedido_id
    ");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $r) {
        $itens_map[$r['pedido_id']] = $r['resumo'];
    }
}

// ── STATS ─────────────────────────────────────────────────────
$stats = $pdo->query("
    SELECT
        COUNT(*)                                                          AS total,
        SUM(status='entregue')                                           AS entregues,
        SUM(status IN ('preparo','pendente'))                             AS em_aberto,
        SUM(status='pendente')                                           AS pendentes,
        SUM(status='preparo')                                            AS preparo,
        COALESCE(SUM(CASE WHEN status!='cancelado' THEN total END), 0)   AS faturamento
    FROM pedidos
")->fetch();

// ── DETALHE DO PEDIDO (modal) ─────────────────────────────────
$detalhe_id = (int)($_GET['ver'] ?? 0);
$detalhe    = null;
if ($detalhe_id > 0) {
    $stmt = $pdo->prepare("
        SELECT p.*,
               DATE_FORMAT(p.criado_em,'%d/%m/%Y') AS data,
               TIME_FORMAT(p.criado_em,'%H:%i')    AS hora
        FROM pedidos p WHERE p.id=?
    ");
    $stmt->execute([$detalhe_id]);
    $detalhe = $stmt->fetch() ?: null;

    if ($detalhe) {
        // Busca itens com adicionais e obs
        $stmt = $pdo->prepare("
            SELECT pi.*
            FROM pedido_itens pi
            WHERE pi.pedido_id=?
            ORDER BY pi.id
        ");
        $stmt->execute([$detalhe_id]);
        $detalhe['itens'] = $stmt->fetchAll();
    }
}

// ── HELPERS ───────────────────────────────────────────────────
$status_cfg = [
    'pendente'  => ['label'=>'Pendente',   'cor'=>'#1d4ed8', 'bg'=>'#eff6ff'],
    'preparo'   => ['label'=>'Em preparo', 'cor'=>'#854d0e', 'bg'=>'#fef9c3'],
    'entregue'  => ['label'=>'Entregue',   'cor'=>'#15803d', 'bg'=>'#f0fdf4'],
    'cancelado' => ['label'=>'Cancelado',  'cor'=>'#be185d', 'bg'=>'#fff0f4'],
];
$pagto_label = ['pix'=>'Pix','cartao'=>'Cartão','dinheiro'=>'Dinheiro'];
$tipo_label  = ['entrega'=>'Entrega','retirada'=>'Retirada no local','local'=>'Comer no local'];
$admin_nome  = $_SESSION['admin_nome'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos — Sabor&Cia Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <style>
        /* ── TABS DE STATUS ─────────────────────── */
        .tab-status { padding:6px 14px; border-radius:50px; border:1px solid var(--borda); background:var(--branco); font-family:var(--f-corpo); font-size:.8rem; font-weight:600; color:var(--cinza); transition:all .2s; white-space:nowrap; text-decoration:none; display:inline-block; }
        .tab-status:hover { border-color:var(--rosa); color:var(--rosa); }
        .tab-status.ativo { background:var(--rosa); border-color:var(--rosa); color:#fff; }

        /* ── TABELA ─────────────────────────────── */
        .pedido-id   { font-weight:700; font-size:.9rem; }
        .pedido-hora { font-size:.74rem; color:var(--cinza); margin-top:2px; }
        .sel-status  { padding:6px 10px; border-radius:8px; border:1px solid var(--borda); background:var(--branco); font-family:var(--f-corpo); font-size:.8rem; color:var(--escuro); outline:none; cursor:pointer; }
        .sel-status:focus { border-color:var(--rosa); }

        /* ── MODAL DETALHE ──────────────────────── */
        .det-bloco { margin-bottom:22px; }
        .det-bloco:last-child { margin-bottom:0; }

        .det-titulo {
            font-size:.7rem; font-weight:700; letter-spacing:1.2px;
            text-transform:uppercase; color:var(--cinza);
            padding-bottom:8px; margin-bottom:10px;
            border-bottom:1px solid var(--borda);
        }

        .det-linha {
            display:flex; justify-content:space-between;
            align-items:flex-start; gap:16px;
            font-size:.84rem; padding:4px 0;
        }
        .det-linha-lbl { color:var(--cinza); flex-shrink:0; min-width:110px; }
        .det-linha-val { font-weight:500; text-align:right; word-break:break-word; }

        /* Itens */
        .det-item {
            padding:10px 0;
            border-bottom:1px solid var(--bg);
        }
        .det-item:last-child { border-bottom:none; }

        .det-item-topo {
            display:flex; justify-content:space-between;
            align-items:flex-start; gap:8px;
        }
        .det-item-nome {
            font-size:.88rem; font-weight:600; flex:1;
        }
        .det-item-preco {
            font-family:var(--f-titulo); font-size:.95rem;
            color:var(--rosa); font-weight:700; white-space:nowrap;
        }
        .det-item-adds {
            font-size:.78rem; color:var(--cinza);
            margin-top:4px; line-height:1.5;
        }
        .det-item-obs {
            font-size:.78rem; color:#854d0e;
            margin-top:3px; font-style:italic;
        }

        /* Totais */
        .det-subtotais { margin-top:12px; border-top:1px solid var(--borda); padding-top:10px; }
        .det-sub-linha {
            display:flex; justify-content:space-between;
            font-size:.83rem; color:var(--cinza); padding:3px 0;
        }
        .det-sub-linha.desconto { color:#16a34a; }
        .det-total {
            display:flex; justify-content:space-between;
            align-items:center; padding-top:10px; margin-top:6px;
            border-top:2px solid var(--borda);
            font-weight:700; font-size:.95rem;
        }
        .det-total span:last-child {
            font-family:var(--f-titulo); font-size:1.25rem; color:var(--rosa);
        }

        /* Troco destaque */
        .det-troco {
            display:inline-flex; align-items:center; gap:6px;
            background:#fef9c3; border:1px solid #fde68a;
            color:#854d0e; font-size:.82rem; font-weight:600;
            padding:5px 12px; border-radius:50px; margin-top:6px;
        }

        /* Botão WPP no modal */
        .btn-wpp {
            display:inline-flex; align-items:center; gap:7px;
            padding:9px 18px; border-radius:8px;
            background:#25D366; color:#fff; border:none;
            font-family:var(--f-corpo); font-size:.85rem; font-weight:600;
            cursor:pointer; transition:opacity .2s; text-decoration:none;
        }
        .btn-wpp:hover { opacity:.88; }
        .btn-wpp svg { width:15px; height:15px; stroke:#fff; fill:none; stroke-width:2; flex-shrink:0; }
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
            <a href="pedidos.php"    class="nav-item ativo"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="11" y2="16"/></svg>Pedidos</a>
            <a href="vendas.php"     class="nav-item"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Vendas</a>
            <div class="nav-label">Catálogo</div>
            <a href="produtos.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>Produtos</a>
            <a href="categorias.php" class="nav-item"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>Categorias</a>
            <a href="estoque.php"    class="nav-item"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>Estoque</a>
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
                    <div class="topbar-titulo">Pedidos</div>
                    <div class="topbar-breadcrumb"><?= (int)$stats['total'] ?> pedidos registrados</div>
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
            <div class="alerta alerta-ok">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <?= htmlspecialchars($mensagem) ?>
            </div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-val"><?= (int)$stats['total'] ?></div>
                    <div class="stat-lbl">Total de pedidos</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= (int)$stats['entregues'] ?></div>
                    <div class="stat-lbl">Entregues</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= (int)$stats['em_aberto'] ?></div>
                    <div class="stat-lbl">Em aberto</div>
                </div>
                <div class="stat">
                    <div class="stat-val">R$ <?= number_format($stats['faturamento'],0,',','.') ?></div>
                    <div class="stat-lbl">Faturamento total</div>
                </div>
            </div>

            <!-- TOOLBAR: TABS + BUSCA -->
            <div class="toolbar">
                <div class="toolbar-esq" style="flex-wrap:wrap;gap:6px">
                    <?php
                    $tabs = [
                        'todos'     => 'Todos ('       . (int)$stats['total']     . ')',
                        'pendente'  => 'Pendente ('    . (int)$stats['pendentes'] . ')',
                        'preparo'   => 'Em preparo ('  . (int)$stats['preparo']   . ')',
                        'entregue'  => 'Entregue ('    . (int)$stats['entregues'] . ')',
                        'cancelado' => 'Cancelado',
                    ];
                    foreach ($tabs as $slug => $label):
                    ?>
                    <a href="pedidos.php?status=<?= $slug ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>"
                       class="tab-status <?= $filtro_status===$slug?'ativo':'' ?>">
                        <?= $label ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <form method="GET" action="pedidos.php" class="busca-wrap">
                    <?php if ($filtro_status !== 'todos'): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($filtro_status) ?>">
                    <?php endif; ?>
                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="busca" class="busca-input"
                        placeholder="Buscar cliente ou #id..."
                        value="<?= htmlspecialchars($filtro_busca) ?>"
                        autocomplete="off">
                </form>
            </div>

            <!-- TABELA -->
            <div class="card">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Itens</th>
                            <th>Pagamento</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista)): ?>
                        <tr><td colspan="7">
                            <div class="tabela-vazio">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--borda)" stroke-width="1.5"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>
                                Nenhum pedido encontrado.
                            </div>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($lista as $p):
                            $resumo = $itens_map[$p['id']] ?? '—';
                        ?>
                        <tr>
                            <td>
                                <div class="pedido-id">#<?= $p['id'] ?></div>
                                <div class="pedido-hora"><?= $p['data'] ?> <?= $p['hora'] ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($p['cliente_nome']) ?></div>
                                <div style="font-size:.75rem;color:var(--cinza)"><?= htmlspecialchars($p['cliente_tel']??'') ?></div>
                            </td>
                            <td style="font-size:.82rem;color:var(--cinza);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                <?= htmlspecialchars($resumo) ?>
                            </td>
                            <td style="font-size:.82rem;color:var(--cinza)">
                                <?= $pagto_label[$p['pagamento']] ?? htmlspecialchars($p['pagamento']) ?>
                                <?php if ($p['pagamento']==='dinheiro' && !empty($p['troco_para'])): ?>
                                <div style="font-size:.72rem;color:#854d0e;margin-top:2px">Troco p/ R$ <?= number_format($p['troco_para'],2,',','.') ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:700;font-size:.9rem">
                                R$ <?= number_format($p['total'],2,',','.') ?>
                            </td>
                            <td>
                                <form method="POST" action="pedidos.php?status=<?= $filtro_status ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>" style="display:inline">
                                    <input type="hidden" name="acao" value="status">
                                    <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                    <select name="novo_status" class="sel-status" onchange="this.form.submit()">
                                        <?php foreach ($status_cfg as $sv => $sc): ?>
                                        <option value="<?= $sv ?>" <?= $p['status']===$sv?'selected':'' ?>><?= $sc['label'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div class="acoes">
                                    <a href="pedidos.php?ver=<?= $p['id'] ?>&status=<?= $filtro_status ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>">
                                        <button class="btn-acao" title="Ver detalhes completos">
                                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </a>
                                    <?php if (!empty($p['cliente_tel'])): ?>
                                    <a href="https://wa.me/<?= preg_replace('/\D/','',$p['cliente_tel']) ?>?text=<?= urlencode('Olá '.$p['cliente_nome'].'! Atualização do seu pedido #'.$p['id'].'.') ?>" target="_blank" rel="noopener">
                                        <button class="btn-acao" title="Enviar WhatsApp">
                                            <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                        </button>
                                    </a>
                                    <?php endif; ?>
                                    <button class="btn-acao danger" title="Excluir pedido" onclick="confirmarExclusao(<?= $p['id'] ?>)">
                                        <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div><!-- /conteudo -->
    </div><!-- /main -->
</div><!-- /admin-wrap -->

<!-- ══════════════════════════════════════════
     MODAL DETALHE DO PEDIDO
══════════════════════════════════════════ -->
<?php if ($detalhe): ?>
<div class="modal-overlay" id="modalDetalhe">
    <div class="modal" style="max-width:520px;max-height:90vh;overflow-y:auto">
        <div class="modal-head">
            <div>
                <h2 style="margin-bottom:2px">Pedido #<?= $detalhe['id'] ?></h2>
                <div style="font-size:.78rem;color:var(--cinza)"><?= $detalhe['data'] ?> às <?= $detalhe['hora'] ?></div>
            </div>
            <button class="modal-close" onclick="fecharModal()">&#x2715;</button>
        </div>
        <div class="modal-body">

            <!-- BLOCO: CLIENTE -->
            <div class="det-bloco">
                <div class="det-titulo">Cliente</div>
                <div class="det-linha">
                    <span class="det-linha-lbl">Nome</span>
                    <span class="det-linha-val"><?= htmlspecialchars($detalhe['cliente_nome']) ?></span>
                </div>
                <?php if (!empty($detalhe['cliente_tel'])): ?>
                <div class="det-linha">
                    <span class="det-linha-lbl">WhatsApp</span>
                    <span class="det-linha-val"><?= htmlspecialchars($detalhe['cliente_tel']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- BLOCO: ENTREGA -->
            <div class="det-bloco">
                <div class="det-titulo">Entrega / Retirada</div>
                <div class="det-linha">
                    <span class="det-linha-lbl">Tipo</span>
                    <span class="det-linha-val"><?= $tipo_label[$detalhe['tipo_entrega']] ?? htmlspecialchars($detalhe['tipo_entrega']) ?></span>
                </div>
                <?php if (!empty($detalhe['endereco'])): ?>
                <div class="det-linha">
                    <span class="det-linha-lbl">Endereço</span>
                    <span class="det-linha-val">
                        <?= htmlspecialchars($detalhe['endereco']) ?>
                        <?= !empty($detalhe['complemento']) ? ', '.htmlspecialchars($detalhe['complemento']) : '' ?>
                        <?= !empty($detalhe['bairro']) ? ' — '.htmlspecialchars($detalhe['bairro']) : '' ?>
                    </span>
                </div>
                <?php endif; ?>
                <?php if (!empty($detalhe['referencia'])): ?>
                <div class="det-linha">
                    <span class="det-linha-lbl">Referência</span>
                    <span class="det-linha-val"><?= htmlspecialchars($detalhe['referencia']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- BLOCO: PAGAMENTO -->
            <div class="det-bloco">
                <div class="det-titulo">Pagamento</div>
                <div class="det-linha">
                    <span class="det-linha-lbl">Forma</span>
                    <span class="det-linha-val"><?= $pagto_label[$detalhe['pagamento']] ?? htmlspecialchars($detalhe['pagamento']) ?></span>
                </div>
                <?php if ($detalhe['pagamento'] === 'dinheiro' && !empty($detalhe['troco_para'])): ?>
                <div class="det-linha">
                    <span class="det-linha-lbl">Troco para</span>
                    <span class="det-linha-val">
                        <span class="det-troco">
                            💵 R$ <?= number_format($detalhe['troco_para'],2,',','.') ?>
                            — troco de R$ <?= number_format(max(0, (float)$detalhe['troco_para'] - (float)$detalhe['total']),2,',','.') ?>
                        </span>
                    </span>
                </div>
                <?php endif; ?>
                <?php if (!empty($detalhe['observacao'])): ?>
                <div class="det-linha">
                    <span class="det-linha-lbl">Observação</span>
                    <span class="det-linha-val"><?= htmlspecialchars($detalhe['observacao']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- BLOCO: ITENS -->
            <div class="det-bloco">
                <div class="det-titulo">Itens do pedido</div>

                <?php if (empty($detalhe['itens'])): ?>
                <p style="font-size:.84rem;color:var(--cinza)">Nenhum item registrado.</p>
                <?php else: ?>
                <?php foreach ($detalhe['itens'] as $it): ?>
                <div class="det-item">
                    <div class="det-item-topo">
                        <span class="det-item-nome">
                            <?= (int)$it['quantidade'] ?>x <?= htmlspecialchars($it['produto_nome']) ?>
                        </span>
                        <span class="det-item-preco">
                            R$ <?= number_format((float)$it['subtotal'],2,',','.') ?>
                        </span>
                    </div>
                    <?php
                    // Adicionais — coluna pode não existir em instâncias antigas
                    $adds = $it['adicionais'] ?? '';
                    $obs  = $it['obs']        ?? '';
                    ?>
                    <?php if (!empty($adds)): ?>
                    <div class="det-item-adds">↳ <?= htmlspecialchars($adds) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($obs)): ?>
                    <div class="det-item-obs">📝 <?= htmlspecialchars($obs) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- Subtotais -->
                <div class="det-subtotais">
                    <div class="det-sub-linha">
                        <span>Subtotal</span>
                        <span>R$ <?= number_format((float)$detalhe['subtotal'],2,',','.') ?></span>
                    </div>
                    <?php if ((float)$detalhe['desconto'] > 0): ?>
                    <div class="det-sub-linha desconto">
                        <span>Desconto</span>
                        <span>− R$ <?= number_format((float)$detalhe['desconto'],2,',','.') ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ((float)$detalhe['taxa_entrega'] > 0): ?>
                    <div class="det-sub-linha">
                        <span>Taxa de entrega</span>
                        <span>R$ <?= number_format((float)$detalhe['taxa_entrega'],2,',','.') ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="det-total">
                    <span>Total</span>
                    <span>R$ <?= number_format((float)$detalhe['total'],2,',','.') ?></span>
                </div>
            </div>

            <!-- BLOCO: ATUALIZAR STATUS -->
            <div class="det-bloco">
                <div class="det-titulo">Atualizar status</div>
                <form method="POST" action="pedidos.php?status=<?= $filtro_status ?>" style="display:flex;gap:10px;align-items:center">
                    <input type="hidden" name="acao" value="status">
                    <input type="hidden" name="pedido_id" value="<?= $detalhe['id'] ?>">
                    <select name="novo_status" class="sel-status" style="flex:1">
                        <?php foreach ($status_cfg as $sv => $sc): ?>
                        <option value="<?= $sv ?>" <?= $detalhe['status']===$sv?'selected':'' ?>><?= $sc['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-rosa">Salvar</button>
                </form>
            </div>

        </div><!-- /modal-body -->

        <div class="modal-foot">
            <?php if (!empty($detalhe['cliente_tel'])): ?>
            <a href="https://wa.me/<?= preg_replace('/\D/','',$detalhe['cliente_tel']) ?>?text=<?= urlencode('Olá '.$detalhe['cliente_nome'].'! Seu pedido #'.$detalhe['id'].' foi atualizado.') ?>"
               target="_blank" rel="noopener" class="btn-wpp">
                <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                WhatsApp
            </a>
            <?php endif; ?>
            <button class="btn btn-danger" onclick="confirmarExclusao(<?= $detalhe['id'] ?>)">
                <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>
                Excluir
            </button>
            <button class="btn btn-cinza" onclick="fecharModal()">Fechar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- CONFIRM EXCLUIR -->
<div class="confirm-overlay" id="confirmOverlay" style="display:none">
    <div class="confirm-box">
        <div class="confirm-icon">
            <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>
        </div>
        <h3>Excluir pedido?</h3>
        <p>Esta ação não pode ser desfeita.</p>
        <div class="confirm-btns">
            <form method="POST" action="pedidos.php?status=<?= $filtro_status ?>" id="formExcluir">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="pedido_id" id="excluirId" value="">
                <button type="submit" class="btn btn-danger">Sim, excluir</button>
            </form>
            <button class="btn btn-cinza" onclick="document.getElementById('confirmOverlay').style.display='none'">Cancelar</button>
        </div>
    </div>
</div>

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
function fecharModal() {
    var url = new URL(window.location.href);
    url.searchParams.delete('ver');
    window.location.href = url.toString();
}
function confirmarExclusao(id) {
    document.getElementById('excluirId').value = id;
    document.getElementById('confirmOverlay').style.display = 'flex';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharModal();
        document.getElementById('confirmOverlay').style.display = 'none';
    }
});
</script>
</body>
</html>