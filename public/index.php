<?php
// ============================================================
// public/index.php
// ============================================================
session_start();

require_once __DIR__ . '/../inc/config.php';  
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';

// ── SESSÃO DO CLIENTE ────────────────────────────────────────
$logado      = isset($_SESSION['cliente_id']);
$cliente_nome= $logado ? explode(' ', $_SESSION['cliente_nome'])[0] : '';

// ── CONFIGURAÇÕES DA LOJA ────────────────────────────────────
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes
    WHERE chave IN (
        'loja_whatsapp','loja_nome','loja_descricao',
        'entrega_tempo','entrega_raio','entrega_taxa',
        'entrega_gratis','entrega_ativa','retirada_ativa'
    )");
$cfg = [];
foreach ($stmt->fetchAll() as $row) $cfg[$row['chave']] = $row['valor'];

$whatsapp       = $cfg['loja_whatsapp']  ?? '5581987028550';
$loja_nome      = $cfg['loja_nome']      ?? 'Sabor & Cia';
$entrega_tempo  = $cfg['entrega_tempo']  ?? '40';
$entrega_raio   = $cfg['entrega_raio']   ?? '5';
$entrega_taxa   = (float)($cfg['entrega_taxa']   ?? 5.00);
$entrega_gratis = (float)($cfg['entrega_gratis'] ?? 50.00);

// ── CATEGORIAS ───────────────────────────────────────────────
$stmt = $pdo->query("SELECT id, slug, nome, imagem_url FROM categorias WHERE ativo=1 ORDER BY ordem");
$categorias = $stmt->fetchAll();

