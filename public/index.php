<?php
// =============================================
// DADOS SIMULADOS - Substituir por banco depois
// =============================================
$produtos_destaque = [
    [
        "id" => 1,
        "nome" => "Açaí Premium 500ml",
        "preco" => 18.90,
        "categoria" => "acai",
        "imagem" => "https://images.unsplash.com/photo-1590301157890-4810ed352733?w=400&q=80",
        "destaque" => true
    ],
    [
        "id" => 2,
        "nome" => "Hambúrguer Smash",
        "preco" => 29.90,
        "categoria" => "hamburguer",
        "imagem" => "https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80",
        "destaque" => true
    ],
    [
        "id" => 3,
        "nome" => "Bolo de Pote Ninho",
        "preco" => 14.90,
        "categoria" => "doces",
        "imagem" => "https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=400&q=80",
        "destaque" => true
    ],
    [
        "id" => 4,
        "nome" => "Milkshake Oreo",
        "preco" => 16.90,
        "categoria" => "bebidas",
        "imagem" => "https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=400&q=80",
        "destaque" => true
    ],
    [
        "id" => 5,
        "nome" => "Açaí Tradicional 300ml",
        "preco" => 12.90,
        "categoria" => "acai",
        "imagem" => "https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400&q=80",
        "destaque" => true
    ],
    [
        "id" => 6,
        "nome" => "Combo Smash Duplo",
        "preco" => 42.90,
        "categoria" => "hamburguer",
        "imagem" => "https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=400&q=80",
        "destaque" => true
    ],
];

$categorias = [
    ["slug" => "acai",      "nome" => "Açaí",       "emoji" => "🍇", "cor" => "#7c3aed"],
    ["slug" => "hamburguer","nome" => "Hambúrguer",  "emoji" => "🍔", "cor" => "#ea580c"],
    ["slug" => "doces",     "nome" => "Doces",       "emoji" => "🍰", "cor" => "#ec4899"],
    ["slug" => "bebidas",   "nome" => "Bebidas",     "emoji" => "🥤", "cor" => "#0ea5e9"],
];

