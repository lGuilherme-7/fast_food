<?php
// admin/pages/clientes.php
require_once '../includes/auth.php';

$mensagem = '';
$erro     = '';

// ============================================
// DADOS SIMULADOS — substituir por banco depois
// ============================================
$clientes = [
    ['id'=>1, 'nome'=>'Ana Clara',    'email'=>'ana@email.com',     'tel'=>'(81) 99999-1111', 'pedidos'=>12, 'gasto'=>428.60, 'ultimo'=>'21/03/2026', 'cadastro'=>'10/01/2026', 'ativo'=>true],
    ['id'=>2, 'nome'=>'Rafael M.',    'email'=>'rafael@email.com',  'tel'=>'(81) 98888-2222', 'pedidos'=>8,  'gasto'=>314.20, 'ultimo'=>'21/03/2026', 'cadastro'=>'15/01/2026', 'ativo'=>true],
    ['id'=>3, 'nome'=>'Juliana P.',   'email'=>'juliana@email.com', 'tel'=>'(81) 97777-3333', 'pedidos'=>21, 'gasto'=>792.40, 'ultimo'=>'20/03/2026', 'cadastro'=>'03/01/2026', 'ativo'=>true],
    ['id'=>4, 'nome'=>'Marcos T.',    'email'=>'marcos@email.com',  'tel'=>'(81) 96666-4444', 'pedidos'=>3,  'gasto'=>119.40, 'ultimo'=>'18/03/2026', 'cadastro'=>'20/02/2026', 'ativo'=>true],
    ['id'=>5, 'nome'=>'Carla S.',     'email'=>'carla@email.com',   'tel'=>'(81) 95555-5555', 'pedidos'=>17, 'gasto'=>521.80, 'ultimo'=>'20/03/2026', 'cadastro'=>'08/01/2026', 'ativo'=>true],
    ['id'=>6, 'nome'=>'Bruno L.',     'email'=>'bruno@email.com',   'tel'=>'(81) 94444-6666', 'pedidos'=>5,  'gasto'=>187.50, 'ultimo'=>'19/03/2026', 'cadastro'=>'01/03/2026', 'ativo'=>true],
    ['id'=>7, 'nome'=>'Fernanda K.',  'email'=>'fernanda@email.com','tel'=>'(81) 93333-7777', 'pedidos'=>9,  'gasto'=>330.10, 'ultimo'=>'17/03/2026', 'cadastro'=>'12/01/2026', 'ativo'=>false],
    ['id'=>8, 'nome'=>'Rodrigo P.',   'email'=>'rodrigo@email.com', 'tel'=>'(81) 92222-8888', 'pedidos'=>31, 'gasto'=>1182.90,'ultimo'=>'21/03/2026', 'cadastro'=>'02/01/2026', 'ativo'=>true],
    ['id'=>9, 'nome'=>'Camila F.',    'email'=>'camila@email.com',  'tel'=>'(81) 91111-9999', 'pedidos'=>6,  'gasto'=>214.40, 'ultimo'=>'15/03/2026', 'cadastro'=>'18/02/2026', 'ativo'=>true],
    ['id'=>10,'nome'=>'Lucas M.',     'email'=>'lucas@email.com',   'tel'=>'(81) 90000-0000', 'pedidos'=>14, 'gasto'=>486.70, 'ultimo'=>'19/03/2026', 'cadastro'=>'05/01/2026', 'ativo'=>true],
];

// Ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $cid  = (int)($_POST['cliente_id'] ?? 0);

    if ($acao === 'bloquear') {
        foreach ($clientes as &$c) {
            if ($c['id'] === $cid) {
                $c['ativo'] = !$c['ativo'];
                $mensagem = 'Cliente ' . ($c['ativo'] ? 'desbloqueado' : 'bloqueado') . ' com sucesso.';
                break;
            }
        }
        unset($c);
    }

    if ($acao === 'excluir') {
        $clientes = array_values(array_filter($clientes, fn($c) => $c['id'] !== $cid));
        $mensagem = 'Cliente removido com sucesso.';
    }
}

