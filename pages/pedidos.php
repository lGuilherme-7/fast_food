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
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --rosa:       #f43f7a;
            --rosa-claro: #fce7f0;
            --rosa-borda: #f0e8ed;
            --escuro:     #1a1014;
            --cinza:      #9ca3af;
            --branco:     #ffffff;
            --bg:         #fafafa;
            --borda:      #e5e7eb;
            --sidebar-w:  240px;
            --r:          12px;
            --f-titulo:   Georgia, 'Times New Roman', serif;
            --f-corpo:    'DM Sans', system-ui, sans-serif;
        }

        html, body { height: 100%; font-family: var(--f-corpo); color: var(--escuro); background: var(--bg); }
        a { text-decoration: none; color: inherit; }

        /* LAYOUT */
        .admin-wrap { display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-w); background: var(--escuro);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; height: 100vh;
            z-index: 200; transition: transform .3s;
        }
        .sidebar-logo { padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,.07); }
        .sidebar-logo a { font-family: var(--f-titulo); font-size: 1.25rem; font-weight: 700; color: #fff; }
        .sidebar-logo a span { color: var(--rosa); }
        .sidebar-logo p { font-size: .72rem; color: rgba(255,255,255,.35); margin-top: 2px; letter-spacing: .5px; text-transform: uppercase; }
        .sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }
        .sidebar-nav::-webkit-scrollbar { display: none; }
        .nav-label { font-size: .68rem; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,.25); padding: 12px 8px 6px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; font-size: .85rem; font-weight: 500; color: rgba(255,255,255,.55); transition: background .2s, color .2s; margin-bottom: 2px; }
        .nav-item:hover { background: rgba(255,255,255,.07); color: #fff; }
        .nav-item.ativo { background: var(--rosa); color: #fff; }
        .nav-item svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; flex-shrink: 0; }
        .sidebar-footer { padding: 16px 12px; border-top: 1px solid rgba(255,255,255,.07); }
        .sidebar-user { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; margin-bottom: 6px; }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--rosa); display: flex; align-items: center; justify-content: center; font-size: .78rem; font-weight: 700; color: #fff; flex-shrink: 0; }
        .user-info { overflow: hidden; }
        .user-nome { font-size: .82rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: .7rem; color: rgba(255,255,255,.35); }
        .btn-logout { display: flex; align-items: center; gap: 8px; width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,.08); background: none; color: rgba(255,255,255,.45); font-family: var(--f-corpo); font-size: .82rem; cursor: pointer; transition: background .2s, color .2s; }
        .btn-logout:hover { background: rgba(244,63,122,.15); color: var(--rosa); border-color: rgba(244,63,122,.3); }
        .btn-logout svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; }

        /* MAIN */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* TOPBAR */
        .topbar { background: var(--branco); border-bottom: 1px solid var(--borda); padding: 0 28px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .topbar-esq { display: flex; align-items: center; gap: 14px; }
        .topbar-titulo { font-family: var(--f-titulo); font-size: 1.1rem; font-weight: 700; color: var(--escuro); }
        .topbar-breadcrumb { font-size: .78rem; color: var(--cinza); }
        .topbar-dir { display: flex; align-items: center; gap: 12px; }
        .btn-menu { display: none; background: none; border: none; cursor: pointer; padding: 4px; }
        .btn-menu svg { width: 20px; height: 20px; stroke: var(--escuro); fill: none; stroke-width: 2; }

        /* CONTEÚDO */
        .conteudo { padding: 28px; flex: 1; }

        /* ALERTAS */
        .alerta {
            padding: 12px 16px; border-radius: var(--r);
            font-size: .85rem; font-weight: 500;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        .alerta-ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .alerta-err { background: #fff0f4; border: 1px solid var(--rosa-borda); color: #be185d; }
        .alerta svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; flex-shrink: 0; }

        /* STATS */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat { background: var(--branco); border: 1px solid var(--borda); border-radius: var(--r); padding: 18px 20px; position: relative; overflow: hidden; }
        .stat::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--rosa); border-radius: var(--r) var(--r) 0 0; }
        .stat-val { font-family: var(--f-titulo); font-size: 1.6rem; font-weight: 700; color: var(--escuro); line-height: 1; margin-bottom: 4px; }
        .stat-lbl { font-size: .76rem; color: var(--cinza); }

        /* TOOLBAR */
        .toolbar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
        }
        .toolbar-esq { display: flex; gap: 8px; flex-wrap: wrap; }

        .tab-status {
            padding: 7px 16px; border-radius: 50px;
            border: 1px solid var(--borda); background: var(--branco);
            font-family: var(--f-corpo); font-size: .82rem; font-weight: 600;
            color: var(--cinza); text-decoration: none;
            transition: all .2s;
        }
        .tab-status:hover { border-color: var(--rosa); color: var(--rosa); }
        .tab-status.ativo { background: var(--rosa); border-color: var(--rosa); color: #fff; }

        .busca-wrap { position: relative; }
        .busca-wrap svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; stroke: var(--cinza); fill: none; stroke-width: 2; pointer-events: none; }
        .busca-input { padding: 8px 14px 8px 34px; border-radius: 8px; border: 1px solid var(--borda); background: var(--branco); font-family: var(--f-corpo); font-size: .85rem; color: var(--escuro); outline: none; width: 220px; transition: border-color .2s, box-shadow .2s; }
        .busca-input:focus { border-color: var(--rosa); box-shadow: 0 0 0 3px rgba(244,63,122,.1); }
        .busca-input::placeholder { color: var(--cinza); }

        /* CARD / TABELA */
        .card { background: var(--branco); border: 1px solid var(--borda); border-radius: var(--r); overflow: hidden; }

        .tabela { width: 100%; border-collapse: collapse; }
        .tabela th { font-size: .72rem; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--cinza); padding: 11px 20px; text-align: left; background: var(--bg); border-bottom: 1px solid var(--borda); white-space: nowrap; }
        .tabela td { padding: 13px 20px; font-size: .85rem; color: var(--escuro); border-bottom: 1px solid var(--borda); vertical-align: middle; }
        .tabela tr:last-child td { border-bottom: none; }
        .tabela tr:hover td { background: var(--bg); }

        .pedido-id  { font-weight: 700; color: var(--rosa); font-size: .88rem; }
        .pedido-hora{ font-size: .72rem; color: var(--cinza); margin-top: 1px; }

        .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 50px; font-size: .72rem; font-weight: 600; white-space: nowrap; }

        /* AÇÕES */
        .acoes { display: flex; align-items: center; gap: 6px; }
        .btn-acao {
            width: 30px; height: 30px; border-radius: 7px;
            border: 1px solid var(--borda); background: var(--branco);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all .2s;
        }
        .btn-acao svg { width: 13px; height: 13px; stroke: var(--cinza); fill: none; stroke-width: 2; }
        .btn-acao:hover { border-color: var(--rosa); background: var(--rosa-claro); }
        .btn-acao:hover svg { stroke: var(--rosa); }
        .btn-acao.danger:hover { border-color: #fca5a5; background: #fff5f5; }
        .btn-acao.danger:hover svg { stroke: #dc2626; }

        /* SELECT STATUS INLINE */
        .sel-status {
            padding: 5px 10px; border-radius: 7px;
            border: 1px solid var(--borda); background: var(--branco);
            font-family: var(--f-corpo); font-size: .78rem; color: var(--escuro);
            cursor: pointer; outline: none;
            transition: border-color .2s;
        }
        .sel-status:focus { border-color: var(--rosa); }

        /* VAZIO */
        .tabela-vazio { text-align: center; padding: 56px 0; color: var(--cinza); }
        .tabela-vazio svg { display: block; margin: 0 auto 12px; stroke: #e5e7eb; }

        /* ================================
           MODAL DETALHE
        ================================ */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 500;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }
        .modal {
            background: var(--branco);
            border-radius: var(--r);
            width: 100%; max-width: 540px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,.2);
        }
        .modal-head {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--borda);
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; background: var(--branco); z-index: 1;
        }
        .modal-head h2 { font-family: var(--f-titulo); font-size: 1.1rem; color: var(--escuro); }
        .modal-close { background: none; border: none; cursor: pointer; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--cinza); transition: background .2s; font-size: 1rem; }
        .modal-close:hover { background: var(--rosa-claro); color: var(--rosa); }

        .modal-body { padding: 20px 24px; }

        .detalhe-sec { margin-bottom: 20px; }
        .detalhe-sec-titulo { font-size: .72rem; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--cinza); margin-bottom: 10px; }

        .detalhe-linha { display: flex; justify-content: space-between; align-items: flex-start; font-size: .85rem; padding: 6px 0; border-bottom: 1px solid var(--bg); gap: 12px; }
        .detalhe-linha:last-child { border-bottom: none; }
        .detalhe-linha span:first-child { color: var(--cinza); flex-shrink: 0; }
        .detalhe-linha span:last-child  { font-weight: 500; text-align: right; }

        .detalhe-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--bg); font-size: .85rem; }
        .detalhe-item:last-child { border-bottom: none; }
        .detalhe-item .nome { font-weight: 500; }
        .detalhe-item .sub  { color: var(--rosa); font-weight: 600; font-family: var(--f-titulo); }

        .detalhe-total { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; margin-top: 4px; border-top: 2px solid var(--borda); font-weight: 700; }
        .detalhe-total span:last-child { font-family: var(--f-titulo); font-size: 1.2rem; color: var(--rosa); }

        .modal-foot { padding: 16px 24px; border-top: 1px solid var(--borda); display: flex; gap: 10px; justify-content: flex-end; }

        .btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border-radius: 8px; font-family: var(--f-corpo); font-size: .85rem; font-weight: 600; border: none; cursor: pointer; transition: opacity .2s; }
        .btn:hover { opacity: .88; }
        .btn-rosa   { background: var(--rosa); color: #fff; }
        .btn-cinza  { background: var(--bg); border: 1px solid var(--borda); color: var(--cinza); }
        .btn-wpp    { background: #25D366; color: #fff; }
        .btn-danger { background: #fff5f5; border: 1px solid #fca5a5; color: #dc2626; }
        .btn svg    { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; }

        /* CONFIRM MODAL */
        .confirm-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 600; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .confirm-box { background: var(--branco); border-radius: var(--r); padding: 28px; max-width: 380px; width: 100%; text-align: center; }
        .confirm-box h3 { font-family: var(--f-titulo); font-size: 1.1rem; margin-bottom: 8px; }
        .confirm-box p  { font-size: .85rem; color: var(--cinza); margin-bottom: 20px; }
        .confirm-btns   { display: flex; gap: 10px; justify-content: center; }

        /* RESPONSIVO */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.aberta { transform: translateX(0); }
            .main { margin-left: 0; }
            .btn-menu { display: block; }
            .conteudo { padding: 20px 16px; }
            .topbar { padding: 0 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .tabela th:nth-child(3),
            .tabela td:nth-child(3),
            .tabela th:nth-child(4),
            .tabela td:nth-child(4) { display: none; }
            .busca-input { width: 160px; }
        }
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