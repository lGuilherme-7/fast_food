<?php
// public/produtos.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';

$logado       = isset($_SESSION['cliente_id']);
$cliente_nome = $logado ? explode(' ', $_SESSION['cliente_nome'])[0] : '';

$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave IN ('loja_nome','loja_whatsapp','entrega_taxa','entrega_gratis')");
$cfg  = [];
foreach ($stmt->fetchAll() as $r) $cfg[$r['chave']] = $r['valor'];
$loja_nome    = $cfg['loja_nome']      ?? 'Sabor & Cia';
$whatsapp     = $cfg['loja_whatsapp']  ?? '5581987028550';
$gratis_acima = (float)($cfg['entrega_gratis'] ?? 50.00);

$categorias = $pdo->query("SELECT id, slug, nome FROM categorias WHERE ativo=1 ORDER BY ordem")->fetchAll();

$cat_ativa = $_GET['cat'] ?? 'todos';
$busca     = trim($_GET['q'] ?? '');

$sql    = "SELECT p.id,p.nome,p.descricao,p.preco,p.imagem_url AS img,c.slug AS cat_slug
           FROM produtos p JOIN categorias c ON c.id=p.categoria_id
           WHERE p.ativo=1 AND p.estoque>0";
$params = [];
if ($cat_ativa !== 'todos') { $sql .= " AND c.slug=?";     $params[] = $cat_ativa; }
if ($busca !== '')          { $sql .= " AND p.nome LIKE ?"; $params[] = '%'.$busca.'%'; }
$sql .= " ORDER BY c.ordem, p.nome";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$lista = $stmt->fetchAll();

// ── ADICIONAIS DO BANCO ───────────────────────────────────────
$adicionais = [];
try {
    $stmt2 = $pdo->query("SELECT * FROM produto_adicionais WHERE ativo=1 ORDER BY produto_id, nome");
    foreach ($stmt2->fetchAll() as $a) {
        $adicionais[$a['produto_id']][] = $a;
    }
} catch (PDOException $e) { $adicionais = []; }

// Adicionais padrão por categoria (fallback)
$adicionais_padrao = [
    'acai' => [
        ['nome'=>'Banana',           'preco_extra'=>0.00],
        ['nome'=>'Granola',          'preco_extra'=>0.00],
        ['nome'=>'Leite condensado', 'preco_extra'=>0.00],
        ['nome'=>'Morango',          'preco_extra'=>2.00],
        ['nome'=>'Kiwi',             'preco_extra'=>2.00],
        ['nome'=>'Nutella',          'preco_extra'=>3.00],
        ['nome'=>'Paçoca',           'preco_extra'=>1.50],
        ['nome'=>'Amendoim',         'preco_extra'=>1.00],
    ],
    'hamburguer' => [
        ['nome'=>'Bacon extra',                 'preco_extra'=>3.00],
        ['nome'=>'Queijo duplo',                'preco_extra'=>2.00],
        ['nome'=>'Ovo',                         'preco_extra'=>2.00],
        ['nome'=>'Cheddar',                     'preco_extra'=>2.50],
        ['nome'=>'Sem cebola',                  'preco_extra'=>0.00],
        ['nome'=>'Ponto: bem passado',          'preco_extra'=>0.00],
        ['nome'=>'Ponto: ao ponto',             'preco_extra'=>0.00],
    ],
    'doces' => [
        ['nome'=>'Cobertura de Nutella', 'preco_extra'=>3.00],
        ['nome'=>'Granulado extra',      'preco_extra'=>0.00],
        ['nome'=>'Sem cobertura',        'preco_extra'=>0.00],
    ],
    'bebidas' => [
        ['nome'=>'Menos gelo',   'preco_extra'=>0.00],
        ['nome'=>'Sem gelo',     'preco_extra'=>0.00],
        ['nome'=>'Canudo extra', 'preco_extra'=>0.00],
    ],
];

