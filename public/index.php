<?php
// ============================================
// DADOS SIMULADOS — trocar por banco depois
// ============================================
$produtos = [
    ["id"=>1,"nome"=>"Açaí Premium 500ml",    "preco"=>18.90,"categoria"=>"acai",      "imagem"=>"https://images.unsplash.com/photo-1590301157890-4810ed352733?w=600&q=80"],
    ["id"=>2,"nome"=>"Hambúrguer Smash",       "preco"=>29.90,"categoria"=>"hamburguer","imagem"=>"https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&q=80"],
    ["id"=>3,"nome"=>"Bolo de Pote Ninho",     "preco"=>14.90,"categoria"=>"doces",     "imagem"=>"https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=600&q=80"],
    ["id"=>4,"nome"=>"Milkshake Oreo",         "preco"=>16.90,"categoria"=>"bebidas",   "imagem"=>"https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&q=80"],
    ["id"=>5,"nome"=>"Açaí Tradicional 300ml", "preco"=>12.90,"categoria"=>"acai",      "imagem"=>"https://images.unsplash.com/photo-1511690743698-d9d85f2fbf38?w=600&q=80"],
    ["id"=>6,"nome"=>"Combo Smash Duplo",      "preco"=>42.90,"categoria"=>"hamburguer","imagem"=>"https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=600&q=80"],
];

$categorias = [
    ["slug"=>"acai",      "nome"=>"Açaí",      "img"=>"https://images.unsplash.com/photo-1590301157890-4810ed352733?w=120&q=80"],
    ["slug"=>"hamburguer","nome"=>"Hambúrguer", "img"=>"https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=120&q=80"],
    ["slug"=>"doces",     "nome"=>"Doces",      "img"=>"https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=120&q=80"],
    ["slug"=>"bebidas",   "nome"=>"Bebidas",    "img"=>"https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=120&q=80"],
];

