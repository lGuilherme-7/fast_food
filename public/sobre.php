<?php
// public/sobre.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';

// Busca todas as configs necessárias
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes");
$cfg  = [];
foreach ($stmt->fetchAll() as $r) $cfg[$r['chave']] = $r['valor'];

function c(array $cfg, string $chave, string $pad = ''): string {
    return htmlspecialchars($cfg[$chave] ?? $pad);
}
function cb(array $cfg, string $chave, bool $pad = false): bool {
    if (!isset($cfg[$chave])) return $pad;
    return in_array($cfg[$chave], ['1','true','on','yes'], true);
}

$loja_nome    = $cfg['loja_nome']      ?? 'Sabor & Cia';
$whatsapp     = $cfg['loja_whatsapp']  ?? '';

$dias = [
    'seg' => 'Segunda-feira',
    'ter' => 'Terça-feira',
    'qua' => 'Quarta-feira',
    'qui' => 'Quinta-feira',
    'sex' => 'Sexta-feira',
    'sab' => 'Sábado',
    'dom' => 'Domingo',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre — <?= htmlspecialchars($loja_nome) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root {
            --rosa:#f43f7a; --rosa-claro:#fce7f0; --rosa-borda:#f0e8ed;
            --escuro:#1a1014; --cinza:#9ca3af; --branco:#ffffff; --bg:#fafafa;
            --borda:#e5e7eb;
            --f-titulo:Georgia,'Times New Roman',serif;
            --f-corpo:'DM Sans',system-ui,sans-serif; --r:12px;
        }
        html { scroll-behavior:smooth; }
        body { font-family:var(--f-corpo); color:var(--escuro); background:var(--bg); }
        a    { color:var(--rosa); text-decoration:none; }
        a:hover { text-decoration:underline; }
        .container { width:100%; max-width:760px; margin:0 auto; padding:0 18px; }

        /* NAVBAR */
        .navbar { background:var(--branco); border-bottom:1px solid var(--rosa-borda); padding:0 18px; }
        .nav-inner { display:flex; align-items:center; justify-content:space-between; height:60px; max-width:760px; margin:0 auto; }
        .nav-logo  { font-family:var(--f-titulo); font-size:1.3rem; font-weight:700; color:var(--escuro); text-decoration:none; }
        .nav-logo span { color:var(--rosa); }
        .nav-back  { display:flex; align-items:center; gap:6px; font-size:.82rem; color:var(--cinza); transition:color .2s; }
        .nav-back:hover { color:var(--rosa); text-decoration:none; }
        .nav-back svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; }

        /* HERO */
        .hero { background:var(--escuro); padding:48px 0 40px; text-align:center; position:relative; overflow:hidden; }
        .hero::before { content:''; position:absolute; width:500px; height:500px; border-radius:50%; background:radial-gradient(circle,rgba(244,63,122,.15) 0%,transparent 70%); top:-200px; right:-100px; pointer-events:none; }
        .hero-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(244,63,122,.15); border:1px solid rgba(244,63,122,.3); color:var(--rosa); font-size:.72rem; font-weight:600; letter-spacing:1px; text-transform:uppercase; padding:4px 14px; border-radius:50px; margin-bottom:16px; position:relative; z-index:1; }
        .hero-badge svg { width:12px; height:12px; stroke:var(--rosa); fill:none; stroke-width:2; }
        .hero h1 { font-family:var(--f-titulo); font-size:clamp(1.8rem,4vw,2.4rem); color:#fff; margin-bottom:8px; position:relative; z-index:1; }
        .hero h1 em { font-style:normal; color:var(--rosa); }
        .hero p { font-size:.88rem; color:rgba(255,255,255,.5); max-width:420px; margin:0 auto; line-height:1.7; position:relative; z-index:1; }

        /* CONTEÚDO */
        .conteudo { padding:40px 0 72px; }

        /* Cards de info rápida */
        .info-rapida { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:36px; }
        .info-card { background:var(--branco); border:1px solid var(--rosa-borda); border-radius:var(--r); padding:16px; text-align:center; }
        .info-card-icone { width:38px; height:38px; border-radius:50%; background:var(--rosa-claro); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; }
        .info-card-icone svg { width:17px; height:17px; stroke:var(--rosa); fill:none; stroke-width:2; }
        .info-card-val { font-family:var(--f-titulo); font-size:1rem; font-weight:700; color:var(--escuro); margin-bottom:2px; }
        .info-card-lbl { font-size:.74rem; color:var(--cinza); }

        /* Seções */
        .sec { background:var(--branco); border:1px solid var(--rosa-borda); border-radius:var(--r); overflow:hidden; margin-bottom:16px; }
        .sec-head { display:flex; align-items:center; gap:12px; padding:16px 20px; background:var(--bg); border-bottom:1px solid var(--rosa-borda); }
        .sec-icone { width:36px; height:36px; border-radius:9px; background:var(--rosa-claro); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .sec-icone svg { width:16px; height:16px; stroke:var(--rosa); fill:none; stroke-width:2; }
        .sec-head-info h2 { font-family:var(--f-titulo); font-size:.95rem; font-weight:700; }
        .sec-head-info p  { font-size:.75rem; color:var(--cinza); margin-top:1px; }
        .sec-body { padding:18px 20px; }

        /* Linha de dado */
        .dado-linha { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:8px 0; border-bottom:1px solid var(--bg); font-size:.86rem; }
        .dado-linha:last-child { border-bottom:none; padding-bottom:0; }
        .dado-lbl { color:var(--cinza); flex-shrink:0; }
        .dado-val { font-weight:500; text-align:right; }

        /* Horários */
        .dia-linha { display:flex; align-items:center; justify-content:space-between; padding:9px 0; border-bottom:1px solid var(--bg); font-size:.86rem; }
        .dia-linha:last-child { border-bottom:none; padding-bottom:0; }
        .dia-nome { color:var(--cinza); }
        .dia-hora { font-weight:600; }
        .badge-aberto  { background:#f0fdf4; color:#15803d; font-size:.72rem; font-weight:700; padding:2px 8px; border-radius:50px; }
        .badge-fechado { background:#f9fafb; color:var(--cinza); font-size:.72rem; font-weight:600; padding:2px 8px; border-radius:50px; }

        /* Hoje destacado */
        .dia-linha.hoje { background:var(--rosa-claro); margin:0 -20px; padding:9px 20px; border-radius:0; }
        .dia-linha.hoje .dia-nome { color:var(--rosa); font-weight:600; }

        /* Pagamentos */
        .pagtos { display:flex; gap:10px; flex-wrap:wrap; margin-top:4px; }
        .pagto-badge { display:inline-flex; align-items:center; gap:6px; background:var(--bg); border:1px solid var(--borda); border-radius:8px; padding:8px 14px; font-size:.84rem; font-weight:500; }
        .pagto-badge svg { width:15px; height:15px; stroke:var(--escuro); fill:none; stroke-width:1.8; }

        /* Entrega */
        .entrega-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .entrega-item { background:var(--bg); border:1px solid var(--borda); border-radius:var(--r); padding:14px; text-align:center; }
        .entrega-item-val { font-family:var(--f-titulo); font-size:1.15rem; font-weight:700; color:var(--rosa); margin-bottom:2px; }
        .entrega-item-lbl { font-size:.76rem; color:var(--cinza); }

        /* Status aberto/fechado agora */
        .status-agora { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; border-radius:50px; font-size:.82rem; font-weight:600; margin-bottom:24px; }
        .status-agora.aberto  { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .status-agora.fechado { background:#fff5f5; color:#dc2626; border:1px solid #fca5a5; }
        .status-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
        .status-agora.aberto  .status-dot { background:#22c55e; animation:pulse 2s infinite; }
        .status-agora.fechado .status-dot { background:#dc2626; }
        @keyframes pulse { 0%,100%{ opacity:1; } 50%{ opacity:.4; } }

        /* Botão WPP */
        .btn-wpp { display:inline-flex; align-items:center; gap:8px; padding:13px 24px; border-radius:50px; background:#25D366; color:#fff; border:none; font-family:var(--f-corpo); font-size:.92rem; font-weight:600; cursor:pointer; transition:opacity .2s; text-decoration:none; }
        .btn-wpp:hover { opacity:.88; text-decoration:none; }
        .btn-wpp svg { width:18px; height:18px; stroke:#fff; fill:none; stroke-width:2; }

        /* CTA bottom */
        .cta-bottom { background:var(--escuro); border-radius:var(--r); padding:28px; text-align:center; margin-top:24px; }
        .cta-bottom h3 { font-family:var(--f-titulo); font-size:1.15rem; color:#fff; margin-bottom:6px; }
        .cta-bottom p  { font-size:.84rem; color:rgba(255,255,255,.5); margin-bottom:18px; }

        .footer-mini { text-align:center; padding:24px 0; font-size:.75rem; color:var(--cinza); border-top:1px solid var(--rosa-borda); }

        @media (max-width:560px) {
            .info-rapida { grid-template-columns:1fr 1fr; }
            .entrega-grid { grid-template-columns:1fr; }
        }
        @media (max-width:380px) {
            .info-rapida { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-inner">
        <a href="index.php" class="nav-logo">Sabor<span>&</span>Cia</a>
        <a href="index.php" class="nav-back">
            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            Voltar ao início
        </a>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="container">
        <div class="hero-badge">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Nossa loja
        </div>
        <h1><?= htmlspecialchars($loja_nome) ?></h1>
        <?php if (!empty($cfg['loja_descricao'])): ?>
        <p><?= c($cfg,'loja_descricao') ?></p>
        <?php else: ?>
        <p>Açaí, hambúrgueres artesanais, doces e bebidas geladas. Tudo feito com carinho.</p>
        <?php endif; ?>
    </div>
</div>

<!-- CONTEÚDO -->
<div class="conteudo">
    <div class="container">

        <?php
        // Verifica se está aberto agora
        $diasSigla  = ['0'=>'dom','1'=>'seg','2'=>'ter','3'=>'qua','4'=>'qui','5'=>'sex','6'=>'sab'];
        $diaAtual   = $diasSigla[date('w')];
        $horaAtual  = date('H:i');
        $ativo      = cb($cfg, 'func_'.$diaAtual, false);
        $abre       = $cfg['func_'.$diaAtual.'_abre']  ?? '';
        $fecha      = $cfg['func_'.$diaAtual.'_fecha'] ?? '';
        $estaAberto = $ativo && $abre && $fecha && ($horaAtual >= $abre && $horaAtual <= $fecha);
        $jaFechou   = $ativo && $abre && $fecha && ($horaAtual > $fecha);

        $tempoParaAbrir = '';
        if ($ativo && $abre && !$estaAberto && !$jaFechou) {
            $diff = (new DateTime($horaAtual))->diff(new DateTime($abre));
            $tempoParaAbrir = ($diff->h > 0 ? $diff->h.'h ' : '') . $diff->i . 'min';
        }
        ?>

       <!-- STATUS AGORA -->
        <div class="status-agora <?= $estaAberto ? 'aberto' : 'fechado' ?>">
            <div class="status-dot"></div>
            <?php if ($estaAberto): ?>
                Aberto agora — <?= htmlspecialchars($abre) ?> às <?= htmlspecialchars($fecha) ?>
            <?php elseif ($ativo && !$jaFechou && $tempoParaAbrir): ?>
                Abre às <?= htmlspecialchars($abre) ?> — faltam <?= $tempoParaAbrir ?>
            <?php elseif ($ativo && $jaFechou): ?>
                Fechado — funcionou hoje das <?= htmlspecialchars($abre) ?> às <?= htmlspecialchars($fecha) ?>
            <?php else: ?>
                Fechado hoje
            <?php endif; ?>
        </div>

        <!-- CARDS RÁPIDOS -->
        <div class="info-rapida">
            <?php if (!empty($cfg['entrega_tempo'])): ?>
            <div class="info-card">
                <div class="info-card-icone"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div class="info-card-val">~<?= (int)$cfg['entrega_tempo'] ?> min</div>
                <div class="info-card-lbl">Tempo de entrega</div>
            </div>
            <?php endif; ?>
            <?php if (!empty($cfg['entrega_raio'])): ?>
            <div class="info-card">
                <div class="info-card-icone"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                <div class="info-card-val"><?= (int)$cfg['entrega_raio'] ?> km</div>
                <div class="info-card-lbl">Raio de entrega</div>
            </div>
            <?php endif; ?>
            <?php
            $taxa = (float)($cfg['entrega_taxa'] ?? 0);
            $gratis = (float)($cfg['entrega_gratis'] ?? 0);
            ?>
            <div class="info-card">
                <div class="info-card-icone"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                <?php if ($gratis > 0): ?>
                <div class="info-card-val">Grátis</div>
                <div class="info-card-lbl">Acima de R$ <?= number_format($gratis,0,',','.') ?></div>
                <?php elseif ($taxa > 0): ?>
                <div class="info-card-val">R$ <?= number_format($taxa,2,',','.') ?></div>
                <div class="info-card-lbl">Taxa de entrega</div>
                <?php else: ?>
                <div class="info-card-val">Grátis</div>
                <div class="info-card-lbl">Entrega sempre grátis</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DADOS DA LOJA -->
        <?php if (!empty($cfg['loja_endereco']) || !empty($cfg['loja_telefone']) || !empty($cfg['loja_whatsapp']) || !empty($cfg['loja_email'])): ?>
        <div class="sec">
            <div class="sec-head">
                <div class="sec-icone"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                <div class="sec-head-info"><h2>Onde estamos</h2><p>Localização e contato</p></div>
            </div>
            <div class="sec-body">
                <?php if (!empty($cfg['loja_endereco'])): ?>
                <div class="dado-linha">
                    <span class="dado-lbl">Endereço</span>
                    <span class="dado-val">
                        <?= c($cfg,'loja_endereco') ?>
                        <?= !empty($cfg['loja_bairro'])  ? ', '  .c($cfg,'loja_bairro')  : '' ?>
                        <?= !empty($cfg['loja_cidade'])  ? ' — ' .c($cfg,'loja_cidade')  : '' ?>
                        <?= !empty($cfg['loja_estado'])  ? '/'   .c($cfg,'loja_estado')  : '' ?>
                        <?= !empty($cfg['loja_cep'])     ? ', CEP '.c($cfg,'loja_cep')   : '' ?>
                    </span>
                </div>
                <?php endif; ?>
                <?php if (!empty($cfg['loja_telefone'])): ?>
                <div class="dado-linha">
                    <span class="dado-lbl">Telefone</span>
                    <span class="dado-val"><?= c($cfg,'loja_telefone') ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($cfg['loja_whatsapp'])): ?>
                <div class="dado-linha">
                    <span class="dado-lbl">WhatsApp</span>
                    <span class="dado-val">
                        <a href="https://wa.me/<?= c($cfg,'loja_whatsapp') ?>" target="_blank" rel="noopener">
                            <?= c($cfg,'loja_whatsapp') ?>
                        </a>
                    </span>
                </div>
                <?php endif; ?>
                <?php if (!empty($cfg['loja_email'])): ?>
                <div class="dado-linha">
                    <span class="dado-lbl">E-mail</span>
                    <span class="dado-val"><a href="mailto:<?= c($cfg,'loja_email') ?>"><?= c($cfg,'loja_email') ?></a></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- HORÁRIOS -->
        <div class="sec">
            <div class="sec-head">
                <div class="sec-icone"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div class="sec-head-info"><h2>Horários de funcionamento</h2><p>Dias e horas de atendimento</p></div>
            </div>
            <div class="sec-body" style="padding:0 20px">
                <?php foreach ($dias as $sigla => $nome):
                    $aberto = cb($cfg, 'func_'.$sigla, false);
                    $abre   = $cfg['func_'.$sigla.'_abre']  ?? '';
                    $fecha  = $cfg['func_'.$sigla.'_fecha'] ?? '';
                    $ehHoje = ($sigla === $diaAtual);
                ?>
                <div class="dia-linha <?= $ehHoje ? 'hoje' : '' ?>">
                    <span class="dia-nome">
                        <?= $nome ?>
                        <?= $ehHoje ? ' <span style="font-size:.7rem;font-weight:700;color:var(--rosa)">(hoje)</span>' : '' ?>
                    </span>
                    <span class="dia-hora">
                        <?php if ($aberto && $abre && $fecha): ?>
                            <span class="badge-aberto"><?= htmlspecialchars($abre) ?> – <?= htmlspecialchars($fecha) ?></span>
                        <?php else: ?>
                            <span class="badge-fechado">Fechado</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ENTREGA -->
        <?php if (cb($cfg,'entrega_ativa',true) || cb($cfg,'retirada_ativa',true)): ?>
        <div class="sec">
            <div class="sec-head">
                <div class="sec-icone"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                <div class="sec-head-info"><h2>Opções de entrega</h2><p>Como receber seu pedido</p></div>
            </div>
            <div class="sec-body">
                <div class="entrega-grid">
                    <?php if (cb($cfg,'entrega_ativa',true)): ?>
                    <div class="entrega-item">
                        <div style="font-size:1.4rem;margin-bottom:6px">🛵</div>
                        <div class="entrega-item-val">Delivery</div>
                        <div class="entrega-item-lbl">
                            ~<?= (int)($cfg['entrega_tempo'] ?? 40) ?> min •
                            <?php if ($gratis > 0): ?>
                                Grátis acima de R$ <?= number_format($gratis,0,',','.') ?>
                            <?php elseif ($taxa > 0): ?>
                                Taxa R$ <?= number_format($taxa,2,',','.') ?>
                            <?php else: ?>
                                Entrega grátis
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (cb($cfg,'retirada_ativa',true)): ?>
                    <div class="entrega-item">
                        <div style="font-size:1.4rem;margin-bottom:6px">🏠</div>
                        <div class="entrega-item-val">Retirada</div>
                        <div class="entrega-item-lbl">~<?= (int)($cfg['retirada_tempo'] ?? 15) ?> min • Sem taxa</div>
                    </div>
                    <?php endif; ?>
                    <?php if (cb($cfg,'local_ativo',false)): ?>
                    <div class="entrega-item">
                        <div style="font-size:1.4rem;margin-bottom:6px">🍽️</div>
                        <div class="entrega-item-val">Comer aqui</div>
                        <div class="entrega-item-lbl">No local • Sem taxa</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- PAGAMENTOS -->
        <?php $temPagto = cb($cfg,'pag_dinheiro',true) || cb($cfg,'pag_cartao',true) || cb($cfg,'pag_pix',true); ?>
        <?php if ($temPagto): ?>
        <div class="sec">
            <div class="sec-head">
                <div class="sec-icone"><svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
                <div class="sec-head-info"><h2>Formas de pagamento</h2><p>Aceitas na entrega ou retirada</p></div>
            </div>
            <div class="sec-body">
                <div class="pagtos">
                    <?php if (cb($cfg,'pag_pix',true)): ?>
                    <div class="pagto-badge">
                        <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        Pix
                    </div>
                    <?php endif; ?>
                    <?php if (cb($cfg,'pag_cartao',true)): ?>
                    <div class="pagto-badge">
                        <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Cartão (débito/crédito)
                    </div>
                    <?php endif; ?>
                    <?php if (cb($cfg,'pag_dinheiro',true)): ?>
                    <div class="pagto-badge">
                        <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                        Dinheiro
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($cfg['pix_chave'])): ?>
                <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--bg)">
                    <div class="dado-linha">
                        <span class="dado-lbl">Chave Pix</span>
                        <span class="dado-val" style="font-family:monospace;font-size:.85rem">
                            <?= c($cfg,'pix_chave') ?>
                        </span>
                    </div>
                    <?php if (!empty($cfg['pix_nome'])): ?>
                    <div class="dado-linha">
                        <span class="dado-lbl">Favorecido</span>
                        <span class="dado-val"><?= c($cfg,'pix_nome') ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="cta-bottom">
            <h3>Pronto para pedir?</h3>
            <p>Monte seu pedido agora mesmo pelo nosso cardápio.</p>
            <a href="produtos.php" class="btn-wpp" style="background:var(--rosa)">
                <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Ver cardápio
            </a>
            <?php if ($whatsapp): ?>
            &nbsp;
            <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank" rel="noopener" class="btn-wpp">
                <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                WhatsApp
            </a>
            <?php endif; ?>
        </div>

    </div>
</div>

<footer class="footer-mini">
    © <?= date('Y') ?> <?= htmlspecialchars($loja_nome) ?> — Todos os direitos reservados.
    &nbsp;·&nbsp; <a href="termos.php">Termos e Privacidade</a>
</footer>

</body>
</html>