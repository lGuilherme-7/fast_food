<?php
// ============================================
// produto.php — Detalhe do produto
// ============================================
$todos = [
    ["id"=>1, "nome"=>"Açaí Premium 500ml",    "preco"=>18.90,"cat"=>"acai",      "desc"=>"Açaí cremoso com granola, banana e leite condensado.",        "descricao"=>"Nosso Açaí Premium é feito com polpa fresca batida na hora, acompanhado de granola crocante, banana prata fatiada e um fio generoso de leite condensado. Servido em copo de 500ml, é a pedida perfeita para qualquer momento do dia.","img"=>"https://images.unsplash.com/photo-1590301157890-4810ed352733?w=800&q=80"],
    ["id"=>2, "nome"=>"Açaí Tradicional 300ml", "preco"=>12.90,"cat"=>"acai",      "desc"=>"Açaí puro batido na hora, simples e fresquinho.",             "descricao"=>"Para quem gosta do clássico: açaí puro, sem adição de açúcar, batido na hora com consistência cremosa. Servido em copo de 300ml. Acompanhamentos à parte.",                                                                       "img"=>"https://images.unsplash.com/photo-1511690743698-d9d85f2fbf38?w=800&q=80"],
    ["id"=>3, "nome"=>"Açaí com Morango 400ml", "preco"=>15.90,"cat"=>"acai",      "desc"=>"Açaí cremoso com morangos frescos e mel.",                    "descricao"=>"Uma combinação que todo mundo ama: açaí cremoso com morangos frescos fatiados e um fio de mel puro. Servido em copo de 400ml, refrescante e cheio de sabor.",                                                                       "img"=>"https://images.unsplash.com/photo-1544145945-f90425340c7e?w=800&q=80"],
    ["id"=>4, "nome"=>"Hambúrguer Smash",        "preco"=>29.90,"cat"=>"hamburguer","desc"=>"Blend da casa 180g, queijo cheddar, alface e tomate.",        "descricao"=>"O nosso Smash clássico: blend artesanal de 180g prensado na chapa bem quente, queijo cheddar derretido, alface americana, tomate e molho especial da casa. Pão brioche tostado na manteiga.",                                       "img"=>"https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&q=80"],
    ["id"=>5, "nome"=>"Combo Smash Duplo",       "preco"=>42.90,"cat"=>"hamburguer","desc"=>"Dois blends 180g, bacon, cheddar e molho especial da casa.", "descricao"=>"Duplo impacto: dois blends artesanais de 180g, bacon crocante, queijo cheddar duplo e nosso molho especial. Para os que não se contentam com menos.",                                                                                  "img"=>"https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=800&q=80"],
    ["id"=>6, "nome"=>"Smash Bacon",             "preco"=>34.90,"cat"=>"hamburguer","desc"=>"Blend 180g, bacon crocante, queijo e cebola caramelizada.",  "descricao"=>"Blend de 180g com bacon artesanal crocante, queijo cheddar, cebola caramelizada no azeite e maionese temperada. Uma explosão de sabor em cada mordida.",                                                                                "img"=>"https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?w=800&q=80"],
    ["id"=>7, "nome"=>"Bolo de Pote Ninho",      "preco"=>14.90,"cat"=>"doces",     "desc"=>"Bolo cremoso de leite ninho com cobertura de Nutella.",       "descricao"=>"Camadas de bolo úmido, creme de leite ninho e cobertura generosa de Nutella. Feito todo dia de manhã, sempre fresquinho.",                                                                                                              "img"=>"https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=800&q=80"],
    ["id"=>8, "nome"=>"Bolo de Pote Oreo",       "preco"=>14.90,"cat"=>"doces",     "desc"=>"Bolo de chocolate com creme de Oreo e biscoito triturado.",  "descricao"=>"Bolo de chocolate húmido com creme de Oreo e uma camada crocante de biscoito triturado por cima. Combina com tudo — especialmente com um milkshake gelado.",                                                                          "img"=>"https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=800&q=80"],
    ["id"=>9, "nome"=>"Brigadeiro Gourmet",      "preco"=>6.90, "cat"=>"doces",     "desc"=>"Brigadeiro artesanal com chocolate belga 70%.",               "descricao"=>"Feito com chocolate belga 70% cacau, manteiga sem sal e creme de leite fresco. Enrolado na mão, passado em granulado belga. Um brigadeiro de verdade.",                                                                                "img"=>"https://images.unsplash.com/photo-1611293388250-580b08c4a145?w=800&q=80"],
    ["id"=>10,"nome"=>"Milkshake Oreo",          "preco"=>16.90,"cat"=>"bebidas",   "desc"=>"Milkshake cremoso de baunilha com Oreo triturado.",           "descricao"=>"Sorvete de baunilha, leite gelado e biscoito Oreo batidos até ficar bem cremoso. Servido em copo de 400ml com chantilly e mais biscoito por cima.",                                                                                     "img"=>"https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=800&q=80"],
    ["id"=>11,"nome"=>"Suco de Laranja",         "preco"=>9.90, "cat"=>"bebidas",   "desc"=>"Suco natural de laranja espremido na hora, 400ml.",           "descricao"=>"Laranjas selecionadas espremidas na hora do pedido. Sem açúcar, sem conservantes. 400ml de puro sabor natural. Com ou sem gelo.",                                                                                                          "img"=>"https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?w=800&q=80"],
    ["id"=>12,"nome"=>"Refrigerante Lata",       "preco"=>6.00, "cat"=>"bebidas",   "desc"=>"Coca-Cola, Guaraná ou Sprite. Lata 350ml gelada.",            "descricao"=>"Latas 350ml bem geladas. Opções: Coca-Cola Original, Coca-Cola Zero, Guaraná Antarctica e Sprite. Informe sua preferência no pedido.",                                                                                                    "img"=>"https://images.unsplash.com/photo-1581636625402-29b2a704ef13?w=800&q=80"],
];

