<?php

require_once __DIR__ . '/../inc/config.php';  
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';


// ============================================
// produtos.php — Cardápio completo
// ============================================
$todos = [
    ["id"=>1, "nome"=>"Açaí Premium 500ml",    "preco"=>18.90,"cat"=>"acai",      "desc"=>"Açaí cremoso com granola, banana e leite condensado.",         "img"=>"https://images.unsplash.com/photo-1590301157890-4810ed352733?w=600&q=80"],
    ["id"=>2, "nome"=>"Açaí Tradicional 300ml", "preco"=>12.90,"cat"=>"acai",      "desc"=>"Açaí puro batido na hora, simples e fresquinho.",              "img"=>"https://images.unsplash.com/photo-1511690743698-d9d85f2fbf38?w=600&q=80"],
    ["id"=>3, "nome"=>"Açaí com Morango 400ml", "preco"=>15.90,"cat"=>"acai",      "desc"=>"Açaí cremoso com morangos frescos e mel.",                     "img"=>"https://images.unsplash.com/photo-1544145945-f90425340c7e?w=600&q=80"],
    ["id"=>4, "nome"=>"Hambúrguer Smash",        "preco"=>29.90,"cat"=>"hamburguer","desc"=>"Blend da casa 180g, queijo cheddar, alface e tomate.",         "img"=>"https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&q=80"],
    ["id"=>5, "nome"=>"Combo Smash Duplo",       "preco"=>42.90,"cat"=>"hamburguer","desc"=>"Dois blends 180g, bacon, cheddar e molho especial da casa.",  "img"=>"https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=600&q=80"],
    ["id"=>6, "nome"=>"Smash Bacon",             "preco"=>34.90,"cat"=>"hamburguer","desc"=>"Blend 180g, bacon crocante, queijo e cebola caramelizada.",   "img"=>"https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?w=600&q=80"],
    ["id"=>7, "nome"=>"Bolo de Pote Ninho",      "preco"=>14.90,"cat"=>"doces",     "desc"=>"Bolo cremoso de leite ninho com cobertura de Nutella.",        "img"=>"https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=600&q=80"],
    ["id"=>8, "nome"=>"Bolo de Pote Oreo",       "preco"=>14.90,"cat"=>"doces",     "desc"=>"Bolo de chocolate com creme de Oreo e biscoito triturado.",   "img"=>"https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600&q=80"],
    ["id"=>9, "nome"=>"Brigadeiro Gourmet",      "preco"=>6.90, "cat"=>"doces",     "desc"=>"Brigadeiro artesanal com chocolate belga 70%.",               "img"=>"https://images.unsplash.com/photo-1611293388250-580b08c4a145?w=600&q=80"],
    ["id"=>10,"nome"=>"Milkshake Oreo",          "preco"=>16.90,"cat"=>"bebidas",   "desc"=>"Milkshake cremoso de baunilha com Oreo triturado.",           "img"=>"https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&q=80"],
    ["id"=>11,"nome"=>"Suco de Laranja",         "preco"=>9.90, "cat"=>"bebidas",   "desc"=>"Suco natural de laranja espremido na hora, 400ml.",           "img"=>"https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?w=600&q=80"],
    ["id"=>12,"nome"=>"Refrigerante Lata",       "preco"=>6.00, "cat"=>"bebidas",   "desc"=>"Coca-Cola, Guaraná ou Sprite. Lata 350ml gelada.",            "img"=>"https://images.unsplash.com/photo-1581636625402-29b2a704ef13?w=600&q=80"],
];

$tabs = [
    "todos"      => "Tudo",
    "acai"       => "Açaí",
    "hamburguer" => "Hambúrguer",
    "doces"      => "Doces",
    "bebidas"    => "Bebidas",
];

$cat_ativa = $_GET['cat']   ?? 'todos';
$busca     = trim($_GET['q'] ?? '');

