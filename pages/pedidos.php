<?php
// admin/pages/pedidos.php
require_once '../includes/auth.php';

// ============================================
// AÇÕES PHP
// ============================================
$mensagem = '';
$erro     = '';

// Dados simulados — substituir por banco depois
$pedidos = [
    ['id'=>1042,'cliente'=>'Ana Clara',   'tel'=>'(81) 99999-1111','itens'=>[['nome'=>'Açaí Premium 500ml','qtd'=>1,'preco'=>18.90],['nome'=>'Milkshake Oreo','qtd'=>1,'preco'=>16.90]],'total'=>35.80,'status'=>'entregue', 'pagto'=>'pix',     'entrega'=>'Rua das Flores, 123 — Centro','hora'=>'14:32','data'=>'21/03/2026','obs'=>''],
    ['id'=>1041,'cliente'=>'Rafael M.',   'tel'=>'(81) 98888-2222','itens'=>[['nome'=>'Combo Smash Duplo','qtd'=>1,'preco'=>42.90]],'total'=>42.90,'status'=>'preparo',  'pagto'=>'cartao',  'entrega'=>'Av. Boa Viagem, 500 — Boa Viagem','hora'=>'14:18','data'=>'21/03/2026','obs'=>'Ponto da carne bem passado'],
    ['id'=>1040,'cliente'=>'Juliana P.',  'tel'=>'(81) 97777-3333','itens'=>[['nome'=>'Bolo de Pote Ninho','qtd'=>2,'preco'=>14.90]],'total'=>29.80,'status'=>'entregue', 'pagto'=>'dinheiro','entrega'=>'Rua do Futuro, 88 — Madalena','hora'=>'13:55','data'=>'21/03/2026','obs'=>''],
    ['id'=>1039,'cliente'=>'Marcos T.',   'tel'=>'(81) 96666-4444','itens'=>[['nome'=>'Hambúrguer Smash','qtd'=>1,'preco'=>29.90],['nome'=>'Suco de Laranja','qtd'=>1,'preco'=>9.90]],'total'=>39.80,'status'=>'cancelado','pagto'=>'pix',     'entrega'=>'Rua Nova, 10 — Derby','hora'=>'13:40','data'=>'21/03/2026','obs'=>''],
    ['id'=>1038,'cliente'=>'Carla S.',    'tel'=>'(81) 95555-5555','itens'=>[['nome'=>'Açaí Tradicional 300ml','qtd'=>1,'preco'=>12.90]],'total'=>12.90,'status'=>'entregue', 'pagto'=>'dinheiro','entrega'=>'Retirada no local','hora'=>'13:22','data'=>'21/03/2026','obs'=>''],
    ['id'=>1037,'cliente'=>'Bruno L.',    'tel'=>'(81) 94444-6666','itens'=>[['nome'=>'Smash Bacon','qtd'=>1,'preco'=>34.90],['nome'=>'Refrigerante Lata','qtd'=>2,'preco'=>6.00]],'total'=>46.90,'status'=>'pendente',  'pagto'=>'cartao',  'entrega'=>'Av. Caxangá, 200 — Iputinga','hora'=>'13:10','data'=>'21/03/2026','obs'=>'Sem cebola'],
    ['id'=>1036,'cliente'=>'Fernanda K.', 'tel'=>'(81) 93333-7777','itens'=>[['nome'=>'Açaí com Morango 400ml','qtd'=>1,'preco'=>15.90],['nome'=>'Brigadeiro Gourmet','qtd'=>3,'preco'=>6.90]],'total'=>36.60,'status'=>'entregue', 'pagto'=>'pix',     'entrega'=>'Rua Harmonia, 45 — Torre','hora'=>'12:50','data'=>'21/03/2026','obs'=>''],
    ['id'=>1035,'cliente'=>'Rodrigo P.',  'tel'=>'(81) 92222-8888','itens'=>[['nome'=>'Combo Smash Duplo','qtd'=>2,'preco'=>42.90]],'total'=>85.80,'status'=>'entregue', 'pagto'=>'pix',     'entrega'=>'Av. Rosa e Silva, 300 — Aflitos','hora'=>'12:30','data'=>'21/03/2026','obs'=>'Urgente'],
];

