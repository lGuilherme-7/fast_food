<?php
/**
 * index.php — Canto do Sabor
 * Home do e-commerce | Versão 2.0
 *
 * Estrutura preparada para MySQL.
 * Arrays PHP simulam queries futuras.
 */

// ─────────────────────────────────────────────────────────────────────
// DADOS — substituir por PDO/MySQLi futuramente
// Ex: $produtos = $pdo->query("SELECT * FROM produtos WHERE destaque=1")->fetchAll();
// ─────────────────────────────────────────────────────────────────────

$categorias = [
    ['id'=>1, 'slug'=>'acai',    'nome'=>'Açaí',       'emoji'=>'🫐', 'cor'=>'#7C3AED', 'imagem'=>'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=400&q=80'],
    ['id'=>2, 'slug'=>'burguer', 'nome'=>'Burguers',   'emoji'=>'🍔', 'cor'=>'#DC2626', 'imagem'=>'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80'],
    ['id'=>3, 'slug'=>'doces',   'nome'=>'Doces',      'emoji'=>'🍰', 'cor'=>'#D97706', 'imagem'=>'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=400&q=80'],
    ['id'=>4, 'slug'=>'bebidas', 'nome'=>'Bebidas',    'emoji'=>'🧃', 'cor'=>'#059669', 'imagem'=>'https://images.unsplash.com/photo-1541658016709-82535e94bc69?w=400&q=80'],
    ['id'=>5, 'slug'=>'combos',  'nome'=>'Combos',     'emoji'=>'🎁', 'cor'=>'#EA580C', 'imagem'=>'https://images.unsplash.com/photo-1561758033-d89a9ad46330?w=400&q=80'],
];

$produtos_destaque = [
    ['id'=>1,'nome'=>'Açaí Cremoso 500ml','slug'=>'acai','preco'=>18.90,'original'=>null,   'tag'=>'Mais Pedido','tag_tipo'=>'hot','img'=>'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=500&q=85','desc'=>'Granola, leite condensado e frutas frescas.'],
    ['id'=>2,'nome'=>'Double Smash','slug'=>'burguer',   'preco'=>32.90,'original'=>38.90,'tag'=>'13% off',    'tag_tipo'=>'off','img'=>'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=500&q=85','desc'=>'Dois blends 150g, cheddar e bacon.'],
    ['id'=>3,'nome'=>'Bolo de Pote Oreo','slug'=>'doces','preco'=>14.90,'original'=>null,   'tag'=>'Novo',       'tag_tipo'=>'new','img'=>'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=500&q=85','desc'=>'Camadas de bolo, creme e Oreo.'],
    ['id'=>4,'nome'=>'Limonada Suíça','slug'=>'bebidas', 'preco'=>12.90,'original'=>null,   'tag'=>null,         'tag_tipo'=>null, 'img'=>'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=500&q=85','desc'=>'Limão siciliano com leite condensado.'],
    ['id'=>5,'nome'=>'Açaí Power 700ml','slug'=>'acai',  'preco'=>25.90,'original'=>29.90,'tag'=>'11% off',    'tag_tipo'=>'off','img'=>'https://images.unsplash.com/photo-1606214174585-fe31582dc6ee?w=500&q=85','desc'=>'Com banana, morango e mel artesanal.'],
    ['id'=>6,'nome'=>'Combo Família','slug'=>'combos',   'preco'=>79.90,'original'=>99.90,'tag'=>'20% off',    'tag_tipo'=>'off','img'=>'https://images.unsplash.com/photo-1561758033-d89a9ad46330?w=500&q=85','desc'=>'2 burguers + 2 açaís + 2 bebidas.'],
    ['id'=>7,'nome'=>'Cheesecake Maracujá','slug'=>'doces','preco'=>16.90,'original'=>null,'tag'=>'Favorito',  'tag_tipo'=>'hot','img'=>'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=500&q=85','desc'=>'Cremoso com calda fresca de maracujá.'],
    ['id'=>8,'nome'=>'Milk Shake 400ml','slug'=>'bebidas','preco'=>16.90,'original'=>null, 'tag'=>null,         'tag_tipo'=>null, 'img'=>'https://images.unsplash.com/photo-1541658016709-82535e94bc69?w=500&q=85','desc'=>'Chocolate, morango ou baunilha.'],
];

