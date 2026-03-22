<?php

require_once __DIR__ . '/../inc/config.php';  
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
// ============================================
// carrinho.php
// Carrinho gerenciado via localStorage no JS
// ============================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho — Sabor &amp; Cia</title>
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

<!-- CARRINHO -->
<div class="carrinho-page">
    <div class="container">

        <div class="page-titulo">
            <h1>Meu carrinho</h1>
            <span id="contadorTitulo"></span>
        </div>

        <!-- VAZIO (escondido por padrão) -->
        <div class="carrinho-vazio" id="estadoVazio" style="display:none">
            <svg width="56" height="56" viewBox="0 0 24 24">
                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            <h2>Seu carrinho está vazio</h2>
            <p>Adicione produtos do cardápio para continuar.</p>
            <a href="produtos.php" class="btn btn-rosa">Ver cardápio</a>
        </div>

        <!-- GRID CARRINHO (visível quando há itens) -->
        <div class="carrinho-grid" id="estadoCheio" style="display:none">

            <!-- LISTA -->
            <div class="itens-lista" id="itensList"></div>

            <!-- RESUMO -->
            <div class="resumo">
                <h2>Resumo do pedido</h2>

                <div class="resumo-linha">
                    <span>Subtotal</span>
                    <span id="resumoSubtotal">R$ 0,00</span>
                </div>
                <div class="resumo-linha">
                    <span>Taxa de entrega</span>
                    <span style="color:var(--rosa);font-weight:600;">Grátis</span>
                </div>

                <div class="resumo-linha total">
                    <span>Total</span>
                    <span id="resumoTotal">R$ 0,00</span>
                </div>

                <div class="resumo-entrega">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Entrega em até 40 minutos</span>
                </div>

                <button class="btn-finalizar" id="btnWpp">
                    <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    Finalizar pelo WhatsApp
                </button>

                <a href="produtos.php" class="btn-continuar">Continuar comprando</a>
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

<!-- SIDEBAR CARRINHO (ícone da navbar) -->
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
    // ============================================================
    // CARRINHO — estado em memória (adaptar para localStorage se quiser persistência)
    // ============================================================
    var cart = [];

    // Navbar scroll
    window.addEventListener('scroll', function() {
        document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 30);
    });

    // Sidebar
    document.getElementById('btnAbrirCart').addEventListener('click', function() { toggleSidebar(true); });
    document.getElementById('btnFecharCart').addEventListener('click', function() { toggleSidebar(false); });
    document.getElementById('cartOverlay').addEventListener('click', function() { toggleSidebar(false); });

    function toggleSidebar(open) {
        document.getElementById('cartSidebar').classList.toggle('open', open);
        document.getElementById('cartOverlay').classList.toggle('open', open);
        document.body.style.overflow = open ? 'hidden' : '';
    }

    // ============================================================
    // ALTERAR QUANTIDADE
    // ============================================================
    function alterarQtd(id, delta) {
        for (var i = 0; i < cart.length; i++) {
            if (cart[i].id === id) {
                cart[i].qtd += delta;
                if (cart[i].qtd <= 0) {
                    cart.splice(i, 1);
                }
                break;
            }
        }
        render();
    }

    function remover(id) {
        cart = cart.filter(function(i) { return i.id !== id; });
        render();
    }

    // ============================================================
    // RENDER PÁGINA PRINCIPAL
    // ============================================================
    function render() {
        var total = 0, itens = 0;
        for (var i = 0; i < cart.length; i++) {
            total += cart[i].preco * cart[i].qtd;
            itens += cart[i].qtd;
        }

        // Badge navbar
        var badge = document.getElementById('cartBadge');
        badge.textContent = itens;
        badge.style.display = itens > 0 ? 'flex' : 'none';

        // Título contador
        document.getElementById('contadorTitulo').textContent =
            itens > 0 ? itens + ' item' + (itens > 1 ? 's' : '') : '';

        // Estado vazio / cheio
        document.getElementById('estadoVazio').style.display  = cart.length === 0 ? 'block' : 'none';
        document.getElementById('estadoCheio').style.display  = cart.length  > 0 ? 'grid'  : 'none';

        // Lista de itens
        var lista = document.getElementById('itensList');
        if (cart.length > 0) {
            var html = '';
            for (var j = 0; j < cart.length; j++) {
                var it = cart[j];
                var sub = (it.preco * it.qtd).toFixed(2).replace('.', ',');
                var unit = it.preco.toFixed(2).replace('.', ',');
                html += '<div class="item-card">'
                    + '<div class="item-img"><img src="' + it.img + '" alt="' + it.nome + '"></div>'
                    + '<div class="item-info">'
                    + '<div class="item-nome">' + it.nome + '</div>'
                    + '<div class="item-preco-unit">R$ ' + unit + ' cada</div>'
                    + '</div>'
                    + '<div class="item-actions">'
                    + '<div class="qtd-ctrl">'
                    + '<button class="qtd-btn" onclick="alterarQtd(' + it.id + ',-1)">&#8722;</button>'
                    + '<div class="qtd-num">' + it.qtd + '</div>'
                    + '<button class="qtd-btn" onclick="alterarQtd(' + it.id + ',1)">&#43;</button>'
                    + '</div>'
                    + '<div class="item-subtotal">R$ ' + sub + '</div>'
                    + '<button class="item-rm" onclick="remover(' + it.id + ')" aria-label="Remover">'
                    + '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>'
                    + '</button>'
                    + '</div>'
                    + '</div>';
            }
            lista.innerHTML = html;
        }

        // Resumo
        document.getElementById('resumoSubtotal').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
        document.getElementById('resumoTotal').textContent    = 'R$ ' + total.toFixed(2).replace('.', ',');

        // Botão WhatsApp
        var pedido = cart.map(function(i) { return i.qtd + 'x ' + i.nome; }).join(', ');
        var msg = encodeURIComponent('Olá! Quero fazer um pedido:\n' + pedido + '\nTotal: R$ ' + total.toFixed(2).replace('.', ','));
        document.getElementById('btnWpp').onclick = function() {
            window.open('https://wa.me/5581987028550?text=' + msg, '_blank', 'noopener');
        };

        // Sidebar (ícone da navbar)
        renderSidebar(total);
    }

    // ============================================================
    // RENDER SIDEBAR
    // ============================================================
    function renderSidebar(total) {
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
                    + '<button class="cart-rm" onclick="remover(' + it.id + ')">&#x2715;</button>'
                    + '</div>';
            }
            el.innerHTML = html;
        }
        document.getElementById('cartTotal').textContent = 'R$ ' + (total || 0).toFixed(2).replace('.', ',');
    }

    // Toast
    var toastTimer;
    function showToast(msg) {
        var el = document.getElementById('toast');
        el.textContent = msg;
        el.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function() { el.classList.remove('show'); }, 2500);
    }

    // Inicializa
    render();
</script>
</body>
</html>