// Filtros GET
$filtro_busca  = trim($_GET['busca'] ?? '');
$filtro_status = $_GET['status'] ?? 'todos';
$ordenar       = $_GET['ord'] ?? 'pedidos';

$lista = array_filter($clientes, function($c) use ($filtro_busca, $filtro_status) {
    $ok_b = empty($filtro_busca) ||
            stripos($c['nome'],  $filtro_busca) !== false ||
            stripos($c['email'], $filtro_busca) !== false ||
            stripos($c['tel'],   $filtro_busca) !== false;
    $ok_s = $filtro_status === 'todos' ||
            ($filtro_status === 'ativo'   &&  $c['ativo']) ||
            ($filtro_status === 'inativo' && !$c['ativo']);
    return $ok_b && $ok_s;
});

// Ordenação
usort($lista, function($a, $b) use ($ordenar) {
    if ($ordenar === 'gasto')    return $b['gasto']   <=> $a['gasto'];
    if ($ordenar === 'nome')     return strcmp($a['nome'], $b['nome']);
    if ($ordenar === 'recente')  return strtotime(str_replace('/','-',$b['ultimo'])) <=> strtotime(str_replace('/','-',$a['ultimo']));
    return $b['pedidos'] <=> $a['pedidos']; // padrão: mais pedidos
});

// Detalhe
$ver_id = (int)($_GET['ver'] ?? 0);
$detalhe = null;
foreach ($clientes as $c) { if ($c['id'] === $ver_id) { $detalhe = $c; break; } }