$depoimentos = [
    ['nome'=>'Juliana M.','nota'=>5,'texto'=>'Melhor açaí da cidade, sem exagero. Chega sempre fresquinho e no tempo certo. Virei cliente fiel!','avatar'=>'J'],
    ['nome'=>'Carlos R.', 'nota'=>5,'texto'=>'O Double Smash é impecável. Carne no ponto, bacon crocante, pão macio. Perfeito!','avatar'=>'C'],
    ['nome'=>'Ana P.',    'nota'=>5,'texto'=>'Peço toda semana. O bolo de pote Oreo é viciante. Atendimento sempre atencioso. 10/10.','avatar'=>'A'],
];

$ofertas = [
    ['titulo'=>'Combo da Semana','sub'=>'2 Burguers + 2 Bebidas','preco'=>'R$ 49,90','eco'=>'Economize R$ 14,00','validade'=>'Só até domingo','emoji'=>'🍔','cor'=>'#DC2626'],
    ['titulo'=>'Açaí com 20% OFF','sub'=>'Todos os tamanhos','preco'=>'A partir de R$ 14,90','eco'=>'No Pix à vista','validade'=>'Hoje somente','emoji'=>'🫐','cor'=>'#7C3AED'],
    ['titulo'=>'3 Bolos por R$ 39','sub'=>'Escolha 3 sabores','preco'=>'R$ 39,00','eco'=>'De R$ 44,70','validade'=>'Estoque limitado','emoji'=>'🍰','cor'=>'#D97706'],
];

