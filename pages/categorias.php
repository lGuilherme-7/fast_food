<?php
// admin/pages/categorias.php
require_once '../includes/auth.php';

// ============================================
// DADOS SIMULADOS — substituir por banco depois
// ============================================
$mensagem = '';
$erro     = '';

$categorias = [
    ['id'=>1,'nome'=>'Açaí',      'slug'=>'acai',      'descricao'=>'Tigelas e copos de açaí fresquinho.',         'cor'=>'#7c3aed','img'=>'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=120&q=70','ativo'=>true, 'ordem'=>1,'produtos'=>3],
    ['id'=>2,'nome'=>'Hambúrguer','slug'=>'hamburguer','descricao'=>'Smash burgers artesanais com blend da casa.', 'cor'=>'#f43f7a','img'=>'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=120&q=70', 'ativo'=>true, 'ordem'=>2,'produtos'=>3],
    ['id'=>3,'nome'=>'Doces',     'slug'=>'doces',     'descricao'=>'Bolos de pote, brigadeiros e sobremesas.',    'cor'=>'#ec4899','img'=>'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=120&q=70',  'ativo'=>true, 'ordem'=>3,'produtos'=>3],
    ['id'=>4,'nome'=>'Bebidas',   'slug'=>'bebidas',   'descricao'=>'Milkshakes, sucos e refrigerantes gelados.',  'cor'=>'#0ea5e9','img'=>'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=120&q=70',  'ativo'=>true, 'ordem'=>4,'produtos'=>3],
];

// ============================================
// AÇÕES POST
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    if ($acao === 'nova' || $acao === 'editar') {
        $nome     = trim($_POST['nome']      ?? '');
        $slug     = trim($_POST['slug']      ?? '');
        $desc     = trim($_POST['descricao'] ?? '');
        $cor      = trim($_POST['cor']       ?? '#f43f7a');
        $img      = trim($_POST['img']       ?? '');
        $ordem    = (int)($_POST['ordem']    ?? 1);
        $ativo    = isset($_POST['ativo']);

        // Gerar slug automático se vazio
        if (empty($slug) && !empty($nome)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nome));
            $slug = trim($slug, '-');
        }

        if (empty($nome)) {
            $erro = 'O nome da categoria é obrigatório.';
        } else {
            if ($acao === 'nova') {
                // TODO: INSERT INTO categorias ...
                $mensagem = 'Categoria "' . $nome . '" criada com sucesso!';
            } else {
                $cid = (int)($_POST['cat_id'] ?? 0);
                // TODO: UPDATE categorias SET ... WHERE id = ?
                $mensagem = 'Categoria "' . $nome . '" atualizada com sucesso!';
                foreach ($categorias as &$c) {
                    if ($c['id'] === $cid) {
                        $c['nome'] = $nome; $c['slug']     = $slug;
                        $c['descricao'] = $desc; $c['cor'] = $cor;
                        $c['img'] = $img; $c['ordem']      = $ordem;
                        $c['ativo'] = $ativo;
                        break;
                    }
                }
                unset($c);
            }
        }
    }

    if ($acao === 'toggle') {
        $cid = (int)($_POST['cat_id'] ?? 0);
        foreach ($categorias as &$c) {
            if ($c['id'] === $cid) {
                $c['ativo'] = !$c['ativo'];
                $mensagem = 'Categoria ' . ($c['ativo'] ? 'ativada' : 'desativada') . '.';
                break;
            }
        }
        unset($c);
    }

    if ($acao === 'excluir') {
        $cid = (int)($_POST['cat_id'] ?? 0);
        $cat_nome = '';
        foreach ($categorias as $c) { if ($c['id'] === $cid) { $cat_nome = $c['nome']; break; } }

        // Verificar se tem produtos (simulado)
        $tem_produtos = false;
        foreach ($categorias as $c) {
            if ($c['id'] === $cid && $c['produtos'] > 0) { $tem_produtos = true; break; }
        }

        if ($tem_produtos) {
            $erro = 'Não é possível excluir "' . $cat_nome . '" pois ela possui produtos vinculados.';
        } else {
            $categorias = array_values(array_filter($categorias, fn($c) => $c['id'] !== $cid));
            $mensagem = 'Categoria "' . $cat_nome . '" removida.';
        }
    }

    if ($acao === 'reordenar') {
        $nova_ordem = json_decode($_POST['ordem'] ?? '[]', true);
        if (is_array($nova_ordem)) {
            foreach ($categorias as &$c) {
                $pos = array_search($c['id'], $nova_ordem);
                if ($pos !== false) $c['ordem'] = $pos + 1;
            }
            unset($c);
            usort($categorias, fn($a, $b) => $a['ordem'] - $b['ordem']);
            // TODO: UPDATE categorias SET ordem = ? WHERE id = ?
            $mensagem = 'Ordem das categorias atualizada.';
        }
    }
}

