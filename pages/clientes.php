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
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --rosa:#f43f7a; --rosa-claro:#fce7f0; --rosa-borda:#f0e8ed;
            --escuro:#1a1014; --cinza:#9ca3af; --branco:#ffffff;
            --bg:#fafafa; --borda:#e5e7eb; --sidebar-w:240px; --r:12px;
            --f-titulo:Georgia,'Times New Roman',serif; --f-corpo:'DM Sans',system-ui,sans-serif;
        }
        html, body { height:100%; font-family:var(--f-corpo); color:var(--escuro); background:var(--bg); overflow-x:hidden; }
        a { text-decoration:none; color:inherit; }
        .admin-wrap { display:flex; min-height:100vh; }

        /* SIDEBAR */
        .sidebar { width:var(--sidebar-w); background:var(--escuro); display:flex; flex-direction:column; position:fixed; top:0; left:0; height:100vh; z-index:200; transition:transform .3s; }
        .sidebar-logo { padding:24px 20px 20px; border-bottom:1px solid rgba(255,255,255,.07); }
        .sidebar-logo a { font-family:var(--f-titulo); font-size:1.25rem; font-weight:700; color:#fff; }
        .sidebar-logo a span { color:var(--rosa); }
        .sidebar-logo p { font-size:.72rem; color:rgba(255,255,255,.35); margin-top:2px; letter-spacing:.5px; text-transform:uppercase; }
        .sidebar-nav { flex:1; padding:16px 12px; overflow-y:auto; }
        .sidebar-nav::-webkit-scrollbar { display:none; }
        .nav-label { font-size:.68rem; font-weight:600; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,.25); padding:12px 8px 6px; }
        .nav-item { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; font-size:.85rem; font-weight:500; color:rgba(255,255,255,.55); transition:background .2s,color .2s; margin-bottom:2px; }
        .nav-item:hover { background:rgba(255,255,255,.07); color:#fff; }
        .nav-item.ativo { background:var(--rosa); color:#fff; }
        .nav-item svg { width:16px; height:16px; stroke:currentColor; fill:none; stroke-width:2; flex-shrink:0; }
        .sidebar-footer { padding:16px 12px; border-top:1px solid rgba(255,255,255,.07); }
        .sidebar-user { display:flex; align-items:center; gap:10px; padding:10px 12px; margin-bottom:6px; }
        .user-avatar { width:32px; height:32px; border-radius:50%; background:var(--rosa); display:flex; align-items:center; justify-content:center; font-size:.78rem; font-weight:700; color:#fff; flex-shrink:0; }
        .user-info { overflow:hidden; }
        .user-nome { font-size:.82rem; font-weight:600; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .user-role { font-size:.7rem; color:rgba(255,255,255,.35); }
        .btn-logout { display:flex; align-items:center; gap:8px; width:100%; padding:9px 12px; border-radius:8px; border:1px solid rgba(255,255,255,.08); background:none; color:rgba(255,255,255,.45); font-family:var(--f-corpo); font-size:.82rem; cursor:pointer; transition:background .2s,color .2s; }
        .btn-logout:hover { background:rgba(244,63,122,.15); color:var(--rosa); border-color:rgba(244,63,122,.3); }
        .btn-logout svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; }

        /* MAIN */
        .main { margin-left:var(--sidebar-w); flex:1; min-width:0; display:flex; flex-direction:column; min-height:100vh; }
        .topbar { background:var(--branco); border-bottom:1px solid var(--borda); padding:0 28px; height:60px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; }
        .topbar-esq { display:flex; align-items:center; gap:14px; }
        .topbar-titulo { font-family:var(--f-titulo); font-size:1.1rem; font-weight:700; }
        .topbar-breadcrumb { font-size:.78rem; color:var(--cinza); }
        .topbar-dir { display:flex; align-items:center; gap:10px; }
        .btn-menu { display:none; background:none; border:none; cursor:pointer; padding:4px; }
        .btn-menu svg { width:20px; height:20px; stroke:var(--escuro); fill:none; stroke-width:2; }
        .conteudo { padding:28px; flex:1; min-width:0; }

        /* ALERTAS */
        .alerta { padding:12px 16px; border-radius:var(--r); font-size:.85rem; font-weight:500; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
        .alerta-ok  { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }
        .alerta-err { background:#fff0f4; border:1px solid var(--rosa-borda); color:#be185d; }
        .alerta svg { width:15px; height:15px; stroke:currentColor; fill:none; stroke-width:2; flex-shrink:0; }

        /* STATS */
        .stats-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; margin-bottom:24px; }
        .stat { background:var(--branco); border:1px solid var(--borda); border-radius:var(--r); padding:18px 20px; position:relative; overflow:hidden; }
        .stat::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:var(--rosa); border-radius:var(--r) var(--r) 0 0; }
        .stat-icone { width:36px; height:36px; border-radius:9px; background:var(--rosa-claro); display:flex; align-items:center; justify-content:center; margin-bottom:12px; }
        .stat-icone svg { width:17px; height:17px; stroke:var(--rosa); fill:none; stroke-width:2; }
        .stat-val { font-family:var(--f-titulo); font-size:1.6rem; font-weight:700; line-height:1; margin-bottom:4px; }
        .stat-lbl { font-size:.76rem; color:var(--cinza); }

        /* TOOLBAR */
        .toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
        .toolbar-esq { display:flex; gap:6px; flex-wrap:wrap; }
        .toolbar-dir { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

        .tab { padding:7px 16px; border-radius:50px; border:1px solid var(--borda); background:var(--branco); font-family:var(--f-corpo); font-size:.82rem; font-weight:600; color:var(--cinza); text-decoration:none; transition:all .2s; white-space:nowrap; }
        .tab:hover { border-color:var(--rosa); color:var(--rosa); }
        .tab.ativo { background:var(--rosa); border-color:var(--rosa); color:#fff; }

        .sel-ord { padding:8px 12px; border-radius:8px; border:1px solid var(--borda); background:var(--branco); font-family:var(--f-corpo); font-size:.82rem; color:var(--escuro); outline:none; cursor:pointer; }
        .sel-ord:focus { border-color:var(--rosa); }

        .busca-wrap { position:relative; }
        .busca-wrap svg { position:absolute; left:11px; top:50%; transform:translateY(-50%); width:14px; height:14px; stroke:var(--cinza); fill:none; stroke-width:2; pointer-events:none; }
        .busca-input { padding:8px 14px 8px 34px; border-radius:8px; border:1px solid var(--borda); background:var(--branco); font-family:var(--f-corpo); font-size:.85rem; color:var(--escuro); outline:none; width:200px; transition:border-color .2s,box-shadow .2s; }
        .busca-input:focus { border-color:var(--rosa); box-shadow:0 0 0 3px rgba(244,63,122,.1); }
        .busca-input::placeholder { color:var(--cinza); }

        /* TABELA */
        .card { background:var(--branco); border:1px solid var(--borda); border-radius:var(--r); overflow:hidden; min-width:0; }
        .tabela-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; }
        .tabela { width:100%; border-collapse:collapse; min-width:700px; }
        .tabela th { font-size:.72rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--cinza); padding:11px 16px; text-align:left; background:var(--bg); border-bottom:1px solid var(--borda); white-space:nowrap; }
        .tabela td { padding:13px 16px; font-size:.85rem; color:var(--escuro); border-bottom:1px solid var(--borda); vertical-align:middle; }
        .tabela tr:last-child td { border-bottom:none; }
        .tabela tr:hover td { background:#fdfdfd; }

        /* Avatar letra */
        .cliente-avatar { width:36px; height:36px; border-radius:50%; background:var(--rosa-claro); color:var(--rosa); font-size:.82rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }

        .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:50px; font-size:.7rem; font-weight:600; white-space:nowrap; }
        .badge-ativo   { background:#f0fdf4; color:#15803d; }
        .badge-inativo { background:#f3f4f6; color:#6b7280; }
        .badge-vip     { background:#fef9c3; color:#854d0e; }

        .cliente-rank { display:inline-flex; align-items:center; gap:4px; font-size:.78rem; font-weight:600; }
        .cliente-rank svg { width:12px; height:12px; fill:#f59e0b; stroke:none; }

        .acoes { display:flex; gap:5px; }
        .btn-acao { width:30px; height:30px; border-radius:7px; border:1px solid var(--borda); background:var(--branco); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s; text-decoration:none; }
        .btn-acao svg { width:13px; height:13px; stroke:var(--cinza); fill:none; stroke-width:2; }
        .btn-acao:hover { border-color:var(--rosa); background:var(--rosa-claro); }
        .btn-acao:hover svg { stroke:var(--rosa); }
        .btn-acao.danger:hover { border-color:#fca5a5; background:#fff5f5; }
        .btn-acao.danger:hover svg { stroke:#dc2626; }

        .tabela-vazio { text-align:center; padding:52px 0; color:var(--cinza); }
        .tabela-vazio svg { display:block; margin:0 auto 12px; stroke:#e5e7eb; }

        /* MODAL DETALHE */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:500; display:flex; align-items:center; justify-content:center; padding:20px; }
        .modal { background:var(--branco); border-radius:var(--r); width:100%; max-width:480px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.2); }
        .modal-head { padding:20px 24px 16px; border-bottom:1px solid var(--borda); display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; background:var(--branco); z-index:1; }
        .modal-head h2 { font-family:var(--f-titulo); font-size:1.1rem; }
        .modal-close { background:none; border:none; cursor:pointer; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--cinza); font-size:1rem; transition:background .2s; }
        .modal-close:hover { background:var(--rosa-claro); color:var(--rosa); }
        .modal-body { padding:22px 24px; }
        .modal-foot { padding:16px 24px; border-top:1px solid var(--borda); display:flex; gap:10px; justify-content:flex-end; }

        .cliente-perfil { display:flex; align-items:center; gap:14px; margin-bottom:22px; }
        .perfil-avatar { width:52px; height:52px; border-radius:50%; background:var(--rosa-claro); color:var(--rosa); font-size:1.1rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .perfil-nome { font-family:var(--f-titulo); font-size:1.15rem; font-weight:700; }
        .perfil-sub  { font-size:.82rem; color:var(--cinza); margin-top:2px; }

        .detalhe-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:18px; }
        .detalhe-item { background:var(--bg); border:1px solid var(--borda); border-radius:9px; padding:12px 14px; }
        .detalhe-item .di-lbl { font-size:.72rem; color:var(--cinza); margin-bottom:3px; }
        .detalhe-item .di-val { font-size:.9rem; font-weight:600; }
        .detalhe-item .di-val.rosa { font-family:var(--f-titulo); color:var(--rosa); font-size:1.1rem; }

        .detalhe-sec { margin-bottom:16px; }
        .detalhe-sec-titulo { font-size:.72rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--cinza); margin-bottom:10px; }
        .detalhe-linha { display:flex; justify-content:space-between; font-size:.85rem; padding:7px 0; border-bottom:1px solid var(--bg); }
        .detalhe-linha:last-child { border-bottom:none; }
        .detalhe-linha span:first-child { color:var(--cinza); }
        .detalhe-linha span:last-child  { font-weight:500; }

        /* CONFIRM */
        .confirm-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:600; display:flex; align-items:center; justify-content:center; padding:20px; }
        .confirm-box { background:var(--branco); border-radius:var(--r); padding:28px; max-width:360px; width:100%; text-align:center; }
        .confirm-icon { width:48px; height:48px; border-radius:50%; background:#fff5f5; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
        .confirm-icon svg { width:22px; height:22px; stroke:#dc2626; fill:none; stroke-width:2; }
        .confirm-box h3 { font-family:var(--f-titulo); font-size:1.1rem; margin-bottom:8px; }
        .confirm-box p  { font-size:.85rem; color:var(--cinza); margin-bottom:20px; line-height:1.6; }
        .confirm-btns   { display:flex; gap:10px; justify-content:center; }

        .btn { display:inline-flex; align-items:center; gap:7px; padding:9px 18px; border-radius:8px; font-family:var(--f-corpo); font-size:.85rem; font-weight:600; border:none; cursor:pointer; transition:opacity .2s; }
        .btn:hover { opacity:.88; }
        .btn-rosa   { background:var(--rosa); color:#fff; }
        .btn-cinza  { background:var(--bg); border:1px solid var(--borda); color:var(--cinza); }
        .btn-danger { background:#fff5f5; border:1px solid #fca5a5; color:#dc2626; }
        .btn-wpp    { background:#25D366; color:#fff; }
        .btn svg    { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; }

        @media (max-width:860px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.aberta { transform:translateX(0); }
            .main { margin-left:0; }
            .btn-menu { display:block; }
            .conteudo { padding:20px 16px; }
            .topbar { padding:0 16px; }
            .stats-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
            .tabela th:nth-child(3), .tabela td:nth-child(3),
            .tabela th:nth-child(5), .tabela td:nth-child(5) { display:none; }
            .busca-input { width:150px; }
            .detalhe-grid { grid-template-columns:1fr; }
        }
        @media (max-width:480px) {
            .stats-grid { gap:10px; }
            .busca-input { width:130px; }
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