// ── PRODUTOS COM ADICIONAIS ──────────────────────────────────
// Busca produtos ativos. Adicionais ficam numa tabela própria.
// Se ainda não criou a tabela produto_adicionais, funciona sem ela.
$stmt = $pdo->query("
    SELECT p.id, p.nome, p.descricao, p.preco, p.imagem_url,
           c.slug AS cat_slug, c.nome AS cat_nome
    FROM produtos p
    JOIN categorias c ON c.id = p.categoria_id
    WHERE p.ativo = 1 AND p.estoque > 0
    ORDER BY c.ordem, p.nome
");
$produtos = $stmt->fetchAll();

// Adicionais por produto (tabela produto_adicionais — opcional)
// Estrutura: id, produto_id, nome, preco_extra
// Se a tabela não existir ainda, $adicionais fica vazio e tudo funciona.
$adicionais = [];
try {
    $stmt = $pdo->query("SELECT * FROM produto_adicionais WHERE ativo=1 ORDER BY produto_id, nome");
    foreach ($stmt->fetchAll() as $a) {
        $adicionais[$a['produto_id']][] = $a;
    }
} catch (PDOException $e) {
    // Tabela ainda não existe — sem problema, continua sem adicionais
    $adicionais = [];
}

// Adicionais padrão por categoria (fallback enquanto não tiver tabela)
// Assim o açaí já aparece com banana, leite etc sem precisar do banco
$adicionais_padrao = [
    'acai' => [
        ['nome'=>'Banana',          'preco_extra'=>0.00],
        ['nome'=>'Granola',         'preco_extra'=>0.00],
        ['nome'=>'Leite condensado','preco_extra'=>0.00],
        ['nome'=>'Morango',         'preco_extra'=>2.00],
        ['nome'=>'Kiwi',            'preco_extra'=>2.00],
        ['nome'=>'Nutella',         'preco_extra'=>3.00],
        ['nome'=>'Paçoca',          'preco_extra'=>1.50],
        ['nome'=>'Amendoim',        'preco_extra'=>1.00],
    ],
    'hamburguer' => [
        ['nome'=>'Bacon extra',     'preco_extra'=>3.00],
        ['nome'=>'Queijo duplo',    'preco_extra'=>2.00],
        ['nome'=>'Ovo',             'preco_extra'=>2.00],
        ['nome'=>'Cheddar',         'preco_extra'=>2.50],
        ['nome'=>'Sem cebola',      'preco_extra'=>0.00],
        ['nome'=>'Ponto da carne: bem passado','preco_extra'=>0.00],
        ['nome'=>'Ponto da carne: ao ponto',   'preco_extra'=>0.00],
    ],
    'doces' => [
        ['nome'=>'Cobertura de Nutella','preco_extra'=>3.00],
        ['nome'=>'Granulado extra',     'preco_extra'=>0.00],
        ['nome'=>'Sem cobertura',       'preco_extra'=>0.00],
    ],
    'bebidas' => [
        ['nome'=>'Menos gelo',      'preco_extra'=>0.00],
        ['nome'=>'Sem gelo',        'preco_extra'=>0.00],
        ['nome'=>'Canudo extra',    'preco_extra'=>0.00],
    ],
];

// Pré-gera JSON com todos os produtos + adicionais para o JS
$produtos_js = [];
foreach ($produtos as $p) {
    $adds = $adicionais[$p['id']] ?? $adicionais_padrao[$p['cat_slug']] ?? [];
    $produtos_js[$p['id']] = [
        'id'        => (int)$p['id'],
        'nome'      => $p['nome'],
        'descricao' => $p['descricao'] ?? '',
        'preco'     => (float)$p['preco'],
        'imagem'    => $p['imagem_url'] ?? '',
        'cat'       => $p['cat_slug'],
        'adicionais'=> array_values($adds),
    ];
}

// Depoimentos (fixos — simples por design)
$depoimentos = [
    ['nome'=>'Ana Clara',  'texto'=>'Melhor açaí da cidade! Sempre fresquinho e cheio de sabor.','nota'=>5],
    ['nome'=>'Rafael M.',  'texto'=>'O smash burger é incrível, já virei cliente fiel mesmo.',   'nota'=>5],
    ['nome'=>'Juliana P.', 'texto'=>'Atendimento rápido e os bolos de pote são demais!',         'nota'=>5],
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
   <link rel="stylesheet" href="../assets/css/public.css">
</head>
<body>

<!-- ══════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════ -->
<nav class="navbar" id="navbar">
    <div class="container nav-inner">
        <a href="#inicio" class="nav-logo">Sabor<span>&</span>Cia</a>

        <ul class="nav-links" id="navLinks">
            <li><a href="#inicio">Início</a></li>
            <li><a href="#cardapio">Cardápio</a></li>
            <li><a href="#ofertas">Ofertas</a></li>
            <li><a href="#sobre">Sobre</a></li>
        </ul>

        <div class="nav-dir">
            <?php if ($logado): ?>
            <!-- USUÁRIO LOGADO: mostra avatar com menu dropdown -->
            <div class="nav-avatar" title="<?= htmlspecialchars($cliente_nome) ?>">
                <?= mb_strtoupper(mb_substr($cliente_nome, 0, 1)) ?>
                <div class="nav-avatar-menu">
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
            <?php else: ?>
            <!-- NÃO LOGADO: botão de entrar -->
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

<!-- ══════════════════════════════════════════
     HERO
══════════════════════════════════════════ -->
<section class="hero" id="inicio">
    <div class="container hero-content">
        <span class="hero-tag">Fresquinho todo dia</span>
        <h1>O sabor que conquista desde a <em>primeira mordida</em></h1>
        <p>Açaí cremoso, burgers artesanais, doces incríveis e bebidas geladas. Feito com amor, entregue na sua porta.</p>
        <div class="hero-btns">
            <a href="#cardapio" class="btn btn-rosa">Ver cardápio</a>
            <a href="https://wa.me/<?= $whatsapp ?>?text=Oi!%20Quero%20fazer%20um%20pedido" target="_blank" rel="noopener" class="btn btn-ghost">Pedir agora</a>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     CATEGORIAS
══════════════════════════════════════════ -->
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

<!-- ══════════════════════════════════════════
     PRODUTOS
══════════════════════════════════════════ -->
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

<!-- ══════════════════════════════════════════
     OFERTA DA SEMANA
══════════════════════════════════════════ -->
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
            <button class="btn btn-rosa" onclick="addCartDireto(99,'Combo Smash + Açaí 500ml',44.90,'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&q=80')">
                Aproveitar combo
            </button>
        </div>
        <div class="oferta-img">
            <img src="https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=700&q=80" alt="Combo da semana">
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     SOBRE
══════════════════════════════════════════ -->
<section class="sobre" id="sobre">
    <div class="container sobre-inner">
        <div class="sobre-foto">
            <img src="https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=80" alt="Nossa cozinha">
        </div>
        <div class="sobre-texto">
            <span class="sobre-tag">Nossa história</span>
            <h2>Feito com carinho,<br>desde o primeiro dia</h2>
            <p>Começamos pequenos, com uma tigela de açaí e muita vontade de fazer diferente. Hoje somos a lanchonete favorita do bairro — sem perder a essência de sempre.</p>
            <p>Cada item do cardápio é pensado para te surpreender. Do açaí cremoso ao smash burger perfeito, aqui cada detalhe importa.</p>
            <div class="sobre-nums">
                <div class="sobre-num"><div class="n">500+</div><div class="l">Pedidos por semana</div></div>
                <div class="sobre-num"><div class="n">4.9</div><div class="l">Avaliação média</div></div>
                <div class="sobre-num"><div class="n">3 anos</div><div class="l">De muito sabor</div></div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     DEPOIMENTOS
══════════════════════════════════════════ -->
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

<!-- ══════════════════════════════════════════
     CTA
══════════════════════════════════════════ -->
<section class="cta">
    <div class="container">
        <h2>Bateu a fome?<br>A gente resolve agora.</h2>
        <p>Peça pelo WhatsApp e receba em minutos. Sem complicação.</p>
        <a href="https://wa.me/<?= $whatsapp ?>?text=Oi!%20Quero%20fazer%20um%20pedido" target="_blank" rel="noopener" class="btn btn-wpp">
            Pedir pelo WhatsApp
        </a>
        <div class="cta-infos">
            <span class="cta-info">Entrega em até <?= $entrega_tempo ?> min</span>
            <span class="cta-info">Raio de <?= $entrega_raio ?> km</span>
            <span class="cta-info">Qualidade garantida</span>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     FOOTER
══════════════════════════════════════════ -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-logo">Sabor<span>&</span>Cia</div>
                <p class="footer-desc">Açaí, hambúrgueres artesanais, doces e bebidas geladas. Tudo com amor e sabor.</p>
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
                    <li><a href="#cardapio" onclick="filtrar(document.querySelector('[data-cat=\'<?= $c['slug'] ?>\']'),'<?= $c['slug'] ?>')"><?= htmlspecialchars($c['nome']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h4>Contato</h4>
                <ul>
                    <li><a href="https://wa.me/<?= $whatsapp ?>" target="_blank" rel="noopener">WhatsApp</a></li>
                    <li><a href="#"><?= htmlspecialchars($cfg['loja_endereco'] ?? 'Rua das Flores, 123') ?></a></li>
                    <li><a href="#">Seg–Dom, 11h–23h</a></li>
                    <li><a href="mailto:<?= htmlspecialchars($cfg['loja_email'] ?? 'contato@saborecia.com') ?>"><?= htmlspecialchars($cfg['loja_email'] ?? 'contato@saborecia.com') ?></a></li>
                </ul>
            </div>
        </div>
        <p class="footer-copy">© <?= date('Y') ?> <?= htmlspecialchars($loja_nome) ?> — Todos os direitos reservados.</p>
    </div>
</footer>

<!-- ══════════════════════════════════════════
     MODAL DE ADICIONAIS
══════════════════════════════════════════ -->
<div class="modal-overlay" id="modalOverlay" onclick="fecharModalFora(event)">
    <div class="modal-box" id="modalBox">
        <div class="modal-img" id="modalImg">
            <img id="modalImgEl" src="" alt="">
        </div>
        <div class="modal-corpo">
            <div class="modal-drag"></div>
            <div class="modal-nome"  id="modalNome"></div>
            <div class="modal-desc"  id="modalDesc"></div>
            <div class="modal-preco-base">A partir de <strong id="modalPrecoBase"></strong></div>

            <!-- Adicionais (injetado via JS) -->
            <div id="modalAdicionaisWrap"></div>

            <!-- Observação -->
            <div class="modal-obs-titulo" id="modalObsTitulo">Alguma observação?</div>
            <textarea class="modal-obs-area" id="modalObs" rows="2" placeholder="Ex: sem cebola, ponto da carne bem passado..."></textarea>
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

<!-- ══════════════════════════════════════════
     CARRINHO SIDEBAR
══════════════════════════════════════════ -->
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
            <span>Total</span>
            <span class="total-valor" id="cartTotal">R$ 0,00</span>
        </div>
        <button class="btn-finalizar" id="btnFinalizarWpp">
            <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            Finalizar pelo WhatsApp
        </button>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<!-- ══════════════════════════════════════════
     DADOS DO PHP → JS (JSON seguro)
══════════════════════════════════════════ -->
<script>
var PRODUTOS    = <?= json_encode(array_values($produtos_js), JSON_UNESCAPED_UNICODE) ?>;
var WHATSAPP    = "<?= $whatsapp ?>";
var TAXA_ENTREGA    = <?= $entrega_taxa ?>;
var GRATIS_ACIMA    = <?= $entrega_gratis ?>;

// Indexa por id para acesso rápido
var PROD_MAP = {};
PRODUTOS.forEach(function(p){ PROD_MAP[p.id] = p; });
</script>

<script>
// ═══════════════════════════════════════════
// ESTADO DO APP
// ═══════════════════════════════════════════
var cart    = JSON.parse(localStorage.getItem('sc_cart') || '[]');
var modalId = null;
var modalQtd= 1;

// Restaura carrinho ao carregar
renderCart();

// ── NAVBAR ──────────────────────────────────
window.addEventListener('scroll', function(){
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 30);
});

// Mobile menu
document.getElementById('btnHamburger').addEventListener('click', function(){
    var links = document.getElementById('navLinks');
    links.style.display = links.style.display === 'flex' ? 'none' : 'flex';
    links.style.flexDirection = 'column';
    links.style.position = 'absolute';
    links.style.top = '60px';
    links.style.left = '0';
    links.style.right = '0';
    links.style.background = '#fff';
    links.style.padding = '16px 18px';
    links.style.borderBottom = '1px solid var(--rosa-border)';
    links.style.gap = '16px';
});

// ── CARRINHO SIDEBAR ─────────────────────────
document.getElementById('btnAbrirCart').addEventListener('click',  function(){ toggleCart(true); });
document.getElementById('btnFecharCart').addEventListener('click', function(){ toggleCart(false); });
document.getElementById('cartOverlay').addEventListener('click',   function(){ toggleCart(false); });

function toggleCart(open){
    document.getElementById('cartSidebar').classList.toggle('open', open);
    document.getElementById('cartOverlay').classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
}

// ── FILTRO POR CATEGORIA ─────────────────────
function filtrar(el, cat){
    if (!el) return;
    document.querySelectorAll('.cat-card').forEach(function(c){ c.classList.remove('ativo'); });
    el.classList.add('ativo');
    document.querySelectorAll('.prod-card').forEach(function(c){
        c.style.display = (cat === 'todos' || c.dataset.cat === cat) ? '' : 'none';
    });
    document.getElementById('produtos').scrollIntoView({ behavior:'smooth', block:'start' });
}

// ═══════════════════════════════════════════
// MODAL DE ADICIONAIS
// ═══════════════════════════════════════════
function abrirModal(prodId){
    var p = PROD_MAP[prodId];
    if (!p) return;

    modalId  = prodId;
    modalQtd = 1;

    // Preenche imagem
    document.getElementById('modalImgEl').src = p.imagem;
    document.getElementById('modalImgEl').alt = p.nome;

    // Preenche textos
    document.getElementById('modalNome').textContent = p.nome;
    document.getElementById('modalDesc').textContent = p.descricao;
    document.getElementById('modalPrecoBase').textContent = 'R$ ' + p.preco.toFixed(2).replace('.',',');
    document.getElementById('modalQtd').textContent  = '1';
    document.getElementById('modalObs').value        = '';

    // Monta adicionais
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

    // Calcula total inicial
    atualizarTotalModal();

    // Abre
    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';

    // Recalcula ao marcar/desmarcar
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
    var adds = [];
    var extra= 0;

    document.querySelectorAll('.add-check:checked').forEach(function(cb){
        adds.push(cb.dataset.nome);
        extra += parseFloat(cb.dataset.preco || 0);
    });

    var obs     = document.getElementById('modalObs').value.trim();
    var precoFinal = p.preco + extra;

    // Cada item do modal é sempre uma linha nova no carrinho
    // (porque os adicionais podem ser diferentes)
    var chave = modalId + '|' + adds.join(',') + '|' + obs;
    var found = false;
    for (var i = 0; i < cart.length; i++){
        if (cart[i].chave === chave){
            cart[i].qtd += modalQtd;
            found = true; break;
        }
    }
    if (!found){
        cart.push({
            chave   : chave,
            id      : modalId,
            nome    : p.nome,
            preco   : precoFinal,
            preco_base: p.preco,
            extra   : extra,
            img     : p.imagem,
            adicionais: adds,
            obs     : obs,
            qtd     : modalQtd,
        });
    }

    salvarCart();
    renderCart();
    showToast(p.nome + ' adicionado!');
    fecharModal();
}

// ── Adicionar direto (sem adicionais — para combos/ofertas) ──
function addCartDireto(id, nome, preco, img){
    var chave = id + '||';
    var found = false;
    for (var i = 0; i < cart.length; i++){
        if (cart[i].chave === chave){ cart[i].qtd++; found=true; break; }
    }
    if (!found) cart.push({ chave:chave, id:id, nome:nome, preco:preco, preco_base:preco, extra:0, img:img, adicionais:[], obs:'', qtd:1 });
    salvarCart();
    renderCart();
    showToast(nome + ' adicionado!');
}

// ═══════════════════════════════════════════
// RENDERIZAR CARRINHO
// ═══════════════════════════════════════════
function renderCart(){
    var total = 0, totalItens = 0;
    cart.forEach(function(i){ total += i.preco * i.qtd; totalItens += i.qtd; });

    // Badge
    var badge = document.getElementById('cartBadge');
    badge.textContent = totalItens;
    badge.style.display = totalItens > 0 ? 'flex' : 'none';

    // Info de entrega
    var entregaEl = document.getElementById('cartEntregaInfo');
    if (total > 0 && GRATIS_ACIMA > 0){
        if (total >= GRATIS_ACIMA){
            entregaEl.innerHTML = '<strong>Entrega grátis!</strong> Parabéns!';
        } else {
            var faltam = (GRATIS_ACIMA - total).toFixed(2).replace('.',',');
            entregaEl.innerHTML = 'Faltam <strong>R$ ' + faltam + '</strong> para entrega grátis';
        }
    } else {
        entregaEl.innerHTML = '';
    }

    // Itens
    var el = document.getElementById('cartItems');
    if (cart.length === 0){
        el.innerHTML = '<p class="cart-vazio">Seu carrinho está vazio.<br>Adicione algo gostoso!</p>';
    } else {
        var html = '';
        cart.forEach(function(it, idx){
            var sub = (it.preco * it.qtd).toFixed(2).replace('.',',');
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

    // Total
    document.getElementById('cartTotal').textContent = 'R$ ' + total.toFixed(2).replace('.',',');

    // Mensagem WhatsApp
    var linhas = cart.map(function(i){
        var linha = i.qtd + 'x ' + i.nome;
        if (i.adicionais.length) linha += ' (' + i.adicionais.join(', ') + ')';
        if (i.obs) linha += ' — obs: ' + i.obs;
        linha += ' — R$ ' + (i.preco * i.qtd).toFixed(2).replace('.',',');
        return linha;
    });
    var totalFinal = total + (total > 0 && total < GRATIS_ACIMA ? TAXA_ENTREGA : 0);
    var msg = 'Olá! Quero fazer um pedido:\n\n' + linhas.join('\n') + '\n\nTotal: R$ ' + totalFinal.toFixed(2).replace('.',',');
    document.getElementById('btnFinalizarWpp').onclick = function(){
        if (cart.length === 0){ showToast('Adicione itens ao carrinho primeiro!'); return; }
        window.open('https://wa.me/' + WHATSAPP + '?text=' + encodeURIComponent(msg), '_blank','noopener');
    };
}

function mudarQtdCart(idx, delta){
    cart[idx].qtd = Math.max(1, cart[idx].qtd + delta);
    salvarCart();
    renderCart();
}

function removeCart(idx){
    cart.splice(idx, 1);
    salvarCart();
    renderCart();
}

function salvarCart(){
    localStorage.setItem('sc_cart', JSON.stringify(cart));
}

// ── TOAST ────────────────────────────────────
var toastTimer;
function showToast(msg){
    var el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ el.classList.remove('show'); }, 2500);
}

// Fechar modal com ESC
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') fecharModal();
});
</script>

</body>
</html>