<?php
// ============================================================
// public/checkout.php
// ============================================================
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/frete.php';
require_once __DIR__ . '/../inc/carrinho.php';
require_once __DIR__ . '/../inc/pedidos.php';

$logado        = cliente_logado();
$sessao        = cliente_sessao();
$cliente_nome  = $sessao['nome']  ?? '';
$cliente_email = $sessao['email'] ?? '';

$enderecos_salvos = [];
if ($logado) {
    $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE cliente_id = ? ORDER BY principal DESC, id DESC");
    $stmt->execute([$sessao['id']]);
    $enderecos_salvos = $stmt->fetchAll();
}

$cfg_stmt = $pdo->query("SELECT chave, valor FROM configuracoes
    WHERE chave IN ('loja_whatsapp','entrega_taxa','entrega_gratis',
                    'entrega_tempo','entrega_raio','entrega_ativa',
                    'retirada_ativa','retirada_tempo','local_ativo',
                    'pag_dinheiro','pag_cartao','pag_pix','loja_nome')");
$cfg = [];
foreach ($cfg_stmt->fetchAll() as $r) $cfg[$r['chave']] = $r['valor'];

$whatsapp       = $cfg['loja_whatsapp']  ?? '5581987028550';
$taxa_entrega   = (float)($cfg['entrega_taxa']   ?? 5.00);
$gratis_acima   = (float)($cfg['entrega_gratis'] ?? 50.00);
$entrega_tempo  = $cfg['entrega_tempo']  ?? '40';
$retirada_tempo = $cfg['retirada_tempo'] ?? '15';
$entrega_ativa  = (bool)(int)($cfg['entrega_ativa']  ?? 1);
$retirada_ativa = (bool)(int)($cfg['retirada_ativa'] ?? 1);
$local_ativo    = (bool)(int)($cfg['local_ativo']    ?? 1);
$pag_dinheiro   = (bool)(int)($cfg['pag_dinheiro']   ?? 1);
$pag_cartao     = (bool)(int)($cfg['pag_cartao']     ?? 1);
$pag_pix        = (bool)(int)($cfg['pag_pix']        ?? 1);

// ── PROCESSAR POST ────────────────────────────────────────────
$erro      = '';
$sucesso   = false;
$pedido_id = null;
$wpp_url   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome']         ?? '');
    $tel       = trim($_POST['tel']          ?? '');
    $tipo      = $_POST['tipo_entrega']      ?? 'entrega';
    $pagto     = $_POST['pagamento']         ?? 'pix';
    $troco     = (float)str_replace([',','R$',' '], ['.','',''], $_POST['troco'] ?? 0);
    $obs       = trim($_POST['obs']          ?? '');
    $cod_cupom = strtoupper(trim($_POST['cupom'] ?? ''));
    $mesa      = trim($_POST['mesa']         ?? '');

    $rua    = trim($_POST['rua']         ?? '');
    $numero = trim($_POST['numero']      ?? '');
    $compl  = trim($_POST['complemento'] ?? '');
    $bairro = trim($_POST['bairro']      ?? '');
    $ref    = trim($_POST['referencia']  ?? '');
    $salvar_end = isset($_POST['salvar_endereco']) && $logado;

    $itens = carrinho_do_post();

    if (empty($nome))  $erro = 'Preencha seu nome.';
    elseif (empty($tel)) $erro = 'Preencha seu WhatsApp.';
    elseif (empty($itens)) $erro = 'Seu carrinho está vazio.';
    elseif ($tipo === 'entrega' && (empty($rua) || empty($numero) || empty($bairro)))
        $erro = 'Preencha o endereço completo para entrega.';
    else {
        $subtotal = carrinho_subtotal($itens);
        $frete    = calcular_frete($pdo, $subtotal, $tipo);
        $taxa     = $frete['taxa'];

        $cupom_resultado = cupom_aplicar($pdo, $cod_cupom, $subtotal);
        $desconto  = $cupom_resultado['desconto'];
        $cupom_id  = $cupom_resultado['cupom_id'];

        $endereco_id = null;
        if ($salvar_end && $tipo === 'entrega') {
            $apelido = trim($_POST['apelido_end'] ?? 'Casa');
            $stmt = $pdo->prepare("
                INSERT INTO enderecos (cliente_id, apelido, rua, numero, complemento, bairro, cidade, estado, referencia)
                VALUES (?, ?, ?, ?, ?, ?, 'Recife', 'PE', ?)
            ");
            $stmt->execute([$sessao['id'], $apelido ?: 'Casa', $rua, $numero, $compl, $bairro, $ref]);
            $endereco_id = $pdo->lastInsertId();
        }

        // Para "local", endereço fica como mesa/observação
        $endereco_str = '';
        if ($tipo === 'entrega')  $endereco_str = $rua . ', ' . $numero;
        if ($tipo === 'local')    $endereco_str = $mesa ? 'Mesa ' . $mesa : 'Consumo no local';

        $dados_pedido = [
            'cliente_id'   => $sessao['id'] ?? null,
            'cliente_nome' => $nome,
            'cliente_tel'  => preg_replace('/\D/', '', $tel),
            'tipo_entrega' => $tipo,
            'endereco'     => $endereco_str,
            'bairro'       => $tipo === 'entrega' ? $bairro : '',
            'complemento'  => $tipo === 'entrega' ? $compl : '',
            'referencia'   => $tipo === 'entrega' ? $ref : ($mesa ? 'Mesa ' . $mesa : ''),
            'pagamento'    => $pagto,
            'troco_para'   => $troco > 0 ? $troco : null,
            'observacao'   => $obs,
            'cupom_id'     => $cupom_id,
        ];

        $pedido_id = pedido_criar($pdo, $dados_pedido, $itens, $taxa, $desconto);

        if ($pedido_id) {
            $pedido_completo = pedido_detalhe($pdo, $pedido_id);
            $wpp_url = pedido_mensagem_wpp($pedido_completo, $pedido_completo['itens'], $whatsapp);
            $sucesso = true;
        } else {
            $erro = 'Erro ao registrar pedido. Tente novamente.';
        }
    }
}

// Qual tipo fica selecionado por padrão
$tipo_default = $entrega_ativa ? 'entrega' : ($retirada_ativa ? 'retirada' : 'local');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — <?= h($cfg['loja_nome'] ?? 'Sabor & Cia') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --rosa:#f43f7a; --rosa-claro:#fce7f0; --rosa-borda:#f0e8ed;
            --escuro:#1a1014; --cinza:#9ca3af; --branco:#ffffff;
            --bg:#fafafa; --borda:#e5e7eb;
            --serif:Georgia,'Times New Roman',serif;
            --sans:'DM Sans',system-ui,sans-serif;
            --r:12px;
        }
        html { scroll-behavior: smooth; }
        body { font-family:var(--sans); color:var(--escuro); background:var(--bg); overflow-x:hidden; }
        a    { text-decoration: none; color: inherit; }
        .container { width:100%; max-width:1060px; margin:0 auto; padding:0 18px; }

        /* NAVBAR */
        .navbar { position:fixed; top:0; left:0; right:0; z-index:900; background:rgba(255,255,255,.96); backdrop-filter:blur(14px); border-bottom:1px solid var(--rosa-borda); }
        .nav-inner { display:flex; align-items:center; justify-content:space-between; height:60px; }
        .nav-logo  { font-family:var(--serif); font-size:1.3rem; font-weight:700; }
        .nav-logo span { color: var(--rosa); }
        .nav-cart  { position:relative; width:40px; height:40px; border-radius:50%; background:var(--rosa-claro); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .2s; }
        .nav-cart:hover { background: var(--rosa); }
        .nav-cart svg { width:17px; height:17px; stroke:var(--rosa); fill:none; stroke-width:2; transition:stroke .2s; }
        .nav-cart:hover svg { stroke: #fff; }
        .cart-badge { position:absolute; top:-3px; right:-3px; background:var(--rosa); color:#fff; font-size:.6rem; font-weight:700; width:17px; height:17px; border-radius:50%; display:none; align-items:center; justify-content:center; border:2px solid #fff; }

        /* PAGE */
        .page { padding: 88px 0 60px; }

        /* STEPS */
        .steps { display:flex; align-items:center; margin-bottom:36px; }
        .step  { display:flex; align-items:center; gap:8px; font-size:.82rem; font-weight:600; color:var(--cinza); }
        .step.ativo { color: var(--rosa); }
        .step.feito { color: var(--escuro); }
        .step-num { width:26px; height:26px; border-radius:50%; border:2px solid var(--rosa-borda); display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; background:var(--branco); flex-shrink:0; }
        .step.ativo .step-num { background:var(--rosa); border-color:var(--rosa); color:#fff; }
        .step.feito .step-num { background:var(--escuro); border-color:var(--escuro); color:#fff; }
        .step-sep { flex:1; height:1px; background:var(--rosa-borda); margin:0 12px; max-width:60px; }

        .page-titulo { margin-bottom:32px; }
        .page-titulo h1 { font-family:var(--serif); font-size:clamp(1.7rem,4vw,2.2rem); margin-bottom:4px; }
        .page-titulo p  { font-size:.85rem; color:var(--cinza); }

        /* GRID */
        .checkout-grid { display:grid; grid-template-columns:1fr 340px; gap:28px; align-items:start; }

        /* SEÇÕES */
        .sec { background:var(--branco); border:1px solid var(--rosa-borda); border-radius:var(--r); padding:22px; margin-bottom:18px; }
        .sec:last-child { margin-bottom: 0; }
        .sec-head { display:flex; align-items:center; gap:10px; margin-bottom:18px; padding-bottom:14px; border-bottom:1px solid var(--rosa-borda); }
        .sec-head-num { width:26px; height:26px; border-radius:50%; background:var(--rosa); color:#fff; font-size:.78rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .sec-titulo { font-family:var(--serif); font-size:1rem; font-weight:700; }

        /* CAMPOS */
        .campo-grid   { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .campo-grid-3 { display:grid; grid-template-columns:2fr 1fr 1fr; gap:14px; }
        .campo { display:flex; flex-direction:column; gap:6px; }
        .campo label { font-size:.82rem; font-weight:600; color:var(--escuro); }
        .campo input,
        .campo select,
        .campo textarea {
            padding:11px 14px; border-radius:var(--r);
            border:1px solid var(--rosa-borda); background:var(--branco);
            font-family:var(--sans); font-size:.88rem; color:var(--escuro);
            outline:none; width:100%;
            transition:border-color .2s, box-shadow .2s;
        }
        .campo input:focus, .campo select:focus, .campo textarea:focus { border-color:var(--rosa); box-shadow:0 0 0 3px rgba(244,63,122,.1); }
        .campo input::placeholder, .campo textarea::placeholder { color: var(--cinza); }
        .campo textarea { resize:vertical; min-height:72px; }
        .campo-hint { font-size:.75rem; color:var(--cinza); margin-top:3px; }

        /* ENDEREÇOS SALVOS */
        .ends-salvos { display:flex; flex-direction:column; gap:8px; margin-bottom:16px; }
        .end-salvo { display:flex; align-items:center; gap:10px; border:1.5px solid var(--rosa-borda); border-radius:var(--r); padding:12px 14px; cursor:pointer; transition:all .2s; }
        .end-salvo:has(input:checked) { border-color:var(--rosa); background:var(--rosa-claro); }
        .end-salvo input[type="radio"] { accent-color:var(--rosa); width:16px; height:16px; flex-shrink:0; }
        .end-salvo-info { flex:1; min-width:0; }
        .end-salvo-apelido { font-size:.82rem; font-weight:700; }
        .end-salvo-txt { font-size:.78rem; color:var(--cinza); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

        /* ── TIPO DE ENTREGA — 3 opções ── */
        .entrega-opts { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:20px; }
        .entrega-opt  { border:1.5px solid var(--rosa-borda); border-radius:var(--r); padding:14px 12px; cursor:pointer; transition:all .2s; display:flex; flex-direction:column; align-items:center; text-align:center; gap:8px; }
        .entrega-opt:has(input:checked) { border-color:var(--rosa); background:var(--rosa-claro); }
        .entrega-opt input[type="radio"] { display: none; }
        .entrega-opt svg { width:24px; height:24px; stroke:var(--cinza); fill:none; stroke-width:1.8; }
        .entrega-opt:has(input:checked) svg { stroke: var(--rosa); }
        .entrega-opt-titulo { font-size:.85rem; font-weight:700; color:var(--escuro); }
        .entrega-opt:has(input:checked) .entrega-opt-titulo { color: var(--rosa); }
        .entrega-opt-sub { font-size:.74rem; color:var(--cinza); line-height:1.4; }

        /* PAGAMENTO */
        .pagto-opts { display:flex; flex-direction:column; gap:9px; }
        .pagto-opt  { border:1.5px solid var(--rosa-borda); border-radius:var(--r); padding:13px 14px; cursor:pointer; transition:all .2s; display:flex; align-items:center; gap:12px; }
        .pagto-opt:has(input:checked) { border-color:var(--rosa); background:var(--rosa-claro); }
        .pagto-opt input[type="radio"] { accent-color:var(--rosa); width:16px; height:16px; flex-shrink:0; }
        .pagto-opt svg { width:20px; height:20px; stroke:var(--cinza); fill:none; stroke-width:1.8; flex-shrink:0; }
        .pagto-opt:has(input:checked) svg { stroke: var(--rosa); }
        .pagto-opt-titulo { font-size:.88rem; font-weight:600; }
        .pagto-opt-sub    { font-size:.76rem; color:var(--cinza); }
        .troco-wrap { margin-top:12px; display:none; }
        .troco-wrap.vis { display: block; }

        /* CUPOM */
        .cupom-wrap { display:flex; gap:8px; margin-top:4px; }
        .cupom-wrap input { flex: 1; }
        .btn-cupom { padding:11px 18px; border-radius:var(--r); background:var(--escuro); color:#fff; border:none; font-family:var(--sans); font-size:.85rem; font-weight:600; cursor:pointer; white-space:nowrap; transition:background .2s; }
        .btn-cupom:hover { background: var(--rosa); }
        .cupom-msg { font-size:.78rem; margin-top:6px; font-weight:500; }
        .cupom-msg.ok  { color: #15803d; }
        .cupom-msg.err { color: #be185d; }

        /* ALERTAS */
        .alerta-erro { background:#fff0f4; border:1px solid var(--rosa-borda); color:#be185d; border-radius:var(--r); padding:12px 16px; font-size:.85rem; font-weight:500; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
        .alerta-erro svg { width:15px; height:15px; stroke:currentColor; fill:none; stroke-width:2; flex-shrink:0; }

        /* RESUMO */
        .resumo { background:var(--branco); border:1px solid var(--rosa-borda); border-radius:var(--r); padding:22px; position:sticky; top:76px; }
        .resumo h2 { font-family:var(--serif); font-size:1.05rem; font-weight:700; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--rosa-borda); }
        .resumo-itens { margin-bottom:14px; }
        .resumo-item  { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; font-size:.82rem; padding:7px 0; border-bottom:1px solid var(--bg); }
        .resumo-item:last-child { border-bottom: none; }
        .resumo-item-nome  { flex:1; color:var(--cinza); }
        .resumo-item-adds  { font-size:.72rem; color:var(--cinza); margin-top:1px; }
        .resumo-item-preco { font-weight:600; white-space:nowrap; }
        .resumo-sep   { border:none; border-top:1px solid var(--rosa-borda); margin:12px 0; }
        .resumo-linha { display:flex; justify-content:space-between; font-size:.83rem; color:var(--cinza); margin-bottom:6px; }
        .resumo-linha.total { font-size:1rem; font-weight:700; color:var(--escuro); padding-top:12px; margin-top:4px; border-top:1px solid var(--rosa-borda); margin-bottom:18px; }
        .resumo-linha.total span:last-child { font-family:var(--serif); font-size:1.3rem; color:var(--rosa); }
        .resumo-linha.desconto span:last-child { color: #16a34a; }
        .resumo-entrega-info { font-size:.78rem; color:var(--cinza); text-align:center; margin-bottom:12px; }
        .resumo-entrega-info strong { color: #16a34a; }

        /* BADGE TIPO — aparece no resumo */
        .resumo-tipo-badge { display:inline-flex; align-items:center; gap:5px; font-size:.75rem; font-weight:600; padding:4px 10px; border-radius:50px; margin-bottom:14px; }
        .resumo-tipo-badge.entrega  { background:#eff6ff; color:#2563eb; }
        .resumo-tipo-badge.retirada { background:#f0fdf4; color:#15803d; }
        .resumo-tipo-badge.local    { background:var(--rosa-claro); color:var(--rosa); }
        .resumo-tipo-badge svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:2; }

        /* BOTÃO CONFIRMAR */
        .btn-confirmar { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:14px; border-radius:var(--r); background:#25D366; color:#fff; border:none; font-family:var(--sans); font-size:.95rem; font-weight:600; cursor:pointer; transition:opacity .2s; margin-bottom:10px; }
        .btn-confirmar:hover { opacity: .88; }
        .btn-confirmar:disabled { opacity:.5; cursor:not-allowed; }
        .btn-confirmar svg { width:18px; height:18px; stroke:#fff; fill:none; stroke-width:2; }
        .btn-voltar { display:block; text-align:center; padding:11px; border-radius:var(--r); border:1px solid var(--rosa-borda); background:var(--branco); color:var(--cinza); font-size:.88rem; font-weight:500; transition:all .2s; }
        .btn-voltar:hover { border-color:var(--rosa); color:var(--rosa); }

        /* CHECKBOX SALVAR */
        .check-row { display:flex; align-items:center; gap:8px; margin-top:12px; font-size:.82rem; color:var(--cinza); cursor:pointer; }
        .check-row input { accent-color:var(--rosa); width:15px; height:15px; cursor:pointer; }

        /* VAZIO */
        .vazio { text-align:center; padding:80px 0; }
        .vazio svg { display:block; margin:0 auto 20px; stroke:var(--rosa-borda); fill:none; }
        .vazio h2  { font-family:var(--serif); font-size:1.5rem; margin-bottom:8px; }
        .vazio p   { font-size:.9rem; color:var(--cinza); margin-bottom:24px; }
        .btn-rosa  { display:inline-flex; align-items:center; justify-content:center; gap:7px; padding:13px 26px; border-radius:50px; background:var(--rosa); color:#fff; border:none; font-family:var(--sans); font-weight:600; font-size:.9rem; cursor:pointer; transition:opacity .2s; text-decoration:none; }
        .btn-rosa:hover { opacity: .85; }

        /* SUCESSO */
        .sucesso-box { text-align:center; padding:60px 0; max-width:440px; margin:0 auto; }
        .sucesso-icone { width:72px; height:72px; border-radius:50%; background:#f0fdf4; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
        .sucesso-icone svg { width:32px; height:32px; stroke:#16a34a; fill:none; stroke-width:2.5; }
        .sucesso-box h2 { font-family:var(--serif); font-size:1.7rem; margin-bottom:10px; }
        .sucesso-box p  { font-size:.9rem; color:var(--cinza); margin-bottom:28px; line-height:1.7; }
        .btn-wpp { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:14px 28px; border-radius:50px; background:#25D366; color:#fff; border:none; font-family:var(--sans); font-size:.95rem; font-weight:600; cursor:pointer; transition:opacity .2s; text-decoration:none; margin-bottom:12px; }
        .btn-wpp:hover { opacity: .88; }
        .btn-wpp svg { width:18px; height:18px; stroke:#fff; fill:none; stroke-width:2; }

        .footer-mini { text-align:center; padding:28px 0; font-size:.78rem; color:var(--cinza); border-top:1px solid var(--rosa-borda); margin-top:60px; }

        .toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(80px); background:var(--escuro); color:#fff; padding:10px 22px; border-radius:50px; font-size:.85rem; font-weight:500; z-index:2000; opacity:0; transition:all .3s; white-space:nowrap; pointer-events:none; }
        .toast.show { transform:translateX(-50%) translateY(0); opacity:1; }

        @media (max-width:860px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .resumo { position: static; }
            .entrega-opts { grid-template-columns: 1fr; }
        }
        @media (max-width:560px) {
            .campo-grid, .campo-grid-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="container nav-inner">
        <a href="index.php" class="nav-logo">Sabor<span>&</span>Cia</a>
        <button class="nav-cart" id="btnAbrirCart" aria-label="Carrinho">
            <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span class="cart-badge" id="cartBadge"></span>
        </button>
    </div>
</nav>

<div class="page">
<div class="container">

    <?php if ($sucesso): ?>
    <!-- ══ SUCESSO ══════════════════════════════ -->
    <div class="sucesso-box">
        <div class="sucesso-icone">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h2>Pedido registrado!</h2>
        <p>
            Pedido <strong>#<?= $pedido_id ?></strong> salvo com sucesso.<br>
            Clique abaixo para enviar pelo WhatsApp e confirmar.
        </p>
        <a href="<?= h($wpp_url) ?>" target="_blank" rel="noopener" class="btn-wpp">
            <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            Confirmar pelo WhatsApp
        </a><br>
        <a href="index.php" class="btn-rosa" style="margin-top:10px">Voltar ao início</a>
    </div>
    <script>localStorage.removeItem('sc_cart');</script>

    <?php else: ?>
    <!-- ══ FORMULÁRIO ═══════════════════════════ -->

    <div class="steps">
        <div class="step feito"><div class="step-num">✓</div><span>Carrinho</span></div>
        <div class="step-sep"></div>
        <div class="step ativo"><div class="step-num">2</div><span>Seus dados</span></div>
        <div class="step-sep"></div>
        <div class="step"><div class="step-num">3</div><span>Confirmação</span></div>
    </div>

    <div class="page-titulo">
        <h1>Finalizar pedido</h1>
        <p>Preencha seus dados. O pedido será confirmado pelo WhatsApp.</p>
    </div>

    <?php if ($erro): ?>
    <div class="alerta-erro">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= h($erro) ?>
    </div>
    <?php endif; ?>

    <!-- CARRINHO VAZIO -->
    <div class="vazio" id="estadoVazio" style="display:none">
        <svg width="56" height="56" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <h2>Carrinho vazio</h2>
        <p>Adicione produtos antes de finalizar.</p>
        <a href="index.php" class="btn-rosa">Ver cardápio</a>
    </div>

    <form method="POST" id="formCheckout" style="display:none">
        <input type="hidden" name="carrinho_json" id="carrinhoJson">

        <div class="checkout-grid">

            <!-- COLUNA ESQUERDA -->
            <div>

                <!-- 1. DADOS PESSOAIS -->
                <div class="sec">
                    <div class="sec-head">
                        <div class="sec-head-num">1</div>
                        <div class="sec-titulo">Dados pessoais</div>
                    </div>
                    <div class="campo-grid">
                        <div class="campo">
                            <label for="nome">Nome completo *</label>
                            <input type="text" id="nome" name="nome"
                                value="<?= h($cliente_nome) ?>"
                                placeholder="Seu nome" autocomplete="name" required>
                        </div>
                        <div class="campo">
                            <label for="tel">WhatsApp *</label>
                            <input type="tel" id="tel" name="tel"
                                placeholder="(81) 99999-9999" autocomplete="tel"
                                oninput="mascaraTel(this)" required>
                        </div>
                    </div>
                    <?php if (!$logado): ?>
                    <p style="font-size:.78rem;color:var(--cinza);margin-top:10px;">
                        Tem conta? <a href="login.php?volta=checkout.php" style="color:var(--rosa);font-weight:600;">Entrar</a> para carregar seus dados.
                    </p>
                    <?php endif; ?>
                </div>

                <!-- 2. COMO QUER RECEBER -->
                <div class="sec">
                    <div class="sec-head">
                        <div class="sec-head-num">2</div>
                        <div class="sec-titulo">Como quer receber?</div>
                    </div>

                    <!-- ── 3 OPÇÕES ── -->
                    <div class="entrega-opts">

                        <?php if ($entrega_ativa): ?>
                        <label class="entrega-opt">
                            <input type="radio" name="tipo_entrega" value="entrega"
                                <?= $tipo_default === 'entrega' ? 'checked' : '' ?>
                                onchange="onTipoChange()">
                            <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                            <div class="entrega-opt-titulo">Entrega</div>
                            <div class="entrega-opt-sub">~<?= $entrega_tempo ?> min<br>Taxa: R$ <?= number_format($taxa_entrega, 2, ',', '.') ?></div>
                        </label>
                        <?php endif; ?>

                        <?php if ($retirada_ativa): ?>
                        <label class="entrega-opt">
                            <input type="radio" name="tipo_entrega" value="retirada"
                                <?= $tipo_default === 'retirada' ? 'checked' : '' ?>
                                onchange="onTipoChange()">
                            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <div class="entrega-opt-titulo">Retirada</div>
                            <div class="entrega-opt-sub">~<?= $retirada_tempo ?> min<br>Grátis</div>
                        </label>
                        <?php endif; ?>

                        <?php if ($local_ativo): ?>
                        <label class="entrega-opt">
                            <input type="radio" name="tipo_entrega" value="local"
                                <?= $tipo_default === 'local' ? 'checked' : '' ?>
                                onchange="onTipoChange()">
                            <svg viewBox="0 0 24 24"><path d="M3 2h18v20H3z"/><path d="M9 22v-4h6v4"/><rect x="7" y="6" width="3" height="3"/><rect x="14" y="6" width="3" height="3"/><rect x="7" y="12" width="3" height="3"/><rect x="14" y="12" width="3" height="3"/></svg>
                            <div class="entrega-opt-titulo">Comer aqui</div>
                            <div class="entrega-opt-sub">No local<br>Sem taxa</div>
                        </label>
                        <?php endif; ?>

                    </div>

                    <!-- CAMPOS DE ENTREGA (rua, número, bairro...) -->
                    <div id="camposEntrega" style="display:<?= $tipo_default === 'entrega' ? 'block' : 'none' ?>">
                        <?php if (!empty($enderecos_salvos)): ?>
                        <p style="font-size:.8rem;font-weight:600;margin-bottom:10px;">Seus endereços:</p>
                        <div class="ends-salvos">
                            <?php foreach ($enderecos_salvos as $e): ?>
                            <label class="end-salvo" onclick="preencherEndereco(<?= h(json_encode($e)) ?>)">
                                <input type="radio" name="end_salvo" value="<?= $e['id'] ?>" <?= $e['principal'] ? 'checked' : '' ?>>
                                <div class="end-salvo-info">
                                    <div class="end-salvo-apelido"><?= h($e['apelido']) ?></div>
                                    <div class="end-salvo-txt"><?= h($e['rua'] . ', ' . $e['numero'] . ' — ' . $e['bairro']) ?></div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                            <label class="end-salvo">
                                <input type="radio" name="end_salvo" value="novo" onchange="mostrarFormEnd()">
                                <div class="end-salvo-info">
                                    <div class="end-salvo-apelido">+ Novo endereço</div>
                                    <div class="end-salvo-txt">Preencher manualmente</div>
                                </div>
                            </label>
                        </div>
                        <?php endif; ?>

                        <div id="formEndereco" <?= !empty($enderecos_salvos) ? 'style="display:none"' : '' ?>>
                            <div class="campo" style="margin-bottom:14px">
                                <label for="rua">Rua / Avenida *</label>
                                <input type="text" id="rua" name="rua" placeholder="Ex: Rua das Flores" autocomplete="street-address">
                            </div>
                            <div class="campo-grid-3" style="margin-bottom:14px">
                                <div class="campo">
                                    <label for="numero">Número *</label>
                                    <input type="text" id="numero" name="numero" placeholder="123">
                                </div>
                                <div class="campo">
                                    <label for="complemento">Complemento</label>
                                    <input type="text" id="complemento" name="complemento" placeholder="Apto...">
                                </div>
                                <div class="campo">
                                    <label for="bairro">Bairro *</label>
                                    <input type="text" id="bairro" name="bairro" placeholder="Seu bairro">
                                </div>
                            </div>
                            <div class="campo" style="margin-bottom:8px">
                                <label for="referencia">Ponto de referência</label>
                                <input type="text" id="referencia" name="referencia" placeholder="Próximo ao...">
                            </div>
                            <?php if ($logado): ?>
                            <label class="check-row">
                                <input type="checkbox" name="salvar_endereco" value="1" id="cbSalvar">
                                Salvar endereço para próximos pedidos
                            </label>
                            <div id="apelidoEndWrap" style="display:none;margin-top:10px">
                                <div class="campo">
                                    <label for="apelido_end">Apelido</label>
                                    <input type="text" id="apelido_end" name="apelido_end" placeholder="Casa, Trabalho...">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CAMPO MESA (só para "comer no local") -->
                    <div id="camposLocal" style="display:<?= $tipo_default === 'local' ? 'block' : 'none' ?>">
                        <div class="campo">
                            <label for="mesa">Número da mesa <span style="font-weight:400;color:var(--cinza)">(opcional)</span></label>
                            <input type="text" id="mesa" name="mesa" placeholder="Ex: Mesa 5">
                            <span class="campo-hint">Deixe em branco se preferir retirar no balcão</span>
                        </div>
                    </div>

                    <!-- Info retirada -->
                    <div id="camposRetirada" style="display:<?= $tipo_default === 'retirada' ? 'block' : 'none' ?>">
                        <div style="background:var(--bg);border:1px solid var(--borda);border-radius:var(--r);padding:14px 16px;font-size:.85rem;color:var(--cinza);line-height:1.6">
                            📍 Retire na loja em até <strong style="color:var(--escuro)"><?= $retirada_tempo ?> minutos</strong>.<br>
                            Você receberá a confirmação pelo WhatsApp.
                        </div>
                    </div>
                </div>

                <!-- 3. PAGAMENTO -->
                <div class="sec">
                    <div class="sec-head">
                        <div class="sec-head-num">3</div>
                        <div class="sec-titulo">Forma de pagamento</div>
                    </div>
                    <div class="pagto-opts">
                        <?php if ($pag_pix): ?>
                        <label class="pagto-opt">
                            <input type="radio" name="pagamento" value="pix" checked onchange="toggleTroco()">
                            <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                            <div>
                                <div class="pagto-opt-titulo">Pix</div>
                                <div class="pagto-opt-sub">Mais rápido, sem troco</div>
                            </div>
                        </label>
                        <?php endif; ?>
                        <?php if ($pag_cartao): ?>
                        <label class="pagto-opt">
                            <input type="radio" name="pagamento" value="cartao" onchange="toggleTroco()">
                            <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            <div>
                                <div class="pagto-opt-titulo">Cartão</div>
                                <div class="pagto-opt-sub">Débito ou crédito</div>
                            </div>
                        </label>
                        <?php endif; ?>
                        <?php if ($pag_dinheiro): ?>
                        <label class="pagto-opt">
                            <input type="radio" name="pagamento" value="dinheiro" onchange="toggleTroco()">
                            <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                            <div>
                                <div class="pagto-opt-titulo">Dinheiro</div>
                                <div class="pagto-opt-sub">Pague na entrega</div>
                            </div>
                        </label>
                        <?php endif; ?>
                    </div>
                    <div class="troco-wrap" id="trocoWrap">
                        <div class="campo" style="margin-top:14px">
                            <label for="troco">Troco para quanto?</label>
                            <input type="text" id="troco" name="troco" placeholder="Ex: R$ 50,00">
                            <span class="campo-hint">Deixe em branco se não precisar de troco</span>
                        </div>
                    </div>
                </div>

                <!-- 4. CUPOM -->
                <div class="sec">
                    <div class="sec-head">
                        <div class="sec-head-num">4</div>
                        <div class="sec-titulo">Cupom de desconto</div>
                    </div>
                    <div class="campo">
                        <label for="cupom">Código do cupom</label>
                        <div class="cupom-wrap">
                            <input type="text" id="cupom" name="cupom"
                                placeholder="EX: BEMVINDO10"
                                style="text-transform:uppercase">
                            <button type="button" class="btn-cupom" onclick="verificarCupom()">Aplicar</button>
                        </div>
                        <div class="cupom-msg" id="cupomMsg"></div>
                    </div>
                </div>

                <!-- 5. OBSERVAÇÕES -->
                <div class="sec">
                    <div class="sec-head">
                        <div class="sec-head-num">5</div>
                        <div class="sec-titulo">Observações</div>
                    </div>
                    <div class="campo">
                        <textarea name="obs" placeholder="Observação geral sobre o pedido..." rows="3"></textarea>
                    </div>
                </div>

            </div><!-- /esquerda -->

            <!-- COLUNA DIREITA — RESUMO -->
            <div>
                <div class="resumo">
                    <h2>Resumo do pedido</h2>

                    <!-- Badge do tipo escolhido -->
                    <div id="resumoTipoBadge" class="resumo-tipo-badge entrega">
                        <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        <span id="resumoTipoLabel">Entrega</span>
                    </div>

                    <div class="resumo-itens" id="resumoItens"></div>
                    <hr class="resumo-sep">

                    <div class="resumo-linha">
                        <span>Subtotal</span>
                        <span id="resumoSubtotal">R$ 0,00</span>
                    </div>
                    <div class="resumo-linha desconto" id="linhaDesconto" style="display:none">
                        <span>Desconto</span>
                        <span id="resumoDesconto"></span>
                    </div>
                    <div class="resumo-linha" id="linhaFrete">
                        <span>Entrega</span>
                        <span id="resumoFrete"></span>
                    </div>
                    <div class="resumo-linha total">
                        <span>Total</span>
                        <span id="resumoTotal">R$ 0,00</span>
                    </div>

                    <div class="resumo-entrega-info" id="resumoEntregaInfo"></div>

                    <button type="submit" class="btn-confirmar" id="btnConfirmar" onclick="prepararSubmit(event)">
                        <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        Confirmar pelo WhatsApp
                    </button>
                    <a href="index.php" class="btn-voltar">Voltar ao cardápio</a>
                </div>
            </div>

        </div><!-- /checkout-grid -->
    </form>

    <?php endif; ?>

</div>
</div>

<footer class="footer-mini">
    © <?= date('Y') ?> <?= h($cfg['loja_nome'] ?? 'Sabor & Cia') ?> — Todos os direitos reservados.
</footer>

<div class="toast" id="toast"></div>

<script>
var TAXA_ENTREGA   = <?= $taxa_entrega ?>;
var GRATIS_ACIMA   = <?= $gratis_acima ?>;
var DESCONTO_ATIVO = 0;
var DESCONTO_TIPO  = '';
var DESCONTO_VALOR = 0;

var cart = JSON.parse(localStorage.getItem('sc_cart') || '[]');

window.addEventListener('load', function(){
    if (cart.length === 0){
        document.getElementById('estadoVazio').style.display = 'block';
    } else {
        document.getElementById('formCheckout').style.display = 'block';
        renderResumo();
        atualizarBadge();
    }
});

function atualizarBadge(){
    var t = cart.reduce(function(s,i){ return s + i.qtd; }, 0);
    var b = document.getElementById('cartBadge');
    b.textContent   = t;
    b.style.display = t > 0 ? 'flex' : 'none';
}

// ── RENDERIZAR RESUMO ────────────────────────
function renderResumo(){
    var subtotal = cart.reduce(function(s,i){ return s + i.preco * i.qtd; }, 0);
    var tipo     = getTipo();

    // Calcular frete
    var frete = 0, freteLabel = '';
    if (tipo === 'retirada' || tipo === 'local'){
        frete = 0; freteLabel = 'Grátis';
    } else if (GRATIS_ACIMA > 0 && subtotal >= GRATIS_ACIMA){
        frete = 0; freteLabel = 'Grátis';
    } else {
        frete = TAXA_ENTREGA;
        freteLabel = 'R$ ' + TAXA_ENTREGA.toFixed(2).replace('.',',');
    }

    // Desconto
    var desconto = 0;
    if (DESCONTO_ATIVO > 0){
        desconto = DESCONTO_TIPO === 'percentual'
            ? subtotal * (DESCONTO_VALOR / 100)
            : Math.min(DESCONTO_VALOR, subtotal);
    }

    var total = subtotal + frete - desconto;

    // Itens
    var html = '';
    cart.forEach(function(it){
        var adds = it.adicionais && it.adicionais.length ? it.adicionais.join(', ') : '';
        html += '<div class="resumo-item">'
              + '<div><div class="resumo-item-nome">' + it.nome + (it.qtd > 1 ? ' ×' + it.qtd : '') + '</div>'
              + (adds ? '<div class="resumo-item-adds">' + adds + '</div>' : '')
              + '</div>'
              + '<div class="resumo-item-preco">R$ ' + (it.preco * it.qtd).toFixed(2).replace('.',',') + '</div>'
              + '</div>';
    });
    document.getElementById('resumoItens').innerHTML     = html;
    document.getElementById('resumoSubtotal').textContent = 'R$ ' + subtotal.toFixed(2).replace('.',',');
    document.getElementById('resumoFrete').textContent    = freteLabel;
    document.getElementById('resumoTotal').textContent    = 'R$ ' + total.toFixed(2).replace('.',',');

    // Desconto
    var ld = document.getElementById('linhaDesconto');
    if (desconto > 0){
        ld.style.display = 'flex';
        document.getElementById('resumoDesconto').textContent = '- R$ ' + desconto.toFixed(2).replace('.',',');
    } else {
        ld.style.display = 'none';
    }

    // Info entrega grátis
    var info = document.getElementById('resumoEntregaInfo');
    if (tipo === 'entrega' && GRATIS_ACIMA > 0){
        if (subtotal >= GRATIS_ACIMA){
            info.innerHTML = '<strong>Entrega grátis!</strong>';
        } else {
            var faltam = (GRATIS_ACIMA - subtotal).toFixed(2).replace('.',',');
            info.innerHTML = 'Faltam <strong>R$ ' + faltam + '</strong> para entrega grátis';
        }
    } else {
        info.innerHTML = '';
    }

    // Badge tipo no resumo
    atualizarBadgeTipo(tipo);
}

function getTipo(){
    var sel = document.querySelector('input[name="tipo_entrega"]:checked');
    return sel ? sel.value : 'entrega';
}

function atualizarBadgeTipo(tipo){
    var badge = document.getElementById('resumoTipoBadge');
    var label = document.getElementById('resumoTipoLabel');
    var icons = {
        entrega:  '<svg viewBox="0 0 24 24" style="width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        retirada: '<svg viewBox="0 0 24 24" style="width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>',
        local:    '<svg viewBox="0 0 24 24" style="width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:2"><path d="M3 2h18v20H3z"/></svg>',
    };
    var labels = { entrega:'Entrega', retirada:'Retirada no local', local:'Comer aqui' };
    badge.className = 'resumo-tipo-badge ' + tipo;
    badge.innerHTML = (icons[tipo] || '') + '<span>' + (labels[tipo] || tipo) + '</span>';
}

// ── TOGGLES DE TIPO ──────────────────────────
function onTipoChange(){
    var tipo = getTipo();
    document.getElementById('camposEntrega').style.display  = tipo === 'entrega'  ? 'block' : 'none';
    document.getElementById('camposLocal').style.display    = tipo === 'local'    ? 'block' : 'none';
    document.getElementById('camposRetirada').style.display = tipo === 'retirada' ? 'block' : 'none';
    renderResumo();
}

function toggleTroco(){
    var val = document.querySelector('input[name="pagamento"]:checked').value;
    document.getElementById('trocoWrap').classList.toggle('vis', val === 'dinheiro');
}
toggleTroco();

function mostrarFormEnd(){
    document.getElementById('formEndereco').style.display = 'block';
}

function preencherEndereco(end){
    mostrarFormEnd();
    document.getElementById('rua').value         = end.rua         || '';
    document.getElementById('numero').value      = end.numero      || '';
    document.getElementById('complemento').value = end.complemento || '';
    document.getElementById('bairro').value      = end.bairro      || '';
    document.getElementById('referencia').value  = end.referencia  || '';
}

<?php if (!empty($enderecos_salvos)): ?>
(function(){
    var ends = <?= json_encode(array_values($enderecos_salvos), JSON_UNESCAPED_UNICODE) ?>;
    var principal = ends.find(function(e){ return e.principal == 1; }) || ends[0];
    if (principal) preencherEndereco(principal);
})();
<?php endif; ?>

var cbSalvar = document.getElementById('cbSalvar');
if (cbSalvar){
    cbSalvar.addEventListener('change', function(){
        var wrap = document.getElementById('apelidoEndWrap');
        if (wrap) wrap.style.display = this.checked ? 'block' : 'none';
    });
}

document.querySelectorAll('input[name="tipo_entrega"]').forEach(function(r){
    r.addEventListener('change', renderResumo);
});

// ── CUPOM AJAX ───────────────────────────────
function verificarCupom(){
    var codigo   = document.getElementById('cupom').value.trim().toUpperCase();
    var msg      = document.getElementById('cupomMsg');
    var subtotal = cart.reduce(function(s,i){ return s + i.preco * i.qtd; }, 0);

    if (!codigo){ msg.textContent = 'Digite um código.'; msg.className = 'cupom-msg err'; return; }

    fetch('ajax_cupom.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'codigo=' + encodeURIComponent(codigo) + '&subtotal=' + subtotal.toFixed(2)
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        msg.textContent = d.mensagem;
        msg.className   = 'cupom-msg ' + (d.valido ? 'ok' : 'err');
        if (d.valido){ DESCONTO_ATIVO = 1; DESCONTO_TIPO = d.tipo; DESCONTO_VALOR = d.valor; renderResumo(); }
    })
    .catch(function(){ msg.textContent = 'Erro ao verificar cupom.'; msg.className = 'cupom-msg err'; });
}

// ── MÁSCARA TELEFONE ─────────────────────────
function mascaraTel(inp){
    var v = inp.value.replace(/\D/g,'').substring(0,11);
    if      (v.length > 6) v = '(' + v.substring(0,2) + ') ' + v.substring(2,7) + '-' + v.substring(7);
    else if (v.length > 2) v = '(' + v.substring(0,2) + ') ' + v.substring(2);
    else if (v.length > 0) v = '(' + v;
    inp.value = v;
}

// ── SUBMIT ───────────────────────────────────
function prepararSubmit(e){
    if (cart.length === 0){ e.preventDefault(); showToast('Carrinho vazio!'); return; }
    var payload = cart.map(function(it){
        return { id:it.id, nome:it.nome, preco:it.preco, qtd:it.qtd, adicionais:it.adicionais||[], obs:it.obs||'' };
    });
    document.getElementById('carrinhoJson').value = JSON.stringify(payload);
}

// ── TOAST ────────────────────────────────────
var toastTimer;
function showToast(msg){
    var el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ el.classList.remove('show'); }, 2800);
}
</script>

</body>
</html>