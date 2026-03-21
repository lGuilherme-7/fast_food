<?php
// ============================================
// checkout.php
// ============================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — Sabor &amp; Cia</title>
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
            <li><a href="produtos.php">Cardápio</a></li>
            <li><a href="index.php#ofertas">Ofertas</a></li>
            <li><a href="index.php#sobre">Sobre</a></li>
        </ul>
        <button class="nav-cart" id="btnAbrirCart" aria-label="Carrinho">
            <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span class="cart-badge" id="cartBadge"></span>
        </button>
    </div>
</nav>

<!-- CHECKOUT -->
<div class="checkout-page">
    <div class="container">

        <div class="page-titulo">
            <h1>Finalizar pedido</h1>
            <p>Preencha seus dados e envie o pedido pelo WhatsApp.</p>
        </div>

        <!-- STEPS -->
        <div class="steps">
            <div class="step feito">
                <div class="step-num">1</div>
                <span class="step-label">Carrinho</span>
            </div>
            <div class="step-sep"></div>
            <div class="step ativo">
                <div class="step-num">2</div>
                <span class="step-label">Dados</span>
            </div>
            <div class="step-sep"></div>
            <div class="step">
                <div class="step-num">3</div>
                <span class="step-label">Confirmação</span>
            </div>
        </div>

        <!-- VAZIO -->
        <div class="checkout-vazio" id="estadoVazio" style="display:none">
            <svg width="56" height="56" viewBox="0 0 24 24">
                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            <h2>Seu carrinho está vazio</h2>
            <p>Adicione produtos antes de finalizar.</p>
            <a href="produtos.php" class="btn btn-rosa">Ver cardápio</a>
        </div>

        <!-- GRID CHECKOUT -->
        <div class="checkout-grid" id="estadoCheio" style="display:none">

            <!-- FORMULÁRIO -->
            <div>

                <!-- DADOS PESSOAIS -->
                <div class="sec">
                    <div class="sec-titulo">Dados pessoais</div>
                    <div class="campo-grid">
                        <div class="campo">
                            <label for="nome">Nome completo</label>
                            <input type="text" id="nome" placeholder="Seu nome" autocomplete="name">
                        </div>
                        <div class="campo">
                            <label for="tel">WhatsApp</label>
                            <input type="tel" id="tel" placeholder="(00) 00000-0000" autocomplete="tel">
                        </div>
                    </div>
                </div>

                <!-- ENTREGA -->
                <div class="sec">
                    <div class="sec-titulo">Entrega</div>

                    <div class="entrega-opts">
                        <label class="entrega-opt">
                            <input type="radio" name="entrega" value="entrega" checked onchange="toggleEntrega()">
                            <div class="entrega-opt-info">
                                <div class="entrega-opt-titulo">Receber em casa</div>
                                <div class="entrega-opt-sub">Até 40 min</div>
                            </div>
                        </label>
                        <label class="entrega-opt">
                            <input type="radio" name="entrega" value="retirada" onchange="toggleEntrega()">
                            <div class="entrega-opt-info">
                                <div class="entrega-opt-titulo">Retirar no local</div>
                                <div class="entrega-opt-sub">Em 15 min</div>
                            </div>
                        </label>
                    </div>

                    <div id="camposEndereco">
                        <div class="campo-grid tres" style="margin-bottom:14px">
                            <div class="campo">
                                <label for="rua">Rua / Avenida</label>
                                <input type="text" id="rua" placeholder="Nome da rua">
                            </div>
                            <div class="campo">
                                <label for="num">Número</label>
                                <input type="text" id="num" placeholder="123">
                            </div>
                            <div class="campo">
                                <label for="compl">Complemento</label>
                                <input type="text" id="compl" placeholder="Apto, bloco...">
                            </div>
                        </div>
                        <div class="campo-grid">
                            <div class="campo">
                                <label for="bairro">Bairro</label>
                                <input type="text" id="bairro" placeholder="Seu bairro">
                            </div>
                            <div class="campo">
                                <label for="ref">Ponto de referência</label>
                                <input type="text" id="ref" placeholder="Próximo ao...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PAGAMENTO -->
                <div class="sec">
                    <div class="sec-titulo">Pagamento</div>
                    <div class="pagto-opts">

                        <label class="pagto-opt">
                            <input type="radio" name="pagto" value="dinheiro" checked onchange="toggleTroco()">
                            <svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            <div class="pagto-opt-info">
                                <div class="pagto-opt-titulo">Dinheiro</div>
                                <div class="pagto-opt-sub">Pagamento na entrega</div>
                            </div>
                        </label>

                        <label class="pagto-opt">
                            <input type="radio" name="pagto" value="cartao" onchange="toggleTroco()">
                            <svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><line x1="6" y1="15" x2="6.01" y2="15"/><line x1="10" y1="15" x2="12" y2="15"/></svg>
                            <div class="pagto-opt-info">
                                <div class="pagto-opt-titulo">Cartão na entrega</div>
                                <div class="pagto-opt-sub">Débito ou crédito</div>
                            </div>
                        </label>

                        <label class="pagto-opt">
                            <input type="radio" name="pagto" value="pix" onchange="toggleTroco()">
                            <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                            <div class="pagto-opt-info">
                                <div class="pagto-opt-titulo">Pix</div>
                                <div class="pagto-opt-sub">Pagamento antecipado</div>
                            </div>
                        </label>

                    </div>

                    <div class="troco-wrap" id="trocoWrap">
                        <div class="campo" style="margin-top:14px">
                            <label for="troco">Precisa de troco para quanto?</label>
                            <input type="text" id="troco" placeholder="Ex: R$ 50,00">
                        </div>
                    </div>
                </div>

                <!-- OBSERVAÇÕES -->
                <div class="sec">
                    <div class="sec-titulo">Observações</div>
                    <div class="campo">
                        <label for="obs">Algum detalhe especial no pedido?</label>
                        <textarea id="obs" placeholder="Ex: sem cebola, ponto do burger, etc."></textarea>
                    </div>
                </div>

            </div>

            <!-- RESUMO -->
            <div class="resumo">
                <h2>Resumo</h2>

                <div class="resumo-itens" id="resumoItens"></div>

                <div class="resumo-linha">
                    <span>Subtotal</span>
                    <span id="resumoSubtotal">R$ 0,00</span>
                </div>
                <div class="resumo-linha">
                    <span>Entrega</span>
                    <span style="color:var(--rosa);font-weight:600">Grátis</span>
                </div>
                <div class="resumo-linha total">
                    <span>Total</span>
                    <span id="resumoTotal">R$ 0,00</span>
                </div>

                <div class="resumo-aviso">
                    <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    <span>Ao confirmar, você será redirecionado ao WhatsApp com seu pedido completo.</span>
                </div>

                <button class="btn-pedido" id="btnConfirmar">
                    <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    Confirmar pedido
                </button>

                <a href="carrinho.php" class="btn-voltar">Voltar ao carrinho</a>
            </div>

        </div>

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