function moeda(float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Canto do Sabor — Açaí artesanal, burguers smash, doces e bebidas. Peça agora e receba em até 40 min.">
    <title>Canto do Sabor — Açaí, Burguers &amp; Mais</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts: DM Serif Display + DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════════════
     NAVBAR
     ═══════════════════════════════════════════════════════════════════ -->
<header class="cs-header" id="cs-header" role="banner">
    <nav class="navbar navbar-expand-lg" aria-label="Navegação principal">
        <div class="container">

            <!-- Logo -->
            <a class="cs-logo" href="index.php" aria-label="Canto do Sabor — Página inicial">
                <span class="cs-logo__mark" aria-hidden="true">✦</span>
                <span class="cs-logo__name">Canto<em>do Sabor</em></span>
            </a>

            <!-- Mobile: carrinho + toggler -->
            <div class="d-flex align-items-center gap-2 d-lg-none ms-auto">
                <button class="cs-icon-btn cs-cart-btn"
                        aria-label="Abrir carrinho"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#cartDrawer">
                    <i class="bi bi-bag-heart" aria-hidden="true"></i>
                    <span class="cs-cart-count" id="badge-mob" aria-live="polite">0</span>
                </button>
                <button class="cs-hamburger"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#csNav"
                        aria-controls="csNav"
                        aria-expanded="false"
                        aria-label="Abrir menu">
                    <span></span><span></span><span></span>
                </button>
            </div>

            <!-- Menu -->
            <div class="collapse navbar-collapse" id="csNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link active" href="#inicio">Início</a></li>
                    <li class="nav-item"><a class="nav-link" href="#cardapio">Cardápio</a></li>
                    <li class="nav-item"><a class="nav-link" href="#ofertas">Ofertas</a></li>
                    <li class="nav-item"><a class="nav-link" href="#sobre">Sobre</a></li>
                </ul>
                <div class="cs-nav-actions d-none d-lg-flex align-items-center gap-3">
                    <button class="cs-icon-btn cs-cart-btn"
                            aria-label="Carrinho"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#cartDrawer">
                        <i class="bi bi-bag-heart" aria-hidden="true"></i>
                        <span class="cs-cart-count" id="badge-desk" aria-live="polite">0</span>
                    </button>
                    <a href="#" class="cs-btn cs-btn--ghost cs-btn--sm">
                        <i class="bi bi-person" aria-hidden="true"></i> Entrar
                    </a>
                </div>
            </div>

        </div>
    </nav>
</header>


<!-- ═══════════════════════════════════════════════════════════════════
     HERO — IMPACTO
     ═══════════════════════════════════════════════════════════════════ -->
<section class="cs-hero" id="inicio" aria-label="Banner principal">

    <!-- BG com parallax suave via JS -->
    <div class="cs-hero__bg" id="heroBg" aria-hidden="true">
        <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=1600&q=90"
             alt="" class="cs-hero__bg-img" loading="eager" fetchpriority="high">
    </div>
    <div class="cs-hero__overlay" aria-hidden="true"></div>

    <!-- Partículas decorativas -->
    <div class="cs-hero__sparks" aria-hidden="true">
        <span></span><span></span><span></span><span></span><span></span>
    </div>

    <div class="container cs-hero__inner">
        <div class="row">
            <div class="col-lg-7 col-xl-6">

                <!-- Pill de confiança -->
                <div class="cs-hero__pill">
                    <span class="cs-hero__pill-dot" aria-hidden="true"></span>
                    Aberto agora · Entrega em ~35 min
                </div>

                <h1 class="cs-hero__headline">
                    O sabor que<br>
                    <em>conquista</em><br>
                    desde a primeira<br>
                    mordida.
                </h1>

                <p class="cs-hero__sub">
                    Açaí cremoso, burguers artesanais, doces irresistíveis —
                    tudo feito na hora e entregue com carinho.
                </p>

                <div class="cs-hero__actions">
                    <a href="#cardapio" class="cs-btn cs-btn--primary cs-btn--lg">
                        Ver Cardápio
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                    <a href="https://wa.me/5511999999999"
                       target="_blank" rel="noopener noreferrer"
                       class="cs-btn cs-btn--wa cs-btn--lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                             fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                        </svg>
                        Pedir Agora
                    </a>
                </div>

                <!-- Métricas -->
                <div class="cs-hero__metrics">
                    <div class="cs-hero__metric">
                        <strong>4.9</strong>
                        <span>⭐ Avaliação</span>
                    </div>
                    <div class="cs-hero__metric-divider" aria-hidden="true"></div>
                    <div class="cs-hero__metric">
                        <strong>+3.200</strong>
                        <span>Pedidos entregues</span>
                    </div>
                    <div class="cs-hero__metric-divider" aria-hidden="true"></div>
                    <div class="cs-hero__metric">
                        <strong>∼35min</strong>
                        <span>Tempo de entrega</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Curva de transição -->
    <div class="cs-hero__curve" aria-hidden="true">
        <svg viewBox="0 0 1440 80" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0,80 C360,0 1080,0 1440,80 L1440,80 L0,80 Z" fill="var(--cs-bg)"/>
        </svg>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════
     CATEGORIAS — FACILIDADE
     ═══════════════════════════════════════════════════════════════════ -->
<section class="cs-section cs-categories" id="categorias" aria-labelledby="cat-ttl">
    <div class="container">

        <header class="cs-section-header cs-section-header--center" data-reveal>
            <p class="cs-eyebrow">O que você quer hoje?</p>
            <h2 class="cs-section-title" id="cat-ttl">Escolha sua categoria</h2>
        </header>

        <div class="cs-cat-track" role="list" aria-label="Categorias">
            <?php foreach ($categorias as $i => $c): ?>
            <a class="cs-cat-card"
               href="#cardapio"
               data-cat="<?= $c['slug'] ?>"
               role="listitem"
               aria-label="Ver <?= htmlspecialchars($c['nome']) ?>"
               data-reveal
               style="--delay:<?= $i * 80 ?>ms">
                <div class="cs-cat-card__img-wrap" style="--accent:<?= $c['cor'] ?>">
                    <img src="<?= $c['imagem'] ?>" alt="<?= htmlspecialchars($c['nome']) ?>" loading="lazy">
                    <div class="cs-cat-card__overlay" aria-hidden="true"></div>
                </div>
                <div class="cs-cat-card__label">
                    <span class="cs-cat-card__emoji" aria-hidden="true"><?= $c['emoji'] ?></span>
                    <span><?= htmlspecialchars($c['nome']) ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════
     PRODUTOS EM DESTAQUE — DESEJO
     ═══════════════════════════════════════════════════════════════════ -->
<section class="cs-section cs-bg-off cs-products" id="cardapio" aria-labelledby="prod-ttl">
    <div class="container">

        <header class="cs-section-header" data-reveal>
            <div>
                <p class="cs-eyebrow">Feito com carinho</p>
                <h2 class="cs-section-title" id="prod-ttl">Destaques do Cardápio</h2>
            </div>
            <!-- Filtros -->
            <div class="cs-filter-group" role="group" aria-label="Filtrar por categoria">
                <button class="cs-filter active" data-filter="all">Todos</button>
                <?php foreach ($categorias as $c): ?>
                <button class="cs-filter" data-filter="<?= $c['slug'] ?>">
                    <?= $c['emoji'] ?> <?= htmlspecialchars($c['nome']) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </header>

        <div class="cs-products-grid" id="productsGrid">
            <?php foreach ($produtos_destaque as $i => $p): ?>
            <article class="cs-product-card"
                     data-cat="<?= $p['slug'] ?>"
                     data-reveal
                     style="--delay:<?= ($i % 4) * 60 ?>ms"
                     aria-label="<?= htmlspecialchars($p['nome']) ?>">

                <?php if ($p['tag']): ?>
                <span class="cs-product-card__tag cs-product-card__tag--<?= $p['tag_tipo'] ?>">
                    <?= htmlspecialchars($p['tag']) ?>
                </span>
                <?php endif; ?>

                <div class="cs-product-card__media">
                    <img src="<?= $p['img'] ?>"
                         alt="<?= htmlspecialchars($p['nome']) ?>"
                         class="cs-product-card__img"
                         loading="lazy">
                    <button class="cs-product-card__wish"
                            aria-label="Favoritar <?= htmlspecialchars($p['nome']) ?>">
                        <i class="bi bi-heart" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="cs-product-card__body">
                    <p class="cs-product-card__cat">
                        <?php
                        $cats = array_column($categorias,'emoji','slug');
                        echo $cats[$p['slug']] ?? '';
                        ?> <?= ucfirst($p['slug']) ?>
                    </p>
                    <h3 class="cs-product-card__name"><?= htmlspecialchars($p['nome']) ?></h3>
                    <p class="cs-product-card__desc"><?= htmlspecialchars($p['desc']) ?></p>

                    <div class="cs-product-card__foot">
                        <div>
                            <?php if ($p['original']): ?>
                            <s class="cs-product-card__old"><?= moeda($p['original']) ?></s>
                            <?php endif; ?>
                            <span class="cs-product-card__price"><?= moeda($p['preco']) ?></span>
                        </div>
                        <button class="cs-btn-add"
                                aria-label="Adicionar <?= htmlspecialchars($p['nome']) ?> ao carrinho"
                                data-id="<?= $p['id'] ?>"
                                data-nome="<?= htmlspecialchars($p['nome']) ?>"
                                data-preco="<?= $p['preco'] ?>"
                                data-img="<?= $p['img'] ?>">
                            <i class="bi bi-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

            </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5" data-reveal>
            <a href="#" class="cs-btn cs-btn--outline cs-btn--lg">
                Ver cardápio completo
                <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════
     OFERTAS — CONVERSÃO
     ═══════════════════════════════════════════════════════════════════ -->
<section class="cs-section cs-offers" id="ofertas" aria-labelledby="off-ttl">
    <div class="container">

        <header class="cs-section-header cs-section-header--center cs-section-header--light" data-reveal>
            <p class="cs-eyebrow cs-eyebrow--light">Só por hoje</p>
            <h2 class="cs-section-title cs-section-title--light" id="off-ttl">Ofertas Especiais</h2>
        </header>

        <div class="row g-4">
            <?php foreach ($ofertas as $i => $o): ?>
            <div class="col-md-4">
                <div class="cs-offer-card" style="--accent:<?= $o['cor'] ?>" data-reveal style="--delay:<?= $i * 100 ?>ms">
                    <div class="cs-offer-card__glow" aria-hidden="true"></div>
                    <span class="cs-offer-card__emoji" aria-hidden="true"><?= $o['emoji'] ?></span>
                    <span class="cs-offer-card__badge"><?= htmlspecialchars($o['validade']) ?></span>
                    <h3 class="cs-offer-card__title"><?= htmlspecialchars($o['titulo']) ?></h3>
                    <p class="cs-offer-card__sub"><?= htmlspecialchars($o['sub']) ?></p>
                    <div class="cs-offer-card__pricing">
                        <strong class="cs-offer-card__price"><?= $o['preco'] ?></strong>
                        <span class="cs-offer-card__eco"><?= $o['eco'] ?></span>
                    </div>
                    <a href="#cardapio" class="cs-offer-card__cta">
                        Pegar oferta <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════
     SOBRE — CONEXÃO EMOCIONAL
     ═══════════════════════════════════════════════════════════════════ -->
<section class="cs-section cs-about" id="sobre" aria-labelledby="about-ttl">
    <div class="container">
        <div class="row align-items-center g-5 g-lg-6">

            <!-- Imagens -->
            <div class="col-lg-5" data-reveal>
                <div class="cs-about-gallery">
                    <img src="https://images.unsplash.com/photo-1556742031-c6961e8560b0?w=700&q=85"
                         alt="Nossa lanchonete" class="cs-about-gallery__main" loading="lazy">
                    <img src="https://images.unsplash.com/photo-1590301157890-4810ed352733?w=400&q=80"
                         alt="Açaí artesanal" class="cs-about-gallery__accent" loading="lazy">
                    <div class="cs-about-gallery__badge">
                        <strong>+ 5 anos</strong>
                        <span>de história</span>
                    </div>
                </div>
            </div>

            <!-- Texto -->
            <div class="col-lg-6 offset-lg-1" data-reveal data-reveal-delay="120">
                <p class="cs-eyebrow">Nossa história</p>
                <h2 class="cs-section-title" id="about-ttl">
                    Feito com amor.<br>
                    <em>Servido com orgulho.</em>
                </h2>
                <p class="cs-about__text">
                    Tudo começou numa cozinha pequena e cheia de vontade. Desde 2019 a gente prepara cada prato como se fosse pra nossa própria família — com ingredientes frescos, receitas próprias e aquele toque especial que faz a diferença.
                </p>
                <p class="cs-about__text">
                    Nosso açaí é batido na hora, o blend do burguer é exclusivo e cada bolo de pote é feito às 5h da manhã pela nossa confeiteira. Isso não é marketing — é o que você vai sentir na primeira garfada.
                </p>
                <ul class="cs-about__pillars">
                    <li><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Ingredientes sempre frescos</li>
                    <li><i class="bi bi-clock-fill" aria-hidden="true"></i> Tudo preparado na hora</li>
                    <li><i class="bi bi-heart-fill" aria-hidden="true"></i> Receitas com identidade própria</li>
                    <li><i class="bi bi-truck" aria-hidden="true"></i> Entrega rápida e cuidadosa</li>
                </ul>
            </div>

        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════
     DEPOIMENTOS — PROVA SOCIAL
     ═══════════════════════════════════════════════════════════════════ -->
<section class="cs-section cs-bg-off cs-reviews" id="avaliações" aria-labelledby="rev-ttl">
    <div class="container">

        <header class="cs-section-header cs-section-header--center" data-reveal>
            <p class="cs-eyebrow">Quem prova aprova</p>
            <h2 class="cs-section-title" id="rev-ttl">O que nossos clientes dizem</h2>
        </header>

        <div class="cs-reviews-grid">
            <?php foreach ($depoimentos as $i => $d): ?>
            <blockquote class="cs-review-card" data-reveal style="--delay:<?= $i * 100 ?>ms">
                <div class="cs-review-card__stars" aria-label="5 estrelas">
                    <?php for($s=0;$s<$d['nota'];$s++): ?><i class="bi bi-star-fill" aria-hidden="true"></i><?php endfor; ?>
                </div>
                <p class="cs-review-card__text">"<?= htmlspecialchars($d['texto']) ?>"</p>
                <footer class="cs-review-card__author">
                    <div class="cs-review-card__avatar" aria-hidden="true">
                        <?= $d['avatar'] ?>
                    </div>
                    <cite><?= htmlspecialchars($d['nome']) ?></cite>
                </footer>
            </blockquote>
            <?php endforeach; ?>
        </div>

        <!-- Resumo geral -->
        <div class="cs-reviews-summary" data-reveal>
            <div class="cs-reviews-summary__score">
                <strong>4.9</strong>
                <div class="cs-reviews-summary__stars" aria-label="4.9 de 5 estrelas">
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    <i class="bi bi-star-half" aria-hidden="true"></i>
                </div>
                <span>Baseado em 380+ avaliações</span>
            </div>
        </div>

    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════
     CTA — WHATSAPP / AÇÃO FINAL
     ═══════════════════════════════════════════════════════════════════ -->
<section class="cs-cta" aria-label="Peça agora pelo WhatsApp">
    <div class="cs-cta__bg" aria-hidden="true"></div>
    <div class="container text-center cs-cta__inner">
        <span class="cs-cta__tag" data-reveal>🛵 Entrega em até 40 minutos</span>
        <h2 class="cs-cta__headline" data-reveal>
            Pronto para pedir?<br>
            <em>A gente tá te esperando.</em>
        </h2>
        <p class="cs-cta__sub" data-reveal>Sem taxa de entrega no primeiro pedido. Pague no Pix ou cartão.</p>
        <div class="cs-cta__actions" data-reveal>
            <a href="https://wa.me/5511999999999?text=Ol%C3%A1!%20Quero%20fazer%20um%20pedido"
               target="_blank" rel="noopener noreferrer"
               class="cs-btn cs-btn--wa cs-btn--xl">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                     fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                </svg>
                Chamar no WhatsApp
            </a>
            <a href="#cardapio" class="cs-btn cs-btn--outline-light cs-btn--xl">
                Ver Cardápio
            </a>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════
     FOOTER
     ═══════════════════════════════════════════════════════════════════ -->
<footer class="cs-footer" aria-label="Rodapé">
    <div class="container">
        <div class="cs-footer__grid">

            <!-- Marca -->
            <div class="cs-footer__brand">
                <a class="cs-logo cs-logo--light" href="index.php" aria-label="Canto do Sabor">
                    <span class="cs-logo__mark" aria-hidden="true">✦</span>
                    <span class="cs-logo__name">Canto<em>do Sabor</em></span>
                </a>
                <p class="cs-footer__desc">
                    Açaí artesanal, burguers e doces feitos do zero todos os dias. Sabor de verdade.
                </p>
                <div class="cs-footer__social">
                    <a href="#" aria-label="Instagram" class="cs-social-link"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                    <a href="#" aria-label="TikTok" class="cs-social-link"><i class="bi bi-tiktok" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Facebook" class="cs-social-link"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                    <a href="https://wa.me/5511999999999" aria-label="WhatsApp" class="cs-social-link cs-social-link--wa"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
                </div>
            </div>

            <!-- Navegação -->
            <div class="cs-footer__col">
                <h3 class="cs-footer__col-title">Menu</h3>
                <ul class="cs-footer__links">
                    <li><a href="#inicio">Início</a></li>
                    <li><a href="#cardapio">Cardápio</a></li>
                    <li><a href="#ofertas">Ofertas</a></li>
                    <li><a href="#sobre">Nossa história</a></li>
                </ul>
            </div>

            <!-- Categorias -->
            <div class="cs-footer__col">
                <h3 class="cs-footer__col-title">Categorias</h3>
                <ul class="cs-footer__links">
                    <?php foreach ($categorias as $c): ?>
                    <li><a href="#cardapio"><?= $c['emoji'] ?> <?= htmlspecialchars($c['nome']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Contato -->
            <div class="cs-footer__col">
                <h3 class="cs-footer__col-title">Contato</h3>
                <ul class="cs-footer__contact">
                    <li><i class="bi bi-geo-alt-fill" aria-hidden="true"></i><span>Rua das Flores, 342 — Centro</span></li>
                    <li><i class="bi bi-telephone-fill" aria-hidden="true"></i><a href="tel:+5511999999999">(11) 9 9999-9999</a></li>
                    <li><i class="bi bi-clock-fill" aria-hidden="true"></i><span>Seg–Dom: 10h às 23h</span></li>
                    <li><i class="bi bi-envelope-fill" aria-hidden="true"></i><a href="mailto:oi@cantodosabor.com.br">oi@cantodosabor.com.br</a></li>
                </ul>
            </div>

        </div>

        <div class="cs-footer__bottom">
            <p>© <?= date('Y') ?> Canto do Sabor. Todos os direitos reservados.</p>
            <p>Desenvolvido com <span aria-label="amor">❤️</span> pela equipe CS</p>
        </div>
    </div>
</footer>


<!-- ═══════════════════════════════════════════════════════════════════
     OFFCANVAS — CARRINHO
     ═══════════════════════════════════════════════════════════════════ -->
<div class="offcanvas offcanvas-end cs-drawer"
     tabindex="-1"
     id="cartDrawer"
     aria-labelledby="cartDrawerTitle">
    <div class="offcanvas-header cs-drawer__header">
        <h2 class="cs-drawer__title" id="cartDrawerTitle">
            <i class="bi bi-bag-heart me-2" aria-hidden="true"></i> Meu Carrinho
        </h2>
        <button type="button" class="cs-drawer__close"
                data-bs-dismiss="offcanvas" aria-label="Fechar carrinho">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>
    <div class="offcanvas-body cs-drawer__body">
        <div id="cart-items" role="list" aria-live="polite"></div>
        <div class="cs-drawer__empty" id="cart-empty">
            <span aria-hidden="true">🛒</span>
            <p>Seu carrinho está vazio</p>
            <a href="#cardapio" class="cs-btn cs-btn--primary cs-btn--sm" data-bs-dismiss="offcanvas">
                Ver produtos
            </a>
        </div>
    </div>
    <div class="cs-drawer__foot" id="cart-foot" hidden>
        <div class="cs-drawer__total">
            <span>Total do pedido</span>
            <strong id="cart-total">R$ 0,00</strong>
        </div>
        <a href="https://wa.me/5511999999999"
           target="_blank" id="cart-wa-link"
           class="cs-btn cs-btn--wa cs-btn--lg w-100">
            <i class="bi bi-whatsapp me-2" aria-hidden="true"></i>
            Finalizar pelo WhatsApp
        </a>
    </div>
</div>


<!-- WhatsApp flutuante -->
<a href="https://wa.me/5511999999999?text=Ol%C3%A1!%20Quero%20fazer%20um%20pedido"
   target="_blank" rel="noopener noreferrer"
   class="cs-wa-float" aria-label="Abrir WhatsApp">
    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26"
         fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
        <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
    </svg>
</a>

<!-- Notificação toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1200">
    <div id="cs-toast" class="toast cs-toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="cs-toast-msg">Item adicionado!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
        </div>
    </div>
</div>


<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>

</body>
</html>