// Stats
$total_clientes = count($clientes);
$total_ativos   = count(array_filter($clientes, fn($c) => $c['ativo']));
$total_gasto    = array_sum(array_column($clientes, 'gasto'));
$ticket_medio   = $total_clientes > 0 ? $total_gasto / array_sum(array_column($clientes, 'pedidos')) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes — Sabor&Cia Admin</title>
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
            <a href="vendas.php"    class="nav-item"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Vendas</a>
            <div class="nav-label">Catálogo</div>
            <a href="produtos.php"  class="nav-item"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>Produtos</a>
            <a href="categorias.php"class="nav-item"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>Categorias</a>
            <a href="estoque.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>Estoque</a>
            <div class="nav-label">Clientes</div>
            <a href="clientes.php"  class="nav-item ativo"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Clientes</a>
            <a href="cupons.php"    class="nav-item"><svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>Cupons</a>
            <div class="nav-label">Sistema</div>
            <a href="configuracoes.php" class="nav-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M12 2v2M4.93 4.93l1.41 1.41M2 12h2M4.93 19.07l1.41-1.41M12 20v2M19.07 19.07l-1.41-1.41M22 12h-2"/></svg>Configurações</a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar">AD</div>
                <div class="user-info"><div class="user-nome">Administrador</div><div class="user-role">admin</div></div>
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
                    <div class="topbar-titulo">Clientes</div>
                    <div class="topbar-breadcrumb"><?= $total_clientes ?> clientes cadastrados</div>
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
            <div class="alerta alerta-ok"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                    <div class="stat-val"><?= $total_clientes ?></div>
                    <div class="stat-lbl">Total de clientes</div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                    <div class="stat-val"><?= $total_ativos ?></div>
                    <div class="stat-lbl">Clientes ativos</div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                    <div class="stat-val">R$ <?= number_format($total_gasto, 0, ',', '.') ?></div>
                    <div class="stat-lbl">Total faturado</div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                    <div class="stat-val">R$ <?= number_format($ticket_medio, 2, ',', '.') ?></div>
                    <div class="stat-lbl">Ticket médio</div>
                </div>
            </div>

            <!-- TOOLBAR -->
            <div class="toolbar">
                <div class="toolbar-esq">
                    <?php foreach (['todos'=>'Todos', 'ativo'=>'Ativos', 'inativo'=>'Bloqueados'] as $s => $l): ?>
                    <a href="clientes.php?status=<?= $s ?>&ord=<?= $ordenar ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>"
                       class="tab <?= $filtro_status === $s ? 'ativo' : '' ?>"><?= $l ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="toolbar-dir">
                    <select class="sel-ord" onchange="window.location='clientes.php?ord='+this.value+'&status=<?= $filtro_status ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>'">
                        <option value="pedidos" <?= $ordenar==='pedidos' ?'selected':'' ?>>Mais pedidos</option>
                        <option value="gasto"   <?= $ordenar==='gasto'   ?'selected':'' ?>>Maior gasto</option>
                        <option value="recente" <?= $ordenar==='recente' ?'selected':'' ?>>Mais recente</option>
                        <option value="nome"    <?= $ordenar==='nome'    ?'selected':'' ?>>Nome A–Z</option>
                    </select>
                    <form method="GET" action="clientes.php" class="busca-wrap">
                        <input type="hidden" name="status" value="<?= $filtro_status ?>">
                        <input type="hidden" name="ord"    value="<?= $ordenar ?>">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="busca" class="busca-input" placeholder="Buscar cliente..."
                            value="<?= htmlspecialchars($filtro_busca) ?>" autocomplete="off">
                    </form>
                </div>
            </div>

            <!-- TABELA -->
            <div class="card">
                <div class="tabela-scroll">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Contato</th>
                            <th>Cadastro</th>
                            <th>Pedidos</th>
                            <th>Total gasto</th>
                            <th>Último pedido</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista)): ?>
                        <tr><td colspan="8">
                            <div class="tabela-vazio">
                                <svg width="36" height="36" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                Nenhum cliente encontrado.
                            </div>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($lista as $c):
                            $inicial = mb_strtoupper(mb_substr($c['nome'], 0, 1));
                            $vip     = $c['pedidos'] >= 15;
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="cliente-avatar"><?= $inicial ?></div>
                                    <div>
                                        <div style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($c['nome']) ?></div>
                                        <?php if ($vip): ?>
                                        <div class="cliente-rank">
                                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            VIP
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:.85rem"><?= htmlspecialchars($c['email']) ?></div>
                                <div style="font-size:.75rem;color:var(--cinza)"><?= htmlspecialchars($c['tel']) ?></div>
                            </td>
                            <td style="font-size:.82rem;color:var(--cinza)"><?= $c['cadastro'] ?></td>
                            <td style="font-weight:700"><?= $c['pedidos'] ?></td>
                            <td style="font-family:var(--f-titulo);color:var(--rosa);font-weight:700">R$ <?= number_format($c['gasto'], 2, ',', '.') ?></td>
                            <td style="font-size:.82rem;color:var(--cinza)"><?= $c['ultimo'] ?></td>
                            <td>
                                <span class="badge <?= $c['ativo'] ? 'badge-ativo' : 'badge-inativo' ?>">
                                    <?= $c['ativo'] ? 'Ativo' : 'Bloqueado' ?>
                                </span>
                            </td>
                            <td>
                                <div class="acoes">
                                    <a href="clientes.php?ver=<?= $c['id'] ?>&status=<?= $filtro_status ?>&ord=<?= $ordenar ?>">
                                        <button class="btn-acao" title="Ver perfil">
                                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </a>
                                    <a href="https://wa.me/<?= preg_replace('/\D/','',$c['tel']) ?>" target="_blank" rel="noopener">
                                        <button class="btn-acao" title="WhatsApp">
                                            <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                        </button>
                                    </a>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="acao"       value="bloquear">
                                        <input type="hidden" name="cliente_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn-acao" title="<?= $c['ativo'] ? 'Bloquear' : 'Desbloquear' ?>">
                                            <svg viewBox="0 0 24 24"><?= $c['ativo'] ? '<circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>' : '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>' ?></svg>
                                        </button>
                                    </form>
                                    <button class="btn-acao danger" title="Excluir" onclick="confirmarExclusao(<?= $c['id'] ?>,'<?= addslashes(htmlspecialchars($c['nome'])) ?>')">
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
            </div>

        </div>
    </div>
