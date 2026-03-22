<?php
// admin/pages/categorias.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$mensagem = '';
$erro     = '';

// ── HELPERS ──────────────────────────────────────────────────
function gerar_slug(string $texto): string {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    return trim(preg_replace('/[\s-]+/', '-', $texto), '-');
}

// ── AÇÕES POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // CRIAR / EDITAR
    if ($acao === 'nova' || $acao === 'editar') {
        $nome  = trim($_POST['nome']      ?? '');
        $slug  = trim($_POST['slug']      ?? '');
        $desc  = trim($_POST['descricao'] ?? '');
        $cor   = trim($_POST['cor']       ?? '#f43f7a');
        $img   = trim($_POST['img']       ?? '');
        $ordem = (int)($_POST['ordem']    ?? 99);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (empty($slug)) $slug = gerar_slug($nome);

        if (empty($nome)) {
            $erro = 'O nome da categoria é obrigatório.';
        } else {
            if ($acao === 'nova') {
                // Verifica slug duplicado
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetchColumn() > 0) $slug .= '-' . time();

                $stmt = $pdo->prepare("
                    INSERT INTO categorias (nome, slug, descricao, cor, imagem_url, ordem, ativo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nome, $slug, $desc, $cor, $img, $ordem, $ativo]);
                $mensagem = 'Categoria "' . $nome . '" criada com sucesso!';
            } else {
                $cid = (int)($_POST['cat_id'] ?? 0);
                $stmt = $pdo->prepare("
                    UPDATE categorias
                    SET nome = ?, slug = ?, descricao = ?, cor = ?,
                        imagem_url = ?, ordem = ?, ativo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nome, $slug, $desc, $cor, $img, $ordem, $ativo, $cid]);
                $mensagem = 'Categoria "' . $nome . '" atualizada com sucesso!';
            }
        }
    }

    // TOGGLE ATIVO
    if ($acao === 'toggle') {
        $cid = (int)($_POST['cat_id'] ?? 0);
        $pdo->prepare("UPDATE categorias SET ativo = NOT ativo WHERE id = ?")->execute([$cid]);
        $novo = (int)$pdo->query("SELECT ativo FROM categorias WHERE id = $cid")->fetchColumn();
        $mensagem = 'Categoria ' . ($novo ? 'ativada' : 'desativada') . '.';
    }

    // EXCLUIR
    if ($acao === 'excluir') {
        $cid = (int)($_POST['cat_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT nome FROM categorias WHERE id = ?");
        $stmt->execute([$cid]);
        $cat_nome = $stmt->fetchColumn() ?: '';

        // Conta produtos vinculados
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE categoria_id = ?");
        $stmt->execute([$cid]);
        $tem_produtos = (int)$stmt->fetchColumn();

        if ($tem_produtos > 0) {
            $erro = 'Não é possível excluir "' . $cat_nome . '" pois possui ' . $tem_produtos . ' produto(s) vinculado(s).';
        } else {
            $pdo->prepare("DELETE FROM categorias WHERE id = ?")->execute([$cid]);
            $mensagem = 'Categoria "' . $cat_nome . '" removida.';
        }
    }

    // REORDENAR (drag & drop)
    if ($acao === 'reordenar') {
        $nova_ordem = json_decode($_POST['ordem'] ?? '[]', true);
        if (is_array($nova_ordem)) {
            $stmt = $pdo->prepare("UPDATE categorias SET ordem = ? WHERE id = ?");
            foreach ($nova_ordem as $pos => $id) {
                $stmt->execute([$pos + 1, (int)$id]);
            }
            $mensagem = 'Ordem das categorias atualizada.';
        }
    }
}

// ── BUSCAR CATEGORIAS COM CONTAGEM DE PRODUTOS ───────────────
$stmt = $pdo->query("
    SELECT
        c.id, c.nome, c.slug, c.descricao, c.cor,
        c.imagem_url AS img, c.ativo, c.ordem,
        COUNT(p.id) AS produtos
    FROM categorias c
    LEFT JOIN produtos p ON p.categoria_id = c.id
    GROUP BY c.id
    ORDER BY c.ordem ASC, c.nome ASC
");
$categorias = $stmt->fetchAll();

// ── STATS ────────────────────────────────────────────────────
$total_ativas   = array_sum(array_column($categorias, 'ativo'));
$total_produtos = array_sum(array_column($categorias, 'produtos'));

// ── EDIÇÃO ───────────────────────────────────────────────────
$editar_id = (int)($_GET['editar'] ?? 0);
$editar    = null;
if ($editar_id > 0) {
    foreach ($categorias as $c) {
        if ((int)$c['id'] === $editar_id) { $editar = $c; break; }
    }
}
$abrir_modal = !empty($_GET['nova']) || $editar !== null;

$admin_nome = $_SESSION['admin_nome'] ?? 'Administrador';

// Cores pré-definidas para o picker
$cores_preset = [
    '#f43f7a','#7c3aed','#0ea5e9','#10b981',
    '#f59e0b','#ef4444','#ec4899','#6366f1',
    '#14b8a6','#f97316','#8b5cf6','#06b6d4',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias — Sabor&Cia Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <style>
        /* Estilos específicos desta página */
        .cats-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px; }

        .cat-card {
            background:var(--branco); border:1px solid var(--borda);
            border-radius:var(--r); overflow:hidden;
            border-left:4px solid var(--rosa);
            cursor:grab; transition:box-shadow .2s, opacity .2s;
            position:relative;
        }
        .cat-card.inativa { opacity:.5; }
        .cat-card.dragging { opacity:.4; box-shadow:0 8px 24px rgba(0,0,0,.15); cursor:grabbing; }
        .cat-card.drag-over { box-shadow:0 0 0 2px var(--rosa); }

        .cat-card-head { display:flex; align-items:center; gap:12px; padding:16px; }
        .cat-card-img  { width:48px; height:48px; border-radius:50%; overflow:hidden; flex-shrink:0; background:var(--rosa-claro); }
        .cat-card-img img { width:100%; height:100%; object-fit:cover; }
        .cat-card-info { flex:1; min-width:0; }
        .cat-card-nome { font-weight:700; font-size:.92rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .cat-card-slug { font-size:.72rem; color:var(--cinza); margin-top:2px; font-family:monospace; }

        .cat-card-body { padding:0 16px 14px; }
        .cat-card-desc { font-size:.8rem; color:var(--cinza); line-height:1.5; margin-bottom:12px; min-height:36px; }
        .cat-card-meta { display:flex; align-items:center; justify-content:space-between; }
        .cat-meta-prod { font-size:.78rem; color:var(--cinza); display:flex; align-items:center; gap:4px; }
        .cat-meta-prod svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:2; }
        .cat-meta-badge { font-size:.7rem; font-weight:600; padding:2px 8px; border-radius:50px; }

        .cat-card-foot { display:flex; border-top:1px solid var(--borda); }
        .cat-foot-btn  { flex:1; padding:9px; background:none; border:none; font-family:var(--f-corpo); font-size:.78rem; font-weight:600; color:var(--cinza); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:5px; transition:background .15s, color .15s; text-decoration:none; }
        .cat-foot-btn + .cat-foot-btn { border-left:1px solid var(--borda); }
        .cat-foot-btn:hover { background:var(--rosa-claro); color:var(--rosa); }
        .cat-foot-btn.danger:hover { background:#fff5f5; color:#dc2626; }
        .cat-foot-btn svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:2; }

        /* drag handle */
        .drag-handle { position:absolute; top:10px; right:10px; cursor:grab; color:var(--borda); }
        .drag-handle svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; }

        /* Btn salvar ordem */
        .btn-salvar-ordem {
            display:none; background:var(--rosa); color:#fff;
            border:none; border-radius:8px; padding:9px 18px;
            font-family:var(--f-corpo); font-size:.85rem; font-weight:600;
            cursor:pointer; transition:opacity .2s;
        }
        .btn-salvar-ordem:hover { opacity:.85; }

        /* Cores picker */
        .cores-wrap { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px; }
        .cor-op {
            width:26px; height:26px; border-radius:50%; cursor:pointer;
            border:2px solid transparent; transition:transform .15s, border-color .15s;
        }
        .cor-op:hover  { transform:scale(1.15); }
        .cor-op.sel    { border-color: var(--escuro); transform:scale(1.15); }
        .cor-input-row { display:flex; gap:8px; align-items:center; }
        .cor-input-row input[type="color"] { width:36px; height:36px; border-radius:8px; border:1px solid var(--borda); padding:2px; cursor:pointer; }
        .cor-input-row input[type="text"]  { flex:1; }

        @media (max-width:640px) { .cats-grid { grid-template-columns:1fr; } }
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
            <a href="categorias.php" class="nav-item ativo"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>Categorias</a>
            <a href="estoque.php"    class="nav-item"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>Estoque</a>
            <div class="nav-label">Clientes</div>
            <a href="clientes.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Clientes</a>
            <a href="cupons.php"     class="nav-item"><svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>Cupons</a>
            <div class="nav-label">Sistema</div>
            <a href="configuracoes.php" class="nav-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M12 2v2M4.93 4.93l1.41 1.41M2 12h2M4.93 19.07l1.41-1.41M12 20v2M19.07 19.07l-1.41-1.41M22 12h-2"/></svg>Configurações</a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar"><?= mb_strtoupper(mb_substr($admin_nome, 0, 2)) ?></div>
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
                    <div class="topbar-titulo">Categorias</div>
                    <div class="topbar-breadcrumb"><?= count($categorias) ?> categorias cadastradas</div>
                </div>
            </div>
            <div class="topbar-dir">
                <button class="btn-salvar-ordem" id="btnSalvarOrdem" onclick="salvarOrdem()">
                    Salvar nova ordem
                </button>
                <a href="../../public/index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ver site
                </a>
                <a href="categorias.php?nova=1" class="btn-novo">
                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nova categoria
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

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-val"><?= count($categorias) ?></div>
                    <div class="stat-lbl">Total de categorias</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= $total_ativas ?></div>
                    <div class="stat-lbl">Ativas no site</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= count($categorias) - $total_ativas ?></div>
                    <div class="stat-lbl">Desativadas</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= $total_produtos ?></div>
                    <div class="stat-lbl">Produtos no total</div>
                </div>
            </div>

            <p style="font-size:.82rem;color:var(--cinza);margin-bottom:16px;">
                Arraste os cards para reordenar como aparecem no cardápio público.
            </p>

            <!-- GRID DE CARDS -->
            <?php if (empty($categorias)): ?>
            <div class="tabela-vazio">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--borda)" stroke-width="1.5"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                Nenhuma categoria cadastrada ainda.
                <a href="categorias.php?nova=1" style="color:var(--rosa);font-weight:600;margin-top:8px;display:block;">Criar a primeira categoria</a>
            </div>
            <?php else: ?>
            <div class="cats-grid" id="catsGrid">
                <?php foreach ($categorias as $c):
                    $cor        = htmlspecialchars($c['cor'] ?: '#f43f7a');
                    $n_produtos = (int)$c['produtos'];
                ?>
                <div class="cat-card <?= !$c['ativo'] ? 'inativa' : '' ?>"
                     data-id="<?= $c['id'] ?>"
                     draggable="true"
                     style="border-left-color:<?= $cor ?>">

                    <div class="drag-handle" title="Arrastar para reordenar">
                        <svg viewBox="0 0 24 24"><circle cx="9" cy="5" r="1" fill="currentColor"/><circle cx="9" cy="12" r="1" fill="currentColor"/><circle cx="9" cy="19" r="1" fill="currentColor"/><circle cx="15" cy="5" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="15" cy="19" r="1" fill="currentColor"/></svg>
                    </div>

                    <div class="cat-card-head">
                        <div class="cat-card-img">
                            <?php if (!empty($c['img'])): ?>
                            <img src="<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['nome']) ?>">
                            <?php else: ?>
                            <div style="width:100%;height:100%;background:<?= $cor ?>22;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;color:<?= $cor ?>">
                                <?= mb_strtoupper(mb_substr($c['nome'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="cat-card-info">
                            <div class="cat-card-nome"><?= htmlspecialchars($c['nome']) ?></div>
                            <div class="cat-card-slug">/<?= htmlspecialchars($c['slug']) ?></div>
                        </div>
                    </div>

                    <div class="cat-card-body">
                        <div class="cat-card-desc">
                            <?= !empty($c['descricao']) ? htmlspecialchars($c['descricao']) : '<em style="color:var(--borda)">Sem descrição</em>' ?>
                        </div>
                        <div class="cat-card-meta">
                            <div class="cat-meta-prod">
                                <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                                <?= $n_produtos ?> produto<?= $n_produtos !== 1 ? 's' : '' ?>
                            </div>
                            <?php if ($c['ativo']): ?>
                            <span class="cat-meta-badge" style="background:<?= $cor ?>22;color:<?= $cor ?>">Ativa</span>
                            <?php else: ?>
                            <span class="cat-meta-badge" style="background:#f3f4f6;color:#9ca3af">Inativa</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="cat-card-foot">
                        <a href="categorias.php?editar=<?= $c['id'] ?>" class="cat-foot-btn">
                            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Editar
                        </a>
                        <form method="POST" style="flex:1;display:flex">
                            <input type="hidden" name="acao" value="toggle">
                            <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                            <button type="submit" class="cat-foot-btn" style="width:100%">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><?= $c['ativo'] ? '<line x1="8" y1="12" x2="16" y2="12"/>' : '<line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>' ?></svg>
                                <?= $c['ativo'] ? 'Desativar' : 'Ativar' ?>
                            </button>
                        </form>
                        <button class="cat-foot-btn danger"
                            onclick="confirmarExclusao(<?= $c['id'] ?>, '<?= addslashes(htmlspecialchars($c['nome'])) ?>', <?= $n_produtos ?>)">
                            <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>
                            Excluir
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div><!-- /conteudo -->
    </div><!-- /main -->
</div><!-- /admin-wrap -->

<!-- ── MODAL NOVA / EDITAR ────────────────────────────────── -->
<?php if ($abrir_modal):
    $cor_atual = $editar['cor'] ?? '#f43f7a';
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
                <div class="campo">
                    <label for="f-nome">Nome da categoria *</label>
                    <input type="text" id="f-nome" name="nome"
                        placeholder="Ex: Açaí, Hambúrguer..."
                        value="<?= htmlspecialchars($editar['nome'] ?? '') ?>"
                        oninput="gerarSlug(this.value)"
                        required>
                </div>

                <!-- Slug -->
                <div class="campo">
                    <label for="f-slug">Slug (URL)</label>
                    <input type="text" id="f-slug" name="slug"
                        placeholder="gerado-automaticamente"
                        value="<?= htmlspecialchars($editar['slug'] ?? '') ?>">
                    <span style="font-size:.74rem;color:var(--cinza);margin-top:3px;display:block">Usado na URL do site. Ex: /cardapio/<strong>acai</strong></span>
                </div>

                <!-- Descrição -->
                <div class="campo">
                    <label for="f-desc">Descrição</label>
                    <textarea id="f-desc" name="descricao"
                        placeholder="Descreva brevemente esta categoria..."
                        rows="2"><?= htmlspecialchars($editar['descricao'] ?? '') ?></textarea>
                </div>

                <!-- Cor -->
                <div class="campo">
                    <label>Cor da categoria</label>
                    <div class="cores-wrap">
                        <?php foreach ($cores_preset as $cor_p): ?>
                        <div class="cor-op <?= $cor_atual === $cor_p ? 'sel' : '' ?>"
                             style="background:<?= $cor_p ?>"
                             title="<?= $cor_p ?>"
                             onclick="escolherCor('<?= $cor_p ?>')"></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="cor-input-row">
                        <input type="color" id="f-cor-picker"
                            value="<?= htmlspecialchars($cor_atual) ?>"
                            oninput="sincronizarCor(this.value);document.getElementById('f-cor-hex').value=this.value">
                        <input type="text" id="f-cor-hex" name="cor"
                            value="<?= htmlspecialchars($cor_atual) ?>"
                            placeholder="#f43f7a"
                            oninput="sincronizarCor(this.value);document.getElementById('f-cor-picker').value=this.value"
                            style="font-family:monospace">
                    </div>
                </div>

                <!-- Imagem + Ordem numa linha -->
                <div style="display:grid;grid-template-columns:1fr 100px;gap:14px;align-items:start">
                    <div class="campo">
                        <label for="f-img">URL da imagem</label>
                        <input type="text" id="f-img" name="img"
                            placeholder="https://..."
                            value="<?= htmlspecialchars($editar['img'] ?? '') ?>"
                            oninput="previewImg(this.value)">
                    </div>
                    <div class="campo">
                        <label for="f-ordem">Ordem</label>
                        <input type="number" id="f-ordem" name="ordem"
                            min="1" placeholder="1"
                            value="<?= (int)($editar['ordem'] ?? count($categorias) + 1) ?>">
                    </div>
                </div>

                <!-- Preview imagem -->
                <div class="img-preview-wrap" style="margin-top:0">
                    <div class="img-preview" id="imgPreview" style="height:80px">
                        <?php if (!empty($editar['img'])): ?>
                        <img src="<?= htmlspecialchars($editar['img']) ?>" alt="Preview">
                        <?php else: ?>
                        <div class="img-preview-vazio" style="font-size:.75rem">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e5e7eb" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            Preview
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ativo -->
                <div class="campo-check">
                    <input type="checkbox" id="f-ativo" name="ativo"
                        <?= ($editar['ativo'] ?? 1) ? 'checked' : '' ?>>
                    <label for="f-ativo">Categoria ativa (visível no site)</label>
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

<!-- ── CONFIRM EXCLUIR ────────────────────────────────────── -->
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
            <button class="btn btn-cinza"
                onclick="document.getElementById('confirmOverlay').style.display='none'">Cancelar</button>
        </div>
    </div>
</div>

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
        var btn = document.getElementById('btnConfirmarExcluir');
        if (produtos > 0) {
            document.getElementById('confirmTxt').textContent =
                'A categoria "' + nome + '" possui ' + produtos + ' produto(s) vinculado(s) e não pode ser excluída. Reatribua os produtos primeiro.';
            btn.style.display = 'none';
        } else {
            document.getElementById('confirmTxt').textContent =
                'A categoria "' + nome + '" será removida permanentemente.';
            btn.style.display = '';
        }
        document.getElementById('confirmOverlay').style.display = 'flex';
    }

    // Gerar slug automático
    function gerarSlug(nome) {
        var slugInput = document.getElementById('f-slug');
        if (!slugInput || slugInput.dataset.manual === 'true') return;
        slugInput.value = nome.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim().replace(/\s+/g, '-');
    }
    var slugInput = document.getElementById('f-slug');
    if (slugInput) {
        slugInput.addEventListener('input', function() { this.dataset.manual = 'true'; });
    }

    // Cores
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
            preview.innerHTML = '<div class="img-preview-vazio" style="font-size:.75rem"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e5e7eb" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>Preview</div>';
            return;
        }
        var img = new Image();
        img.onload  = function() { preview.innerHTML = '<img src="'+url+'" alt="Preview" style="width:100%;height:100%;object-fit:cover;">'; };
        img.onerror = function() { preview.innerHTML = '<div class="img-preview-vazio" style="font-size:.75rem">URL inválida</div>'; };
        img.src = url;
    }

    // ── DRAG & DROP PARA REORDENAR ──────────────────────────
    var dragSrc = null;

    function initDrag() {
        document.querySelectorAll('.cat-card').forEach(function(card) {
            card.addEventListener('dragstart', function(e) {
                dragSrc = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            card.addEventListener('dragend', function() {
                this.classList.remove('dragging');
                document.querySelectorAll('.cat-card').forEach(function(c) { c.classList.remove('drag-over'); });
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
                    var grid  = document.getElementById('catsGrid');
                    var cards = Array.from(grid.querySelectorAll('.cat-card'));
                    var srcIdx  = cards.indexOf(dragSrc);
                    var destIdx = cards.indexOf(this);
                    if (srcIdx < destIdx) { grid.insertBefore(dragSrc, this.nextSibling); }
                    else                  { grid.insertBefore(dragSrc, this); }
                    this.classList.remove('drag-over');
                    document.getElementById('btnSalvarOrdem').style.display = '';
                }
            });
        });
    }
    initDrag();

    // Salvar nova ordem via POST
    function salvarOrdem() {
        var ids  = Array.from(document.querySelectorAll('#catsGrid .cat-card')).map(function(c) { return parseInt(c.dataset.id); });
        var form = document.createElement('form');
        form.method = 'POST'; form.action = 'categorias.php';
        var i1 = document.createElement('input'); i1.type='hidden'; i1.name='acao';  i1.value='reordenar';
        var i2 = document.createElement('input'); i2.type='hidden'; i2.name='ordem'; i2.value=JSON.stringify(ids);
        form.appendChild(i1); form.appendChild(i2);
        document.body.appendChild(form); form.submit();
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