// Atualizar status via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $pid    = (int)($_POST['pedido_id'] ?? 0);
    $acao   = $_POST['acao'];

    if ($acao === 'status' && isset($_POST['novo_status'])) {
        $novo = $_POST['novo_status'];
        $validos = ['pendente','preparo','entregue','cancelado'];
        if (in_array($novo, $validos)) {
            // TODO: UPDATE pedidos SET status = ? WHERE id = ?
            $mensagem = 'Status do pedido #' . $pid . ' atualizado para ' . ucfirst($novo) . '.';
            // Simular atualização no array
            foreach ($pedidos as &$p) {
                if ($p['id'] === $pid) { $p['status'] = $novo; break; }
            }
            unset($p);
        }
    }

    if ($acao === 'excluir') {
        // TODO: DELETE FROM pedidos WHERE id = ?
        $mensagem = 'Pedido #' . $pid . ' removido.';
        $pedidos = array_filter($pedidos, function($p) use ($pid) { return $p['id'] !== $pid; });
    }
}

// Filtros GET
$filtro_status = $_GET['status'] ?? 'todos';
$filtro_busca  = trim($_GET['busca'] ?? '');

$lista = array_filter($pedidos, function($p) use ($filtro_status, $filtro_busca) {
    $ok_s = ($filtro_status === 'todos' || $p['status'] === $filtro_status);
    $ok_b = (empty($filtro_busca) ||
             stripos($p['cliente'], $filtro_busca) !== false ||
             strpos((string)$p['id'], $filtro_busca) !== false);
    return $ok_s && $ok_b;
});

// Pedido selecionado para detalhe
$detalhe_id = (int)($_GET['ver'] ?? 0);
$detalhe    = null;
foreach ($pedidos as $p) {
    if ($p['id'] === $detalhe_id) { $detalhe = $p; break; }
}

$status_cfg = [
    'pendente'  => ['bg'=>'#eff6ff','cor'=>'#1d4ed8','label'=>'Pendente'],
    'preparo'   => ['bg'=>'#fef9c3','cor'=>'#854d0e','label'=>'Em preparo'],
    'entregue'  => ['bg'=>'#f0fdf4','cor'=>'#15803d','label'=>'Entregue'],
    'cancelado' => ['bg'=>'#fff0f4','cor'=>'#be185d','label'=>'Cancelado'],
];

$pagto_label = ['pix'=>'Pix','cartao'=>'Cartão','dinheiro'=>'Dinheiro'];