$lista = array_filter($todos, function($p) use ($cat_ativa, $busca) {
    $ok_cat   = ($cat_ativa === 'todos' || $p['cat'] === $cat_ativa);
    $ok_busca = (empty($busca) || stripos($p['nome'], $busca) !== false);
    return $ok_cat && $ok_busca;
});
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio — Sabor &amp; Cia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/public.css">
   
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="container nav-inner">
        <a href="index.php" class="nav-logo">Sabor<span>&</span>Cia</a>
        <ul class="nav-links">
            <li><a href="index.php">Início</a></li>
            <li><a href="produtos.php" style="color:var(--rosa)">Cardápio</a></li>
            <li><a href="index.php#ofertas">Ofertas</a></li>
            <li><a href="index.php#sobre">Sobre</a></li>
        </ul>
        <button class="nav-cart" id="btnAbrirCart" aria-label="Carrinho">
            <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span class="cart-badge" id="cartBadge"></span>
        </button>
    </div>
</nav>

<!-- PAGE HERO -->
<section class="page-hero">
    <div class="container page-hero-inner">
        <h1>Nosso <em>cardápio</em></h1>
        <p>Escolha o que quiser e adicione direto ao carrinho.</p>
    </div>
</section>

<!-- FILTROS -->
<div class="filtros-bar">
    <div class="container filtros-inner">

        <div class="filtros-tabs">
            <?php foreach ($tabs as $slug => $label): ?>
            <a href="produtos.php?cat=<?= $slug ?><?= $busca ? '&q='.urlencode($busca) : '' ?>"
               class="tab <?= $cat_ativa === $slug ? 'ativo' : '' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <form method="GET" action="produtos.php" class="busca-form">
            <?php if ($cat_ativa !== 'todos'): ?>
            <input type="hidden" name="cat" value="<?= htmlspecialchars($cat_ativa) ?>">
            <?php endif; ?>
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" class="busca-input"
                placeholder="Buscar..."
                value="<?= htmlspecialchars($busca) ?>"
                autocomplete="off">
        </form>

    </div>
</div>

<!-- PRODUTOS -->
<section class="pag-produtos">
    <div class="container">

        <p class="resultado">
            <?php if ($busca): ?>
                Resultados para <strong>"<?= htmlspecialchars($busca) ?>"</strong> —
            <?php endif; ?>
            <strong><?= count($lista) ?></strong> produto<?= count($lista) !== 1 ? 's' : '' ?> encontrado<?= count($lista) !== 1 ? 's' : '' ?>
        </p>

        <div class="cards-grid">
            <?php if (empty($lista)): ?>
            <div class="vazio">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Nenhum produto encontrado.
            </div>
            <?php else: ?>
            <?php foreach ($lista as $p): ?>
            <div class="card">
                <div class="card-img">
                    <img src="<?= $p['img'] ?>" alt="<?= htmlspecialchars($p['nome']) ?>" loading="lazy">
                </div>
                <div class="card-body">
                    <div class="card-nome"><?= htmlspecialchars($p['nome']) ?></div>
                    <div class="card-desc"><?= htmlspecialchars($p['desc']) ?></div>
                    <div class="card-rodape">
                        <span class="card-preco">R$ <?= number_format($p['preco'], 2, ',', '.') ?></span>
                        <button class="card-add"
                            onclick="addCart(<?= $p['id'] ?>,'<?= addslashes(htmlspecialchars($p['nome'])) ?>',<?= $p['preco'] ?>,'<?= $p['img'] ?>')">
                            + Adicionar
                        </button>
                    </div>
                    <a href="produto.php?id=<?= $p['id'] ?>" class="card-ver">Ver detalhes</a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-logo">Sabor<span>&</span>Cia</div>
                <p class="footer-desc">Açaí, hambúrgueres artesanais, doces e bebidas geladas. Tudo com amor e sabor.</p>
                <div class="footer-social">
                    <a href="#" class="soc" aria-label="Instagram">
                        <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <a href="#" class="soc" aria-label="Facebook">
                        <svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <a href="https://wa.me/5581987028550" class="soc" aria-label="WhatsApp" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    </a>
                </div>
            </div>
            <div>
                <h4>Cardápio</h4>
                <ul>
                    <li><a href="produtos.php?cat=acai">Açaí</a></li>
                    <li><a href="produtos.php?cat=hamburguer">Hambúrgueres</a></li>
                    <li><a href="produtos.php?cat=doces">Doces</a></li>
                    <li><a href="produtos.php?cat=bebidas">Bebidas</a></li>
                </ul>
            </div>
            <div>
                <h4>Contato</h4>
                <ul>
                    <li><a href="https://wa.me/5581987028550" target="_blank" rel="noopener">WhatsApp</a></li>
                    <li><a href="#">Rua das Flores, 123</a></li>
                    <li><a href="#">Seg–Dom, 11h–23h</a></li>
                    <li><a href="mailto:contato@saborecia.com">contato@saborecia.com</a></li>
                </ul>
            </div>
        </div>
        <p class="footer-copy">© <?= date('Y') ?> Sabor&amp;Cia — Todos os direitos reservados.</p>
    </div>