$depoimentos = [
    ["nome"=>"Ana Clara",  "texto"=>"Melhor açaí da cidade! Sempre fresquinho e cheio de sabor.", "nota"=>5],
    ["nome"=>"Rafael M.",  "texto"=>"O smash burger é incrível, já virei cliente fiel mesmo.",    "nota"=>5],
    ["nome"=>"Juliana P.", "texto"=>"Atendimento rápido e os bolos de pote são demais!",          "nota"=>5],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabor &amp; Cia — Açaí, Burgers, Doces e Bebidas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/public.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="container nav-inner">
        <a href="#inicio" class="nav-logo">Sabor<span>&</span>Cia</a>
        <ul class="nav-links">
            <li><a href="#inicio">Início</a></li>
            <li><a href="#cardapio">Cardápio</a></li>
            <li><a href="#ofertas">Ofertas</a></li>
            <li><a href="#sobre">Sobre</a></li>
        </ul>
        <button class="nav-cart" id="btnAbrirCart" aria-label="Carrinho">
            <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span class="cart-badge" id="cartBadge"></span>
        </button>
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
            <a href="https://wa.me/5581987028550?text=Oi!%20Quero%20fazer%20um%20pedido" target="_blank" rel="noopener" class="btn btn-ghost">Pedir agora</a>
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
            <div class="cat-card" data-cat="<?= $c['slug'] ?>" onclick="filtrar(this,'<?= $c['slug'] ?>')">
                <img src="<?= $c['img'] ?>" alt="<?= htmlspecialchars($c['nome']) ?>">
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
            <div class="prod-card" data-cat="<?= $p['categoria'] ?>">
                <div class="prod-img">
                    <img src="<?= $p['imagem'] ?>" alt="<?= htmlspecialchars($p['nome']) ?>" loading="lazy">
                </div>
                <div class="prod-body">
                    <div class="prod-nome"><?= htmlspecialchars($p['nome']) ?></div>
                    <div class="prod-preco">R$ <?= number_format($p['preco'], 2, ',', '.') ?></div>
                    <button class="btn-add" onclick="addCart(<?= $p['id'] ?>,'<?= addslashes(htmlspecialchars($p['nome'])) ?>',<?= $p['preco'] ?>,'<?= $p['imagem'] ?>')">
                        + Adicionar
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- OFERTA -->
<section class="oferta" id="ofertas">
    <div class="container oferta-inner">
        <div class="oferta-texto">
            <span class="oferta-badge">Oferta da semana</span>
            <h2>Combo Smash<br><span>+ Açaí 500ml</span></h2>
            <p>Burger artesanal com blend da casa, cheddar e bacon crocante + açaí cremoso com granola e banana. Um preço impossível de recusar.</p>
            <div class="oferta-preco">
                <span class="oferta-de">R$ 59,80</span>
                <span class="oferta-por">R$ 44,90</span>
            </div>
            <button class="btn btn-rosa" onclick="addCart(99,'Combo Smash + Açaí',44.90,'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&q=80')">
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
            <p>Começamos pequenos, com uma tigela de açaí e muita vontade de fazer diferente. Hoje somos a lanchonete favorita do bairro — sem perder a essência de sempre: comida de verdade, ingredientes selecionados e muito afeto.</p>
            <p>Cada item do cardápio é pensado para te surpreender. Do açaí cremoso ao smash burger perfeito, aqui cada detalhe importa.</p>
            <div class="sobre-nums">
                <div class="sobre-num">
                    <div class="n">500+</div>
                    <div class="l">Pedidos por semana</div>
                </div>
                <div class="sobre-num">
                    <div class="n">4.9</div>
                    <div class="l">Avaliação média</div>
                </div>
                <div class="sobre-num">
                    <div class="n">3 anos</div>
                    <div class="l">De muito sabor</div>
                </div>
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

<!-- CTA FINAL -->
<section class="cta">
    <div class="container">
        <h2>Bateu a fome?<br>A gente resolve agora.</h2>
        <p>Peça pelo WhatsApp e receba em minutos. Sem complicação.</p>
        <a href="https://wa.me/5581987028550?text=Oi!%20Quero%20fazer%20um%20pedido" target="_blank" rel="noopener" class="btn btn-wpp">
            Pedir pelo WhatsApp
        </a>
        <div class="cta-infos">
            <span class="cta-info">Entrega em até 40 min</span>
            <span class="cta-info">Raio de 5 km</span>
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
                    <li><a href="#cardapio">Açaí</a></li>
                    <li><a href="#cardapio">Hambúrgueres</a></li>
                    <li><a href="#cardapio">Doces</a></li>
                    <li><a href="#cardapio">Bebidas</a></li>
                    <li><a href="#ofertas">Combos</a></li>
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
            <span class="total-valor" id="cartTotal">R$ 0,00</span>
        </div>
        <button class="btn btn-wpp" style="width:100%" id="btnFinalizarWpp">
            Finalizar pelo WhatsApp
        </button>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
    var cart = [];

    // Navbar scroll
    window.addEventListener('scroll', function () {
        document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 30);
    });

    // Carrinho: abrir / fechar
    document.getElementById('btnAbrirCart').addEventListener('click', function () { toggleCart(true); });
    document.getElementById('btnFecharCart').addEventListener('click', function () { toggleCart(false); });
    document.getElementById('cartOverlay').addEventListener('click', function () { toggleCart(false); });

    function toggleCart(open) {
        document.getElementById('cartSidebar').classList.toggle('open', open);
        document.getElementById('cartOverlay').classList.toggle('open', open);
        document.body.style.overflow = open ? 'hidden' : '';
    }

    // Adicionar ao carrinho
    function addCart(id, nome, preco, img) {
        var found = false;
        for (var i = 0; i < cart.length; i++) {
            if (cart[i].id === id) { cart[i].qtd++; found = true; break; }
        }
        if (!found) { cart.push({ id: id, nome: nome, preco: preco, img: img, qtd: 1 }); }
        renderCart();
        showToast(nome + ' adicionado!');
    }

    // Remover do carrinho
    function removeCart(id) {
        cart = cart.filter(function (i) { return i.id !== id; });
        renderCart();
    }

    // Renderizar carrinho
    function renderCart() {
        var total = 0, totalItens = 0;
        for (var i = 0; i < cart.length; i++) {
            total += cart[i].preco * cart[i].qtd;
            totalItens += cart[i].qtd;
        }

        var badge = document.getElementById('cartBadge');
        badge.textContent = totalItens;
        badge.style.display = totalItens > 0 ? 'flex' : 'none';

        var itemsEl = document.getElementById('cartItems');
        if (cart.length === 0) {
            itemsEl.innerHTML = '<p class="cart-vazio">Seu carrinho está vazio.<br>Adicione algo gostoso!</p>';
        } else {
            var html = '';
            for (var j = 0; j < cart.length; j++) {
                var it = cart[j];
                var subtotal = (it.preco * it.qtd).toFixed(2).replace('.', ',');
                html += '<div class="cart-item">'
                    + '<div class="cart-item-img"><img src="' + it.img + '" alt="' + it.nome + '"></div>'
                    + '<div class="cart-item-info">'
                    + '<div class="cart-item-nome">' + it.nome + (it.qtd > 1 ? ' x' + it.qtd : '') + '</div>'
                    + '<div class="cart-item-preco">R$ ' + subtotal + '</div>'
                    + '</div>'
                    + '<button class="cart-rm" onclick="removeCart(' + it.id + ')" aria-label="Remover">&#x2715;</button>'
                    + '</div>';
            }
            itemsEl.innerHTML = html;
        }

        document.getElementById('cartTotal').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');

        var pedido = cart.map(function (i) { return i.qtd + 'x ' + i.nome; }).join(', ');
        var msg = encodeURIComponent('Olá! Quero fazer um pedido:\n' + pedido + '\nTotal: R$ ' + total.toFixed(2).replace('.', ','));
        document.getElementById('btnFinalizarWpp').onclick = function () {
            window.open('https://wa.me/5581987028550?text=' + msg, '_blank', 'noopener');
        };
    }

    // Toast
    var toastTimer;
    function showToast(msg) {
        var el = document.getElementById('toast');
        el.textContent = msg;
        el.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { el.classList.remove('show'); }, 2500);
    }

    // Filtro por categoria
    function filtrar(el, cat) {
        document.querySelectorAll('.cat-card').forEach(function (c) { c.classList.remove('ativo'); });
        el.classList.add('ativo');
        document.querySelectorAll('.prod-card').forEach(function (c) {
            c.style.display = (cat === 'todos' || c.dataset.cat === cat) ? '' : 'none';
        });
        document.getElementById('produtos').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
</script>
</body>
</html>