$total_geral = array_sum(array_column($pedidos, 'total'));
$qtd_entregue = count(array_filter($pedidos, fn($p) => $p['status'] === 'entregue'));
$qtd_preparo  = count(array_filter($pedidos, fn($p) => $p['status'] === 'preparo'));
$qtd_pendente = count(array_filter($pedidos, fn($p) => $p['status'] === 'pendente'));
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
            <a href="pedidos.php" class="nav-item ativo">
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

    <!-- MAIN -->
    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="topbar-esq">
                <button class="btn-menu" id="btnMenu">
                    <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div>
                    <div class="topbar-titulo">Pedidos</div>
                    <div class="topbar-breadcrumb">Gerencie e acompanhe os pedidos</div>
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

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-val"><?= count($pedidos) ?></div>
                    <div class="stat-lbl">Total de pedidos</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= $qtd_entregue ?></div>
                    <div class="stat-lbl">Entregues</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= $qtd_preparo + $qtd_pendente ?></div>
                    <div class="stat-lbl">Em aberto</div>
                </div>
                <div class="stat">
                    <div class="stat-val">R$ <?= number_format($total_geral, 0, ',', '.') ?></div>
                    <div class="stat-lbl">Faturamento</div>
                </div>
            </div>

            <!-- TOOLBAR -->
            <div class="toolbar">
                <div class="toolbar-esq">
                    <?php
                    $tabs_status = [
                        'todos'     => 'Todos (' . count($pedidos) . ')',
                        'pendente'  => 'Pendente (' . $qtd_pendente . ')',
                        'preparo'   => 'Em preparo (' . $qtd_preparo . ')',
                        'entregue'  => 'Entregue (' . $qtd_entregue . ')',
                        'cancelado' => 'Cancelado',
                    ];
                    foreach ($tabs_status as $slug => $label):
                    ?>
                    <a href="pedidos.php?status=<?= $slug ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>"
                       class="tab-status <?= $filtro_status === $slug ? 'ativo' : '' ?>">
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
                            <th>Pedido</th>
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
                        <tr>
                            <td colspan="7">
                                <div class="tabela-vazio">
                                    <svg width="36" height="36" viewBox="0 0 24 24" stroke-width="1.5"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>
                                    Nenhum pedido encontrado.
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($lista as $p):
                            $s = $status_cfg[$p['status']] ?? $status_cfg['pendente'];
                            $nomes_itens = implode(', ', array_map(fn($i) => $i['qtd'].'x '.$i['nome'], $p['itens']));
                        ?>
                        <tr>
                            <td>
                                <div class="pedido-id">#<?= $p['id'] ?></div>
                                <div class="pedido-hora"><?= $p['hora'] ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($p['cliente']) ?></div>
                                <div style="font-size:.75rem;color:var(--cinza)"><?= htmlspecialchars($p['tel']) ?></div>
                            </td>
                            <td style="color:var(--cinza);font-size:.82rem;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars($nomes_itens) ?>
                            </td>
                            <td style="font-size:.82rem;color:var(--cinza)">
                                <?= $pagto_label[$p['pagto']] ?? $p['pagto'] ?>
                            </td>
                            <td style="font-weight:700;font-size:.9rem;">
                                R$ <?= number_format($p['total'], 2, ',', '.') ?>
                            </td>
                            <td>
                                <form method="POST" action="pedidos.php?status=<?= $filtro_status ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>" style="display:inline">
                                    <input type="hidden" name="acao" value="status">
                                    <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                    <select name="novo_status" class="sel-status" onchange="this.form.submit()">
                                        <?php foreach ($status_cfg as $sv => $sc): ?>
                                        <option value="<?= $sv ?>" <?= $p['status'] === $sv ? 'selected' : '' ?>>
                                            <?= $sc['label'] ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div class="acoes">
                                    <!-- Ver detalhe -->
                                    <a href="pedidos.php?ver=<?= $p['id'] ?>&status=<?= $filtro_status ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>">
                                        <button class="btn-acao" title="Ver detalhes">
                                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </a>
                                    <!-- WhatsApp -->
                                    <a href="https://wa.me/<?= preg_replace('/\D/','',$p['tel']) ?>?text=<?= urlencode('Olá '.$p['cliente'].'! Atualização do seu pedido #'.$p['id'].'.') ?>" target="_blank" rel="noopener">
                                        <button class="btn-acao" title="Contato WhatsApp">
                                            <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                        </button>
                                    </a>
                                    <!-- Excluir -->
                                    <button class="btn-acao danger" title="Excluir pedido" onclick="confirmarExclusao(<?= $p['id'] ?>)">
                                        <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
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

<!-- ================================
     MODAL DETALHE DO PEDIDO