// Monta o mapa de produtos para JS (igual ao index.php)
$produtos_js = [];
foreach ($lista as $p) {
    $adds = $adicionais[$p['id']] ?? $adicionais_padrao[$p['cat_slug']] ?? [];
    $produtos_js[$p['id']] = [
        'id'         => (int)$p['id'],
        'nome'       => $p['nome'],
        'descricao'  => $p['descricao'] ?? '',
        'preco'      => (float)$p['preco'],
        'imagem'     => $p['img'] ?? '',
        'cat'        => $p['cat_slug'],
        'adicionais' => array_values($adds),
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio — <?= htmlspecialchars($loja_nome) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/public.css">
    <style>
        /* Modal de adicionais — igual ao index.php */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; display:flex; align-items:flex-end; justify-content:center; padding:0; opacity:0; pointer-events:none; transition:opacity .25s; }
        .modal-overlay.open { opacity:1; pointer-events:all; }
        .modal-box { background:#fff; width:100%; max-width:480px; border-radius:20px 20px 0 0; max-height:90vh; overflow-y:auto; transform:translateY(100%); transition:transform .3s cubic-bezier(.32,.72,0,1); padding-bottom:env(safe-area-inset-bottom,0); }
        .modal-overlay.open .modal-box { transform:translateY(0); }
        .modal-box::-webkit-scrollbar { display:none; }
        .modal-img   { height:200px; overflow:hidden; border-radius:20px 20px 0 0; }
        .modal-img img { width:100%; height:100%; object-fit:cover; }
        .modal-corpo { padding:20px 20px 0; }
        .modal-drag  { width:36px; height:4px; border-radius:2px; background:#f0e8ed; margin:0 auto 16px; }
        .modal-nome  { font-family:Georgia,'Times New Roman',serif; font-size:1.2rem; font-weight:700; margin-bottom:4px; }
        .modal-desc  { font-size:.85rem; color:#9ca3af; line-height:1.6; margin-bottom:16px; }
        .modal-preco-base { font-size:.82rem; color:#9ca3af; margin-bottom:20px; }
        .modal-preco-base strong { color:#f43f7a; font-family:Georgia,'Times New Roman',serif; font-size:1.1rem; }
        .modal-adicionais-titulo { font-size:.72rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#9ca3af; padding:12px 20px; background:#fafafa; border-top:1px solid #f0e8ed; border-bottom:1px solid #f0e8ed; margin:0 -20px; }
        .modal-adicional { display:flex; align-items:center; justify-content:space-between; padding:13px 0; border-bottom:1px solid #fafafa; cursor:pointer; gap:12px; }
        .modal-adicional:last-child { border-bottom:none; }
        .modal-adicional-info { flex:1; min-width:0; }
        .modal-adicional-nome  { font-size:.88rem; font-weight:500; }
        .modal-adicional-preco { font-size:.78rem; color:#9ca3af; margin-top:2px; }
        .modal-adicional-preco.extra { color:#f43f7a; }
        .check-custom { width:22px; height:22px; border-radius:6px; border:2px solid #f0e8ed; background:#fff; flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:all .2s; }
        .modal-adicional input[type="checkbox"] { display:none; }
        .modal-adicional input:checked + .check-custom { background:#f43f7a; border-color:#f43f7a; }
        .modal-adicional input:checked + .check-custom::after { content:''; display:block; width:5px; height:9px; border:2px solid #fff; border-top:none; border-left:none; transform:rotate(45deg) translate(-1px,-1px); }
        .modal-obs-titulo { font-size:.72rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#9ca3af; padding:12px 20px; background:#fafafa; border-top:1px solid #f0e8ed; border-bottom:1px solid #f0e8ed; margin:0 -20px; }
        .modal-obs-area { width:100%; margin-top:12px; padding:10px 13px; border-radius:10px; border:1px solid #f0e8ed; background:#fafafa; font-family:'DM Sans',system-ui,sans-serif; font-size:.88rem; color:#1a1014; resize:none; outline:none; transition:border-color .2s; }
        .modal-obs-area:focus { border-color:#f43f7a; }
        .modal-foot { position:sticky; bottom:0; background:#fff; border-top:1px solid #f0e8ed; padding:14px 20px; margin:0 -20px; display:flex; align-items:center; gap:12px; }
        .modal-qtd { display:flex; align-items:center; border:1.5px solid #f0e8ed; border-radius:10px; overflow:hidden; }
        .modal-qtd button { width:36px; height:38px; background:none; border:none; font-size:1.1rem; font-weight:700; cursor:pointer; color:#1a1014; transition:background .15s; }
        .modal-qtd button:hover { background:#fce7f0; }
        .modal-qtd span { font-size:.95rem; font-weight:700; min-width:28px; text-align:center; }
        .modal-confirmar { flex:1; padding:12px; border-radius:10px; background:#f43f7a; color:#fff; border:none; font-family:'DM Sans',system-ui,sans-serif; font-size:.92rem; font-weight:600; cursor:pointer; transition:opacity .2s; display:flex; align-items:center; justify-content:space-between; gap:8px; }
        .modal-confirmar:hover { opacity:.88; }
        .modal-confirmar-total { font-family:Georgia,'Times New Roman',serif; font-size:1rem; }
    </style>
</head>
<body>

<nav class="navbar" id="navbar">
    <div class="container nav-inner">
        <a href="index.php" class="nav-logo">Sabor<span>&</span>Cia</a>
        <ul class="nav-links" id="navLinks">
            <li><a href="index.php">Início</a></li>
            <li><a href="produtos.php" class="ativo">Cardápio</a></li>
            <li><a href="index.php#ofertas">Ofertas</a></li>
            <li><a href="sobre.php">Sobre</a></li>
        </ul>
        <div class="nav-dir">
            <?php if ($logado): ?>
            <div class="nav-avatar" title="<?= htmlspecialchars($cliente_nome) ?>">
                <?= mb_strtoupper(mb_substr($cliente_nome, 0, 1)) ?>
                <div class="nav-avatar-menu"><div class="nav-avatar-menu-inner">
                    <a href="minha-conta.php"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Minha conta</a>
                    <a href="meus-pedidos.php"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>Meus pedidos</a>
                    <div class="nav-avatar-sep"><a href="logout.php"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Sair</a></div>
                </div></div>
            </div>
            <?php else: ?>
            <a href="login.php" class="nav-login-btn"><svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>Entrar</a>
            <?php endif; ?>
            <button class="nav-cart" id="btnAbrirCart" aria-label="Carrinho">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <span class="cart-badge" id="cartBadge"></span>
            </button>
            <button class="nav-hamburger" id="btnHamburger"><svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
        </div>
    </div>
</nav>

<section class="page-hero">
    <div class="container page-hero-inner">
        <h1>Nosso <em>cardápio</em></h1>
        <p>Escolha o que quiser e adicione direto ao carrinho.</p>
    </div>
</section>

<div class="filtros-bar">
    <div class="container filtros-inner">
        <div class="filtros-tabs">
            <a href="produtos.php?cat=todos<?= $busca?'&q='.urlencode($busca):'' ?>" class="tab <?= $cat_ativa==='todos'?'ativo':'' ?>">Tudo</a>
            <?php foreach ($categorias as $c): ?>
            <a href="produtos.php?cat=<?= urlencode($c['slug']) ?><?= $busca?'&q='.urlencode($busca):'' ?>" class="tab <?= $cat_ativa===$c['slug']?'ativo':'' ?>"><?= htmlspecialchars($c['nome']) ?></a>
            <?php endforeach; ?>
        </div>
        <form method="GET" action="produtos.php" class="busca-form">
            <?php if ($cat_ativa !== 'todos'): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($cat_ativa) ?>"><?php endif; ?>
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" class="busca-input" placeholder="Buscar..." value="<?= htmlspecialchars($busca) ?>" autocomplete="off">
        </form>
    </div>
</div>

<section class="pag-produtos">
    <div class="container">
        <p class="resultado">
            <?php if ($busca): ?>Resultados para <strong>"<?= htmlspecialchars($busca) ?>"</strong> — <?php endif; ?>
            <strong><?= count($lista) ?></strong> produto<?= count($lista)!==1?'s':'' ?> encontrado<?= count($lista)!==1?'s':'' ?>
        </p>
        <div class="cards-grid">
            <?php if (empty($lista)): ?>
            <div class="vazio">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Nenhum produto encontrado.
            </div>
            <?php else: foreach ($lista as $p): ?>
            <div class="card">
                <div class="card-img"><img src="<?= htmlspecialchars($p['img']??'') ?>" alt="<?= htmlspecialchars($p['nome']) ?>" loading="lazy"></div>
                <div class="card-body">
                    <div class="card-nome"><?= htmlspecialchars($p['nome']) ?></div>
                    <div class="card-desc"><?= htmlspecialchars($p['descricao']??'') ?></div>
                    <div class="card-rodape">
                        <span class="card-preco">R$ <?= number_format($p['preco'],2,',','.') ?></span>
                        <button class="card-add" onclick="abrirModal(<?= (int)$p['id'] ?>)">+ Adicionar</button>
                    </div>
                    <a href="produto.php?id=<?= $p['id'] ?>" class="card-ver">Ver detalhes →</a>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-logo">Sabor<span>&</span>Cia</div>
                <p class="footer-desc">Açaí, hambúrgueres artesanais, doces e bebidas geladas.</p>
                <div class="footer-social">
                    <a href="#" class="soc"><svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
                    <a href="#" class="soc"><svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                    <a href="https://wa.me/<?= $whatsapp ?>" class="soc" target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg></a>
                </div>
            </div>
            <div>
                <h4>Cardápio</h4>
                <ul><?php foreach ($categorias as $c): ?><li><a href="produtos.php?cat=<?= urlencode($c['slug']) ?>"><?= htmlspecialchars($c['nome']) ?></a></li><?php endforeach; ?></ul>
            </div>
            <div>
                <h4>Contato</h4>
                <ul>
                    <li><a href="https://wa.me/<?= $whatsapp ?>" target="_blank" rel="noopener">WhatsApp</a></li>
                    <li><a href="sobre.php">Sobre a loja</a></li>
                </ul>
            </div>
        </div>
        <p class="footer-copy">© <?= date('Y') ?> <?= htmlspecialchars($loja_nome) ?> — Todos os direitos reservados.</p>
    </div>
</footer>

<!-- MODAL DE ADICIONAIS -->
<div class="modal-overlay" id="modalOverlay" onclick="fecharModalFora(event)">
    <div class="modal-box" id="modalBox">
        <div class="modal-img"><img id="modalImgEl" src="" alt=""></div>
        <div class="modal-corpo">
            <div class="modal-drag"></div>
            <div class="modal-nome"       id="modalNome"></div>
            <div class="modal-desc"       id="modalDesc"></div>
            <div class="modal-preco-base">A partir de <strong id="modalPrecoBase"></strong></div>
            <div id="modalAdicionaisWrap"></div>
            <div class="modal-obs-titulo">Alguma observação?</div>
            <textarea class="modal-obs-area" id="modalObs" rows="2" placeholder="Ex: sem cebola, ponto bem passado..."></textarea>
        </div>
        <div class="modal-foot">
            <div class="modal-qtd">
                <button onclick="mudarQtd(-1)">−</button>
                <span id="modalQtd">1</span>
                <button onclick="mudarQtd(1)">+</button>
            </div>
            <button class="modal-confirmar" id="btnConfirmar" onclick="confirmarAdicional()">
                <span>Adicionar ao carrinho</span>
                <span class="modal-confirmar-total" id="modalTotal"></span>
            </button>
        </div>
    </div>
</div>

<!-- CARRINHO -->
<div class="cart-overlay" id="cartOverlay"></div>
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-head"><h3>Meu carrinho</h3><button class="cart-close" id="btnFecharCart">&#x2715;</button></div>
    <div class="cart-items" id="cartItems"><p class="cart-vazio">Seu carrinho está vazio.<br>Adicione algo gostoso!</p></div>
    <div class="cart-foot">
        <div class="cart-entrega-info" id="cartEntregaInfo"></div>
        <div class="cart-total"><span>Subtotal</span><span class="cart-total-val" id="cartTotal">R$ 0,00</span></div>
        <a href="checkout.php" class="btn-finalizar" id="btnFinalizar">
            <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>Finalizar pedido
        </a>
    </div>
</div>
<div class="toast" id="toast"></div>

<script>
var PRODUTOS     = <?= json_encode(array_values($produtos_js), JSON_UNESCAPED_UNICODE) ?>;
var GRATIS_ACIMA = <?= $gratis_acima ?>;

var PROD_MAP = {};
PRODUTOS.forEach(function(p){ PROD_MAP[p.id] = p; });

var cart     = JSON.parse(localStorage.getItem('sc_cart') || '[]');
var modalId  = null;
var modalQtdVal = 1;

renderCart();

// ── NAVBAR ────────────────────────────────────────────────────
window.addEventListener('scroll', function(){
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 30);
});
document.getElementById('btnHamburger').addEventListener('click', function(){
    var l = document.getElementById('navLinks'), open = l.style.display === 'flex';
    l.style.cssText = open ? '' : 'display:flex;flex-direction:column;position:absolute;top:60px;left:0;right:0;background:#fff;padding:16px 18px;border-bottom:1px solid #f0e8ed;gap:16px;z-index:800;';
});

// ── CARRINHO ──────────────────────────────────────────────────
document.getElementById('btnAbrirCart').addEventListener('click',  function(){ toggleCart(true); });
document.getElementById('btnFecharCart').addEventListener('click', function(){ toggleCart(false); });
document.getElementById('cartOverlay').addEventListener('click',   function(){ toggleCart(false); });
document.getElementById('btnFinalizar').addEventListener('click',  function(e){
    if (cart.length === 0){ e.preventDefault(); showToast('Adicione itens primeiro!'); }
});

function toggleCart(open){
    document.getElementById('cartSidebar').classList.toggle('open', open);
    document.getElementById('cartOverlay').classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
}

// ── MODAL ─────────────────────────────────────────────────────
function abrirModal(prodId){
    var p = PROD_MAP[prodId];
    if (!p) return;
    modalId      = prodId;
    modalQtdVal  = 1;

    document.getElementById('modalImgEl').src             = p.imagem;
    document.getElementById('modalNome').textContent      = p.nome;
    document.getElementById('modalDesc').textContent      = p.descricao;
    document.getElementById('modalPrecoBase').textContent = 'R$ ' + p.preco.toFixed(2).replace('.',',');
    document.getElementById('modalQtd').textContent       = '1';
    document.getElementById('modalObs').value             = '';

    var wrap = document.getElementById('modalAdicionaisWrap');
    wrap.innerHTML = '';

    if (p.adicionais && p.adicionais.length > 0) {
        var html = '<div class="modal-adicionais-titulo">Adicionais e personalizações</div>';
        p.adicionais.forEach(function(a, i){
            var precoLabel = a.preco_extra > 0 ? '+R$ ' + parseFloat(a.preco_extra).toFixed(2).replace('.',',') : 'Grátis';
            var extraClass = a.preco_extra > 0 ? 'extra' : '';
            html += '<label class="modal-adicional">'
                  + '<div class="modal-adicional-info">'
                  + '<div class="modal-adicional-nome">' + a.nome + '</div>'
                  + '<div class="modal-adicional-preco ' + extraClass + '">' + precoLabel + '</div>'
                  + '</div>'
                  + '<input type="checkbox" class="add-check" data-preco="' + a.preco_extra + '" data-nome="' + a.nome + '" id="add' + i + '">'
                  + '<div class="check-custom"></div>'
                  + '</label>';
        });
        wrap.innerHTML = html;
        wrap.querySelectorAll('.add-check').forEach(function(cb){
            cb.addEventListener('change', atualizarTotalModal);
        });
    }

    atualizarTotalModal();
    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function fecharModal(){
    document.getElementById('modalOverlay').classList.remove('open');
    document.body.style.overflow = '';
    modalId = null;
}

function fecharModalFora(e){
    if (e.target === document.getElementById('modalOverlay')) fecharModal();
}

function mudarQtd(delta){
    modalQtdVal = Math.max(1, modalQtdVal + delta);
    document.getElementById('modalQtd').textContent = modalQtdVal;
    atualizarTotalModal();
}

function atualizarTotalModal(){
    if (!modalId) return;
    var p = PROD_MAP[modalId];
    var extra = 0;
    document.querySelectorAll('.add-check:checked').forEach(function(cb){
        extra += parseFloat(cb.dataset.preco || 0);
    });
    var total = (p.preco + extra) * modalQtdVal;
    document.getElementById('modalTotal').textContent = 'R$ ' + total.toFixed(2).replace('.',',');
}

function confirmarAdicional(){
    if (!modalId) return;
    var p = PROD_MAP[modalId];
    var adds = [], extra = 0;
    document.querySelectorAll('.add-check:checked').forEach(function(cb){
        adds.push(cb.dataset.nome);
        extra += parseFloat(cb.dataset.preco || 0);
    });
    var obs        = document.getElementById('modalObs').value.trim();
    var precoFinal = p.preco + extra;
    var chave      = modalId + '|' + adds.join(',') + '|' + obs;
    var found      = false;
    for (var i = 0; i < cart.length; i++){
        if (cart[i].chave === chave){ cart[i].qtd += modalQtdVal; found = true; break; }
    }
    if (!found){
        cart.push({ chave:chave, id:modalId, nome:p.nome, preco:precoFinal, img:p.imagem, adicionais:adds, obs:obs, qtd:modalQtdVal });
    }
    salvarCart();
    renderCart();
    showToast(p.nome + ' adicionado!');
    fecharModal();
    toggleCart(true);
}

// ── RENDERIZAR CARRINHO ───────────────────────────────────────
function renderCart(){
    var total = 0, itens = 0;
    cart.forEach(function(i){ total += i.preco * i.qtd; itens += i.qtd; });

    var b = document.getElementById('cartBadge');
    b.textContent = itens; b.style.display = itens > 0 ? 'flex' : 'none';

    var info = document.getElementById('cartEntregaInfo');
    if (total > 0 && GRATIS_ACIMA > 0){
        info.innerHTML = total >= GRATIS_ACIMA
            ? '<strong>Entrega grátis!</strong>'
            : 'Faltam <strong>R$ '+(GRATIS_ACIMA-total).toFixed(2).replace('.',',')+'</strong> para entrega grátis';
    } else { info.innerHTML = ''; }

    var el = document.getElementById('cartItems');
    if (!cart.length){
        el.innerHTML = '<p class="cart-vazio">Seu carrinho está vazio.<br>Adicione algo gostoso!</p>';
    } else {
        el.innerHTML = cart.map(function(it, idx){
            var adds = it.adicionais && it.adicionais.length ? it.adicionais.join(', ') : '';
            if (it.obs) adds += (adds ? ' • ' : '') + it.obs;
            return '<div class="cart-item">'
                + '<div class="cart-item-img"><img src="'+it.img+'" alt="'+it.nome+'"></div>'
                + '<div class="cart-item-info">'
                + '<div class="cart-item-nome">'+it.nome+'</div>'
                + (adds ? '<div class="cart-item-adds" style="font-size:.74rem;color:#9ca3af;margin-top:2px;line-height:1.4">'+adds+'</div>' : '')
                + '<div class="cart-item-preco">R$ '+(it.preco*it.qtd).toFixed(2).replace('.',',')+'</div>'
                + '<div class="cart-item-qtd">'
                + '<button onclick="mudarQtdCart('+idx+',-1)">−</button>'
                + '<span>'+it.qtd+'</span>'
                + '<button onclick="mudarQtdCart('+idx+',1)">+</button>'
                + '</div></div>'
                + '<button class="cart-rm" onclick="removeCart('+idx+')">✕</button>'
                + '</div>';
        }).join('');
    }
    document.getElementById('cartTotal').textContent = 'R$ ' + total.toFixed(2).replace('.',',');
}

function mudarQtdCart(idx, delta){
    cart[idx].qtd = Math.max(1, cart[idx].qtd + delta);
    salvarCart(); renderCart();
}

function removeCart(idx){
    cart.splice(idx, 1);
    salvarCart(); renderCart();
}

function salvarCart(){ localStorage.setItem('sc_cart', JSON.stringify(cart)); }

var toastTimer;
function showToast(msg){
    var el = document.getElementById('toast');
    el.textContent = msg; el.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ el.classList.remove('show'); }, 2500);
}

document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') fecharModal();
});
</script>
</body>
</html>