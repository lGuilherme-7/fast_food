<?php
// admin/pages/produtos.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$mensagem = '';
$erro     = '';

// ── HELPER UPLOAD ─────────────────────────────────────────────
function processar_upload(string $campo, string $pasta): string {
    if (empty($_FILES[$campo]['tmp_name'])) return '';
    $f   = $_FILES[$campo];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) return '';
    if ($f['size'] > 5 * 1024 * 1024) return '';
    if (!is_dir($pasta)) mkdir($pasta, 0755, true);
    $nome = uniqid('img_', true) . '.' . $ext;
    return move_uploaded_file($f['tmp_name'], $pasta . '/' . $nome) ? '/uploads/' . $nome : '';
}
$upload_dir = __DIR__ . '/../../uploads';

// ── CATEGORIAS ────────────────────────────────────────────────
$categorias_lista = $pdo->query("SELECT id, nome FROM categorias WHERE ativo=1 ORDER BY ordem")->fetchAll();

// ── AÇÕES POST ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    if ($acao === 'novo' || $acao === 'editar') {
        $nome     = trim($_POST['nome']    ?? '');
        $preco    = (float)str_replace(',', '.', $_POST['preco']  ?? 0);
        $custo    = (float)str_replace(',', '.', $_POST['custo']  ?? 0);
        $cat_id   = (int)($_POST['cat_id']   ?? 0);
        $estoque  = (int)($_POST['estoque']  ?? 0);
        $estq_min = (int)($_POST['estq_min'] ?? 5);
        $desc     = trim($_POST['desc'] ?? '');
        $ativo    = isset($_POST['ativo']) ? 1 : 0;
        $img_upload = processar_upload('img_arquivo', $upload_dir);
        $img        = $img_upload ?: trim($_POST['img_url'] ?? '');

        if (empty($nome) || $preco <= 0 || $cat_id === 0) {
            $erro = 'Preencha nome, preço e categoria.';
        } elseif ($acao === 'novo') {
            $pdo->prepare("INSERT INTO produtos (categoria_id,nome,descricao,preco,custo,estoque,estoque_min,imagem_url,ativo) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$cat_id,$nome,$desc,$preco,$custo,$estoque,$estq_min,$img,$ativo]);
            $mensagem = 'Produto "' . $nome . '" criado com sucesso!';
        } else {
            $pid = (int)($_POST['produto_id'] ?? 0);
            if (empty($img)) $img = $pdo->query("SELECT imagem_url FROM produtos WHERE id=$pid")->fetchColumn();
            $pdo->prepare("UPDATE produtos SET categoria_id=?,nome=?,descricao=?,preco=?,custo=?,estoque=?,estoque_min=?,imagem_url=?,ativo=? WHERE id=?")
                ->execute([$cat_id,$nome,$desc,$preco,$custo,$estoque,$estq_min,$img,$ativo,$pid]);
            $mensagem = 'Produto "' . $nome . '" atualizado com sucesso!';
        }
    }

    if ($acao === 'toggle') {
        $pid = (int)($_POST['produto_id'] ?? 0);
        $pdo->prepare("UPDATE produtos SET ativo=NOT ativo WHERE id=?")->execute([$pid]);
        $novo = (int)$pdo->query("SELECT ativo FROM produtos WHERE id=$pid")->fetchColumn();
        $mensagem = 'Produto ' . ($novo ? 'ativado' : 'desativado') . '.';
    }

    if ($acao === 'excluir') {
        $pid  = (int)($_POST['produto_id'] ?? 0);
        $usou = (int)$pdo->query("SELECT COUNT(*) FROM pedido_itens WHERE produto_id=$pid")->fetchColumn();
        if ($usou > 0) {
            $pdo->prepare("UPDATE produtos SET ativo=0 WHERE id=?")->execute([$pid]);
            $mensagem = 'Produto desativado (possui pedidos vinculados).';
        } else {
            $pdo->prepare("DELETE FROM produtos WHERE id=?")->execute([$pid]);
            $mensagem = 'Produto removido com sucesso.';
        }
    }
}

// ── FILTROS ───────────────────────────────────────────────────
$filtro_cat   = $_GET['cat']  ?? 'todas';
$filtro_busca = trim($_GET['busca'] ?? '');
$view_mode    = $_GET['view'] ?? 'tabela';