// Ordenar por campo 'ordem'
usort($categorias, fn($a, $b) => $a['ordem'] - $b['ordem']);

// Edição
$editar_id = (int)($_GET['editar'] ?? 0);
$editar    = null;
foreach ($categorias as $c) {
    if ($c['id'] === $editar_id) { $editar = $c; break; }
}
$abrir_modal = !empty($_GET['nova']) || $editar !== null;

// Stats
$total_ativas   = count(array_filter($categorias, fn($c) => $c['ativo']));
$total_produtos = array_sum(array_column($categorias, 'produtos'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias — Sabor&Cia Admin</title>
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
        .sidebar { width: var(--sidebar-w); background: var(--escuro); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; z-index: 200; transition: transform .3s; }
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
        .sidebar-user { display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin-bottom: 6px; }
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
        .topbar-dir { display: flex; align-items: center; gap: 10px; }
        .btn-menu { display: none; background: none; border: none; cursor: pointer; padding: 4px; }
        .btn-menu svg { width: 20px; height: 20px; stroke: var(--escuro); fill: none; stroke-width: 2; }

        /* CONTEÚDO */
        .conteudo { padding: 28px; flex: 1; }

        /* ALERTAS */
        .alerta { padding: 12px 16px; border-radius: var(--r); font-size: .85rem; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
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
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }

        .btn-novo { display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border-radius: 8px; background: var(--rosa); color: #fff; border: none; font-family: var(--f-corpo); font-size: .85rem; font-weight: 600; cursor: pointer; transition: opacity .2s; text-decoration: none; }
        .btn-novo:hover { opacity: .88; }
        .btn-novo svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; }

        .aviso-ordem { font-size: .8rem; color: var(--cinza); display: flex; align-items: center; gap: 6px; }
        .aviso-ordem svg { width: 14px; height: 14px; stroke: var(--cinza); fill: none; stroke-width: 2; }

        /* ================================
           GRID DE CATEGORIAS
        ================================ */
        .cats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        .cat-card {
            background: var(--branco);
            border: 1px solid var(--borda);
            border-radius: var(--r);
            overflow: hidden;
            transition: box-shadow .2s;
            cursor: grab;
            user-select: none;
        }
        .cat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
        .cat-card.dragging { opacity: .4; box-shadow: 0 8px 32px rgba(0,0,0,.15); cursor: grabbing; }
        .cat-card.drag-over { border: 2px dashed var(--rosa); background: var(--rosa-claro); }
        .cat-card.inativa { opacity: .5; }

        .cat-card-inner { display: flex; align-items: stretch; }

        /* Faixa colorida lateral */
        .cat-cor { width: 6px; flex-shrink: 0; }

        /* Imagem */
        .cat-img { width: 80px; height: 80px; overflow: hidden; flex-shrink: 0; }
        .cat-img img { width: 100%; height: 100%; object-fit: cover; }
        .cat-img-vazia { width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background: var(--bg); flex-shrink: 0; }
        .cat-img-vazia svg { width: 24px; height: 24px; stroke: var(--borda); fill: none; stroke-width: 1.5; }

        /* Info */
        .cat-info { flex: 1; padding: 14px 16px; min-width: 0; }
        .cat-info-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 4px; }
        .cat-nome { font-weight: 700; font-size: .95rem; color: var(--escuro); }
        .cat-slug { font-size: .72rem; color: var(--cinza); font-family: monospace; background: var(--bg); padding: 1px 6px; border-radius: 4px; border: 1px solid var(--borda); }
        .cat-desc { font-size: .8rem; color: var(--cinza); line-height: 1.5; margin-bottom: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cat-meta { display: flex; align-items: center; gap: 12px; }
        .cat-prods { font-size: .78rem; color: var(--cinza); display: flex; align-items: center; gap: 4px; }
        .cat-prods svg { width: 12px; height: 12px; stroke: var(--cinza); fill: none; stroke-width: 2; }
        .cat-ordem { font-size: .72rem; color: var(--cinza); }

        /* Badge */
        .badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 50px; font-size: .7rem; font-weight: 600; }
        .badge-ativo   { background: #f0fdf4; color: #15803d; }
        .badge-inativo { background: #f3f4f6; color: #6b7280; }

        /* Ações do card */
        .cat-acoes {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 12px 14px;
            border-left: 1px solid var(--borda);
            flex-shrink: 0;
        }
        .btn-acao { width: 30px; height: 30px; border-radius: 7px; border: 1px solid var(--borda); background: var(--branco); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s; }
        .btn-acao svg { width: 13px; height: 13px; stroke: var(--cinza); fill: none; stroke-width: 2; }
        .btn-acao:hover { border-color: var(--rosa); background: var(--rosa-claro); }
        .btn-acao:hover svg { stroke: var(--rosa); }
        .btn-acao.danger:hover { border-color: #fca5a5; background: #fff5f5; }
        .btn-acao.danger:hover svg { stroke: #dc2626; }

        /* DRAG HANDLE */
        .drag-handle { display: flex; align-items: center; justify-content: center; padding: 0 10px; cursor: grab; color: var(--borda); transition: color .2s; }
        .drag-handle:hover { color: var(--cinza); }
        .drag-handle svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; }

        /* TOGGLE */
        .toggle-wrap { display: inline-flex; }
        .toggle-inp { display: none; }
        .toggle-label { width: 34px; height: 19px; background: #d1d5db; border-radius: 50px; cursor: pointer; position: relative; transition: background .2s; display: block; }
        .toggle-label::after { content: ''; position: absolute; width: 13px; height: 13px; border-radius: 50%; background: #fff; top: 3px; left: 3px; transition: transform .2s; }
        .toggle-inp:checked + .toggle-label { background: #22c55e; }
        .toggle-inp:checked + .toggle-label::after { transform: translateX(15px); }

        /* ================================
           TABELA VAZIO
        ================================ */
        .lista-vazia { text-align: center; padding: 60px 0; color: var(--cinza); background: var(--branco); border: 1px solid var(--borda); border-radius: var(--r); }
        .lista-vazia svg { display: block; margin: 0 auto 12px; stroke: #e5e7eb; }

        /* ================================
           MODAL NOVA / EDITAR
        ================================ */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 500; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .modal { background: var(--branco); border-radius: var(--r); width: 100%; max-width: 520px; max-height: 92vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
        .modal-head { padding: 20px 24px 16px; border-bottom: 1px solid var(--borda); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; background: var(--branco); z-index: 1; }
        .modal-head h2 { font-family: var(--f-titulo); font-size: 1.15rem; color: var(--escuro); }
        .modal-close { background: none; border: none; cursor: pointer; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--cinza); font-size: 1rem; transition: background .2s; }
        .modal-close:hover { background: var(--rosa-claro); color: var(--rosa); }
        .modal-body { padding: 24px; }
        .modal-foot { padding: 16px 24px; border-top: 1px solid var(--borda); display: flex; gap: 10px; justify-content: flex-end; }

        /* CAMPOS */
        .campo-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
        .campo-full   { margin-bottom: 14px; }
        .campo { display: flex; flex-direction: column; gap: 6px; }
        .campo label { font-size: .82rem; font-weight: 600; color: var(--escuro); }
        .campo input,
        .campo select,
        .campo textarea {
            padding: 10px 13px; border-radius: 9px;
            border: 1px solid var(--borda); background: var(--branco);
            font-family: var(--f-corpo); font-size: .88rem; color: var(--escuro);
            outline: none; width: 100%;
            transition: border-color .2s, box-shadow .2s;
        }
        .campo input:focus,
        .campo select:focus,
        .campo textarea:focus { border-color: var(--rosa); box-shadow: 0 0 0 3px rgba(244,63,122,.1); }
        .campo input::placeholder,
        .campo textarea::placeholder { color: var(--cinza); }
        .campo textarea { resize: vertical; min-height: 70px; }
        .campo-hint { font-size: .75rem; color: var(--cinza); margin-top: 3px; }

        /* Cor picker */
        .cor-wrap { display: flex; align-items: center; gap: 10px; }
        .cor-input { width: 44px; height: 38px; padding: 3px; border-radius: 8px; border: 1px solid var(--borda); cursor: pointer; background: none; }
        .cor-hex { flex: 1; }

        /* Preview cor na categoria */
        .cor-preview {
            display: flex; gap: 8px; flex-wrap: wrap;
            margin-top: 8px;
        }
        .cor-op {
            width: 28px; height: 28px; border-radius: 50%;
            cursor: pointer; border: 2px solid transparent;
            transition: transform .15s, border-color .15s;
        }
        .cor-op:hover { transform: scale(1.15); }
        .cor-op.sel { border-color: var(--escuro); transform: scale(1.15); }

        /* Img preview */
        .img-preview { width: 100%; height: 100px; border-radius: 9px; overflow: hidden; border: 1px solid var(--borda); background: var(--bg); display: flex; align-items: center; justify-content: center; margin-top: 8px; }
        .img-preview img { width: 100%; height: 100%; object-fit: cover; }
        .img-preview-vazio { color: var(--cinza); font-size: .78rem; text-align: center; }
        .img-preview-vazio svg { display: block; margin: 0 auto 4px; stroke: var(--borda); fill: none; stroke-width: 1.5; }

        /* Checkbox */
        .campo-check { display: flex; align-items: center; gap: 10px; padding: 10px 0; }
        .campo-check input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--rosa); cursor: pointer; }
        .campo-check label { font-size: .88rem; font-weight: 500; cursor: pointer; }

        /* BTNS */
        .btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border-radius: 8px; font-family: var(--f-corpo); font-size: .85rem; font-weight: 600; border: none; cursor: pointer; transition: opacity .2s; }
        .btn:hover { opacity: .88; }
        .btn-rosa   { background: var(--rosa); color: #fff; }
        .btn-cinza  { background: var(--bg); border: 1px solid var(--borda); color: var(--cinza); }
        .btn-danger { background: #fff5f5; border: 1px solid #fca5a5; color: #dc2626; }
        .btn svg    { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; }

        /* CONFIRM */
        .confirm-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 600; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .confirm-box { background: var(--branco); border-radius: var(--r); padding: 28px; max-width: 360px; width: 100%; text-align: center; }
        .confirm-icon { width: 48px; height: 48px; border-radius: 50%; background: #fff5f5; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
        .confirm-icon svg { width: 22px; height: 22px; stroke: #dc2626; fill: none; stroke-width: 2; }
        .confirm-box h3 { font-family: var(--f-titulo); font-size: 1.1rem; margin-bottom: 8px; }
        .confirm-box p  { font-size: .85rem; color: var(--cinza); margin-bottom: 20px; line-height: 1.6; }
        .confirm-btns   { display: flex; gap: 10px; justify-content: center; }

        /* RESPONSIVO */
        @media (max-width: 860px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.aberta { transform: translateX(0); }
            .main { margin-left: 0; }
            .btn-menu { display: block; }
            .conteudo { padding: 20px 16px; }
            .topbar { padding: 0 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .cats-grid { grid-template-columns: 1fr; }
            .campo-grid-2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
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
            <a href="categorias.php" class="nav-item ativo">
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
                    <div class="topbar-titulo">Categorias</div>
                    <div class="topbar-breadcrumb"><?= count($categorias) ?> categorias cadastradas</div>
                </div>
            </div>
            <div class="topbar-dir">
                <a href="../../index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ver site
                </a>
                <a href="categorias.php?nova=1" class="btn-novo">
                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nova categoria
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
                    <div class="stat-val"><?= count($categorias) ?></div>
                    <div class="stat-lbl">Total de categorias</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= $total_ativas ?></div>
                    <div class="stat-lbl">Ativas no cardápio</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= count($categorias) - $total_ativas ?></div>
                    <div class="stat-lbl">Desativadas</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= $total_produtos ?></div>
                    <div class="stat-lbl">Produtos vinculados</div>
                </div>
            </div>

            <!-- TOOLBAR -->
            <div class="toolbar">
                <div class="aviso-ordem">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Arraste os cards para reordenar como aparecem no cardápio.
                </div>
                <button class="btn btn-cinza" id="btnSalvarOrdem" style="display:none" onclick="salvarOrdem()">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    Salvar ordem
                </button>
            </div>

            <!-- GRID DE CATEGORIAS -->
            <?php if (empty($categorias)): ?>
            <div class="lista-vazia">
                <svg width="36" height="36" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                Nenhuma categoria cadastrada ainda.
            </div>
            <?php else: ?>
            <div class="cats-grid" id="catsGrid">
                <?php foreach ($categorias as $c): ?>
                <div class="cat-card <?= !$c['ativo'] ? 'inativa' : '' ?>"
                     draggable="true"
                     data-id="<?= $c['id'] ?>">
                    <div class="cat-card-inner">

                        <!-- Handle drag -->
                        <div class="drag-handle" title="Arrastar para reordenar">
                            <svg viewBox="0 0 24 24"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                        </div>

                        <!-- Cor lateral -->
                        <div class="cat-cor" style="background:<?= htmlspecialchars($c['cor']) ?>"></div>

                        <!-- Imagem -->
                        <?php if (!empty($c['img'])): ?>
                        <div class="cat-img">
                            <img src="<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['nome']) ?>">
                        </div>
                        <?php else: ?>
                        <div class="cat-img-vazia">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </div>
                        <?php endif; ?>

                        <!-- Info -->
                        <div class="cat-info">
                            <div class="cat-info-top">
                                <div>
                                    <div class="cat-nome"><?= htmlspecialchars($c['nome']) ?></div>
                                    <code class="cat-slug">/<?= htmlspecialchars($c['slug']) ?></code>
                                </div>
                                <span class="badge <?= $c['ativo'] ? 'badge-ativo' : 'badge-inativo' ?>">
                                    <?= $c['ativo'] ? 'Ativa' : 'Inativa' ?>
                                </span>
                            </div>
                            <div class="cat-desc"><?= htmlspecialchars($c['descricao'] ?: 'Sem descrição') ?></div>
                            <div class="cat-meta">
                                <div class="cat-prods">
                                    <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                                    <?= $c['produtos'] ?> produto<?= $c['produtos'] !== 1 ? 's' : '' ?>
                                </div>
                                <div class="cat-ordem">Posição <?= $c['ordem'] ?></div>
                            </div>
                        </div>

                        <!-- Ações -->
                        <div class="cat-acoes">
                            <!-- Toggle ativo -->
                            <form method="POST" style="margin-bottom:4px">
                                <input type="hidden" name="acao" value="toggle">
                                <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="tog<?= $c['id'] ?>"
                                        <?= $c['ativo'] ? 'checked' : '' ?>
                                        onchange="this.form.submit()">
                                    <label class="toggle-label" for="tog<?= $c['id'] ?>"></label>
                                </div>
                            </form>

                            <!-- Editar -->
                            <a href="categorias.php?editar=<?= $c['id'] ?>">
                                <button class="btn-acao" title="Editar">
                                    <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                            </a>

                            <!-- Ver produtos -->
                            <a href="produtos.php?cat=<?= urlencode($c['nome']) ?>">
                                <button class="btn-acao" title="Ver produtos">
                                    <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </a>

                            <!-- Excluir -->
                            <button class="btn-acao danger" title="Excluir"
                                onclick="confirmarExclusao(<?= $c['id'] ?>, '<?= addslashes(htmlspecialchars($c['nome'])) ?>', <?= $c['produtos'] ?>)">
                                <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>
                            </button>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div><!-- /conteudo -->
    </div><!-- /main -->
</div><!-- /admin-wrap -->

<!-- ================================
     MODAL NOVA / EDITAR CATEGORIA
================================ -->
<?php if ($abrir_modal):
    $cores_preset = ['#f43f7a','#7c3aed','#0ea5e9','#ec4899','#f59e0b','#22c55e','#ef4444','#64748b'];
?>
<div class="modal-overlay" id="modalCat">
    <div class="modal">
        <div class="modal-head">
            <h2><?= $editar ? 'Editar categoria' : 'Nova categoria' ?></h2>
            <button class="modal-close" onclick="fecharModal()">&#x2715;</button>
        </div>

        <form method="POST" action="categorias.php">
            <input type="hidden" name="acao" value="<?= $editar ? 'editar' : 'nova' ?>">
            <?php if ($editar): ?>
            <input type="hidden" name="cat_id" value="<?= $editar['id'] ?>">
            <?php endif; ?>

            <div class="modal-body">

                <!-- Nome -->
                <div class="campo-full">
                    <div class="campo">
                        <label for="f-nome">Nome da categoria *</label>
                        <input type="text" id="f-nome" name="nome"
                            placeholder="Ex: Açaí"
                            value="<?= htmlspecialchars($editar['nome'] ?? '') ?>"
                            oninput="gerarSlug(this.value)"
                            required>
                    </div>
                </div>

                <!-- Slug -->
                <div class="campo-full">
                    <div class="campo">
                        <label for="f-slug">Slug (URL)</label>
                        <input type="text" id="f-slug" name="slug"
                            placeholder="acai"
                            value="<?= htmlspecialchars($editar['slug'] ?? '') ?>">
                        <span class="campo-hint">Gerado automaticamente. Use apenas letras minúsculas e hífens.</span>
                    </div>
                </div>

                <!-- Descrição -->
                <div class="campo-full">
                    <div class="campo">
                        <label for="f-desc">Descrição</label>
                        <textarea id="f-desc" name="descricao"
                            placeholder="Breve descrição da categoria..."><?= htmlspecialchars($editar['descricao'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Cor + Ordem -->
                <div class="campo-grid-2">
                    <div class="campo">
                        <label>Cor de destaque</label>
                        <div class="cor-wrap">
                            <input type="color" class="cor-input" id="f-cor-picker"
                                value="<?= htmlspecialchars($editar['cor'] ?? '#f43f7a') ?>"
                                oninput="document.getElementById('f-cor-hex').value = this.value; sincronizarCor(this.value)">
                            <input type="text" class="cor-hex" id="f-cor-hex" name="cor"
                                value="<?= htmlspecialchars($editar['cor'] ?? '#f43f7a') ?>"
                                placeholder="#f43f7a"
                                oninput="document.getElementById('f-cor-picker').value = this.value">
                        </div>
                        <div class="cor-preview" id="corPreview">
                            <?php foreach ($cores_preset as $cor): ?>
                            <div class="cor-op <?= ($editar['cor'] ?? '#f43f7a') === $cor ? 'sel' : '' ?>"
                                 style="background:<?= $cor ?>"
                                 onclick="escolherCor('<?= $cor ?>')"
                                 title="<?= $cor ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="campo">
                        <label for="f-ordem">Ordem de exibição</label>
                        <input type="number" id="f-ordem" name="ordem"
                            min="1" placeholder="1"
                            value="<?= $editar['ordem'] ?? (count($categorias) + 1) ?>">
                        <span class="campo-hint">Menor número aparece primeiro.</span>
                    </div>
                </div>

                <!-- Imagem -->
                <div class="campo-full">
                    <div class="campo">
                        <label for="f-img">URL da imagem</label>
                        <input type="text" id="f-img" name="img"
                            placeholder="https://..."
                            value="<?= htmlspecialchars($editar['img'] ?? '') ?>"
                            oninput="previewImg(this.value)">
                    </div>
                    <div class="img-preview" id="imgPreview">
                        <?php if (!empty($editar['img'])): ?>
                        <img src="<?= htmlspecialchars($editar['img']) ?>" alt="Preview">
                        <?php else: ?>
                        <div class="img-preview-vazio">
                            <svg width="24" height="24" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            Preview
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ativo -->
                <div class="campo-check">
                    <input type="checkbox" id="f-ativo" name="ativo"
                        <?= ($editar['ativo'] ?? true) ? 'checked' : '' ?>>
                    <label for="f-ativo">Categoria ativa (visível no cardápio)</label>
                </div>

            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-cinza" onclick="fecharModal()">Cancelar</button>
                <button type="submit" class="btn btn-rosa">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    <?= $editar ? 'Salvar alterações' : 'Criar categoria' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- CONFIRM EXCLUIR -->
<div class="confirm-overlay" id="confirmOverlay" style="display:none">
    <div class="confirm-box">
        <div class="confirm-icon">
            <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>
        </div>
        <h3>Excluir categoria?</h3>
        <p id="confirmTxt">Esta ação não pode ser desfeita.</p>
        <div class="confirm-btns">
            <form method="POST" id="formExcluir" action="categorias.php">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="cat_id" id="excluirId" value="">
                <button type="submit" class="btn btn-danger" id="btnConfirmarExcluir">Sim, excluir</button>
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

    // Fechar modal
    function fecharModal() {
        var url = new URL(window.location.href);
        url.searchParams.delete('nova');
        url.searchParams.delete('editar');
        window.location.href = url.toString();
    }

    // Confirmar exclusão
    function confirmarExclusao(id, nome, produtos) {
        document.getElementById('excluirId').value = id;
        var txt = 'A categoria "' + nome + '" será removida permanentemente.';
        if (produtos > 0) {
            txt = 'A categoria "' + nome + '" possui ' + produtos + ' produto(s) vinculado(s) e não pode ser excluída. Reatribua os produtos primeiro.';
            document.getElementById('btnConfirmarExcluir').style.display = 'none';
        } else {
            document.getElementById('btnConfirmarExcluir').style.display = '';
        }
        document.getElementById('confirmTxt').textContent = txt;
        document.getElementById('confirmOverlay').style.display = 'flex';
    }

    // Gerar slug automático
    function gerarSlug(nome) {
        var slugInput = document.getElementById('f-slug');
        if (!slugInput || slugInput.dataset.manual === 'true') return;
        var slug = nome.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim().replace(/\s+/g, '-');
        slugInput.value = slug;
    }
    var slugInput = document.getElementById('f-slug');
    if (slugInput) {
        slugInput.addEventListener('input', function() {
            this.dataset.manual = 'true';
        });
    }

    // Cores preset
    function escolherCor(cor) {
        var picker = document.getElementById('f-cor-picker');
        var hex    = document.getElementById('f-cor-hex');
        if (picker) picker.value = cor;
        if (hex)    hex.value    = cor;
        document.querySelectorAll('.cor-op').forEach(function(el) {
            el.classList.toggle('sel', el.title === cor);
        });
    }
    function sincronizarCor(cor) {
        document.querySelectorAll('.cor-op').forEach(function(el) {
            el.classList.toggle('sel', el.title === cor);
        });
    }

    // Preview imagem
    function previewImg(url) {
        var preview = document.getElementById('imgPreview');
        if (!preview) return;
        if (!url) {
            preview.innerHTML = '<div class="img-preview-vazio"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#e5e7eb" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>Preview</div>';
            return;
        }
        var img = new Image();
        img.onload  = function() { preview.innerHTML = '<img src="' + url + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">'; };
        img.onerror = function() { preview.innerHTML = '<div class="img-preview-vazio">URL inválida</div>'; };
        img.src = url;
    }

    // ================================
    // DRAG & DROP PARA REORDENAR
    // ================================
    var dragSrc = null;

    document.querySelectorAll('.cat-card').forEach(function(card) {
        card.addEventListener('dragstart', function(e) {
            dragSrc = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            document.querySelectorAll('.cat-card').forEach(function(c) {
                c.classList.remove('drag-over');
            });
        });
        card.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            if (this !== dragSrc) {
                document.querySelectorAll('.cat-card').forEach(function(c) { c.classList.remove('drag-over'); });
                this.classList.add('drag-over');
            }
        });
        card.addEventListener('drop', function(e) {
            e.preventDefault();
            if (dragSrc && this !== dragSrc) {
                var grid = document.getElementById('catsGrid');
                var cards = Array.from(grid.querySelectorAll('.cat-card'));
                var srcIdx  = cards.indexOf(dragSrc);
                var destIdx = cards.indexOf(this);
                if (srcIdx < destIdx) {
                    grid.insertBefore(dragSrc, this.nextSibling);
                } else {
                    grid.insertBefore(dragSrc, this);
                }
                this.classList.remove('drag-over');
                document.getElementById('btnSalvarOrdem').style.display = '';
            }
        });
    });

    // Salvar nova ordem via POST
    function salvarOrdem() {
        var cards = document.querySelectorAll('#catsGrid .cat-card');
        var ids = Array.from(cards).map(function(c) { return parseInt(c.dataset.id); });
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'categorias.php';
        var inp1 = document.createElement('input');
        inp1.type = 'hidden'; inp1.name = 'acao'; inp1.value = 'reordenar';
        var inp2 = document.createElement('input');
        inp2.type = 'hidden'; inp2.name = 'ordem'; inp2.value = JSON.stringify(ids);
        form.appendChild(inp1); form.appendChild(inp2);
        document.body.appendChild(form);
        form.submit();
    }

    // ESC fecha modais
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharModal();
            document.getElementById('confirmOverlay').style.display = 'none';
        }
    });
</script>

</body>
</html>