</footer>

<!-- CARRINHO -->
<div class="cart-overlay" id="cartOverlay"></div>
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-head">
        <h3>Meu carrinho</h3>
        <button class="cart-close" id="btnFecharCart" aria-label="Fechar">&#x2715;</button>
    </div>
    <div class="cart-items" id="cartItems">
        <p class="cart-vazio">Seu carrinho está vazio.<br>Adicione algo gostoso!</p>
    </div>
    <div class="cart-foot">
        <div class="cart-total">
            <span>Total</span>
            <span id="cartTotal">R$ 0,00</span>
        </div>
        <button class="btn btn-wpp" style="width:100%;justify-content:center;" id="btnFinalizarWpp">
            Finalizar pelo WhatsApp
        </button>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
    var cart = [];

    window.addEventListener('scroll', function() {
        document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 30);
    });

    document.getElementById('btnAbrirCart').addEventListener('click', function() { toggleCart(true); });
    document.getElementById('btnFecharCart').addEventListener('click', function() { toggleCart(false); });
    document.getElementById('cartOverlay').addEventListener('click', function() { toggleCart(false); });

    function toggleCart(open) {
        document.getElementById('cartSidebar').classList.toggle('open', open);
        document.getElementById('cartOverlay').classList.toggle('open', open);
        document.body.style.overflow = open ? 'hidden' : '';
    }

    function addCart(id, nome, preco, img) {
        var found = false;
        for (var i = 0; i < cart.length; i++) {
            if (cart[i].id === id) { cart[i].qtd++; found = true; break; }
        }
        if (!found) cart.push({ id:id, nome:nome, preco:preco, img:img, qtd:1 });
        renderCart();
        showToast(nome + ' adicionado!');
    }

    function removeCart(id) {
        cart = cart.filter(function(i) { return i.id !== id; });
        renderCart();
    }

    function renderCart() {
        var total = 0, itens = 0;
        for (var i = 0; i < cart.length; i++) { total += cart[i].preco * cart[i].qtd; itens += cart[i].qtd; }

        var badge = document.getElementById('cartBadge');
        badge.textContent = itens;
        badge.style.display = itens > 0 ? 'flex' : 'none';

        var el = document.getElementById('cartItems');
        if (!cart.length) {
            el.innerHTML = '<p class="cart-vazio">Seu carrinho está vazio.<br>Adicione algo gostoso!</p>';
        } else {
            var html = '';
            for (var j = 0; j < cart.length; j++) {
                var it = cart[j];
                html += '<div class="cart-item">'
                    + '<div class="cart-item-img"><img src="' + it.img + '" alt="' + it.nome + '"></div>'
                    + '<div class="cart-item-info">'
                    + '<div class="cart-item-nome">' + it.nome + (it.qtd > 1 ? ' x' + it.qtd : '') + '</div>'
                    + '<div class="cart-item-preco">R$ ' + (it.preco * it.qtd).toFixed(2).replace('.', ',') + '</div>'
                    + '</div>'
                    + '<button class="cart-rm" onclick="removeCart(' + it.id + ')">&#x2715;</button>'
                    + '</div>';
            }
            el.innerHTML = html;
        }

        document.getElementById('cartTotal').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');

        var pedido = cart.map(function(i) { return i.qtd + 'x ' + i.nome; }).join(', ');
        var msg = encodeURIComponent('Olá! Quero fazer um pedido:\n' + pedido + '\nTotal: R$ ' + total.toFixed(2).replace('.', ','));
        document.getElementById('btnFinalizarWpp').onclick = function() {
            window.open('https://wa.me/5581987028550?text=' + msg, '_blank', 'noopener');
        };
    }

    var toastTimer;
    function showToast(msg) {
        var el = document.getElementById('toast');
        el.textContent = msg;
        el.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function() { el.classList.remove('show'); }, 2500);
    }
</script>
</body>
</html>