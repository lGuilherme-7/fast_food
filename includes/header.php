<?php
// includes/header.php
// Topbar do painel admin
// Variáveis esperadas: $page_titulo (string), $page_sub (string, opcional)
// Uso: require_once __DIR__ . '/../../includes/header.php';

$admin_nome  = $_SESSION['admin_nome']  ?? 'Administrador';
$_page_titulo = $page_titulo ?? 'Painel';
$_page_sub    = $page_sub    ?? '';
?>
<div class="topbar">
    <div class="topbar-esq">
        <button class="btn-menu" id="btnMenu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="6"  x2="21" y2="6"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <div>
            <div class="topbar-titulo"><?= htmlspecialchars($_page_titulo) ?></div>
            <?php if ($_page_sub): ?>
            <div class="topbar-breadcrumb"><?= htmlspecialchars($_page_sub) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="topbar-dir">
        <a href="../../public/index.php" target="_blank"
           style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;text-decoration:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                <polyline points="15 3 21 3 21 9"/>
                <line x1="10" y1="14" x2="21" y2="3"/>
            </svg>
            Ver site
        </a>
    </div>
</div>