================================ -->
<?php if ($detalhe): $s = $status_cfg[$detalhe['status']]; ?>
<div class="modal-overlay" id="modalDetalhe">
    <div class="modal">
        <div class="modal-head">
            <h2>Pedido #<?= $detalhe['id'] ?></h2>
            <button class="modal-close" onclick="fecharModal()">&#x2715;</button>
        </div>
        <div class="modal-body">

            <!-- INFO CLIENTE -->
            <div class="detalhe-sec">
                <div class="detalhe-sec-titulo">Cliente</div>
                <div class="detalhe-linha">
                    <span>Nome</span>
                    <span><?= htmlspecialchars($detalhe['cliente']) ?></span>
                </div>
                <div class="detalhe-linha">
                    <span>WhatsApp</span>
                    <span><?= htmlspecialchars($detalhe['tel']) ?></span>
                </div>
                <div class="detalhe-linha">
                    <span>Data/hora</span>
                    <span><?= $detalhe['data'] ?> às <?= $detalhe['hora'] ?></span>
                </div>
            </div>

            <!-- ENTREGA -->
            <div class="detalhe-sec">
                <div class="detalhe-sec-titulo">Entrega</div>
                <div class="detalhe-linha">
                    <span>Endereço</span>
                    <span><?= htmlspecialchars($detalhe['entrega']) ?></span>
                </div>
                <div class="detalhe-linha">
                    <span>Pagamento</span>
                    <span><?= $pagto_label[$detalhe['pagto']] ?? $detalhe['pagto'] ?></span>
                </div>
                <?php if ($detalhe['obs']): ?>
                <div class="detalhe-linha">
                    <span>Obs</span>
                    <span><?= htmlspecialchars($detalhe['obs']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- ITENS -->
            <div class="detalhe-sec">
                <div class="detalhe-sec-titulo">Itens do pedido</div>
                <?php foreach ($detalhe['itens'] as $it): ?>
                <div class="detalhe-item">
                    <span class="nome"><?= $it['qtd'] ?>x <?= htmlspecialchars($it['nome']) ?></span>
                    <span class="sub">R$ <?= number_format($it['preco'] * $it['qtd'], 2, ',', '.') ?></span>
                </div>
                <?php endforeach; ?>
                <div class="detalhe-total">
                    <span>Total</span>
                    <span>R$ <?= number_format($detalhe['total'], 2, ',', '.') ?></span>
                </div>
            </div>

            <!-- MUDAR STATUS -->
            <div class="detalhe-sec">
                <div class="detalhe-sec-titulo">Atualizar status</div>
                <form method="POST" action="pedidos.php?status=<?= $filtro_status ?>" style="display:flex;gap:10px;align-items:center">
                    <input type="hidden" name="acao" value="status">
                    <input type="hidden" name="pedido_id" value="<?= $detalhe['id'] ?>">
                    <select name="novo_status" class="sel-status" style="flex:1">
                        <?php foreach ($status_cfg as $sv => $sc): ?>
                        <option value="<?= $sv ?>" <?= $detalhe['status'] === $sv ? 'selected' : '' ?>><?= $sc['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-rosa">Salvar</button>
                </form>
            </div>

        </div>
        <div class="modal-foot">
            <a href="https://wa.me/<?= preg_replace('/\D/','',$detalhe['tel']) ?>?text=<?= urlencode('Olá '.$detalhe['cliente'].'! Atualização do seu pedido #'.$detalhe['id'].'.') ?>" target="_blank" rel="noopener">
                <button class="btn btn-wpp">
                    <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    WhatsApp
                </button>
            </a>
            <button class="btn btn-danger" onclick="confirmarExclusao(<?= $detalhe['id'] ?>)">
                <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>
                Excluir pedido
            </button>
            <button class="btn btn-cinza" onclick="fecharModal()">Fechar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- CONFIRM EXCLUIR -->
<div class="confirm-overlay" id="confirmOverlay" style="display:none">
    <div class="confirm-box">
        <h3>Excluir pedido?</h3>
        <p>Esta ação não pode ser desfeita. O pedido será removido permanentemente.</p>
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

    // Fechar modal detalhe
    function fecharModal() {
        var url = new URL(window.location.href);
        url.searchParams.delete('ver');
        window.location.href = url.toString();
    }

    // Confirmar exclusão
    function confirmarExclusao(id) {
        document.getElementById('excluirId').value = id;
        document.getElementById('confirmOverlay').style.display = 'flex';
    }

    // Fechar modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharModal();
            document.getElementById('confirmOverlay').style.display = 'none';
        }
    });
</script>

</body>
</html>