$id = (int)($_GET['id'] ?? 0);
$produto = null;
foreach ($todos as $p) {
    if ($p['id'] === $id) { $produto = $p; break; }
}
if (!$produto) { header('Location: produtos.php'); exit; }

$relacionados = array_slice(
    array_values(array_filter($todos, function($p) use ($produto) {
        return $p['cat'] === $produto['cat'] && $p['id'] !== $produto['id'];
    })),
    0, 3
);

$nomes_cat = ['acai'=>'Açaí','hamburguer'=>'Hambúrguer','doces'=>'Doces','bebidas'=>'Bebidas'];
$cat_nome  = $nomes_cat[$produto['cat']] ?? ucfirst($produto['cat']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produto['nome']) ?> — Sabor &amp; Cia</title>
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

<!-- PRODUTO -->
<div class="produto-page">
    <div class="container">

        <!-- Breadcrumb -->
        <nav class="breadcrumb" aria-label="Navegação">
            <a href="index.php">Início</a>
            <span>/</span>
            <a href="produtos.php">Cardápio</a>
            <span>/</span>
            <a href="produtos.php?cat=<?= $produto['cat'] ?>"><?= $cat_nome ?></a>
            <span>/</span>
            <?= htmlspecialchars($produto['nome']) ?>
        </nav>

        <!-- Grid principal -->
        <div class="produto-grid">

            <div class="produto-foto">
                <img src="<?= $produto['img'] ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
            </div>

            <div>
                <span class="produto-cat"><?= $cat_nome ?></span>
                <h1 class="produto-nome"><?= htmlspecialchars($produto['nome']) ?></h1>
                <div class="produto-preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></div>
                <p class="produto-descricao"><?= htmlspecialchars($produto['descricao']) ?></p>

                <div class="qtd-row">
                    <span class="qtd-label">Quantidade</span>
                    <div class="qtd-ctrl">
                        <button class="qtd-btn" onclick="mudarQtd(-1)">&#8722;</button>
                        <div class="qtd-num" id="qtdNum">1</div>
                        <button class="qtd-btn" onclick="mudarQtd(1)">&#43;</button>
                    </div>
                </div>

                <button class="btn-comprar" id="btnComprar">
                    Adicionar ao carrinho
                </button>

                <a href="https://wa.me/5581987028550?text=<?= urlencode('Olá! Quero pedir: 1x ' . $produto['nome'] . ' — R$ ' . number_format($produto['preco'], 2, ',', '.')) ?>"
                   target="_blank" rel="noopener" class="btn-wpp-prod">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                    </svg>
                    Pedir pelo WhatsApp
                </a>
            </div>
        </div>

        <!-- Relacionados -->
        <?php if (!empty($relacionados)): ?>
        <div class="relacionados">
            <h2>Você também pode gostar</h2>
            <div class="rel-grid">
                <?php foreach ($relacionados as $r): ?>
                <div class="rel-card">
                    <div class="rel-img">
                        <img src="<?= $r['img'] ?>" alt="<?= htmlspecialchars($r['nome']) ?>" loading="lazy">
                    </div>
                    <div class="rel-body">
                        <div class="rel-nome"><?= htmlspecialchars($r['nome']) ?></div>
                        <div class="rel-desc"><?= htmlspecialchars($r['desc']) ?></div>
                        <div class="rel-rodape">
                            <span class="rel-preco">R$ <?= number_format($r['preco'], 2, ',', '.') ?></span>
                            <button class="rel-add"
                                onclick="addCart(<?= $r['id'] ?>,'<?= addslashes(htmlspecialchars($r['nome'])) ?>',<?= $r['preco'] ?>,'<?= $r['img'] ?>')">
                                + Adicionar
                            </button>
                        </div>
                        <a href="produto.php?id=<?= $r['id'] ?>" class="rel-ver">Ver detalhes</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

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
    var qtd  = 1;

    var prodId    = <?= $produto['id'] ?>;
    var prodNome  = '<?= addslashes(htmlspecialchars($produto['nome'])) ?>';
    var prodPreco = <?= $produto['preco'] ?>;
    var prodImg   = '<?= $produto['img'] ?>';

    function mudarQtd(delta) {
        qtd = Math.max(1, qtd + delta);
        document.getElementById('qtdNum').textContent = qtd;
    }

    document.getElementById('btnComprar').addEventListener('click', function() {
        for (var i = 0; i < qtd; i++) addCart(prodId, prodNome, prodPreco, prodImg);
    });

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