</div>

<!-- MODAL DETALHE -->
<?php if ($detalhe): ?>
<div class="modal-overlay">
    <div class="modal">
        <div class="modal-head">
            <h2>Perfil do cliente</h2>
            <button class="modal-close" onclick="fecharModal()">&#x2715;</button>
        </div>
        <div class="modal-body">
            <div class="cliente-perfil">
                <div class="perfil-avatar"><?= mb_strtoupper(mb_substr($detalhe['nome'],0,1)) ?></div>
                <div>
                    <div class="perfil-nome"><?= htmlspecialchars($detalhe['nome']) ?></div>
                    <div class="perfil-sub"><?= htmlspecialchars($detalhe['email']) ?></div>
                </div>
            </div>
            <div class="detalhe-grid">
                <div class="detalhe-item">
                    <div class="di-lbl">Total pedidos</div>
                    <div class="di-val"><?= $detalhe['pedidos'] ?></div>
                </div>
                <div class="detalhe-item">
                    <div class="di-lbl">Total gasto</div>
                    <div class="di-val rosa">R$ <?= number_format($detalhe['gasto'], 2, ',', '.') ?></div>
                </div>
                <div class="detalhe-item">
                    <div class="di-lbl">Cadastro</div>
                    <div class="di-val"><?= $detalhe['cadastro'] ?></div>
                </div>
                <div class="detalhe-item">
                    <div class="di-lbl">Último pedido</div>
                    <div class="di-val"><?= $detalhe['ultimo'] ?></div>
                </div>
            </div>
            <div class="detalhe-sec">
                <div class="detalhe-sec-titulo">Contato</div>
                <div class="detalhe-linha"><span>WhatsApp</span><span><?= htmlspecialchars($detalhe['tel']) ?></span></div>
                <div class="detalhe-linha"><span>E-mail</span><span><?= htmlspecialchars($detalhe['email']) ?></span></div>
                <div class="detalhe-linha"><span>Status</span>
                    <span class="badge <?= $detalhe['ativo'] ? 'badge-ativo' : 'badge-inativo' ?>"><?= $detalhe['ativo'] ? 'Ativo' : 'Bloqueado' ?></span>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <a href="https://wa.me/<?= preg_replace('/\D/','',$detalhe['tel']) ?>" target="_blank" rel="noopener">
                <button class="btn btn-wpp"><svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>WhatsApp</button>
            </a>
            <a href="pedidos.php?busca=<?= urlencode($detalhe['nome']) ?>">
                <button class="btn btn-cinza">Ver pedidos</button>
            </a>
            <button class="btn btn-cinza" onclick="fecharModal()">Fechar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- CONFIRM EXCLUIR -->
<div class="confirm-overlay" id="confirmOverlay" style="display:none">
    <div class="confirm-box">
        <div class="confirm-icon"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg></div>
        <h3>Excluir cliente?</h3>
        <p id="confirmTxt">Esta ação não pode ser desfeita.</p>
        <div class="confirm-btns">
            <form method="POST" id="formExcluir">
                <input type="hidden" name="acao"       value="excluir">
                <input type="hidden" name="cliente_id" id="excluirId" value="">
                <button type="submit" class="btn btn-danger">Excluir</button>
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
    function confirmarExclusao(id, nome) {
        document.getElementById('excluirId').value = id;
        document.getElementById('confirmTxt').textContent = 'O cliente "' + nome + '" e todo seu histórico será removido permanentemente.';
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