$depoimentos = [
    ["nome" => "Ana Clara",   "texto" => "Melhor açaí da cidade! Sempre fresquinho e cheio de sabor.", "nota" => 5],
    ["nome" => "Rafael M.",   "texto" => "O smash burger é incrível, já virei cliente fiel mesmo.",    "nota" => 5],
    ["nome" => "Juliana P.",  "texto" => "Atendimento rápido e os bolos de pote são demais!",          "nota" => 5],
];
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabor & Cia — Açaí, Burgers, Doces e Bebidas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">



    <style>
        /* ================================
           RESET & VARIÁVEIS
        ================================ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --rosa:       #f43f7a;
            --rosa-claro: #fce7f0;
            --roxo:       #7c3aed;
            --laranja:    #ff6b2c;
            --creme:      #fff8f4;
            --branco:     #ffffff;
            --escuro:     #1a1014;
            --cinza:      #6b7280;
            --borda:      #e5e7eb;

            --font-titulo: 'Playfair Display', Georgia, serif;
            --font-corpo:  'DM Sans', sans-serif;

            --r: 12px;
            --sombra: 0 4px 24px rgba(0,0,0,.08);
            --sombra-hover: 0 8px 32px rgba(0,0,0,.14);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-corpo);
            color: var(--escuro);
            background: var(--branco);
            overflow-x: hidden;
        }

        img{ 
            display: block; 
            width: 100%; 
            object-fit: cover; 
        }

        a{ 
            text-decoration: none; 
            color: inherit; 
        }

        .container {
            width: 100%;
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ================================
           BOTÕES
        ================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 50px;
            font-family: var(--font-corpo);
            font-weight: 600;
            font-size: .95rem;
            cursor: pointer;
            border: none;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--sombra-hover); }
        .btn:active { transform: translateY(0); }

        .btn-primary {
            background: var(--rosa);
            color: #fff;
        }
        .btn-outline {
            background: transparent;
            border: 2px solid #fff;
            color: #fff;
        }
        .btn-wpp {
            background: #25D366;
            color: #fff;
            font-size: 1.05rem;
            padding: 16px 36px;
        }
        .btn-dark {
            background: var(--escuro);
            color: #fff;
        }

        /* ================================
           NAVBAR
        ================================ */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 999;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--borda);
            transition: box-shadow .3s;
        }
        .navbar.scrolled { box-shadow: 0 2px 20px rgba(0,0,0,.08); }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100px;
        }

        .nav-logo {
            font-family: var(--font-titulo);
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--escuro);
            letter-spacing: -.5px;
        }
        .nav-logo span { color: var(--rosa); }

        .nav-links {
            display: flex;
            gap: 32px;
            list-style: none;
        }
        .nav-links a {
            font-weight: 500;
            font-size: .9rem;
            color: var(--cinza);
            transition: color .2s;
        }
        .nav-links a:hover { color: var(--rosa); }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .nav-cart {
            position: relative;
            background: var(--rosa-claro);
            border: none;
            width: 42px; height: 42px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: background .2s;
        }
        .nav-cart:hover { background: var(--rosa); }
        .nav-cart:hover .cart-icon { filter: brightness(10); }
        .cart-icon { font-size: 1.1rem; }

        .cart-badge {
            position: absolute;
            top: -4px; right: -4px;
            background: var(--rosa);
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            width: 18px; height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }

        .nav-hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* ================================
           HERO
        ================================ */
        .hero {
            min-height: 100vh;
            background: url('https://images.unsplash.com/photo-1561758033-d89a9ad46330?w=1400&q=80') center/cover no-repeat;
            display: flex;
            align-items: center;
            position: relative;
        }
        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(20,5,10,.75) 0%, rgba(20,5,10,.45) 100%);
        }
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 600px;
            padding-top: 64px;
        }
        .hero-tag {
            display: inline-block;
            background: var(--rosa);
            color: #fff;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 6px 16px;
            border-radius: 50px;
            margin-bottom: 20px;
        }
        .hero h1 {
            font-family: var(--font-titulo);
            font-size: clamp(2.4rem, 6vw, 3.8rem);
            line-height: 1.1;
            color: #fff;
            margin-bottom: 18px;
        }
        .hero h1 em {
            font-style: normal;
            color: var(--rosa);
        }
        .hero p {
            color: rgba(255,255,255,.8);
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 32px;
            max-width: 480px;
        }
        .hero-btns {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        /* ================================
           CATEGORIAS
        ================================ */
        .categorias {
            background: var(--branco);
            padding: 72px 0 56px;
        }
        .section-header {
            text-align: center;
            margin-bottom: 44px;
        }
        .section-header h2 {
            font-family: var(--font-titulo);
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            line-height: 1.2;
            margin-bottom: 10px;
        }
        .section-header p {
            color: var(--cinza);
            font-size: .95rem;
        }
        .linha-rosa {
            display: inline-block;
            width: 48px; height: 3px;
            background: var(--rosa);
            border-radius: 2px;
            margin: 12px auto 0;
        }

        .cat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .cat-card {
            background: var(--creme);
            border-radius: var(--r);
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: transform .25s ease, box-shadow .25s ease;
            border: 2px solid transparent;
        }
        .cat-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--sombra-hover);
            border-color: var(--rosa);
        }
        .cat-emoji {
            font-size: 2.8rem;
            margin-bottom: 12px;
            display: block;
        }
        .cat-card h3 {
            font-size: 1rem;
            font-weight: 600;
        }
        .cat-card p {
            font-size: .82rem;
            color: var(--cinza);
            margin-top: 4px;
        }

        /* ================================
           PRODUTOS EM DESTAQUE
        ================================ */
        .produtos {
            background: var(--rosa-claro);
            padding: 72px 0;
        }
        .prod-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .prod-card {
            background: var(--branco);
            border-radius: var(--r);
            overflow: hidden;
            box-shadow: var(--sombra);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .prod-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--sombra-hover);
        }
        .prod-img {
            height: 190px;
            overflow: hidden;
        }
        .prod-img img {
            height: 100%;
            transition: transform .4s ease;
        }
        .prod-card:hover .prod-img img { transform: scale(1.06); }

        .prod-body {
            padding: 18px 18px 20px;
        }
        .prod-nome {
            font-weight: 600;
            font-size: .95rem;
            margin-bottom: 6px;
        }
        .prod-preco {
            font-family: var(--font-titulo);
            font-size: 1.3rem;
            color: var(--rosa);
            font-weight: 700;
            margin-bottom: 14px;
        }
        .btn-add {
            width: 100%;
            padding: 11px;
            border-radius: 8px;
            background: var(--escuro);
            color: #fff;
            border: none;
            font-family: var(--font-corpo);
            font-weight: 600;
            font-size: .88rem;
            cursor: pointer;
            transition: background .2s, transform .15s;
        }
        .btn-add:hover { background: var(--rosa); transform: scale(1.02); }

        /* ================================
           OFERTA
        ================================ */
        .oferta {
            background: var(--escuro);
            padding: 72px 0;
            position: relative;
            overflow: hidden;
        }
        .oferta::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(244,63,122,.18) 0%, transparent 70%);
            top: -120px; right: -80px;
            pointer-events: none;
        }
        .oferta-inner {
            display: flex;
            align-items: center;
            gap: 60px;
        }
        .oferta-texto { flex: 1; }
        .oferta-badge {
            display: inline-block;
            background: var(--rosa);
            color: #fff;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 50px;
            margin-bottom: 16px;
        }
        .oferta-texto h2 {
            font-family: var(--font-titulo);
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            color: #fff;
            line-height: 1.15;
            margin-bottom: 14px;
        }
        .oferta-texto h2 span { color: var(--rosa); }
        .oferta-texto p {
            color: rgba(255,255,255,.65);
            line-height: 1.7;
            margin-bottom: 28px;
            font-size: .95rem;
        }
        .oferta-preco {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 28px;
        }
        .oferta-de {
            color: rgba(255,255,255,.4);
            text-decoration: line-through;
            font-size: 1.1rem;
        }
        .oferta-por {
            font-family: var(--font-titulo);
            font-size: 2.6rem;
            color: var(--rosa);
            font-weight: 700;
        }
        .oferta-img {
            flex: 0 0 360px;
            height: 300px;
            border-radius: var(--r);
            overflow: hidden;
        }

        /* ================================
           SOBRE / HISTÓRIA
        ================================ */
        .sobre {
            background: var(--branco);
            padding: 80px 0;
        }
        .sobre-inner {
            display: flex;
            align-items: center;
            gap: 64px;
        }
        .sobre-img {
            flex: 0 0 45%;
            height: 400px;
            border-radius: var(--r);
            overflow: hidden;
        }
        .sobre-texto { flex: 1; }
        .sobre-texto h2 {
            font-family: var(--font-titulo);
            font-size: clamp(1.8rem, 3.5vw, 2.4rem);
            line-height: 1.2;
            margin-bottom: 16px;
        }
        .sobre-texto p {
            color: var(--cinza);
            line-height: 1.8;
            margin-bottom: 14px;
            font-size: .95rem;
        }
        .sobre-itens {
            display: flex;
            gap: 20px;
            margin-top: 28px;
        }
        .sobre-item {
            flex: 1;
            text-align: center;
            padding: 16px;
            background: var(--creme);
            border-radius: 10px;
        }
        .sobre-item .num {
            font-family: var(--font-titulo);
            font-size: 1.8rem;
            color: var(--rosa);
            font-weight: 700;
        }
        .sobre-item .label {
            font-size: .78rem;
            color: var(--cinza);
            margin-top: 2px;
        }

        /* ================================
           DEPOIMENTOS
        ================================ */
        .depoimentos {
            background: var(--creme);
            padding: 72px 0;
        }
        .dep-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-top: 44px;
        }
        .dep-card {
            background: var(--branco);
            border-radius: var(--r);
            padding: 28px 24px;
            box-shadow: var(--sombra);
        }
        .dep-estrelas { color: #f59e0b; font-size: 1rem; margin-bottom: 12px; }
        .dep-texto {
            font-size: .92rem;
            color: var(--escuro);
            line-height: 1.7;
            margin-bottom: 16px;
            font-style: italic;
        }
        .dep-nome { font-weight: 600; font-size: .85rem; color: var(--cinza); }

        /* ================================
           CTA FINAL
        ================================ */
        .cta-final {
            background: linear-gradient(135deg, var(--rosa) 0%, #c2185b 100%);
            padding: 88px 0;
            text-align: center;
        }
        .cta-final h2 {
            font-family: var(--font-titulo);
            font-size: clamp(2rem, 5vw, 3.2rem);
            color: #fff;
            line-height: 1.15;
            margin-bottom: 14px;
        }
        .cta-final p {
            color: rgba(255,255,255,.8);
            font-size: 1rem;
            margin-bottom: 36px;
        }
        .cta-info {
            display: flex;
            justify-content: center;
            gap: 36px;
            margin-top: 36px;
        }
        .cta-info-item {
            color: rgba(255,255,255,.8);
            font-size: .88rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ================================
           FOOTER
        ================================ */
        .footer {
            background: var(--escuro);
            padding: 56px 0 28px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 48px;
            padding-bottom: 40px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .footer-logo {
            font-family: var(--font-titulo);
            font-size: 1.5rem;
            font-weight: 900;
            color: #fff;
            margin-bottom: 12px;
        }
        .footer-logo span { color: var(--rosa); }
        .footer-desc {
            color: rgba(255,255,255,.5);
            font-size: .88rem;
            line-height: 1.7;
            margin-bottom: 20px;
        }
        .footer-social { display: flex; gap: 10px; }
        .soc-btn {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: rgba(255,255,255,.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            transition: background .2s;
        }
        .soc-btn:hover { background: var(--rosa); }

        .footer h4 {
            color: #fff;
            font-size: .85rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }
        .footer ul { list-style: none; }
        .footer ul li { margin-bottom: 10px; }
        .footer ul a {
            color: rgba(255,255,255,.5);
            font-size: .88rem;
            transition: color .2s;
        }
        .footer ul a:hover { color: var(--rosa); }
        .footer-copy {
            text-align: center;
            color: rgba(255,255,255,.3);
            font-size: .8rem;
            padding-top: 28px;
        }

        /* ================================
           CARRINHO SIDEBAR
        ================================ */
        .cart-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 1100;
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s;
        }
        .cart-overlay.open { opacity: 1; pointer-events: all; }

        .cart-sidebar {
            position: fixed;
            top: 0; right: -380px;
            width: 360px;
            height: 100vh;
            background: var(--branco);
            z-index: 1200;
            transition: right .35s cubic-bezier(.4,0,.2,1);
            display: flex;
            flex-direction: column;
        }
        .cart-sidebar.open { right: 0; }
        .cart-head {
            padding: 22px 22px 16px;
            border-bottom: 1px solid var(--borda);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cart-head h3 {
            font-family: var(--font-titulo);
            font-size: 1.3rem;
        }
        .cart-close {
            background: none;
            border: none;
            font-size: 1.4rem;
            cursor: pointer;
            color: var(--cinza);
        }
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 16px 22px;
        }
        .cart-empty {
            text-align: center;
            color: var(--cinza);
            padding: 48px 0;
            font-size: .95rem;
        }
        .cart-item {
            display: flex;
            gap: 14px;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid var(--borda);
        }
        .cart-item-img {
            width: 56px; height: 56px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .cart-item-info { flex: 1; }
        .cart-item-nome { font-weight: 600; font-size: .9rem; }
        .cart-item-preco { color: var(--rosa); font-weight: 700; font-size: .9rem; }
        .cart-item-rm {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--cinza);
            font-size: 1.1rem;
        }
        .cart-foot {
            padding: 20px 22px;
            border-top: 1px solid var(--borda);
        }
        .cart-total {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 16px;
        }
        .cart-total span:last-child { color: var(--rosa); font-family: var(--font-titulo); font-size: 1.2rem; }

        /* ================================
           TOAST
        ================================ */
        .toast {
            position: fixed;
            bottom: 24px; left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: var(--escuro);
            color: #fff;
            padding: 14px 24px;
            border-radius: 50px;
            font-size: .9rem;
            font-weight: 500;
            z-index: 2000;
            opacity: 0;
            transition: all .35s cubic-bezier(.4,0,.2,1);
            white-space: nowrap;
        }
        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* ================================
           RESPONSIVIDADE
        ================================ */
        @media (max-width: 900px) {
            .nav-links { display: none; }
            .nav-hamburger { display: block; }

            .cat-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .prod-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .oferta-inner { flex-direction: column; gap: 36px; }
            .oferta-img { flex: none; width: 100%; height: 220px; }

            .sobre-inner { flex-direction: column; gap: 36px; }
            .sobre-img { flex: none; width: 100%; height: 260px; }

            .dep-grid { grid-template-columns: 1fr; }

            .footer-grid { grid-template-columns: 1fr 1fr; }

            .cta-info { flex-direction: column; gap: 12px; align-items: center; }
        }

        @media (max-width: 580px) {
            .prod-grid { grid-template-columns: 1fr; }
            .cat-grid {
                display: flex;
                overflow-x: auto;
                gap: 14px;
                scroll-snap-type: x mandatory;
                padding-bottom: 8px;
                -webkit-overflow-scrolling: touch;
            }
            .cat-card {
                min-width: 150px;
                scroll-snap-align: start;
                flex-shrink: 0;
            }
            .hero-btns { flex-direction: column; }
            .hero-btns .btn { text-align: center; justify-content: center; }
            .sobre-itens { flex-direction: column; }
            .footer-grid { grid-template-columns: 1fr; gap: 32px; }
            .cart-sidebar { width: 100%; }
        }
    </style>
</head>
<body>

<!-- ================================
     NAVBAR
================================ -->
<nav class="navbar" id="navbar">
    <div class="container nav-inner">
        <a href="#" class="nav-logo">Sabor<span>&</span>Cia</a>

        <ul class="nav-links">
            <li><a href="#inicio">Início</a></li>
            <li><a href="#cardapio">Cardápio</a></li>
            <li><a href="#ofertas">Ofertas</a></li>
            <li><a href="#sobre">Sobre</a></li>
        </ul>

        <div class="nav-actions">
            <button class="nav-cart" id="btnAbrirCart" aria-label="Abrir carrinho">
                <span class="cart-icon">🛒</span>
                <span class="cart-badge" id="cartBadge">0</span>
            </button>
            <button class="nav-hamburger" id="navHamburger">☰</button>
        </div>
    </div>
</nav>

<!-- ================================
     HERO
================================ -->
<section class="hero" id="inicio">
    <div class="container hero-content">
        <span class="hero-tag">✨ Fresquinho todo dia</span>
        <h1>O sabor que conquista desde a <em>primeira mordida</em></h1>
        <p>Açaí cremoso, burgers artesanais, doces incríveis e bebidas geladas. Tudo feito com amor, entregue na sua porta.</p>
        <div class="hero-btns">
            <a href="#cardapio" class="btn btn-primary">🍽️ Ver Cardápio</a>
            <a href="https://wa.me/5581987028550?text=Oi!%20Quero%20fazer%20um%20pedido" target="_blank" class="btn btn-outline">📲 Pedir Agora</a>
        </div>
    </div>
</section>

<!-- ================================
     CATEGORIAS
================================ -->
<section class="categorias" id="cardapio">
    <div class="container">
        <div class="section-header">
            <h2>O que você quer hoje?</h2>
            <p>Escolha a sua favorita e já vai!</p>
            <span class="linha-rosa"></span>
        </div>

        <div class="cat-grid">
            <?php foreach ($categorias as $cat): ?>
            <div class="cat-card" onclick="filtrarProdutos('<?= $cat['slug'] ?>')">
                <span class="cat-emoji"><?= $cat['emoji'] ?></span>
                <h3><?= $cat['nome'] ?></h3>
                <p>Ver todos</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================================
     PRODUTOS EM DESTAQUE
================================ -->
<section class="produtos" id="produtos">
    <div class="container">
        <div class="section-header">
            <h2>Mais Pedidos</h2>
            <p>Os queridinhos dos nossos clientes</p>
            <span class="linha-rosa"></span>
        </div>

        <div class="prod-grid" id="prodGrid">
            <?php foreach ($produtos_destaque as $p): ?>
            <div class="prod-card" data-categoria="<?= $p['categoria'] ?>">
                <div class="prod-img">
                    <img src="<?= $p['imagem'] ?>" alt="<?= htmlspecialchars($p['nome']) ?>" loading="lazy">
                </div>
                <div class="prod-body">
                    <div class="prod-nome"><?= htmlspecialchars($p['nome']) ?></div>
                    <div class="prod-preco">R$ <?= number_format($p['preco'], 2, ',', '.') ?></div>
                    <button class="btn-add"
                        onclick="adicionarCarrinho(<?= $p['id'] ?>, '<?= addslashes($p['nome']) ?>', <?= $p['preco'] ?>, '<?= $p['imagem'] ?>')">
                        + Adicionar ao carrinho
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================================
     OFERTA DA SEMANA
================================ -->
<section class="oferta" id="ofertas">
    <div class="container oferta-inner">
        <div class="oferta-texto">
            <span class="oferta-badge">🔥 Oferta da semana</span>
            <h2>Combo Smash<br><span>+ Açaí 500ml</span></h2>
            <p>Lanche artesanal com blend da casa, cheddar, bacon crocante + açaí cremoso com granola e banana. Tudo por um preço impossível de recusar.</p>
            <div class="oferta-preco">
                <span class="oferta-de">R$ 59,80</span>
                <span class="oferta-por">R$ 44,90</span>
            </div>
            <button class="btn btn-primary" onclick="adicionarCarrinho(99, 'Combo Smash + Açaí', 44.90, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200')">
                🛒 Aproveitar Combo
            </button>
        </div>
        <div class="oferta-img">
            <img src="https://images.unsplash.com/photo-1561758033-d89a9ad46330?w=600&q=80" alt="Combo da semana">
        </div>
    </div>
</section>

<!-- ================================
     SOBRE
================================ -->
<section class="sobre" id="sobre">
    <div class="container sobre-inner">
        <div class="sobre-img">
            <img src="https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=600&q=80" alt="Nossa cozinha">
        </div>
        <div class="sobre-texto">
            <span class="hero-tag" style="background:var(--creme);color:var(--rosa);margin-bottom:16px;display:inline-block;">Nossa história</span>
            <h2>Feito com carinho,<br>desde o primeiro dia</h2>
            <p>Começamos pequenos, com uma tigela de açaí e muita vontade de fazer diferente. Hoje somos a lanchonete favorita do bairro, mas sem perder a essência: comida de verdade, com ingredientes selecionados e muito afeto.</p>
            <p>Cada item do cardápio é pensado para te surpreender. Do açaí cremoso ao smash burger perfeito — aqui, cada detalhe importa.</p>
            <div class="sobre-itens">
                <div class="sobre-item">
                    <div class="num">500+</div>
                    <div class="label">Pedidos por semana</div>
                </div>
                <div class="sobre-item">
                    <div class="num">4.9⭐</div>
                    <div class="label">Avaliação média</div>
                </div>
                <div class="sobre-item">
                    <div class="num">3 anos</div>
                    <div class="label">De muito sabor</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================================
     DEPOIMENTOS
================================ -->
<section class="depoimentos">
    <div class="container">
        <div class="section-header">
            <h2>O que dizem nossos clientes</h2>
            <p>Quem prova, sempre volta</p>
            <span class="linha-rosa"></span>
        </div>
        <div class="dep-grid">
            <?php foreach ($depoimentos as $d): ?>
            <div class="dep-card">
                <div class="dep-estrelas"><?= str_repeat('⭐', $d['nota']) ?></div>
                <p class="dep-texto">"<?= htmlspecialchars($d['texto']) ?>"</p>
                <span class="dep-nome">— <?= htmlspecialchars($d['nome']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================================
     CTA FINAL
================================ -->
<section class="cta-final">
    <div class="container">
        <h2>Bateu aquela fome?<br>A gente resolve agora! 🚀</h2>
        <p>Peça pelo WhatsApp e receba em minutos. Sem complicação.</p>
        <a href="https://wa.me/5581987028550?text=Oi!%20Quero%20fazer%20um%20pedido" target="_blank" class="btn btn-wpp">
            💬 Pedir pelo WhatsApp
        </a>
        <div class="cta-info">
            <span class="cta-info-item">🕐 Entrega em até 40 min</span>
            <span class="cta-info-item">📍 Raio de 5km</span>
            <span class="cta-info-item">⭐ Qualidade garantida</span>
        </div>
    </div>
</section>

<!-- ================================
     FOOTER
================================ -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-logo">Sabor<span>&</span>Cia</div>
                <p class="footer-desc">Açaí, hambúrgueres artesanais, doces e bebidas geladas. Tudo com muito amor e sabor.</p>
                <div class="footer-social">
                    <a href="#" class="soc-btn">📸</a>
                    <a href="#" class="soc-btn">📘</a>
                    <a href="#" class="soc-btn">🎵</a>
                </div>
            </div>
            <div>
                <h4>Cardápio</h4>
                <ul>
                    <li><a href="#">Açaí</a></li>
                    <li><a href="#">Hambúrgueres</a></li>
                    <li><a href="#">Doces</a></li>
                    <li><a href="#">Bebidas</a></li>
                    <li><a href="#">Combos</a></li>
                </ul>
            </div>
            <div>
                <h4>Contato</h4>
                <ul>
                    <li><a href="#">📲 WhatsApp</a></li>
                    <li><a href="#">📍 Rua das Flores, 123</a></li>
                    <li><a href="#">🕐 Seg–Dom, 11h–23h</a></li>
                    <li><a href="#">📧 contato@saborecia.com</a></li>
                </ul>
            </div>
        </div>
        <p class="footer-copy">© <?= date('Y') ?> Sabor&Cia — Todos os direitos reservados.</p>
    </div>
</footer>

<!-- ================================
     CARRINHO SIDEBAR
================================ -->
<div class="cart-overlay" id="cartOverlay" onclick="fecharCart()"></div>
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-head">
        <h3>🛒 Meu Carrinho</h3>
        <button class="cart-close" onclick="fecharCart()">✕</button>
    </div>
    <div class="cart-items" id="cartItems">
        <p class="cart-empty">Seu carrinho está vazio.<br>Adicione algo gostoso! 😋</p>
    </div>
    <div class="cart-foot">
        <div class="cart-total">
            <span>Total</span>
            <span id="cartTotal">R$ 0,00</span>
        </div>
        <button class="btn btn-wpp" style="width:100%;justify-content:center;" id="btnFinalizarWpp">
            💬 Finalizar pelo WhatsApp
        </button>
    </div>
</div>

<!-- Toast notificação -->
<div class="toast" id="toast">✅ Adicionado ao carrinho!</div>

<script>
// ================================
// ESTADO DO CARRINHO
// ================================
let carrinho = [];
let totalItens = 0;

// ================================
// NAVBAR SCROLL
// ================================
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 30);
});

// ================================
// CARRINHO - ABRIR / FECHAR
// ================================
document.getElementById('btnAbrirCart').addEventListener('click', abrirCart);

function abrirCart() {
    document.getElementById('cartSidebar').classList.add('open');
    document.getElementById('cartOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function fecharCart() {
    document.getElementById('cartSidebar').classList.remove('open');
    document.getElementById('cartOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// ================================
// ADICIONAR AO CARRINHO
// ================================
function adicionarCarrinho(id, nome, preco, img) {
    const existente = carrinho.find(i => i.id === id);
    if (existente) {
        existente.qtd++;
    } else {
        carrinho.push({ id, nome, preco, img, qtd: 1 });
    }
    totalItens++;
    atualizarUI();
    mostrarToast('✅ ' + nome + ' adicionado!');
}

function removerCarrinho(id) {
    const idx = carrinho.findIndex(i => i.id === id);
    if (idx !== -1) {
        totalItens -= carrinho[idx].qtd;
        carrinho.splice(idx, 1);
        atualizarUI();
    }
}

// ================================
// ATUALIZAR UI
// ================================
function atualizarUI() {
    // Badge
    const badge = document.getElementById('cartBadge');
    badge.textContent = totalItens;
    badge.style.display = totalItens > 0 ? 'flex' : 'none';

    // Items
    const container = document.getElementById('cartItems');
    if (carrinho.length === 0) {
        container.innerHTML = '<p class="cart-empty">Seu carrinho está vazio.<br>Adicione algo gostoso! 😋</p>';
    } else {
        container.innerHTML = carrinho.map(item => `
            <div class="cart-item">
                <div class="cart-item-img">
                    <img src="${item.img}" alt="${item.nome}" style="height:56px;object-fit:cover;">
                </div>
                <div class="cart-item-info">
                    <div class="cart-item-nome">${item.nome} ${item.qtd > 1 ? '×' + item.qtd : ''}</div>
                    <div class="cart-item-preco">R$ ${(item.preco * item.qtd).toFixed(2).replace('.', ',')}</div>
                </div>
                <button class="cart-item-rm" onclick="removerCarrinho(${item.id})">🗑️</button>
            </div>
        `).join('');
    }

    // Total
    const total = carrinho.reduce((s, i) => s + i.preco * i.qtd, 0);
    document.getElementById('cartTotal').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');

    // Link WPP
    const pedido = carrinho.map(i => `${i.qtd}x ${i.nome}`).join(', ');
    const msg = encodeURIComponent(`Olá! Quero fazer um pedido:\n${pedido}\nTotal: R$ ${total.toFixed(2).replace('.', ',')}`);
    document.getElementById('btnFinalizarWpp').onclick = () => {
        window.open(`https://wa.me/5581987028550?text=${msg}`, '_blank');
    };
}

// ================================
// TOAST
// ================================
function mostrarToast(msg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
}

// ================================
// FILTRO POR CATEGORIA
// ================================
function filtrarProdutos(cat) {
    const cards = document.querySelectorAll('.prod-card');
    cards.forEach(c => {
        const visivel = c.dataset.categoria === cat;
        c.style.display = visivel ? 'block' : 'none';
    });
    // Scroll suave até os produtos
    document.getElementById('produtos').scrollIntoView({ behavior: 'smooth' });
    // Resetar depois de 4s
    setTimeout(() => cards.forEach(c => c.style.display = 'block'), 4000);
}

// ================================
// BADGE INICIAL
// ================================
document.getElementById('cartBadge').style.display = 'none';
</script>
</body>
</html>