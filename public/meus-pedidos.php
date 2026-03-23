<?php
// public/meus-pedidos.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';

exigir_login_cliente();

$cliente_id   = $_SESSION['cliente_id'];
$cliente_nome = $_SESSION['cliente_nome'];

// Detalhe de pedido específico
$ver_id = (int)($_GET['ver'] ?? 0);
$detalhe = null;
if ($ver_id > 0) {
    $stmt = $pdo->prepare("
        SELECT p.*, DATE_FORMAT(p.criado_em, '%d/%m/%Y %H:%i') AS data_br
        FROM pedidos p WHERE p.id = ? AND p.cliente_id = ?
    ");
    $stmt->execute([$ver_id, $cliente_id]);
    $detalhe = $stmt->fetch() ?: null;

    if ($detalhe) {
        $stmt = $pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id = ?");
        $stmt->execute([$ver_id]);
        $detalhe['itens'] = $stmt->fetchAll();
    }
}

// Lista de pedidos
$pedidos = $pdo->prepare("
    SELECT id, status, total, tipo_entrega,
           DATE_FORMAT(criado_em, '%d/%m/%Y %H:%i') AS data_br,
           criado_em
    FROM pedidos
    WHERE cliente_id = ?
    ORDER BY criado_em DESC
");
$pedidos->execute([$cliente_id]);
$pedidos = $pedidos->fetchAll();

$loja_nome = $pdo->query("SELECT valor FROM configuracoes WHERE chave='loja_nome'")->fetchColumn() ?: 'Sabor & Cia';

$status_cfg = [
    'pendente'   => ['bg'=>'#eff6ff','cor'=>'#1d4ed8','label'=>'Pendente'],
    'preparo'    => ['bg'=>'#fef9c3','cor'=>'#854d0e','label'=>'Em preparo'],
    'entregando' => ['bg'=>'#f0fdf4','cor'=>'#15803d','label'=>'A caminho'],
    'entregue'   => ['bg'=>'#f0fdf4','cor'=>'#15803d','label'=>'Entregue'],
    'cancelado'  => ['bg'=>'#fff0f4','cor'=>'#be185d','label'=>'Cancelado'],
];

$entrega_cfg = [
    'delivery'  => 'Delivery',
    'retirada'  => 'Retirada',
    'local'     => 'Comer no local',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus pedidos — <?= h($loja_nome) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root { --rosa:#f43f7a; --rosa-light:#fce7f0; --rosa-border:#f0e8ed; --dark:#1a1014; --gray:#9ca3af; --white:#fff; --bg:#fafafa; --serif:Georgia,'Times New Roman',serif; --sans:'DM Sans',system-ui,sans-serif; --r:14px; }
        body  { font-family:var(--sans); color:var(--dark); background:var(--bg); min-height:100vh; }
        a     { text-decoration:none; color:inherit; }

        /* NAVBAR */
        .navbar { position:fixed; top:0; left:0; right:0; z-index:900; background:rgba(255,255,255,.95); backdrop-filter:blur(14px); border-bottom:1px solid var(--rosa-border); }
        .nav-inner { display:flex; align-items:center; justify-content:space-between; height:60px; max-width:900px; margin:0 auto; padding:0 18px; }
        .nav-logo { font-family:var(--serif); font-size:1.3rem; font-weight:700; }
        .nav-logo span { color:var(--rosa); }
        .nav-back { display:flex; align-items:center; gap:6px; font-size:.85rem; color:var(--gray); transition:color .2s; }
        .nav-back:hover { color:var(--rosa); }
        .nav-back svg { width:15px; height:15px; stroke:currentColor; fill:none; stroke-width:2; }
        .nav-links-dir { display:flex; align-items:center; gap:16px; }
        .nav-link-item { font-size:.83rem; color:var(--gray); transition:color .2s; display:flex; align-items:center; gap:5px; }
        .nav-link-item:hover { color:var(--rosa); }
        .nav-link-item svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; }

        /* PÁGINA */
        .page { max-width:900px; margin:0 auto; padding:80px 18px 48px; }

        .page-titulo { font-family:var(--serif); font-size:1.8rem; font-weight:700; margin-bottom:6px; }
        .page-sub    { font-size:.88rem; color:var(--gray); margin-bottom:28px; }

        /* VAZIO */
        .vazio { text-align:center; padding:64px 0; color:var(--gray); line-height:1.8; }
        .vazio svg { width:48px; height:48px; stroke:var(--rosa-border); fill:none; stroke-width:1.5; margin:0 auto 16px; display:block; }
        .vazio a { color:var(--rosa); font-weight:600; }

        /* LISTA DE PEDIDOS */
        .pedido-card { background:var(--white); border:1px solid var(--rosa-border); border-radius:var(--r); padding:18px 20px; margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; gap:16px; transition:border-color .2s, box-shadow .2s; cursor:pointer; }
        .pedido-card:hover { border-color:var(--rosa); box-shadow:0 4px 14px rgba(244,63,122,.08); }
        .pedido-esq { display:flex; flex-direction:column; gap:4px; }
        .pedido-num { font-weight:700; font-size:.95rem; }
        .pedido-num span { color:var(--rosa); }
        .pedido-data { font-size:.78rem; color:var(--gray); }
        .pedido-tipo { font-size:.78rem; color:var(--gray); margin-top:2px; }
        .pedido-dir  { display:flex; align-items:center; gap:12px; }
        .pedido-total { font-family:var(--serif); font-size:1.1rem; font-weight:700; color:var(--dark); }
        .badge { display:inline-flex; align-items:center; padding:4px 10px; border-radius:50px; font-size:.74rem; font-weight:600; }
        .seta { color:var(--gray); }
        .seta svg { width:16px; height:16px; stroke:currentColor; fill:none; stroke-width:2; }

        /* MODAL DETALHE */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; display:flex; align-items:center; justify-content:center; padding:20px; opacity:0; pointer-events:none; transition:opacity .25s; }
        .modal-overlay.open { opacity:1; pointer-events:all; }
        .modal-box { background:var(--white); width:100%; max-width:500px; border-radius:16px; max-height:90vh; overflow-y:auto; transform:translateY(20px); transition:transform .3s; }
        .modal-overlay.open .modal-box { transform:translateY(0); }
        .modal-box::-webkit-scrollbar { display:none; }
        .modal-head { display:flex; align-items:center; justify-content:space-between; padding:18px 20px; border-bottom:1px solid var(--rosa-border); position:sticky; top:0; background:var(--white); z-index:1; }
        .modal-head h2 { font-family:var(--serif); font-size:1.05rem; font-weight:700; }
        .modal-close { background:none; border:none; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:1.1rem; color:var(--gray); transition:all .2s; }
        .modal-close:hover { background:var(--rosa-light); color:var(--rosa); }
        .modal-body { padding:20px; }

        /* Detalhes */
        .det-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px; }
        .det-item { background:var(--bg); border-radius:10px; padding:12px 14px; }
        .det-lbl  { font-size:.72rem; color:var(--gray); text-transform:uppercase; letter-spacing:.5px; margin-bottom:3px; }
        .det-val  { font-size:.88rem; font-weight:600; }

        /* Itens do pedido */
        .itens-titulo { font-size:.72rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--gray); padding:10px 0 8px; border-top:1px solid var(--rosa-border); margin-top:4px; }
        .item-linha { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; padding:9px 0; border-bottom:1px solid var(--bg); }
        .item-linha:last-child { border-bottom:none; }
        .item-qtd  { font-size:.78rem; font-weight:700; color:var(--rosa); min-width:20px; }
        .item-nome { font-size:.85rem; font-weight:600; flex:1; }
        .item-preco{ font-size:.85rem; font-weight:700; white-space:nowrap; }

        .det-totais { border-top:1px solid var(--rosa-border); margin-top:12px; padding-top:12px; }
        .det-linha  { display:flex; justify-content:space-between; font-size:.84rem; color:var(--gray); margin-bottom:6px; }
        .det-linha.total { font-size:1rem; font-weight:700; color:var(--dark); margin-bottom:0; }
        .det-linha.total .val { color:var(--rosa); font-family:var(--serif); }

        /* Repetir pedido */
        .btn-repetir { width:100%; margin-top:16px; padding:12px; border-radius:50px; background:var(--rosa); color:#fff; border:none; font-family:var(--sans); font-size:.9rem; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:7px; transition:opacity .2s; }
        .btn-repetir:hover { opacity:.88; }
        .btn-repetir svg { width:14px; height:14px; stroke:#fff; fill:none; stroke-width:2.5; }

        @media (max-width:560px) {
            .det-grid { grid-template-columns:1fr; }
            .pedido-card { flex-direction:column; align-items:flex-start; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-inner">
        <a href="index.php" class="nav-back">
            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            <span class="nav-logo">Sabor<span>&</span>Cia</span>
        </a>
        <div class="nav-links-dir">
            <a href="minha-conta.php" class="nav-link-item">
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Minha conta
            </a>
            <a href="logout.php" class="nav-link-item">
                <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sair
            </a>
        </div>
    </div>
</nav>

<div class="page">

    <h1 class="page-titulo">Meus pedidos</h1>
    <p class="page-sub"><?= count($pedidos) ?> pedido<?= count($pedidos) !== 1 ? 's' : '' ?> no histórico</p>

    <?php if (empty($pedidos)): ?>
    <div class="vazio">
        <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>
        Você ainda não fez nenhum pedido.<br>
        <a href="produtos.php">Ver cardápio e fazer o primeiro pedido</a>
    </div>
    <?php else: ?>

    <?php foreach ($pedidos as $p):
        $s = $status_cfg[$p['status']] ?? $status_cfg['pendente'];
        $t = $entrega_cfg[$p['tipo_entrega']] ?? $p['tipo_entrega'];
    ?>
    <div class="pedido-card" onclick="abrirDetalhe(<?= $p['id'] ?>)">
        <div class="pedido-esq">
            <div class="pedido-num">Pedido <span>#<?= $p['id'] ?></span></div>
            <div class="pedido-data"><?= $p['data_br'] ?></div>
            <div class="pedido-tipo"><?= h($t) ?></div>
        </div>
        <div class="pedido-dir">
            <div class="pedido-total">R$ <?= number_format($p['total'], 2, ',', '.') ?></div>
            <span class="badge" style="background:<?= $s['bg'] ?>;color:<?= $s['cor'] ?>"><?= $s['label'] ?></span>
            <div class="seta"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>

<!-- MODAL DETALHE -->
<div class="modal-overlay" id="modalOverlay" onclick="fecharModalFora(event)">
    <div class="modal-box">
        <div class="modal-head">
            <h2 id="modalTitulo">Detalhes do pedido</h2>
            <button class="modal-close" onclick="fecharModal()">&#x2715;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <p style="text-align:center;color:var(--gray);padding:24px 0">Carregando...</p>
        </div>
    </div>
</div>

<script>
var STATUS_CFG = <?= json_encode($status_cfg, JSON_UNESCAPED_UNICODE) ?>;
var ENTREGA_CFG = <?= json_encode($entrega_cfg, JSON_UNESCAPED_UNICODE) ?>;

// Dados dos pedidos já em JSON para o modal (evita request AJAX)
var PEDIDOS_DATA = <?= json_encode(array_map(function($p) use ($pdo) {
    $stmt = $pdo->prepare("SELECT produto_nome, quantidade, subtotal FROM pedido_itens WHERE pedido_id = ?");
    $stmt->execute([$p['id']]);
    $p['itens'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $p;
}, $pedidos), JSON_UNESCAPED_UNICODE) ?>;

var PEDIDOS_MAP = {};
PEDIDOS_DATA.forEach(function(p){ PEDIDOS_MAP[p.id] = p; });

function abrirDetalhe(id) {
    var p = PEDIDOS_MAP[id]; if (!p) return;
    var s = STATUS_CFG[p.status] || STATUS_CFG['pendente'];
    var t = ENTREGA_CFG[p.tipo_entrega] || p.tipo_entrega;

    document.getElementById('modalTitulo').textContent = 'Pedido #' + p.id;

    var html = '<div class="det-grid">';
    html += '<div class="det-item"><div class="det-lbl">Data</div><div class="det-val">'+p.data_br+'</div></div>';
    html += '<div class="det-item"><div class="det-lbl">Status</div><div class="det-val"><span class="badge" style="background:'+s.bg+';color:'+s.cor+'">'+s.label+'</span></div></div>';
    html += '<div class="det-item"><div class="det-lbl">Entrega</div><div class="det-val">'+t+'</div></div>';
    html += '<div class="det-item"><div class="det-lbl">Pagamento</div><div class="det-val">'+(p.pagamento||'—')+'</div></div>';
    if (p.endereco) html += '<div class="det-item" style="grid-column:1/-1"><div class="det-lbl">Endereço</div><div class="det-val">'+p.endereco+(p.bairro?', '+p.bairro:'')+'</div></div>';
    html += '</div>';

    html += '<div class="itens-titulo">Itens do pedido</div>';
    (p.itens || []).forEach(function(it){
        html += '<div class="item-linha"><span class="item-qtd">'+it.quantidade+'×</span><span class="item-nome">'+it.produto_nome+'</span><span class="item-preco">R$ '+parseFloat(it.subtotal).toFixed(2).replace('.',',')+'</span></div>';
    });

    html += '<div class="det-totais">';
    if (parseFloat(p.desconto) > 0) html += '<div class="det-linha"><span>Desconto</span><span style="color:#16a34a">− R$ '+parseFloat(p.desconto).toFixed(2).replace('.',',')+'</span></div>';
    if (parseFloat(p.taxa_entrega) > 0) html += '<div class="det-linha"><span>Taxa de entrega</span><span>R$ '+parseFloat(p.taxa_entrega).toFixed(2).replace('.',',')+'</span></div>';
    html += '<div class="det-linha total"><span>Total</span><span class="val">R$ '+parseFloat(p.total).toFixed(2).replace('.',',')+'</span></div>';
    html += '</div>';

    if (p.observacao) html += '<div style="margin-top:14px;background:var(--bg);border-radius:10px;padding:12px 14px;font-size:.83rem;color:var(--gray)"><strong style="color:var(--dark)">Observação:</strong> '+p.observacao+'</div>';

    html += '<button class="btn-repetir" onclick="repetirPedido('+id+')"><svg viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.7"/></svg>Repetir este pedido</button>';

    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function fecharModal(){
    document.getElementById('modalOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
function fecharModalFora(e){ if (e.target === document.getElementById('modalOverlay')) fecharModal(); }

function repetirPedido(id){
    var p = PEDIDOS_MAP[id]; if (!p || !p.itens) return;
    var cart = JSON.parse(localStorage.getItem('sc_cart') || '[]');
    p.itens.forEach(function(it){
        var chave = 'rep-'+it.produto_nome;
        var found = false;
        for (var i=0;i<cart.length;i++){ if(cart[i].chave===chave){ cart[i].qtd+=parseInt(it.quantidade); found=true; break; } }
        if (!found) cart.push({chave,id:0,nome:it.produto_nome,preco:parseFloat(it.subtotal)/parseInt(it.quantidade),preco_base:parseFloat(it.subtotal)/parseInt(it.quantidade),extra:0,img:'',adicionais:[],obs:'',qtd:parseInt(it.quantidade)});
    });
    localStorage.setItem('sc_cart', JSON.stringify(cart));
    window.location.href = 'checkout.php';
}

document.addEventListener('keydown', function(e){ if(e.key==='Escape') fecharModal(); });
</script>
</body>
</html>