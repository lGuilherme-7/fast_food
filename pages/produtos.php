<?php
// admin/pages/produtos.php
require_once '../includes/auth.php';
require_once '../../config/db.php';

// ============================================
// AÇÕES PHP
// ============================================
$mensagem = '';
$erro     = '';

// Dados simulados — substituir por banco depois
$categorias_lista = ['Açaí', 'Hambúrguer', 'Doces', 'Bebidas'];

$produtos = [
    ['id'=>1, 'nome'=>'Açaí Premium 500ml',    'preco'=>18.90, 'cat'=>'Açaí',       'estoque'=>48, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=120&q=70', 'desc'=>'Açaí cremoso com granola, banana e leite condensado.'],
    ['id'=>2, 'nome'=>'Hambúrguer Smash',       'preco'=>29.90, 'cat'=>'Hambúrguer', 'estoque'=>32, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=120&q=70',  'desc'=>'Blend da casa 180g, queijo cheddar, alface e tomate.'],
    ['id'=>3, 'nome'=>'Bolo de Pote Ninho',     'preco'=>14.90, 'cat'=>'Doces',      'estoque'=>20, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=120&q=70',  'desc'=>'Bolo cremoso de leite ninho com cobertura de Nutella.'],
    ['id'=>4, 'nome'=>'Milkshake Oreo',         'preco'=>16.90, 'cat'=>'Bebidas',    'estoque'=>15, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=120&q=70',  'desc'=>'Milkshake cremoso de baunilha com Oreo triturado.'],
    ['id'=>5, 'nome'=>'Açaí Tradicional 300ml', 'preco'=>12.90, 'cat'=>'Açaí',       'estoque'=>60, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1511690743698-d9d85f2fbf38?w=120&q=70',  'desc'=>'Açaí puro batido na hora, simples e fresquinho.'],
    ['id'=>6, 'nome'=>'Combo Smash Duplo',      'preco'=>42.90, 'cat'=>'Hambúrguer', 'estoque'=>18, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=120&q=70',    'desc'=>'Dois blends 180g, bacon, cheddar e molho especial.'],
    ['id'=>7, 'nome'=>'Açaí com Morango 400ml', 'preco'=>15.90, 'cat'=>'Açaí',       'estoque'=>35, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=120&q=70',    'desc'=>'Açaí cremoso com morangos frescos e mel.'],
    ['id'=>8, 'nome'=>'Smash Bacon',            'preco'=>34.90, 'cat'=>'Hambúrguer', 'estoque'=>22, 'ativo'=>false, 'img'=>'https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?w=120&q=70',  'desc'=>'Blend 180g, bacon crocante, queijo e cebola caramelizada.'],
    ['id'=>9, 'nome'=>'Brigadeiro Gourmet',     'preco'=>6.90,  'cat'=>'Doces',      'estoque'=>50, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1611293388250-580b08c4a145?w=120&q=70',  'desc'=>'Brigadeiro artesanal com chocolate belga 70%.'],
    ['id'=>10,'nome'=>'Suco de Laranja',        'preco'=>9.90,  'cat'=>'Bebidas',    'estoque'=>0,  'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?w=120&q=70',  'desc'=>'Suco natural de laranja espremido na hora, 400ml.'],
    ['id'=>11,'nome'=>'Bolo de Pote Oreo',      'preco'=>14.90, 'cat'=>'Doces',      'estoque'=>12, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=120&q=70',  'desc'=>'Bolo de chocolate com creme de Oreo e biscoito triturado.'],
    ['id'=>12,'nome'=>'Refrigerante Lata',      'preco'=>6.00,  'cat'=>'Bebidas',    'estoque'=>80, 'ativo'=>true,  'img'=>'https://images.unsplash.com/photo-1581636625402-29b2a704ef13?w=120&q=70',  'desc'=>'Coca-Cola, Guaraná ou Sprite. Lata 350ml gelada.'],
];

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    if ($acao === 'novo' || $acao === 'editar') {
        $nome   = trim($_POST['nome']   ?? '');
        $preco  = (float)str_replace(',', '.', $_POST['preco'] ?? 0);
        $cat    = trim($_POST['cat']    ?? '');
        $estoque= (int)($_POST['estoque'] ?? 0);
        $desc   = trim($_POST['desc']   ?? '');
        $img    = trim($_POST['img']    ?? '');
        $ativo  = isset($_POST['ativo']);

        if (empty($nome) || $preco <= 0 || empty($cat)) {
            $erro = 'Preencha nome, preço e categoria.';
        } else {
            if ($acao === 'novo') {
                // TODO: INSERT INTO produtos ...
                $mensagem = 'Produto "' . $nome . '" criado com sucesso!';
            } else {
                $pid = (int)($_POST['produto_id'] ?? 0);
                // TODO: UPDATE produtos SET ... WHERE id = ?
                $mensagem = 'Produto "' . $nome . '" atualizado com sucesso!';
                foreach ($produtos as &$p) {
                    if ($p['id'] === $pid) {
                        $p['nome'] = $nome; $p['preco'] = $preco;
                        $p['cat']  = $cat;  $p['estoque'] = $estoque;
                        $p['desc'] = $desc; $p['img']  = $img;
                        $p['ativo']= $ativo;
                        break;
                    }
                }
                unset($p);
            }
        }
    }

    if ($acao === 'toggle') {
        $pid = (int)($_POST['produto_id'] ?? 0);
        foreach ($produtos as &$p) {
            if ($p['id'] === $pid) {
                $p['ativo'] = !$p['ativo'];
                $mensagem = 'Produto ' . ($p['ativo'] ? 'ativado' : 'desativado') . '.';
                break;
            }
        }
        unset($p);
    }

    if ($acao === 'excluir') {
        $pid = (int)($_POST['produto_id'] ?? 0);
        $produtos = array_values(array_filter($produtos, fn($p) => $p['id'] !== $pid));
        $mensagem = 'Produto removido com sucesso.';
    }
}

// Filtros GET
$filtro_cat   = $_GET['cat']   ?? 'todas';
$filtro_busca = trim($_GET['busca'] ?? '');
$view_mode    = $_GET['view']  ?? 'tabela'; // tabela | cards

$lista = array_filter($produtos, function($p) use ($filtro_cat, $filtro_busca) {
    $ok_c = ($filtro_cat === 'todas' || $p['cat'] === $filtro_cat);
    $ok_b = empty($filtro_busca) || stripos($p['nome'], $filtro_busca) !== false;
    return $ok_c && $ok_b;
});

// Produto para edição
$editar_id = (int)($_GET['editar'] ?? 0);
$editar    = null;
foreach ($produtos as $p) {
    if ($p['id'] === $editar_id) { $editar = $p; break; }
}

$abrir_modal = !empty($_GET['novo']) || $editar !== null;

// Stats
$total_ativos   = count(array_filter($produtos, fn($p) => $p['ativo']));
$total_inativos = count($produtos) - $total_ativos;
$sem_estoque    = count(array_filter($produtos, fn($p) => $p['estoque'] === 0));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos — Sabor&Cia Admin</title>
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
            <a href="produtos.php" class="nav-item ativo">
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
                    <div class="topbar-titulo">Produtos</div>
                    <div class="topbar-breadcrumb">Catálogo — <?= count($produtos) ?> produtos cadastrados</div>
                </div>
            </div>
            <div class="topbar-dir">
                <a href="../../index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ver site
                </a>
                <a href="produtos.php?novo=1<?= $view_mode !== 'tabela' ? '&view='.$view_mode : '' ?>" class="btn-novo">
                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo produto
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
                    <div class="stat-val"><?= count($produtos) ?></div>
                    <div class="stat-lbl">Total de produtos</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= $total_ativos ?></div>
                    <div class="stat-lbl">Ativos no cardápio</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= $total_inativos ?></div>
                    <div class="stat-lbl">Desativados</div>
                </div>
                <div class="stat">
                    <div class="stat-val" style="color:<?= $sem_estoque > 0 ? '#ef4444' : 'var(--escuro)' ?>"><?= $sem_estoque ?></div>
                    <div class="stat-lbl">Sem estoque</div>
                </div>
            </div>

            <!-- TOOLBAR -->
            <div class="toolbar">
                <div class="toolbar-esq">
                    <a href="produtos.php?cat=todas<?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>&view=<?= $view_mode ?>"
                       class="tab-cat <?= $filtro_cat === 'todas' ? 'ativo' : '' ?>">Todas</a>
                    <?php foreach ($categorias_lista as $c): ?>
                    <a href="produtos.php?cat=<?= urlencode($c) ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>&view=<?= $view_mode ?>"
                       class="tab-cat <?= $filtro_cat === $c ? 'ativo' : '' ?>">
                        <?= htmlspecialchars($c) ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="toolbar-dir">
                    <form method="GET" action="produtos.php" class="busca-wrap">
                        <?php if ($filtro_cat !== 'todas'): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($filtro_cat) ?>"><?php endif; ?>
                        <input type="hidden" name="view" value="<?= $view_mode ?>">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="busca" class="busca-input"
                            placeholder="Buscar produto..."
                            value="<?= htmlspecialchars($filtro_busca) ?>"
                            autocomplete="off">
                    </form>

                    <div class="view-btns">
                        <a href="produtos.php?cat=<?= urlencode($filtro_cat) ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>&view=tabela">
                            <button class="view-btn <?= $view_mode === 'tabela' ? 'ativo' : '' ?>" title="Visualização tabela">
                                <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </button>
                        </a>
                        <a href="produtos.php?cat=<?= urlencode($filtro_cat) ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>&view=cards">
                            <button class="view-btn <?= $view_mode === 'cards' ? 'ativo' : '' ?>" title="Visualização cards">
                                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            </button>
                        </a>
                    </div>
                </div>
            </div>

            <!-- RESULTADO -->
            <p style="font-size:.82rem;color:var(--cinza);margin-bottom:14px;">
                <strong style="color:var(--escuro)"><?= count($lista) ?></strong> produto<?= count($lista) !== 1 ? 's' : '' ?> encontrado<?= count($lista) !== 1 ? 's' : '' ?>
            </p>

            <?php if ($view_mode === 'tabela'): ?>
            <!-- ==================== TABELA ==================== -->
            <div class="card">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Status</th>
                            <th>Ativo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista)): ?>
                        <tr><td colspan="7">
                            <div class="tabela-vazio">
                                <svg width="36" height="36" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                                Nenhum produto encontrado.
                            </div>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($lista as $p): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div class="prod-thumb">
                                        <img src="<?= $p['img'] ?>" alt="<?= htmlspecialchars($p['nome']) ?>">
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($p['nome']) ?></div>
                                        <div class="prod-id">#<?= $p['id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background:var(--rosa-claro);color:var(--rosa);">
                                    <?= htmlspecialchars($p['cat']) ?>
                                </span>
                            </td>
                            <td style="font-family:var(--f-titulo);font-size:.95rem;color:var(--rosa);font-weight:700;">
                                R$ <?= number_format($p['preco'], 2, ',', '.') ?>
                            </td>
                            <td>
                                <?php
                                $cls = $p['estoque'] === 0 ? 'zero' : ($p['estoque'] <= 10 ? 'baixo' : 'ok');
                                $label_estq = $p['estoque'] === 0 ? 'Sem estoque' : ($p['estoque'] <= 10 ? 'Estoque baixo' : 'Em estoque');
                                ?>
                                <div class="estoque-num <?= $cls ?>"><?= $p['estoque'] ?> un.</div>
                                <div style="font-size:.72rem;color:var(--cinza)"><?= $label_estq ?></div>
                            </td>
                            <td>
                                <?php if ($p['estoque'] === 0): ?>
                                <span class="badge badge-sem-estq">Sem estoque</span>
                                <?php elseif ($p['ativo']): ?>
                                <span class="badge badge-ativo">Ativo</span>
                                <?php else: ?>
                                <span class="badge badge-inativo">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="acao" value="toggle">
                                    <input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
                                    <div class="toggle-wrap">
                                        <input type="checkbox" class="toggle-inp" id="tog<?= $p['id'] ?>"
                                            <?= $p['ativo'] ? 'checked' : '' ?>
                                            onchange="this.form.submit()">
                                        <label class="toggle-label" for="tog<?= $p['id'] ?>"></label>
                                    </div>
                                </form>
                            </td>
                            <td>
                                <div class="acoes">
                                    <a href="produtos.php?editar=<?= $p['id'] ?>&cat=<?= urlencode($filtro_cat) ?>&view=<?= $view_mode ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>">
                                        <button class="btn-acao" title="Editar">
                                            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                    </a>
                                    <a href="../../produto.php?id=<?= $p['id'] ?>" target="_blank">
                                        <button class="btn-acao" title="Ver no site">
                                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </a>
                                    <button class="btn-acao danger" title="Excluir" onclick="confirmarExclusao(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['nome'])) ?>')">
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

            <?php else: ?>
            <!-- ==================== CARDS ==================== -->
            <?php if (empty($lista)): ?>
            <div class="tabela-vazio">
                <svg width="36" height="36" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                Nenhum produto encontrado.
            </div>
            <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($lista as $p): ?>
                <div class="prod-card-admin <?= !$p['ativo'] ? 'inativo' : '' ?>">
                    <div class="pca-img">
                        <img src="<?= $p['img'] ?>" alt="<?= htmlspecialchars($p['nome']) ?>">
                        <div class="pca-badge">
                            <?php if ($p['estoque'] === 0): ?>
                            <span class="badge badge-sem-estq">Sem estoque</span>
                            <?php elseif (!$p['ativo']): ?>
                            <span class="badge badge-inativo">Inativo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pca-body">
                        <div class="pca-cat"><?= htmlspecialchars($p['cat']) ?></div>
                        <div class="pca-nome"><?= htmlspecialchars($p['nome']) ?></div>
                        <div class="pca-linha">
                            <div class="pca-preco">R$ <?= number_format($p['preco'], 2, ',', '.') ?></div>
                            <div class="pca-estq"><?= $p['estoque'] ?> un.</div>
                        </div>
                    </div>
                    <div class="pca-acoes">
                        <a href="produtos.php?editar=<?= $p['id'] ?>&view=cards&cat=<?= urlencode($filtro_cat) ?>" class="pca-btn">
                            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Editar
                        </a>
                        <button class="pca-btn danger" onclick="confirmarExclusao(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['nome'])) ?>')">
                            <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>
                            Excluir
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

        </div><!-- /conteudo -->
    </div><!-- /main -->
</div><!-- /admin-wrap -->

<!-- ================================
     MODAL NOVO / EDITAR
================================ -->
<?php if ($abrir_modal): ?>
<div class="modal-overlay" id="modalProduto">
    <div class="modal">
        <div class="modal-head">
            <h2><?= $editar ? 'Editar produto' : 'Novo produto' ?></h2>
            <button class="modal-close" onclick="fecharModal()">&#x2715;</button>
        </div>

        <form method="POST" action="produtos.php?cat=<?= urlencode($filtro_cat) ?>&view=<?= $view_mode ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>">
            <input type="hidden" name="acao" value="<?= $editar ? 'editar' : 'novo' ?>">
            <?php if ($editar): ?>
            <input type="hidden" name="produto_id" value="<?= $editar['id'] ?>">
            <?php endif; ?>

            <div class="modal-body">

                <!-- Nome -->
                <div class="campo-full">
                    <div class="campo">
                        <label for="f-nome">Nome do produto *</label>
                        <input type="text" id="f-nome" name="nome"
                            placeholder="Ex: Açaí Premium 500ml"
                            value="<?= htmlspecialchars($editar['nome'] ?? '') ?>"
                            required>
                    </div>
                </div>

                <!-- Preço / Categoria / Estoque -->
                <div class="campo-grid-3">
                    <div class="campo">
                        <label for="f-preco">Preço (R$) *</label>
                        <input type="text" id="f-preco" name="preco"
                            placeholder="0,00"
                            value="<?= $editar ? number_format($editar['preco'], 2, ',', '.') : '' ?>"
                            required>
                    </div>
                    <div class="campo">
                        <label for="f-cat">Categoria *</label>
                        <select id="f-cat" name="cat" required>
                            <option value="">Selecione</option>
                            <?php foreach ($categorias_lista as $c): ?>
                            <option value="<?= $c ?>" <?= ($editar['cat'] ?? '') === $c ? 'selected' : '' ?>>
                                <?= $c ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="f-estoque">Estoque</label>
                        <input type="number" id="f-estoque" name="estoque"
                            min="0" placeholder="0"
                            value="<?= $editar['estoque'] ?? 0 ?>">
                    </div>
                </div>

                <!-- Descrição -->
                <div class="campo-full">
                    <div class="campo">
                        <label for="f-desc">Descrição curta</label>
                        <textarea id="f-desc" name="desc" placeholder="Descreva o produto brevemente..."><?= htmlspecialchars($editar['desc'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- URL da imagem -->
                <div class="campo-full">
                    <div class="campo">
                        <label for="f-img">URL da imagem</label>
                        <input type="text" id="f-img" name="img"
                            placeholder="https://..."
                            value="<?= htmlspecialchars($editar['img'] ?? '') ?>"
                            oninput="previewImg(this.value)">
                    </div>
                    <div class="img-preview-wrap">
                        <div class="img-preview" id="imgPreview">
                            <?php if (!empty($editar['img'])): ?>
                            <img src="<?= htmlspecialchars($editar['img']) ?>" alt="Preview" id="imgTag">
                            <?php else: ?>
                            <div class="img-preview-vazio" id="imgVazio">
                                <svg width="28" height="28" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                Preview da imagem
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Ativo -->
                <div class="campo-check">
                    <input type="checkbox" id="f-ativo" name="ativo"
                        <?= ($editar['ativo'] ?? true) ? 'checked' : '' ?>>
                    <label for="f-ativo">Produto ativo (visível no cardápio)</label>
                </div>

            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-cinza" onclick="fecharModal()">Cancelar</button>
                <button type="submit" class="btn btn-rosa">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    <?= $editar ? 'Salvar alterações' : 'Criar produto' ?>
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
        <h3>Excluir produto?</h3>
        <p id="confirmTxt">Esta ação não pode ser desfeita.</p>
        <div class="confirm-btns">
            <form method="POST" id="formExcluir" action="produtos.php?cat=<?= urlencode($filtro_cat) ?>&view=<?= $view_mode ?>">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="produto_id" id="excluirId" value="">
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

    // Fechar modal
    function fecharModal() {
        var url = new URL(window.location.href);
        url.searchParams.delete('novo');
        url.searchParams.delete('editar');
        window.location.href = url.toString();
    }

    // Confirmar exclusão
    function confirmarExclusao(id, nome) {
        document.getElementById('excluirId').value = id;
        document.getElementById('confirmTxt').textContent = 'O produto "' + nome + '" será removido permanentemente.';
        document.getElementById('confirmOverlay').style.display = 'flex';
    }

    // Preview imagem
    function previewImg(url) {
        var preview = document.getElementById('imgPreview');
        if (!url) {
            preview.innerHTML = '<div class="img-preview-vazio" id="imgVazio">'
                + '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e5e7eb" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>'
                + 'Preview da imagem</div>';
            return;
        }
        var img = new Image();
        img.onload = function() {
            preview.innerHTML = '<img src="' + url + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">';
        };
        img.onerror = function() {
            preview.innerHTML = '<div class="img-preview-vazio">URL inválida</div>';
        };
        img.src = url;
    }

    // ESC fecha modal e confirm
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharModal();
            document.getElementById('confirmOverlay').style.display = 'none';
        }
    });
</script>

</body>
</html>