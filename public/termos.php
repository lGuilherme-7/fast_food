<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos e Política de Privacidade — Sabor & Cia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root {
            --rosa:#f43f7a; --rosa-claro:#fce7f0; --rosa-borda:#f0e8ed;
            --escuro:#1a1014; --cinza:#9ca3af; --branco:#ffffff; --bg:#fafafa;
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
        .hero { background:var(--escuro); padding:48px 0 40px; text-align:center; }
        .hero-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(244,63,122,.15); border:1px solid rgba(244,63,122,.3); color:var(--rosa); font-size:.72rem; font-weight:600; letter-spacing:1px; text-transform:uppercase; padding:4px 14px; border-radius:50px; margin-bottom:16px; }
        .hero-badge svg { width:12px; height:12px; stroke:var(--rosa); fill:none; stroke-width:2; }
        .hero h1 { font-family:var(--f-titulo); font-size:clamp(1.6rem,4vw,2.2rem); color:#fff; margin-bottom:8px; }
        .hero p   { font-size:.85rem; color:rgba(255,255,255,.5); }

        /* CONTEÚDO */
        .conteudo { padding:40px 0 72px; }

        .atualizado { display:flex; align-items:center; gap:6px; font-size:.78rem; color:var(--cinza); margin-bottom:32px; padding-bottom:16px; border-bottom:1px solid var(--rosa-borda); }
        .atualizado svg { width:13px; height:13px; stroke:var(--cinza); fill:none; stroke-width:2; }

        /* Índice */
        .indice { background:var(--branco); border:1px solid var(--rosa-borda); border-radius:var(--r); padding:20px 22px; margin-bottom:36px; }
        .indice-titulo { font-size:.72rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--cinza); margin-bottom:12px; }
        .indice ol { list-style:none; display:flex; flex-direction:column; gap:6px; counter-reset:indice; }
        .indice ol li { counter-increment:indice; display:flex; align-items:center; gap:8px; font-size:.84rem; }
        .indice ol li::before { content:counter(indice); width:20px; height:20px; border-radius:50%; background:var(--rosa-claro); color:var(--rosa); font-size:.7rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .indice ol li a { color:var(--escuro); transition:color .2s; }
        .indice ol li a:hover { color:var(--rosa); text-decoration:none; }

        /* Seções */
        .sec { margin-bottom:36px; }
        .sec-num { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; background:var(--rosa); color:#fff; font-size:.78rem; font-weight:700; flex-shrink:0; margin-right:10px; }
        .sec h2 { font-family:var(--f-titulo); font-size:1.1rem; font-weight:700; margin-bottom:14px; display:flex; align-items:center; padding-bottom:10px; border-bottom:1px solid var(--rosa-borda); }
        .sec p  { font-size:.88rem; color:#4b4b4b; line-height:1.8; margin-bottom:10px; }
        .sec p:last-child { margin-bottom:0; }
        .sec ul { list-style:none; display:flex; flex-direction:column; gap:8px; margin:10px 0; }
        .sec ul li { font-size:.88rem; color:#4b4b4b; line-height:1.7; display:flex; align-items:flex-start; gap:8px; }
        .sec ul li::before { content:''; width:6px; height:6px; border-radius:50%; background:var(--rosa); flex-shrink:0; margin-top:7px; }

        /* Destaque */
        .destaque { background:var(--rosa-claro); border-left:3px solid var(--rosa); border-radius:0 var(--r) var(--r) 0; padding:12px 16px; margin:12px 0; font-size:.85rem; color:var(--escuro); line-height:1.7; }

        /* Contato */
        .contato-box { background:var(--branco); border:1px solid var(--rosa-borda); border-radius:var(--r); padding:20px 22px; display:flex; align-items:center; gap:16px; margin-top:8px; }
        .contato-icone { width:40px; height:40px; border-radius:50%; background:var(--rosa-claro); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .contato-icone svg { width:18px; height:18px; stroke:var(--rosa); fill:none; stroke-width:2; }
        .contato-info { flex:1; }
        .contato-info strong { font-size:.88rem; display:block; margin-bottom:2px; }
        .contato-info span   { font-size:.82rem; color:var(--cinza); }

        /* MODAL DE ACEITE */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; display:flex; align-items:flex-end; justify-content:center; padding:0; }
        .modal-box { background:var(--branco); width:100%; max-width:520px; border-radius:20px 20px 0 0; padding:28px 24px 32px; }
        .modal-logo { font-family:var(--f-titulo); font-size:1.1rem; font-weight:700; margin-bottom:4px; }
        .modal-logo span { color:var(--rosa); }
        .modal-titulo { font-family:var(--f-titulo); font-size:1.15rem; font-weight:700; margin-bottom:8px; }
        .modal-texto  { font-size:.84rem; color:var(--cinza); line-height:1.7; margin-bottom:18px; }
        .modal-texto a { color:var(--rosa); font-weight:600; }
        .modal-check  { display:flex; align-items:flex-start; gap:10px; margin-bottom:20px; cursor:pointer; }
        .modal-check input { accent-color:var(--rosa); width:17px; height:17px; flex-shrink:0; margin-top:2px; cursor:pointer; }
        .modal-check span { font-size:.84rem; color:var(--escuro); line-height:1.6; }
        .modal-check span a { color:var(--rosa); font-weight:600; }
        .modal-btns { display:flex; flex-direction:column; gap:10px; }
        .btn-aceitar { width:100%; padding:14px; border-radius:var(--r); background:var(--rosa); color:#fff; border:none; font-family:var(--f-corpo); font-size:.95rem; font-weight:600; cursor:pointer; transition:opacity .2s; }
        .btn-aceitar:hover   { opacity:.88; }
        .btn-aceitar:disabled { opacity:.45; cursor:not-allowed; }
        .btn-recusar { width:100%; padding:11px; border-radius:var(--r); border:1px solid var(--rosa-borda); background:var(--branco); color:var(--cinza); font-family:var(--f-corpo); font-size:.88rem; font-weight:500; cursor:pointer; transition:all .2s; }
        .btn-recusar:hover { border-color:var(--rosa); color:var(--rosa); }

        /* Toast */
        .toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(80px); background:var(--escuro); color:#fff; padding:11px 22px; border-radius:50px; font-size:.85rem; font-weight:500; z-index:2000; opacity:0; transition:all .3s; white-space:nowrap; pointer-events:none; }
        .toast.show { transform:translateX(-50%) translateY(0); opacity:1; }

        .footer-mini { text-align:center; padding:24px 0; font-size:.75rem; color:var(--cinza); border-top:1px solid var(--rosa-borda); }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-inner">
        <a href="index.php" class="nav-logo">Sabor<span>&</span>Cia</a>
        <a href="index.php" class="nav-back">
            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            Voltar ao site
        </a>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="container">
        <div class="hero-badge">
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Documento legal
        </div>
        <h1>Termos e Política de Privacidade</h1>
        <p>Última atualização: <?= date('d/m/Y') ?></p>
    </div>
</div>

<!-- CONTEÚDO -->
<div class="conteudo">
    <div class="container">

        <div class="atualizado">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Atualizado em <?= date('d \d\e F \d\e Y', mktime(0,0,0,date('m'),date('d'),date('Y'))) ?>
        </div>

        <!-- ÍNDICE -->
        <div class="indice">
            <div class="indice-titulo">Neste documento</div>
            <ol>
                <li><a href="#s1">Sobre estes termos</a></li>
                <li><a href="#s2">Como funciona o pedido</a></li>
                <li><a href="#s3">Dados que coletamos</a></li>
                <li><a href="#s4">Como usamos seus dados</a></li>
                <li><a href="#s5">Compartilhamento de dados</a></li>
                <li><a href="#s6">Seus direitos</a></li>
                <li><a href="#s7">Segurança</a></li>
                <li><a href="#s8">Contato</a></li>
            </ol>
        </div>

        <!-- S1 -->
        <div class="sec" id="s1">
            <h2><span class="sec-num">1</span>Sobre estes termos</h2>
            <p>Ao usar o site da <strong>Sabor & Cia</strong> para realizar pedidos, você concorda com as condições descritas neste documento. Eles se aplicam a todos os clientes que realizam pedidos pelo nosso site ou WhatsApp.</p>
            <p>Se tiver dúvidas, entre em contato antes de finalizar seu pedido.</p>
        </div>

        <!-- S2 -->
        <div class="sec" id="s2">
            <h2><span class="sec-num">2</span>Como funciona o pedido</h2>
            <p>Nosso sistema funciona da seguinte forma:</p>
            <ul>
                <li>Você monta seu pedido pelo site e escolhe a forma de entrega (delivery, retirada ou consumo no local).</li>
                <li>O pedido é confirmado pelo WhatsApp, onde um atendente vai validar disponibilidade e prazo.</li>
                <li>O pagamento é combinado no momento da entrega ou retirada — aceitamos dinheiro, cartão e Pix.</li>
                <li>Pedidos podem ser cancelados antes do início do preparo, sem custo.</li>
            </ul>
            <div class="destaque">
                Prazos de entrega são estimados e podem variar conforme demanda e distância. Informamos qualquer atraso pelo WhatsApp.
            </div>
        </div>

        <!-- S3 -->
        <div class="sec" id="s3">
            <h2><span class="sec-num">3</span>Dados que coletamos</h2>
            <p>Para realizar seu pedido, coletamos apenas o necessário:</p>
            <ul>
                <li><strong>Nome</strong> — para identificar o pedido.</li>
                <li><strong>WhatsApp / telefone</strong> — para confirmar e acompanhar o pedido.</li>
                <li><strong>Endereço de entrega</strong> — apenas quando a opção de delivery é selecionada.</li>
                <li><strong>E-mail</strong> — somente se você criar uma conta no site (opcional).</li>
            </ul>
            <p>Não coletamos dados de cartão de crédito. Pagamentos em cartão são processados na máquina física na entrega.</p>
        </div>

        <!-- S4 -->
        <div class="sec" id="s4">
            <h2><span class="sec-num">4</span>Como usamos seus dados</h2>
            <p>Seus dados são usados exclusivamente para:</p>
            <ul>
                <li>Processar e entregar seu pedido.</li>
                <li>Entrar em contato sobre o status do pedido via WhatsApp.</li>
                <li>Melhorar nossos produtos e atendimento com base no histórico de pedidos.</li>
            </ul>
            <div class="destaque">
                Não enviamos spam, não vendemos dados e não usamos suas informações para fins publicitários sem sua autorização.
            </div>
        </div>

        <!-- S5 -->
        <div class="sec" id="s5">
            <h2><span class="sec-num">5</span>Compartilhamento de dados</h2>
            <p>Seus dados <strong>não são vendidos ou repassados a terceiros</strong>. O único compartilhamento que pode ocorrer é:</p>
            <ul>
                <li>Com o entregador parceiro — apenas nome e endereço, para fins de entrega.</li>
                <li>Por obrigação legal, caso exigido por autoridade competente.</li>
            </ul>
        </div>

        <!-- S6 -->
        <div class="sec" id="s6">
            <h2><span class="sec-num">6</span>Seus direitos</h2>
            <p>De acordo com a Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018), você tem direito a:</p>
            <ul>
                <li>Saber quais dados temos sobre você.</li>
                <li>Solicitar a correção de dados incorretos.</li>
                <li>Pedir a exclusão dos seus dados a qualquer momento.</li>
                <li>Revogar o consentimento para uso dos seus dados.</li>
            </ul>
            <p>Para exercer qualquer desses direitos, basta entrar em contato pelo WhatsApp ou e-mail.</p>
        </div>

        <!-- S7 -->
        <div class="sec" id="s7">
            <h2><span class="sec-num">7</span>Segurança</h2>
            <p>Adotamos medidas básicas de segurança para proteger suas informações, incluindo senhas criptografadas para contas de clientes e acesso restrito ao painel administrativo.</p>
            <p>Como qualquer sistema online, não podemos garantir segurança absoluta, mas nos comprometemos a agir com responsabilidade em caso de qualquer incidente.</p>
        </div>

        <!-- S8 -->
        <div class="sec" id="s8">
            <h2><span class="sec-num">8</span>Contato</h2>
            <p>Se tiver dúvidas sobre estes termos ou quiser exercer seus direitos de privacidade, fale conosco:</p>
            <div class="contato-box">
                <div class="contato-icone">
                    <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                </div>
                <div class="contato-info">
                    <strong>WhatsApp — atendimento direto</strong>
                    <span>Resposta em até 24 horas em dias úteis</span>
                </div>
            </div>
        </div>

    </div>
</div>

<footer class="footer-mini">
    © <?= date('Y') ?> Sabor&amp;Cia — Todos os direitos reservados.
</footer>

<!-- MODAL DE ACEITE (aparece se vier com ?aceite=1 na URL) -->
<?php if (isset($_GET['aceite'])): ?>
<div class="modal-overlay" id="modalAceite">
    <div class="modal-box">
        <div class="modal-logo">Sabor<span>&</span>Cia</div>
        <div class="modal-titulo">Antes de continuar</div>
        <div class="modal-texto">
            Para criar sua conta e realizar pedidos, precisamos que você leia e concorde com nossos termos de uso e política de privacidade.
        </div>
        <label class="modal-check">
            <input type="checkbox" id="cbAceite" onchange="toggleBtnAceitar()">
            <span>Li e concordo com os <a href="termos.php" target="_blank">Termos de Uso e Política de Privacidade</a> da Sabor & Cia.</span>
        </label>
        <div class="modal-btns">
            <button class="btn-aceitar" id="btnAceitar" disabled onclick="aceitar()">
                Concordar e continuar
            </button>
            <button class="btn-recusar" onclick="recusar()">Não concordar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="toast" id="toast"></div>

<script>
function toggleBtnAceitar(){
    document.getElementById('btnAceitar').disabled = !document.getElementById('cbAceite').checked;
}
function aceitar(){
    document.getElementById('modalAceite').style.display = 'none';
    showToast('Termos aceitos! Redirecionando...');
    setTimeout(function(){
        var volta = new URLSearchParams(window.location.search).get('volta') || 'cadastro.php';
        window.location.href = volta + '?termos=aceito';
    }, 1200);
}
function recusar(){
    window.location.href = 'index.php';
}
var toastTimer;
function showToast(msg){
    var el = document.getElementById('toast');
    el.textContent = msg; el.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ el.classList.remove('show'); }, 2500);
}
</script>
</body>
</html>