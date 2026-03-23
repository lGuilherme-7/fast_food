<?php
// ============================================================
// public/index.php
// ============================================================
session_start();

require_once __DIR__ . '/../inc/config.php';  
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';

$logado       = isset($_SESSION['cliente_id']);
$cliente_nome = $logado ? explode(' ', $_SESSION['cliente_nome'])[0] : '';

$stmt = $pdo->query("SELECT chave, valor FROM configuracoes
    WHERE chave IN (
        'loja_whatsapp','loja_nome','loja_descricao',
        'entrega_tempo','entrega_raio','entrega_taxa',
        'entrega_gratis','entrega_ativa','retirada_ativa',
        'local_ativo','loja_endereco','loja_email'
    )");
$cfg = [];
foreach ($stmt->fetchAll() as $row) $cfg[$row['chave']] = $row['valor'];

$whatsapp       = $cfg['loja_whatsapp']  ?? '5581987028550';
$loja_nome      = $cfg['loja_nome']      ?? 'Sabor & Cia';
$entrega_tempo  = $cfg['entrega_tempo']  ?? '40';
$entrega_raio   = $cfg['entrega_raio']   ?? '5';
$entrega_taxa   = (float)($cfg['entrega_taxa']   ?? 5.00);
$entrega_gratis = (float)($cfg['entrega_gratis'] ?? 50.00);

$stmt = $pdo->query("SELECT id, slug, nome, imagem_url FROM categorias WHERE ativo=1 ORDER BY ordem");
$categorias = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT p.id, p.nome, p.descricao, p.preco, p.imagem_url,
           c.slug AS cat_slug, c.nome AS cat_nome
    FROM produtos p
    JOIN categorias c ON c.id = p.categoria_id
    WHERE p.ativo = 1 AND p.estoque > 0
    ORDER BY c.ordem, p.nome
");
$produtos = $stmt->fetchAll();

$adicionais = [];
try {
    $stmt = $pdo->query("SELECT * FROM produto_adicionais WHERE ativo=1 ORDER BY produto_id, nome");
    foreach ($stmt->fetchAll() as $a) {
        $adicionais[$a['produto_id']][] = $a;
    }
} catch (PDOException $e) {
    $adicionais = [];
}

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
        ['nome'=>'Bacon extra',                    'preco_extra'=>3.00],
        ['nome'=>'Queijo duplo',                   'preco_extra'=>2.00],
        ['nome'=>'Ovo',                            'preco_extra'=>2.00],
        ['nome'=>'Cheddar',                        'preco_extra'=>2.50],
        ['nome'=>'Sem cebola',                     'preco_extra'=>0.00],
        ['nome'=>'Ponto da carne: bem passado',    'preco_extra'=>0.00],
        ['nome'=>'Ponto da carne: ao ponto',       'preco_extra'=>0.00],
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

$produtos_js = [];
foreach ($produtos as $p) {
    $adds = $adicionais[$p['id']] ?? $adicionais_padrao[$p['cat_slug']] ?? [];
    $produtos_js[$p['id']] = [
        'id'         => (int)$p['id'],
        'nome'       => $p['nome'],
        'descricao'  => $p['descricao'] ?? '',
        'preco'      => (float)$p['preco'],
        'imagem'     => $p['imagem_url'] ?? '',
        'cat'        => $p['cat_slug'],
        'adicionais' => array_values($adds),
    ];
}

$depoimentos = [
    ['nome'=>'Ana Clara',  'texto'=>'Melhor açaí da cidade! Sempre fresquinho e cheio de sabor.', 'nota'=>5],
    ['nome'=>'Rafael M.',  'texto'=>'O smash burger é incrível, já virei cliente fiel mesmo.',    'nota'=>5],
    ['nome'=>'Juliana P.', 'texto'=>'Atendimento rápido e os bolos de pote são demais!',          'nota'=>5],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($loja_nome) ?> — Açaí, Burgers, Doces e Bebidas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --rosa:        #f43f7a;
            --rosa-light:  #fce7f0;
            --rosa-border: #f0e8ed;
            --dark:        #1a1014;
            --gray:        #9ca3af;
            --white:       #ffffff;
            --bg:          #fafafa;
            --serif:       Georgia, 'Times New Roman', serif;
            --sans:        'DM Sans', system-ui, sans-serif;
            --r:           14px;
        }
        html  { scroll-behavior: smooth; }
        body  { font-family: var(--sans); color: var(--dark); background: var(--white); overflow-x: hidden; }
        img   { display: block; width: 100%; height: 100%; object-fit: cover; }
        a     { text-decoration: none; color: inherit; }
        .container { width: 100%; max-width: 1100px; margin: 0 auto; padding: 0 18px; }

        .btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; padding:13px 26px; border-radius:50px; font-family:var(--sans); font-weight:600; font-size:.9rem; border:none; cursor:pointer; transition:opacity .2s,transform .15s; text-decoration:none; }
        .btn:active { transform: scale(.97); }
        .btn:hover  { opacity: .85; }
        .btn-rosa   { background: var(--rosa); color: #fff; }
        .btn-ghost  { background: transparent; border: 1.5px solid rgba(255,255,255,.6); color: #fff; }
        .btn-wpp    { background: #25D366; color: #fff; font-size: .95rem; padding: 15px 32px; }
        .btn-dark   { background: var(--dark); color: #fff; }

        .sec-header    { margin-bottom: 28px; }
        .sec-header h2 { font-family: var(--serif); font-size: clamp(1.5rem,4vw,2.2rem); font-weight: 700; line-height: 1.2; }
        .sec-header p  { font-size: .85rem; color: var(--gray); margin-top: 4px; }
        .sec-linha     { display: block; width: 36px; height: 3px; background: var(--rosa); border-radius: 2px; margin-top: 10px; }

        /* NAVBAR */
        .navbar { position:fixed; top:0; left:0; right:0; z-index:900; background:rgba(255,255,255,.95); backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px); border-bottom:1px solid var(--rosa-border); transition:box-shadow .3s; }
        .navbar.scrolled { box-shadow: 0 2px 16px rgba(244,63,122,.1); }
        .nav-inner { display:flex; align-items:center; justify-content:space-between; height:60px; }
        .nav-logo  { font-family:var(--serif); font-size:1.35rem; font-weight:700; letter-spacing:-.3px; }
        .nav-logo span { color: var(--rosa); }
        .nav-links { display:flex; gap:24px; list-style:none; }
        .nav-links a { font-size:.85rem; font-weight:500; color:var(--gray); transition:color .2s; }
        .nav-links a:hover { color: var(--rosa); }
        .nav-dir { display:flex; align-items:center; gap:10px; }

        .nav-login-btn { display:flex; align-items:center; gap:6px; padding:7px 16px; border-radius:50px; border:1.5px solid var(--rosa-border); background:var(--white); font-family:var(--sans); font-size:.82rem; font-weight:600; color:var(--dark); cursor:pointer; transition:all .2s; text-decoration:none; }
        .nav-login-btn:hover { border-color:var(--rosa); color:var(--rosa); }
        .nav-login-btn svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; }

        .nav-avatar { width:34px; height:34px; border-radius:50%; background:var(--rosa); color:#fff; font-size:.8rem; font-weight:700; display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative; }
        .nav-avatar::after { content:''; position:absolute; top:100%; left:0; right:0; height:12px; }
        .nav-avatar-menu { position:absolute; right:0; top:calc(100% + 0px); padding-top:0; background:transparent; z-index:100; opacity:0; pointer-events:none; transform:translateY(4px); transition:opacity .18s ease,transform .18s ease; }
        .nav-avatar-menu-inner { background:var(--white); border:1px solid var(--rosa-border); border-radius:10px; min-width:170px; box-shadow:0 4px 20px rgba(0,0,0,.1); overflow:hidden; }
        .nav-avatar:hover .nav-avatar-menu, .nav-avatar-menu:hover { opacity:1; pointer-events:auto; transform:translateY(0); }
        .nav-avatar:not(:hover) .nav-avatar-menu:not(:hover) { transition-delay:300ms; }
        .nav-avatar-menu-inner a, .nav-avatar-menu-inner button { display:flex; align-items:center; gap:8px; width:100%; padding:10px 14px; font-family:var(--sans); font-size:.83rem; font-weight:500; color:var(--dark); background:none; border:none; cursor:pointer; text-decoration:none; transition:background .15s; }
        .nav-avatar-menu-inner a:hover, .nav-avatar-menu-inner button:hover { background:var(--rosa-light); color:var(--rosa); }
        .nav-avatar-menu-inner svg { width:13px; height:13px; stroke:currentColor; fill:none; stroke-width:2; }
        .nav-avatar-sep { border-top:1px solid var(--rosa-border); }

        /* Botão carrinho */
        .nav-cart { position:relative; width:40px; height:40px; border-radius:50%; background:var(--rosa-light); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .2s; }
        .nav-cart:hover { background: var(--rosa); }
        .nav-cart svg { width:17px; height:17px; stroke:var(--rosa); fill:none; stroke-width:2; transition:stroke .2s; }
        .nav-cart:hover svg { stroke: #fff; }
        .cart-badge { position:absolute; top:-3px; right:-3px; background:var(--rosa); color:#fff; font-size:.6rem; font-weight:700; width:17px; height:17px; border-radius:50%; display:none; align-items:center; justify-content:center; border:2px solid #fff; }

        .nav-hamburger { display:none; background:none; border:none; cursor:pointer; padding:4px; }
        .nav-hamburger svg { width:22px; height:22px; stroke:var(--dark); fill:none; stroke-width:2; }

        /* HERO */
        .hero { min-height:100svh; background:url('https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=1400&q=85') center/cover no-repeat; display:flex; align-items:center; position:relative; padding-top:60px; }
        .hero::after { content:''; position:absolute; inset:0; background:linear-gradient(140deg,rgba(20,5,10,.82) 0%,rgba(20,5,10,.42) 100%); }
        .hero-content { position:relative; z-index:1; max-width:540px; padding:48px 0; }
        .hero-tag { display:inline-block; background:var(--rosa); color:#fff; font-size:.72rem; font-weight:600; letter-spacing:2px; text-transform:uppercase; padding:5px 14px; border-radius:50px; margin-bottom:18px; }
        .hero h1 { font-family:var(--serif); font-size:clamp(2.2rem,7vw,3.6rem); color:#fff; line-height:1.12; margin-bottom:14px; }
        .hero h1 em { font-style:normal; color:var(--rosa); }
        .hero p  { color:rgba(255,255,255,.72); font-size:.95rem; line-height:1.75; margin-bottom:30px; max-width:420px; }
        .hero-btns { display:flex; gap:12px; flex-wrap:wrap; }

        /* CATEGORIAS */
        .categorias { padding:60px 0 48px; }
        .cat-scroll { display:flex; gap:12px; overflow-x:auto; padding-bottom:4px; -webkit-overflow-scrolling:touch; scroll-snap-type:x mandatory; }
        .cat-scroll::-webkit-scrollbar { display:none; }
        .cat-card { background:var(--white); border:1px solid var(--rosa-border); border-radius:var(--r); padding:14px 16px; text-align:center; min-width:96px; flex-shrink:0; scroll-snap-align:start; cursor:pointer; transition:border-color .2s,background .2s; }
        .cat-card:hover, .cat-card.ativo { border-color:var(--rosa); background:var(--rosa-light); }
        .cat-card img  { width:44px; height:44px; border-radius:50%; margin:0 auto 8px; object-fit:cover; }
        .cat-card span { font-size:.78rem; font-weight:600; color:var(--dark); }

        /* PRODUTOS */
        .produtos { padding:48px 0; background:var(--rosa-light); }
        .prod-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
        .prod-card { background:var(--white); border:1px solid var(--rosa-border); border-radius:var(--r); overflow:hidden; transition:transform .2s,box-shadow .2s; }
        .prod-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(244,63,122,.1); }
        .prod-img { height:150px; overflow:hidden; position:relative; }
        .prod-img img { transition: transform .4s; }
        .prod-card:hover .prod-img img { transform: scale(1.05); }
        .prod-body  { padding:14px 14px 16px; }
        .prod-nome  { font-size:.88rem; font-weight:600; margin-bottom:3px; }
        .prod-desc  { font-size:.76rem; color:var(--gray); margin-bottom:8px; line-height:1.4; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .prod-footer{ display:flex; align-items:center; justify-content:space-between; gap:8px; }
        .prod-preco { font-family:var(--serif); font-size:1.15rem; color:var(--rosa); font-weight:700; }
        .btn-add { display:flex; align-items:center; gap:5px; padding:8px 14px; border-radius:8px; background:var(--dark); color:#fff; border:none; font-family:var(--sans); font-weight:600; font-size:.8rem; cursor:pointer; transition:background .2s; white-space:nowrap; }
        .btn-add:hover { background: var(--rosa); }
        .btn-add svg { width:13px; height:13px; stroke:currentColor; fill:none; stroke-width:2.5; }

        /* MODAL ADICIONAIS */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; display:flex; align-items:flex-end; justify-content:center; padding:0; opacity:0; pointer-events:none; transition:opacity .25s; }
        .modal-overlay.open { opacity:1; pointer-events:all; }
        .modal-box { background:var(--white); width:100%; max-width:480px; border-radius:20px 20px 0 0; max-height:90vh; overflow-y:auto; transform:translateY(100%); transition:transform .3s cubic-bezier(.32,.72,0,1); padding-bottom:env(safe-area-inset-bottom,0); }
        .modal-overlay.open .modal-box { transform: translateY(0); }
        .modal-box::-webkit-scrollbar { display: none; }
        .modal-img   { height:200px; overflow:hidden; border-radius:20px 20px 0 0; }
        .modal-corpo { padding:20px 20px 0; }
        .modal-drag  { width:36px; height:4px; border-radius:2px; background:var(--rosa-border); margin:0 auto 16px; }
        .modal-nome  { font-family:var(--serif); font-size:1.2rem; font-weight:700; margin-bottom:4px; }
        .modal-desc  { font-size:.85rem; color:var(--gray); line-height:1.6; margin-bottom:16px; }
        .modal-preco-base { font-size:.82rem; color:var(--gray); margin-bottom:20px; }
        .modal-preco-base strong { color:var(--rosa); font-family:var(--serif); font-size:1.1rem; }
        .modal-adicionais-titulo { font-size:.72rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--gray); padding:12px 20px; background:var(--bg); border-top:1px solid var(--rosa-border); border-bottom:1px solid var(--rosa-border); margin:0 -20px; }
        .modal-adicional { display:flex; align-items:center; justify-content:space-between; padding:13px 0; border-bottom:1px solid var(--bg); cursor:pointer; gap:12px; }
        .modal-adicional:last-child { border-bottom: none; }
        .modal-adicional-info { flex:1; min-width:0; }
        .modal-adicional-nome  { font-size:.88rem; font-weight:500; }
        .modal-adicional-preco { font-size:.78rem; color:var(--gray); margin-top:2px; }
        .modal-adicional-preco.extra { color: var(--rosa); }
        .check-custom { width:22px; height:22px; border-radius:6px; border:2px solid var(--rosa-border); background:var(--white); flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:all .2s; }
        .modal-adicional input[type="checkbox"] { display: none; }
        .modal-adicional input:checked + .check-custom { background:var(--rosa); border-color:var(--rosa); }
        .modal-adicional input:checked + .check-custom::after { content:''; display:block; width:5px; height:9px; border:2px solid #fff; border-top:none; border-left:none; transform:rotate(45deg) translate(-1px,-1px); }
        .modal-obs-titulo { font-size:.72rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--gray); padding:12px 20px; background:var(--bg); border-top:1px solid var(--rosa-border); border-bottom:1px solid var(--rosa-border); margin:0 -20px; }
        .modal-obs-area { width:100%; margin-top:12px; padding:10px 13px; border-radius:10px; border:1px solid var(--rosa-border); background:var(--bg); font-family:var(--sans); font-size:.88rem; color:var(--dark); resize:none; outline:none; transition:border-color .2s; }
        .modal-obs-area:focus { border-color: var(--rosa); }
        .modal-foot { position:sticky; bottom:0; background:var(--white); border-top:1px solid var(--rosa-border); padding:14px 20px; margin:0 -20px; display:flex; align-items:center; gap:12px; }
        .modal-qtd { display:flex; align-items:center; gap:0; border:1.5px solid var(--rosa-border); border-radius:10px; overflow:hidden; }
        .modal-qtd button { width:36px; height:38px; background:none; border:none; font-size:1.1rem; font-weight:700; cursor:pointer; color:var(--dark); transition:background .15s; }
        .modal-qtd button:hover { background: var(--rosa-light); }
        .modal-qtd span { font-size:.95rem; font-weight:700; min-width:28px; text-align:center; }
        .modal-confirmar { flex:1; padding:12px; border-radius:10px; background:var(--rosa); color:#fff; border:none; font-family:var(--sans); font-size:.92rem; font-weight:600; cursor:pointer; transition:opacity .2s; display:flex; align-items:center; justify-content:space-between; gap:8px; }
        .modal-confirmar:hover { opacity: .88; }
        .modal-confirmar-total { font-family:var(--serif); font-size:1rem; }

        /* OFERTA */
        .oferta { padding:60px 0; background:var(--dark); overflow:hidden; }
        .oferta-inner { display:grid; grid-template-columns:1fr 1fr; gap:48px; align-items:center; }
        .oferta-badge { display:inline-block; background:var(--rosa); color:#fff; font-size:.7rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; padding:4px 12px; border-radius:50px; margin-bottom:14px; }
        .oferta-texto h2 { font-family:var(--serif); font-size:clamp(1.8rem,4vw,2.6rem); color:#fff; line-height:1.15; margin-bottom:12px; }
        .oferta-texto h2 span { color: var(--rosa); }
        .oferta-texto p  { color:rgba(255,255,255,.6); font-size:.9rem; line-height:1.75; margin-bottom:20px; }
        .oferta-preco    { display:flex; align-items:baseline; gap:10px; margin-bottom:22px; }
        .oferta-de  { font-size:.9rem; color:rgba(255,255,255,.35); text-decoration:line-through; }
        .oferta-por { font-family:var(--serif); font-size:2rem; font-weight:700; color:var(--rosa); }
        .oferta-img { border-radius:16px; overflow:hidden; aspect-ratio:1/1; max-width:420px; }

        /* SOBRE */
        .sobre { padding:60px 0; }
        .sobre-inner { display:grid; grid-template-columns:1fr 1fr; gap:48px; align-items:center; }
        .sobre-foto  { border-radius:16px; overflow:hidden; aspect-ratio:4/3; }
        .sobre-tag   { display:inline-block; background:var(--rosa-light); color:var(--rosa); font-size:.75rem; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; padding:4px 12px; border-radius:50px; margin-bottom:14px; }
        .sobre-texto h2 { font-family:var(--serif); font-size:clamp(1.5rem,3vw,2.1rem); font-weight:700; line-height:1.2; margin-bottom:14px; }
        .sobre-texto p   { font-size:.88rem; color:var(--gray); line-height:1.8; margin-bottom:12px; }
        .sobre-nums  { display:flex; gap:24px; margin-top:24px; }
        .sobre-num .n { font-family:var(--serif); font-size:1.8rem; font-weight:700; color:var(--rosa); }
        .sobre-num .l { font-size:.76rem; color:var(--gray); margin-top:2px; }

        /* DEPOIMENTOS */
        .depoimentos { padding:60px 0; background:var(--bg); }
        .dep-grid  { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
        .dep-card  { background:var(--white); border:1px solid var(--rosa-border); border-radius:var(--r); padding:22px; }
        .dep-stars { color:var(--rosa); font-size:1rem; margin-bottom:10px; }
        .dep-texto { font-size:.85rem; color:var(--gray); line-height:1.7; margin-bottom:14px; }
        .dep-nome  { font-size:.78rem; font-weight:600; color:var(--dark); }

        /* CTA */
        .cta { padding:70px 0; background:var(--rosa); text-align:center; }
        .cta h2   { font-family:var(--serif); font-size:clamp(1.6rem,5vw,2.4rem); color:#fff; margin-bottom:10px; }
        .cta p    { color:rgba(255,255,255,.8); font-size:.9rem; margin-bottom:28px; }
        .cta-infos{ display:flex; justify-content:center; gap:20px; flex-wrap:wrap; margin-top:20px; }
        .cta-info { background:rgba(255,255,255,.15); color:#fff; font-size:.8rem; font-weight:500; padding:6px 16px; border-radius:50px; }

        /* FOOTER */
        .footer { background:var(--dark); padding:48px 0 28px; }
        .footer-grid { display:grid; grid-template-columns:2fr 1fr 1fr; gap:32px; margin-bottom:36px; }
        .footer-logo { font-family:var(--serif); font-size:1.25rem; font-weight:700; color:#fff; margin-bottom:10px; }
        .footer-logo span { color: var(--rosa); }
        .footer-desc { font-size:.82rem; color:rgba(255,255,255,.4); line-height:1.7; margin-bottom:18px; }
        .footer-social { display:flex; gap:10px; }
        .soc { width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,.07); display:flex; align-items:center; justify-content:center; transition:background .2s; }
        .soc:hover { background: var(--rosa); }
        .soc svg { width:15px; height:15px; stroke:rgba(255,255,255,.6); fill:none; stroke-width:2; }
        .soc:hover svg { stroke: #fff; }
        .footer h4 { font-size:.78rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:rgba(255,255,255,.4); margin-bottom:14px; }
        .footer ul { list-style:none; display:flex; flex-direction:column; gap:8px; }
        .footer ul a { font-size:.82rem; color:rgba(255,255,255,.5); transition:color .2s; }
        .footer ul a:hover { color: var(--rosa); }
        .footer-copy { font-size:.75rem; color:rgba(255,255,255,.2); border-top:1px solid rgba(255,255,255,.06); padding-top:24px; text-align:center; }

        /* CARRINHO SIDEBAR */
        .cart-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:800; opacity:0; pointer-events:none; transition:opacity .3s; }
        .cart-overlay.open { opacity:1; pointer-events:all; }
        .cart-sidebar { position:fixed; top:0; right:0; bottom:0; width:360px; max-width:100vw; background:var(--white); z-index:810; transform:translateX(100%); transition:transform .3s cubic-bezier(.32,.72,0,1); display:flex; flex-direction:column; box-shadow:-4px 0 24px rgba(0,0,0,.12); }
        .cart-sidebar.open { transform: translateX(0); }
        .cart-head { padding:18px 20px; border-bottom:1px solid var(--rosa-border); display:flex; align-items:center; justify-content:space-between; }
        .cart-head h3 { font-family:var(--serif); font-size:1.1rem; font-weight:700; }
        .cart-close { background: none; border: 1.5px solid var(--rosa-border); font-size: 1.1rem; cursor: pointer; color: var(--dark); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all .2s; flex-shrink: 0; }
.cart-close:hover { background: var(--rosa); color: #fff; border-color: var(--rosa); }
        .cart-items { flex:1; overflow-y:auto; padding:16px 20px; }
        .cart-vazio { font-size:.85rem; color:var(--gray); text-align:center; padding:32px 0; line-height:1.7; }
        .cart-item { display:flex; align-items:flex-start; gap:12px; padding:12px 0; border-bottom:1px solid var(--bg); }
        .cart-item:last-child { border-bottom: none; }
        .cart-item-img  { width:52px; height:52px; border-radius:10px; overflow:hidden; flex-shrink:0; border:1px solid var(--rosa-border); }
        .cart-item-info { flex:1; min-width:0; }
        .cart-item-nome  { font-size:.85rem; font-weight:600; }
        .cart-item-adds  { font-size:.74rem; color:var(--gray); margin-top:2px; line-height:1.5; }
        .cart-item-preco { font-family:var(--serif); font-size:.95rem; color:var(--rosa); font-weight:700; margin-top:4px; }
        .cart-item-qtd  { display:flex; align-items:center; gap:6px; margin-top:6px; }
        .cart-item-qtd button { width:22px; height:22px; border-radius:6px; border:1px solid var(--rosa-border); background:var(--white); cursor:pointer; font-size:.85rem; font-weight:700; display:flex; align-items:center; justify-content:center; transition:all .15s; }
        .cart-item-qtd button:hover { background:var(--rosa); color:#fff; border-color:var(--rosa); }
        .cart-item-qtd span { font-size:.85rem; font-weight:700; min-width:20px; text-align:center; }
        .cart-rm { background:none; border:none; cursor:pointer; color:var(--gray); font-size:.85rem; padding:4px; transition:color .2s; flex-shrink:0; margin-top:2px; }
        .cart-rm:hover { color: #dc2626; }

        .cart-foot { padding:16px 20px; border-top:1px solid var(--rosa-border); }
        .cart-entrega-info { font-size:.78rem; color:var(--gray); text-align:center; margin-bottom:10px; }
        .cart-entrega-info strong { color: #16a34a; }
        .cart-total { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
        .cart-total span:first-child { font-size:.85rem; color:var(--gray); }
        .total-valor { font-family:var(--serif); font-size:1.3rem; font-weight:700; color:var(--rosa); }

        /* ── BOTÃO FINALIZAR — vai para checkout ── */
        .btn-finalizar {
            width:100%; padding:14px; border-radius:12px;
            background:var(--rosa); color:#fff; border:none;
            font-family:var(--sans); font-size:.92rem; font-weight:600;
            cursor:pointer; transition:opacity .2s;
            display:flex; align-items:center; justify-content:center; gap:8px;
            text-decoration:none;
        }
        .btn-finalizar:hover { opacity: .88; }
        .btn-finalizar svg { width:18px; height:18px; stroke:currentColor; fill:none; stroke-width:2; }

        /* TOAST */
        .toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(80px); background:var(--dark); color:#fff; padding:11px 22px; border-radius:50px; font-size:.85rem; font-weight:500; z-index:2000; opacity:0; transition:all .3s; white-space:nowrap; pointer-events:none; }
        .toast.show { transform:translateX(-50%) translateY(0); opacity:1; }

        /* RESPONSIVO */
        @media (max-width:860px) {
            .nav-links { display: none; }
            .nav-hamburger { display: block; }
            .prod-grid { grid-template-columns: repeat(2,1fr); }
            .oferta-inner, .sobre-inner { grid-template-columns: 1fr; }
            .oferta-img { max-width: 100%; }
            .dep-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; gap: 24px; }
            .sobre-nums { gap: 16px; }
            .cart-sidebar { width: 100%; }
        }
        @media (max-width:560px) {
            .prod-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .modal-box { border-radius: 16px 16px 0 0; }
        }
        @media (max-width:380px) {
            .prod-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="container nav-inner">
        <a href="#inicio" class="nav-logo">Sabor<span>&</span>Cia</a>

        <ul class="nav-links" id="navLinks">
            <li><a href="#inicio">Início</a></li>
            <li><a href="produtos.php">Cardápio</a></li>
            <li><a href="#ofertas">Ofertas</a></li>
            <li><a href="#sobre">Sobre</a></li>
        </ul>

        <div class="nav-dir">
            <?php if ($logado): ?>
            <div class="nav-avatar" title="<?= htmlspecialchars($cliente_nome) ?>">
                <?= mb_strtoupper(mb_substr($cliente_nome, 0, 1)) ?>
                <div class="nav-avatar-menu">
                    <div class="nav-avatar-menu-inner">
                        <a href="minha-conta.php">
                            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Minha conta
                        </a>
                        <a href="meus-pedidos.php">
                            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>
                            Meus pedidos
                        </a>
                        <div class="nav-avatar-sep">
                            <a href="logout.php">
                                <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                Sair
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <a href="login.php" class="nav-login-btn">
                <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Entrar
            </a>
            <?php endif; ?>

            <button class="nav-cart" id="btnAbrirCart" aria-label="Carrinho">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <span class="cart-badge" id="cartBadge"></span>
            </button>

            <button class="nav-hamburger" id="btnHamburger" aria-label="Menu">
                <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero" id="inicio">
    <div class="container hero-content">
        <span class="hero-tag">Fresquinho todo dia</span>
        <h1>O sabor que conquista desde a <em>primeira mordida</em></h1>
        <p>Açaí cremoso, burgers artesanais, doces incríveis e bebidas geladas. Feito com amor, entregue na sua porta.</p>
        <div class="hero-btns">
            <a href="#cardapio" class="btn btn-rosa">Ver cardápio</a>
            <a href="https://wa.me/<?= $whatsapp ?>?text=Oi!%20Quero%20fazer%20um%20pedido" target="_blank" rel="noopener" class="btn btn-ghost">Pedir pelo WhatsApp</a>
        </div>
    </div>
</section>

<!-- CATEGORIAS -->
<section class="categorias" id="cardapio">
    <div class="container">
        <div class="sec-header">
            <h2>O que você quer hoje?</h2>
            <p>Toque numa categoria para filtrar</p>
            <span class="sec-linha"></span>
        </div>
        <div class="cat-scroll">
            <div class="cat-card ativo" data-cat="todos" onclick="filtrar(this,'todos')">
                <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=120&q=80" alt="Todos">
                <span>Tudo</span>
            </div>
            <?php foreach ($categorias as $c): ?>
            <div class="cat-card" data-cat="<?= htmlspecialchars($c['slug']) ?>" onclick="filtrar(this,'<?= htmlspecialchars($c['slug']) ?>')">
                <img src="<?= htmlspecialchars($c['imagem_url'] ?? '') ?>" alt="<?= htmlspecialchars($c['nome']) ?>">
                <span><?= htmlspecialchars($c['nome']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PRODUTOS -->
<section class="produtos" id="produtos">
    <div class="container">
        <div class="sec-header">
            <h2>Mais pedidos</h2>
            <p>Os queridinhos dos nossos clientes</p>
            <span class="sec-linha"></span>
        </div>
        <div class="prod-grid" id="prodGrid">
            <?php foreach ($produtos as $p): ?>
            <div class="prod-card" data-cat="<?= htmlspecialchars($p['cat_slug']) ?>">
                <div class="prod-img">
                    <img src="<?= htmlspecialchars($p['imagem_url'] ?? '') ?>" alt="<?= htmlspecialchars($p['nome']) ?>" loading="lazy">
                </div>
                <div class="prod-body">
                    <div class="prod-nome"><?= htmlspecialchars($p['nome']) ?></div>
                    <?php if (!empty($p['descricao'])): ?>
                    <div class="prod-desc"><?= htmlspecialchars($p['descricao']) ?></div>
                    <?php endif; ?>
                    <div class="prod-footer">
                        <div class="prod-preco">R$ <?= number_format($p['preco'], 2, ',', '.') ?></div>
                        <button class="btn-add" onclick="abrirModal(<?= (int)$p['id'] ?>)">
                            <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Adicionar
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- OFERTA DA SEMANA -->
<section class="oferta" id="ofertas">
    <div class="container oferta-inner">
        <div class="oferta-texto">
            <span class="oferta-badge">Oferta da semana</span>
            <h2>Combo Smash<br><span>+ Açaí 500ml</span></h2>
            <p>Burger artesanal com blend da casa, cheddar e bacon crocante + açaí cremoso com granola e banana.</p>
            <div class="oferta-preco">
                <span class="oferta-de">R$ 59,80</span>
                <span class="oferta-por">R$ 44,90</span>
            </div>
            <button class="btn btn-rosa" onclick="addCartDireto(99,'Combo Smash + Açaí 500ml',44.90,'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&q=80')">
                Aproveitar combo
            </button>
        </div>
        <div class="oferta-img">
            <img src="https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=700&q=80" alt="Combo da semana">
        </div>
    </div>
</section>

<!-- SOBRE -->
<section class="sobre" id="sobre">
    <div class="container sobre-inner">
        <div class="sobre-foto">
            <img src="https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=80" alt="Nossa cozinha">
        </div>
        <div class="sobre-texto">
            <span class="sobre-tag">Nossa história</span>
            <h2>Feito com carinho,<br>desde o primeiro dia</h2>
            <p>Começamos pequenos, com uma tigela de açaí e muita vontade de fazer diferente. Hoje somos a lanchonete favorita do bairro.</p>
            <p>Cada item do cardápio é pensado para te surpreender. Do açaí cremoso ao smash burger perfeito.</p>
            <div class="sobre-nums">
                <div class="sobre-num"><div class="n">500+</div><div class="l">Pedidos por semana</div></div>
                <div class="sobre-num"><div class="n">4.9</div><div class="l">Avaliação média</div></div>
                <div class="sobre-num"><div class="n">3 anos</div><div class="l">De muito sabor</div></div>
            </div>
        </div>
    </div>
</section>

<!-- DEPOIMENTOS -->
<section class="depoimentos">
    <div class="container">
        <div class="sec-header">
            <h2>O que dizem nossos clientes</h2>
            <p>Quem prova, sempre volta</p>
            <span class="sec-linha"></span>
        </div>
        <div class="dep-grid">
            <?php foreach ($depoimentos as $d): ?>
            <div class="dep-card">
                <div class="dep-stars"><?= str_repeat('★', $d['nota']) ?></div>
                <p class="dep-texto">"<?= htmlspecialchars($d['texto']) ?>"</p>
                <span class="dep-nome">— <?= htmlspecialchars($d['nome']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta">
    <div class="container">
        <h2>Bateu a fome?<br>A gente resolve agora.</h2>
        <p>Monte seu pedido e finalize em segundos.</p>
        <a href="#cardapio" class="btn btn-wpp">Ver cardápio completo</a>
        <div class="cta-infos">
            <span class="cta-info">Entrega em até <?= $entrega_tempo ?> min</span>
            <span class="cta-info">Raio de <?= $entrega_raio ?> km</span>
            <span class="cta-info">Qualidade garantida</span>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-logo">Sabor<span>&</span>Cia</div>
                <p class="footer-desc">Açaí, hambúrgueres artesanais, doces e bebidas geladas.</p>
                <div class="footer-social">
                    <a href="#" class="soc" aria-label="Instagram"><svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
                    <a href="#" class="soc" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                    <a href="https://wa.me/<?= $whatsapp ?>" class="soc" aria-label="WhatsApp" target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg></a>
                </div>
            </div>
            <div>
                <h4>Cardápio</h4>
                <ul>
                    <?php foreach ($categorias as $c): ?>
                    <li><a href="#cardapio"><?= htmlspecialchars($c['nome']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h4>Contato</h4>
                <ul>
                    <li><a href="https://wa.me/<?= $whatsapp ?>" target="_blank" rel="noopener">WhatsApp</a></li>
                    <li><a href="#"><?= htmlspecialchars($cfg['loja_endereco'] ?? 'Rua das Flores, 123') ?></a></li>
                    <li><a href="#">Seg–Dom, 11h–23h</a></li>
                    <li><a href="mailto:<?= htmlspecialchars($cfg['loja_email'] ?? '') ?>"><?= htmlspecialchars($cfg['loja_email'] ?? 'contato@saborecia.com') ?></a></li>
                </ul>
            </div>
        </div>
        <p class="footer-copy">© <?= date('Y') ?> <?= htmlspecialchars($loja_nome) ?> — Todos os direitos reservados.</p>
    </div>
</footer>

<!-- MODAL ADICIONAIS -->
<div class="modal-overlay" id="modalOverlay" onclick="fecharModalFora(event)">
    <div class="modal-box" id="modalBox">
        <div class="modal-img"><img id="modalImgEl" src="" alt=""></div>
        <div class="modal-corpo">
            <div class="modal-drag"></div>
            <div class="modal-nome" id="modalNome"></div>
            <div class="modal-desc" id="modalDesc"></div>
            <div class="modal-preco-base">A partir de <strong id="modalPrecoBase"></strong></div>
            <div id="modalAdicionaisWrap"></div>
            <div class="modal-obs-titulo" id="modalObsTitulo">Alguma observação?</div>
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

<!-- CARRINHO SIDEBAR -->
<div class="cart-overlay" id="cartOverlay"></div>
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-head">
        <h3>Meu carrinho</h3>
        <button class="cart-close" id="btnFecharCart">&#x2715;</button>
    </div>
    <div class="cart-items" id="cartItems">
        <p class="cart-vazio">Seu carrinho está vazio.<br>Adicione algo gostoso!</p>
    </div>
    <div class="cart-foot">
        <div class="cart-entrega-info" id="cartEntregaInfo"></div>
        <div class="cart-total">
            <span>Subtotal</span>
            <span class="total-valor" id="cartTotal">R$ 0,00</span>
        </div>
        <!-- Vai para checkout, não mais para WhatsApp direto -->
        <a href="checkout.php" class="btn-finalizar" id="btnFinalizar">
            <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
            Finalizar pedido
        </a>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
var PRODUTOS        = <?= json_encode(array_values($produtos_js), JSON_UNESCAPED_UNICODE) ?>;
var WHATSAPP        = "<?= $whatsapp ?>";
var TAXA_ENTREGA    = <?= $entrega_taxa ?>;
var GRATIS_ACIMA    = <?= $entrega_gratis ?>;

var PROD_MAP = {};
PRODUTOS.forEach(function(p){ PROD_MAP[p.id] = p; });
</script>

<script>
// ═══════════════════════════════════
// ESTADO
// ═══════════════════════════════════
var cart    = JSON.parse(localStorage.getItem('sc_cart') || '[]');
var modalId = null;
var modalQtd= 1;

renderCart();

// Scroll navbar
window.addEventListener('scroll', function(){
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 30);
});

// Mobile menu
document.getElementById('btnHamburger').addEventListener('click', function(){
    var links = document.getElementById('navLinks');
    var aberto = links.style.display === 'flex';
    links.style.display    = aberto ? 'none' : 'flex';
    links.style.flexDirection = 'column';
    links.style.position   = 'absolute';
    links.style.top        = '60px';
    links.style.left       = '0';
    links.style.right      = '0';
    links.style.background = '#fff';
    links.style.padding    = '16px 18px';
    links.style.borderBottom = '1px solid var(--rosa-border)';
    links.style.gap        = '16px';
    links.style.zIndex     = '800';
});

// ─── CARRINHO ─────────────────────
document.getElementById('btnAbrirCart').addEventListener('click',  function(){ toggleCart(true); });
document.getElementById('btnFecharCart').addEventListener('click', function(){ toggleCart(false); });
document.getElementById('cartOverlay').addEventListener('click',   function(){ toggleCart(false); });

function toggleCart(open){
    document.getElementById('cartSidebar').classList.toggle('open', open);
    document.getElementById('cartOverlay').classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
}

// Botão finalizar — só vai para checkout se tiver itens
document.getElementById('btnFinalizar').addEventListener('click', function(e){
    if (cart.length === 0){
        e.preventDefault();
        showToast('Adicione itens ao carrinho primeiro!');
    }
    // se tem itens, o <a href="checkout.php"> navega normalmente
});

// ─── FILTRO POR CATEGORIA ──────────
function filtrar(el, cat){
    if (!el) return;
    document.querySelectorAll('.cat-card').forEach(function(c){ c.classList.remove('ativo'); });
    el.classList.add('ativo');
    document.querySelectorAll('.prod-card').forEach(function(c){
        c.style.display = (cat === 'todos' || c.dataset.cat === cat) ? '' : 'none';
    });
    document.getElementById('produtos').scrollIntoView({ behavior:'smooth', block:'start' });
}

// ─── MODAL ADICIONAIS ──────────────
function abrirModal(prodId){
    var p = PROD_MAP[prodId];
    if (!p) return;
    modalId  = prodId;
    modalQtd = 1;
    document.getElementById('modalImgEl').src   = p.imagem;
    document.getElementById('modalImgEl').alt   = p.nome;
    document.getElementById('modalNome').textContent = p.nome;
    document.getElementById('modalDesc').textContent = p.descricao;
    document.getElementById('modalPrecoBase').textContent = 'R$ ' + p.preco.toFixed(2).replace('.',',');
    document.getElementById('modalQtd').textContent  = '1';
    document.getElementById('modalObs').value        = '';

    var wrap = document.getElementById('modalAdicionaisWrap');
    if (p.adicionais && p.adicionais.length > 0){
        var html = '<div class="modal-adicionais-titulo">Adicionais e personalizações</div>';
        p.adicionais.forEach(function(a, i){
            var precoLabel = a.preco_extra > 0 ? '+R$ ' + a.preco_extra.toFixed(2).replace('.',',') : 'Grátis';
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
    } else {
        wrap.innerHTML = '';
    }

    atualizarTotalModal();
    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';

    wrap.querySelectorAll('.add-check').forEach(function(cb){
        cb.addEventListener('change', atualizarTotalModal);
    });
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
    modalQtd = Math.max(1, modalQtd + delta);
    document.getElementById('modalQtd').textContent = modalQtd;
    atualizarTotalModal();
}

function atualizarTotalModal(){
    if (!modalId) return;
    var p = PROD_MAP[modalId];
    var extra = 0;
    document.querySelectorAll('.add-check:checked').forEach(function(cb){
        extra += parseFloat(cb.dataset.preco || 0);
    });
    var total = (p.preco + extra) * modalQtd;
    document.getElementById('modalTotal').textContent = 'R$ ' + total.toFixed(2).replace('.',',');
}

function confirmarAdicional(){
    if (!modalId) return;
    var p    = PROD_MAP[modalId];
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
        if (cart[i].chave === chave){ cart[i].qtd += modalQtd; found = true; break; }
    }
    if (!found){
        cart.push({ chave:chave, id:modalId, nome:p.nome, preco:precoFinal, preco_base:p.preco, extra:extra, img:p.imagem, adicionais:adds, obs:obs, qtd:modalQtd });
    }
    salvarCart();
    renderCart();
    showToast(p.nome + ' adicionado!');
    fecharModal();
}

function addCartDireto(id, nome, preco, img){
    var chave = id + '||';
    var found = false;
    for (var i = 0; i < cart.length; i++){
        if (cart[i].chave === chave){ cart[i].qtd++; found = true; break; }
    }
    if (!found) cart.push({ chave:chave, id:id, nome:nome, preco:preco, preco_base:preco, extra:0, img:img, adicionais:[], obs:'', qtd:1 });
    salvarCart();
    renderCart();
    showToast(nome + ' adicionado!');
}

// ─── RENDERIZAR CARRINHO ───────────
function renderCart(){
    var total = 0, totalItens = 0;
    cart.forEach(function(i){ total += i.preco * i.qtd; totalItens += i.qtd; });

    var badge = document.getElementById('cartBadge');
    badge.textContent  = totalItens;
    badge.style.display = totalItens > 0 ? 'flex' : 'none';

    var entregaEl = document.getElementById('cartEntregaInfo');
    if (total > 0 && GRATIS_ACIMA > 0){
        if (total >= GRATIS_ACIMA){
            entregaEl.innerHTML = '<strong>Entrega grátis!</strong>';
        } else {
            var faltam = (GRATIS_ACIMA - total).toFixed(2).replace('.',',');
            entregaEl.innerHTML = 'Faltam <strong>R$ ' + faltam + '</strong> para entrega grátis';
        }
    } else {
        entregaEl.innerHTML = '';
    }

    var el = document.getElementById('cartItems');
    if (cart.length === 0){
        el.innerHTML = '<p class="cart-vazio">Seu carrinho está vazio.<br>Adicione algo gostoso!</p>';
    } else {
        var html = '';
        cart.forEach(function(it, idx){
            var sub      = (it.preco * it.qtd).toFixed(2).replace('.',',');
            var addsText = it.adicionais.length > 0 ? it.adicionais.join(', ') : '';
            if (it.obs) addsText += (addsText ? ' • ' : '') + it.obs;
            html += '<div class="cart-item">'
                + '<div class="cart-item-img"><img src="' + it.img + '" alt="' + it.nome + '"></div>'
                + '<div class="cart-item-info">'
                + '<div class="cart-item-nome">' + it.nome + '</div>'
                + (addsText ? '<div class="cart-item-adds">' + addsText + '</div>' : '')
                + '<div class="cart-item-preco">R$ ' + sub + '</div>'
                + '<div class="cart-item-qtd">'
                + '<button onclick="mudarQtdCart(' + idx + ',-1)">−</button>'
                + '<span>' + it.qtd + '</span>'
                + '<button onclick="mudarQtdCart(' + idx + ',1)">+</button>'
                + '</div>'
                + '</div>'
                + '<button class="cart-rm" onclick="removeCart(' + idx + ')" title="Remover">✕</button>'
                + '</div>';
        });
        el.innerHTML = html;
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
function salvarCart(){
    localStorage.setItem('sc_cart', JSON.stringify(cart));
}

// ─── TOAST ────────────────────────
var toastTimer;
function showToast(msg){
    var el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ el.classList.remove('show'); }, 2500);
}

document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') fecharModal();
});
</script>

</body>
</html>