<!-- SIDEBAR -->
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
        <a href="carrinho.php" class="btn btn-rosa" style="width:100%;justify-content:center;display:flex;">
            Ver carrinho completo
        </a>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
    var cart = [];

    window.addEventListener('scroll', function() {
        document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 30);
    });

    document.getElementById('btnAbrirCart').addEventListener('click', function() { toggleSidebar(true); });
    document.getElementById('btnFecharCart').addEventListener('click', function() { toggleSidebar(false); });
    document.getElementById('cartOverlay').addEventListener('click', function() { toggleSidebar(false); });

    function toggleSidebar(open) {
        document.getElementById('cartSidebar').classList.toggle('open', open);
        document.getElementById('cartOverlay').classList.toggle('open', open);
        document.body.style.overflow = open ? 'hidden' : '';
    }

    function removerSidebar(id) {
        cart = cart.filter(function(i) { return i.id !== id; });
        render();
    }

    // Entrega / retirada
    function toggleEntrega() {
        var val = document.querySelector('input[name="entrega"]:checked').value;
        document.getElementById('camposEndereco').style.display = val === 'entrega' ? 'block' : 'none';
    }

    // Troco
    function toggleTroco() {
        var val = document.querySelector('input[name="pagto"]:checked').value;
        var wrap = document.getElementById('trocoWrap');
        wrap.classList.toggle('visivel', val === 'dinheiro');
    }
    toggleTroco(); // estado inicial

    // Confirmar pedido
    document.getElementById('btnConfirmar').addEventListener('click', function() {
        var nome  = document.getElementById('nome').value.trim();
        var tel   = document.getElementById('tel').value.trim();

        if (!nome || !tel) {
            showToast('Preencha seu nome e WhatsApp.');
            return;
        }

        var entrega = document.querySelector('input[name="entrega"]:checked').value;
        var pagto   = document.querySelector('input[name="pagto"]:checked').value;
        var obs     = document.getElementById('obs').value.trim();
        var troco   = document.getElementById('troco').value.trim();

        var enderecoTxt = '';
        if (entrega === 'entrega') {
            var rua    = document.getElementById('rua').value.trim();
            var num    = document.getElementById('num').value.trim();
            var compl  = document.getElementById('compl').value.trim();
            var bairro = document.getElementById('bairro').value.trim();
            var ref    = document.getElementById('ref').value.trim();
            if (!rua || !num || !bairro) {
                showToast('Preencha o endereço de entrega.');
                return;
            }
            enderecoTxt = rua + ', ' + num + (compl ? ', ' + compl : '') + ' — ' + bairro + (ref ? ' (' + ref + ')' : '');
        }

        var total = 0;
        for (var i = 0; i < cart.length; i++) total += cart[i].preco * cart[i].qtd;

        var itensTexto = cart.map(function(i) { return i.qtd + 'x ' + i.nome; }).join('\n');

        var pagtoLabels = { dinheiro: 'Dinheiro', cartao: 'Cartão na entrega', pix: 'Pix' };

        var msg = 'Olá! Quero fazer um pedido:\n\n'
            + '*Itens:*\n' + itensTexto + '\n\n'
            + '*Total:* R$ ' + total.toFixed(2).replace('.', ',') + '\n\n'
            + '*Nome:* ' + nome + '\n'
            + '*Telefone:* ' + tel + '\n'
            + '*Entrega:* ' + (entrega === 'entrega' ? 'Entregar — ' + enderecoTxt : 'Retirar no local') + '\n'
            + '*Pagamento:* ' + pagtoLabels[pagto]
            + (pagto === 'dinheiro' && troco ? ' (troco para ' + troco + ')' : '')
            + (obs ? '\n*Obs:* ' + obs : '');

        window.open('https://wa.me/5581987028550?text=' + encodeURIComponent(msg), '_blank', 'noopener');
    });

    // Render
    function render() {
        var total = 0, itens = 0;
        for (var i = 0; i < cart.length; i++) { total += cart[i].preco * cart[i].qtd; itens += cart[i].qtd; }

        var badge = document.getElementById('cartBadge');
        badge.textContent = itens;
        badge.style.display = itens > 0 ? 'flex' : 'none';

        document.getElementById('estadoVazio').style.display = cart.length === 0 ? 'block' : 'none';
        document.getElementById('estadoCheio').style.display = cart.length  > 0 ? 'grid'  : 'none';

        // Resumo lateral
        var resumoItens = document.getElementById('resumoItens');
        var html = '';
        for (var j = 0; j < cart.length; j++) {
            var it = cart[j];
            html += '<div class="resumo-item">'
                + '<span>' + it.nome + (it.qtd > 1 ? ' x' + it.qtd : '') + '</span>'
                + '<span>R$ ' + (it.preco * it.qtd).toFixed(2).replace('.', ',') + '</span>'
                + '</div>';
        }
        resumoItens.innerHTML = html;

        var totalFmt = 'R$ ' + total.toFixed(2).replace('.', ',');
        document.getElementById('resumoSubtotal').textContent = totalFmt;
        document.getElementById('resumoTotal').textContent    = totalFmt;

        // Sidebar
        var sideEl = document.getElementById('cartItems');
        if (!cart.length) {
            sideEl.innerHTML = '<p class="cart-vazio">Seu carrinho está vazio.<br>Adicione algo gostoso!</p>';
        } else {
            var shtml = '';
            for (var k = 0; k < cart.length; k++) {
                var si = cart[k];
                shtml += '<div class="cart-item">'
                    + '<div class="cart-item-img"><img src="' + si.img + '" alt="' + si.nome + '"></div>'
                    + '<div class="cart-item-info">'
                    + '<div class="cart-item-nome">' + si.nome + (si.qtd > 1 ? ' x' + si.qtd : '') + '</div>'
                    + '<div class="cart-item-preco">R$ ' + (si.preco * si.qtd).toFixed(2).replace('.', ',') + '</div>'
                    + '</div>'
                    + '<button class="cart-rm" onclick="removerSidebar(' + si.id + ')">&#x2715;</button>'
                    + '</div>';
            }
            sideEl.innerHTML = shtml;
        }
        document.getElementById('cartTotal').textContent = totalFmt;
    }

    var toastTimer;
    function showToast(msg) {
        var el = document.getElementById('toast');
        el.textContent = msg;
        el.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function() { el.classList.remove('show'); }, 2800);
    }

    render();
</script>
</body>
</html>