$sql = "SELECT p.id,p.nome,p.descricao AS `desc`,p.preco,p.custo,p.estoque,p.estoque_min,p.imagem_url AS img,p.ativo,c.nome AS cat,c.id AS cat_id FROM produtos p JOIN categorias c ON c.id=p.categoria_id WHERE 1=1";
$params = [];
if ($filtro_cat !== 'todas') { $sql .= " AND c.nome=?";    $params[] = $filtro_cat; }
if ($filtro_busca !== '')    { $sql .= " AND p.nome LIKE ?"; $params[] = '%'.$filtro_busca.'%'; }
$sql .= " ORDER BY c.ordem, p.nome";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$lista = $stmt->fetchAll();

$stats = $pdo->query("SELECT COUNT(*) AS total,SUM(ativo=1) AS ativos,SUM(ativo=0) AS inativos,SUM(estoque=0) AS sem_estoque FROM produtos")->fetch();

$editar_id = (int)($_GET['editar'] ?? 0);
$editar    = null;
if ($editar_id > 0) {
    $stmt = $pdo->prepare("SELECT p.*,c.nome AS cat,c.id AS cat_id FROM produtos p JOIN categorias c ON c.id=p.categoria_id WHERE p.id=?");
    $stmt->execute([$editar_id]);
    $editar = $stmt->fetch() ?: null;
    if ($editar) $editar['img'] = $editar['imagem_url'];
}
$abrir_modal = !empty($_GET['novo']) || $editar !== null;
$admin_nome  = $_SESSION['admin_nome'] ?? 'Administrador';
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
    <style>
        .cards-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
        .prod-card-admin { background:var(--branco); border:1px solid var(--borda); border-radius:var(--r); overflow:hidden; transition:box-shadow .2s; }
        .prod-card-admin:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); }
        .prod-card-admin.inativo { opacity:.5; }
        .pca-img { height:120px; overflow:hidden; position:relative; }
        .pca-img img { width:100%; height:100%; object-fit:cover; }
        .pca-badge { position:absolute; top:8px; left:8px; }
        .pca-body { padding:12px 14px; }
        .pca-cat  { font-size:.7rem; font-weight:600; color:var(--rosa); text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
        .pca-nome { font-size:.88rem; font-weight:600; margin-bottom:8px; }
        .pca-linha { display:flex; justify-content:space-between; align-items:center; }
        .pca-preco { font-family:var(--f-titulo); color:var(--rosa); font-size:.95rem; font-weight:700; }
        .pca-estq  { font-size:.75rem; color:var(--cinza); }
        .pca-acoes { display:flex; border-top:1px solid var(--borda); }
        .pca-btn   { flex:1; padding:9px; background:none; border:none; font-family:var(--f-corpo); font-size:.78rem; font-weight:600; color:var(--cinza); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:5px; transition:background .15s,color .15s; text-decoration:none; }
        .pca-btn + .pca-btn { border-left:1px solid var(--borda); }
        .pca-btn:hover { background:var(--rosa-claro); color:var(--rosa); }
        .pca-btn.danger:hover { background:#fff5f5; color:#dc2626; }
        .pca-btn svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:2; }

        /* Upload */
        .upload-area { border:2px dashed var(--borda); border-radius:var(--r); padding:18px; text-align:center; cursor:pointer; transition:border-color .2s,background .2s; position:relative; }
        .upload-area:hover,.upload-area.drag { border-color:var(--rosa); background:var(--rosa-claro); }
        .upload-area input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
        .upload-area svg { width:24px; height:24px; stroke:var(--cinza); fill:none; stroke-width:1.5; margin-bottom:6px; }
        .upload-area p  { font-size:.82rem; color:var(--cinza); margin:0; }
        .upload-area strong { color:var(--rosa); }
        .upload-preview { width:100%; max-height:100px; object-fit:cover; border-radius:8px; margin-top:10px; display:none; }
        .upload-nome { font-size:.78rem; color:var(--rosa); font-weight:600; margin-top:6px; display:none; }
        .img-ou { text-align:center; font-size:.75rem; color:var(--cinza); margin:10px 0 8px; position:relative; }
        .img-ou::before,.img-ou::after { content:''; position:absolute; top:50%; width:42%; height:1px; background:var(--borda); }
        .img-ou::before { left:0; } .img-ou::after { right:0; }

        @media (max-width:1024px){ .cards-grid{ grid-template-columns:repeat(3,1fr); } }
        @media (max-width:760px) { .cards-grid{ grid-template-columns:repeat(2,1fr); } }
        @media (max-width:480px) { .cards-grid{ grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="admin-wrap">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo"><a href="../public/index.php">Sabor<span>&</span>Cia</a><p>Painel administrativo</p></div>
        <nav class="sidebar-nav">
            <div class="nav-label">Geral</div>
            <a href="dashboard.php" class="nav-item"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
            <a href="pedidos.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="11" y2="16"/></svg>Pedidos</a>
            <a href="vendas.php"    class="nav-item"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Vendas</a>
            <div class="nav-label">Catálogo</div>
            <a href="produtos.php"  class="nav-item ativo"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>Produtos</a>
            <a href="categorias.php"class="nav-item"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>Categorias</a>
            <a href="estoque.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>Estoque</a>
            <div class="nav-label">Clientes</div>
            <a href="clientes.php"  class="nav-item"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Clientes</a>
            <a href="cupons.php"    class="nav-item"><svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>Cupons</a>
            <div class="nav-label">Sistema</div>
            <a href="configuracoes.php" class="nav-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M12 2v2M4.93 4.93l1.41 1.41M2 12h2M4.93 19.07l1.41-1.41M12 20v2M19.07 19.07l-1.41-1.41M22 12h-2"/></svg>Configurações</a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar"><?= mb_strtoupper(mb_substr($admin_nome,0,2)) ?></div>
                <div class="user-info"><div class="user-nome"><?= htmlspecialchars($admin_nome) ?></div><div class="user-role">admin</div></div>
            </div>
            <a href="../public/index.php"><button class="btn-logout"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Sair do painel</button></a>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="topbar-esq">
                <button class="btn-menu" id="btnMenu"><svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
                <div><div class="topbar-titulo">Produtos</div><div class="topbar-breadcrumb">Catálogo — <?= (int)$stats['total'] ?> produtos cadastrados</div></div>
            </div>
            <div class="topbar-dir">
                <a href="../../public/index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>Ver site</a>
                <a href="produtos.php?novo=1&cat=<?= urlencode($filtro_cat) ?>&view=<?= $view_mode ?>" class="btn-novo"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Novo produto</a>
            </div>
        </div>

        <div class="conteudo">
            <?php if ($mensagem): ?><div class="alerta alerta-ok"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
            <?php if ($erro):     ?><div class="alerta alerta-err"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?= htmlspecialchars($erro) ?></div><?php endif; ?>

            <div class="stats-grid">
                <div class="stat"><div class="stat-val"><?= (int)$stats['total'] ?></div><div class="stat-lbl">Total de produtos</div></div>
                <div class="stat"><div class="stat-val"><?= (int)$stats['ativos'] ?></div><div class="stat-lbl">Ativos no cardápio</div></div>
                <div class="stat"><div class="stat-val"><?= (int)$stats['inativos'] ?></div><div class="stat-lbl">Desativados</div></div>
                <div class="stat"><div class="stat-val" style="color:<?= (int)$stats['sem_estoque']>0?'#ef4444':'var(--escuro)' ?>"><?= (int)$stats['sem_estoque'] ?></div><div class="stat-lbl">Sem estoque</div></div>
            </div>

            <div class="toolbar">
                <div class="toolbar-esq">
                    <a href="produtos.php?cat=todas&view=<?= $view_mode ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>" class="tab-cat <?= $filtro_cat==='todas'?'ativo':'' ?>">Todas</a>
                    <?php foreach ($categorias_lista as $c): ?>
                    <a href="produtos.php?cat=<?= urlencode($c['nome']) ?>&view=<?= $view_mode ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>" class="tab-cat <?= $filtro_cat===$c['nome']?'ativo':'' ?>"><?= htmlspecialchars($c['nome']) ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="toolbar-dir">
                    <form method="GET" action="produtos.php" class="busca-wrap">
                        <?php if ($filtro_cat!=='todas'): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($filtro_cat) ?>"><?php endif; ?>
                        <input type="hidden" name="view" value="<?= $view_mode ?>">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="busca" class="busca-input" placeholder="Buscar produto..." value="<?= htmlspecialchars($filtro_busca) ?>" autocomplete="off">
                    </form>
                    <div class="view-btns">
                        <a href="produtos.php?cat=<?= urlencode($filtro_cat) ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>&view=tabela"><button class="view-btn <?= $view_mode==='tabela'?'ativo':'' ?>" title="Tabela"><svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></button></a>
                        <a href="produtos.php?cat=<?= urlencode($filtro_cat) ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>&view=cards"><button class="view-btn <?= $view_mode==='cards'?'ativo':'' ?>" title="Cards"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></button></a>
                    </div>
                </div>
            </div>

            <p style="font-size:.82rem;color:var(--cinza);margin-bottom:14px;"><strong style="color:var(--escuro)"><?= count($lista) ?></strong> produto<?= count($lista)!==1?'s':'' ?> encontrado<?= count($lista)!==1?'s':'' ?></p>

            <?php if ($view_mode === 'tabela'): ?>
            <div class="card"><table class="tabela">
                <thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Ativo</th><th>Ações</th></tr></thead>
                <tbody>
                <?php if (empty($lista)): ?>
                <tr><td colspan="7"><div class="tabela-vazio"><svg width="36" height="36" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>Nenhum produto encontrado.</div></td></tr>
                <?php else: foreach ($lista as $p):
                    $estq=$p['estoque']; $cls=$estq===0?'zero':($estq<=($p['estoque_min']??5)?'baixo':'ok');
                    $lbl=$estq===0?'Sem estoque':($cls==='baixo'?'Estoque baixo':'Em estoque'); ?>
                <tr>
                    <td><div style="display:flex;align-items:center;gap:12px;"><div class="prod-thumb"><img src="<?= htmlspecialchars($p['img']??'') ?>" alt="<?= htmlspecialchars($p['nome']) ?>"></div><div><div style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($p['nome']) ?></div><div class="prod-id">#<?= $p['id'] ?></div></div></div></td>
                    <td><span class="badge" style="background:var(--rosa-claro);color:var(--rosa);"><?= htmlspecialchars($p['cat']) ?></span></td>
                    <td style="font-family:var(--f-titulo);font-size:.95rem;color:var(--rosa);font-weight:700;">R$ <?= number_format($p['preco'],2,',','.') ?></td>
                    <td><div class="estoque-num <?= $cls ?>"><?= $estq ?> un.</div><div style="font-size:.72rem;color:var(--cinza)"><?= $lbl ?></div></td>
                    <td><?php if($estq===0):?><span class="badge badge-sem-estq">Sem estoque</span><?php elseif($p['ativo']):?><span class="badge badge-ativo">Ativo</span><?php else:?><span class="badge badge-inativo">Inativo</span><?php endif;?></td>
                    <td><form method="POST" style="display:inline"><input type="hidden" name="acao" value="toggle"><input type="hidden" name="produto_id" value="<?= $p['id'] ?>"><div class="toggle-wrap"><input type="checkbox" class="toggle-inp" id="tog<?= $p['id'] ?>" <?= $p['ativo']?'checked':'' ?> onchange="this.form.submit()"><label class="toggle-label" for="tog<?= $p['id'] ?>"></label></div></form></td>
                    <td><div class="acoes">
                        <a href="produtos.php?editar=<?= $p['id'] ?>&cat=<?= urlencode($filtro_cat) ?>&view=<?= $view_mode ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>"><button class="btn-acao" title="Editar"><svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button></a>
                        <a href="../../public/produto.php?id=<?= $p['id'] ?>" target="_blank"><button class="btn-acao" title="Ver no site"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></a>
                        <button class="btn-acao danger" title="Excluir" onclick="confirmarExclusao(<?= $p['id'] ?>,'<?= addslashes(htmlspecialchars($p['nome'])) ?>')"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg></button>
                    </div></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table></div>

            <?php else: ?>
            <?php if (empty($lista)): ?>
            <div class="tabela-vazio"><svg width="36" height="36" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>Nenhum produto encontrado.</div>
            <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($lista as $p): ?>
                <div class="prod-card-admin <?= !$p['ativo']?'inativo':'' ?>">
                    <div class="pca-img"><img src="<?= htmlspecialchars($p['img']??'') ?>" alt="<?= htmlspecialchars($p['nome']) ?>"><div class="pca-badge"><?php if((int)$p['estoque']===0):?><span class="badge badge-sem-estq">Sem estoque</span><?php elseif(!$p['ativo']):?><span class="badge badge-inativo">Inativo</span><?php endif;?></div></div>
                    <div class="pca-body"><div class="pca-cat"><?= htmlspecialchars($p['cat']) ?></div><div class="pca-nome"><?= htmlspecialchars($p['nome']) ?></div><div class="pca-linha"><div class="pca-preco">R$ <?= number_format($p['preco'],2,',','.') ?></div><div class="pca-estq"><?= (int)$p['estoque'] ?> un.</div></div></div>
                    <div class="pca-acoes">
                        <a href="produtos.php?editar=<?= $p['id'] ?>&view=cards&cat=<?= urlencode($filtro_cat) ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>" class="pca-btn"><svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Editar</a>
                        <button class="pca-btn danger" onclick="confirmarExclusao(<?= $p['id'] ?>,'<?= addslashes(htmlspecialchars($p['nome'])) ?>')"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>Excluir</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; endif; ?>
        </div>
    </div>
</div>

<?php if ($abrir_modal): ?>
<div class="modal-overlay" id="modalProduto">
    <div class="modal">
        <div class="modal-head">
            <h2><?= $editar ? 'Editar produto' : 'Novo produto' ?></h2>
            <button class="modal-close" onclick="fecharModal()">&#x2715;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" action="produtos.php?cat=<?= urlencode($filtro_cat) ?>&view=<?= $view_mode ?><?= $filtro_busca?'&busca='.urlencode($filtro_busca):'' ?>">
            <input type="hidden" name="acao" value="<?= $editar?'editar':'novo' ?>">
            <?php if ($editar): ?><input type="hidden" name="produto_id" value="<?= $editar['id'] ?>"><?php endif; ?>

            <div class="modal-body">
                <div class="campo-full"><div class="campo">
                    <label>Nome do produto *</label>
                    <input type="text" name="nome" placeholder="Ex: Açaí Premium 500ml" value="<?= htmlspecialchars($editar['nome']??'') ?>" required>
                </div></div>

                <div class="campo-grid-3">
                    <div class="campo"><label>Preço (R$) *</label><input type="text" name="preco" placeholder="0,00" value="<?= $editar?number_format($editar['preco'],2,',','.'): '' ?>" required></div>
                    <div class="campo"><label>Categoria *</label>
                        <select name="cat_id" required><option value="">Selecione</option>
                        <?php foreach($categorias_lista as $c): ?><option value="<?= $c['id'] ?>" <?= (int)($editar['cat_id']??0)===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo"><label>Estoque</label><input type="number" name="estoque" min="0" placeholder="0" value="<?= (int)($editar['estoque']??0) ?>"></div>
                </div>

                <div class="campo-grid-3">
                    <div class="campo"><label>Custo (R$)</label><input type="text" name="custo" placeholder="0,00" value="<?= $editar?number_format($editar['custo']??0,2,',','.'):'' ?>"></div>
                    <div class="campo"><label>Estoque mínimo</label><input type="number" name="estq_min" min="0" placeholder="5" value="<?= (int)($editar['estoque_min']??5) ?>"></div>
                    <div class="campo"></div>
                </div>

                <div class="campo-full"><div class="campo">
                    <label>Descrição curta</label>
                    <textarea name="desc" placeholder="Descreva o produto brevemente..."><?= htmlspecialchars($editar['descricao']??$editar['desc']??'') ?></textarea>
                </div></div>

                <!-- IMAGEM -->
                <div class="campo-full"><div class="campo">
                    <label>Imagem do produto</label>
                    <div class="upload-area" id="uploadArea">
                        <input type="file" id="imgArquivo" name="img_arquivo" accept="image/jpeg,image/png,image/webp,image/gif" onchange="onArquivoSelecionado(this)">
                        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <p><strong>Clique</strong> ou arraste uma imagem</p>
                        <p style="font-size:.72rem;margin-top:2px">JPG, PNG, WEBP — máx. 5 MB</p>
                        <?php if (!empty($editar['img'])): ?>
                        <img src="<?= htmlspecialchars($editar['img']) ?>" id="uploadPreview" class="upload-preview" style="display:block" alt="">
                        <?php else: ?>
                        <img src="" id="uploadPreview" class="upload-preview" alt="">
                        <?php endif; ?>
                        <div class="upload-nome" id="uploadNome"></div>
                    </div>
                    <div class="img-ou">ou cole uma URL</div>
                    <input type="text" id="fImgUrl" name="img_url" placeholder="https://..." value="<?= htmlspecialchars($editar['img']??'') ?>" oninput="onUrlDigitada(this.value)">
                </div></div>

                <div class="campo-check">
                    <input type="checkbox" id="f-ativo" name="ativo" <?= ($editar['ativo']??1)?'checked':'' ?>>
                    <label for="f-ativo">Produto ativo (visível no cardápio)</label>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-cinza" onclick="fecharModal()">Cancelar</button>
                <button type="submit" class="btn btn-rosa"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><?= $editar?'Salvar alterações':'Criar produto' ?></button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="confirm-overlay" id="confirmOverlay" style="display:none">
    <div class="confirm-box">
        <div class="confirm-icon"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg></div>
        <h3>Excluir produto?</h3><p id="confirmTxt">Esta ação não pode ser desfeita.</p>
        <div class="confirm-btns">
            <form method="POST" id="formExcluir" action="produtos.php?cat=<?= urlencode($filtro_cat) ?>&view=<?= $view_mode ?>">
                <input type="hidden" name="acao" value="excluir"><input type="hidden" name="produto_id" id="excluirId" value="">
                <button type="submit" class="btn btn-danger">Sim, excluir</button>
            </form>
            <button class="btn btn-cinza" onclick="document.getElementById('confirmOverlay').style.display='none'">Cancelar</button>
        </div>
    </div>
</div>

<div id="overlayMobile" onclick="fecharMenu()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199;"></div>

<script>
document.getElementById('btnMenu').addEventListener('click',function(){
    document.getElementById('sidebar').classList.toggle('aberta');
    document.getElementById('overlayMobile').style.display=document.getElementById('sidebar').classList.contains('aberta')?'block':'none';
});
function fecharMenu(){document.getElementById('sidebar').classList.remove('aberta');document.getElementById('overlayMobile').style.display='none';}
function fecharModal(){var u=new URL(window.location.href);u.searchParams.delete('novo');u.searchParams.delete('editar');window.location.href=u.toString();}
function confirmarExclusao(id,nome){document.getElementById('excluirId').value=id;document.getElementById('confirmTxt').textContent='O produto "'+nome+'" será removido permanentemente.';document.getElementById('confirmOverlay').style.display='flex';}

function onArquivoSelecionado(input){
    if(!input.files||!input.files[0])return;
    var r=new FileReader();
    r.onload=function(e){var p=document.getElementById('uploadPreview');p.src=e.target.result;p.style.display='block';};
    r.readAsDataURL(input.files[0]);
    document.getElementById('uploadNome').textContent=input.files[0].name;
    document.getElementById('uploadNome').style.display='block';
    document.getElementById('fImgUrl').value='';
}
function onUrlDigitada(url){
    var p=document.getElementById('uploadPreview'),n=document.getElementById('uploadNome'),a=document.getElementById('imgArquivo');
    if(!url){p.style.display='none';return;}
    a.value='';n.style.display='none';
    var i=new Image();i.onload=function(){p.src=url;p.style.display='block';};i.onerror=function(){p.style.display='none';};i.src=url;
}
var area=document.getElementById('uploadArea');
if(area){
    area.addEventListener('dragover',function(e){e.preventDefault();this.classList.add('drag');});
    area.addEventListener('dragleave',function(){this.classList.remove('drag');});
    area.addEventListener('drop',function(e){
        e.preventDefault();this.classList.remove('drag');
        var f=e.dataTransfer.files[0];if(!f)return;
        var dt=new DataTransfer();dt.items.add(f);
        var inp=document.getElementById('imgArquivo');inp.files=dt.files;
        onArquivoSelecionado(inp);
    });
}
document.addEventListener('keydown',function(e){if(e.key==='Escape'){fecharModal();document.getElementById('confirmOverlay').style.display='none';}});
</script>
</body>
</html>