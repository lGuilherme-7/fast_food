<?php
// public/minha-conta.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';

exigir_login_cliente();

$cliente_id = $_SESSION['cliente_id'];
$mensagem   = '';
$erro       = '';

// Busca dados atuais
$stmt = $pdo->prepare("SELECT id, nome, email, telefone FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();

// ── SALVAR DADOS ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

    if ($_POST['acao'] === 'dados') {
        $nome = trim($_POST['nome'] ?? '');
        $tel  = preg_replace('/\D/', '', $_POST['tel'] ?? '');

        if (empty($nome)) {
            $erro = 'O nome não pode ficar em branco.';
        } else {
            $pdo->prepare("UPDATE clientes SET nome = ?, telefone = ? WHERE id = ?")->execute([$nome, $tel, $cliente_id]);
            $_SESSION['cliente_nome'] = $nome;
            $cliente['nome']          = $nome;
            $cliente['telefone']      = $tel;
            $mensagem = 'Dados atualizados com sucesso!';
        }
    }

    if ($_POST['acao'] === 'senha') {
        $atual = $_POST['senha_atual'] ?? '';
        $nova  = $_POST['senha_nova']  ?? '';
        $conf  = $_POST['senha_conf']  ?? '';

        $stmt = $pdo->prepare("SELECT senha_hash FROM clientes WHERE id = ?");
        $stmt->execute([$cliente_id]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($atual, $hash)) {
            $erro = 'Senha atual incorreta.';
        } elseif (strlen($nova) < 6) {
            $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
        } elseif ($nova !== $conf) {
            $erro = 'As senhas não coincidem.';
        } else {
            $pdo->prepare("UPDATE clientes SET senha_hash = ? WHERE id = ?")->execute([password_hash($nova, PASSWORD_BCRYPT), $cliente_id]);
            $mensagem = 'Senha alterada com sucesso!';
        }
    }
}

// Stats do cliente
$stats = $pdo->prepare("
    SELECT COUNT(*) AS total_pedidos,
           COALESCE(SUM(CASE WHEN status != 'cancelado' THEN total END), 0) AS total_gasto
    FROM pedidos WHERE cliente_id = ?
");
$stats->execute([$cliente_id]);
$stats = $stats->fetch();

$loja_nome = $pdo->query("SELECT valor FROM configuracoes WHERE chave='loja_nome'")->fetchColumn() ?: 'Sabor & Cia';
$inicial   = mb_strtoupper(mb_substr($cliente['nome'], 0, 1));
$primeiro  = explode(' ', $cliente['nome'])[0];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha conta — <?= h($loja_nome) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root { --rosa:#f43f7a; --rosa-light:#fce7f0; --rosa-border:#f0e8ed; --dark:#1a1014; --gray:#9ca3af; --white:#fff; --bg:#fafafa; --serif:Georgia,'Times New Roman',serif; --sans:'DM Sans',system-ui,sans-serif; --r:14px; }
        body  { font-family:var(--sans); color:var(--dark); background:var(--bg); min-height:100vh; }
        a     { text-decoration:none; color:inherit; }

        .navbar { position:fixed; top:0; left:0; right:0; z-index:900; background:rgba(255,255,255,.95); backdrop-filter:blur(14px); border-bottom:1px solid var(--rosa-border); }
        .nav-inner { display:flex; align-items:center; justify-content:space-between; height:60px; max-width:900px; margin:0 auto; padding:0 18px; }
        .nav-logo { font-family:var(--serif); font-size:1.3rem; font-weight:700; }
        .nav-logo span { color:var(--rosa); }
        .nav-back { display:flex; align-items:center; gap:6px; font-size:.85rem; color:var(--gray); transition:color .2s; }
        .nav-back:hover { color:var(--rosa); }
        .nav-back svg { width:15px; height:15px; stroke:currentColor; fill:none; stroke-width:2; }
        .nav-sair { display:flex; align-items:center; gap:6px; font-size:.82rem; color:var(--gray); transition:color .2s; }
        .nav-sair:hover { color:var(--rosa); }
        .nav-sair svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; }

        .page { max-width:900px; margin:0 auto; padding:80px 18px 48px; }

        /* Perfil topo */
        .perfil-topo { display:flex; align-items:center; gap:20px; background:var(--dark); border-radius:16px; padding:28px 28px; margin-bottom:28px; }
        .perfil-avatar { width:64px; height:64px; border-radius:50%; background:var(--rosa); color:#fff; font-family:var(--serif); font-size:1.6rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .perfil-info h1 { font-family:var(--serif); font-size:1.3rem; font-weight:700; color:#fff; }
        .perfil-info p  { font-size:.82rem; color:rgba(255,255,255,.45); margin-top:3px; }
        .perfil-stats { display:flex; gap:24px; margin-left:auto; }
        .p-stat .val { font-family:var(--serif); font-size:1.4rem; font-weight:700; color:var(--rosa); }
        .p-stat .lbl { font-size:.72rem; color:rgba(255,255,255,.4); margin-top:2px; }

        /* Alertas */
        .alerta { display:flex; align-items:center; gap:8px; padding:12px 16px; border-radius:10px; font-size:.85rem; font-weight:500; margin-bottom:20px; }
        .alerta svg { width:15px; height:15px; stroke:currentColor; fill:none; stroke-width:2; flex-shrink:0; }
        .alerta-ok  { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }
        .alerta-err { background:#fff0f4; border:1px solid var(--rosa-border); color:#be185d; }

        /* Cards de seção */
        .sec-card { background:var(--white); border:1px solid var(--rosa-border); border-radius:var(--r); overflow:hidden; margin-bottom:20px; }
        .sec-head  { display:flex; align-items:center; gap:12px; padding:16px 20px; background:var(--bg); border-bottom:1px solid var(--rosa-border); }
        .sec-head-ic { width:34px; height:34px; border-radius:9px; background:var(--rosa-light); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .sec-head-ic svg { width:15px; height:15px; stroke:var(--rosa); fill:none; stroke-width:2; }
        .sec-head h2 { font-family:var(--serif); font-size:.95rem; font-weight:700; }
        .sec-head p  { font-size:.76rem; color:var(--gray); margin-top:1px; }
        .sec-body { padding:20px; }

        /* Campos */
        .campos-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
        .campo { margin-bottom:14px; }
        .campo:last-child { margin-bottom:0; }
        .campo label { display:block; font-size:.82rem; font-weight:600; margin-bottom:6px; }
        .campo input { width:100%; padding:11px 13px; border-radius:var(--r); border:1.5px solid var(--rosa-border); background:var(--bg); font-family:var(--sans); font-size:.9rem; color:var(--dark); outline:none; transition:border-color .2s, box-shadow .2s; }
        .campo input:focus { border-color:var(--rosa); box-shadow:0 0 0 3px rgba(244,63,122,.1); background:var(--white); }
        .campo input[readonly] { opacity:.6; cursor:not-allowed; }
        .campo-hint { font-size:.74rem; color:var(--gray); margin-top:4px; display:block; }

        /* Senha toggle */
        .senha-wrap { position:relative; }
        .senha-wrap input { padding-right:44px; }
        .senha-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; padding:4px; display:flex; align-items:center; }
        .senha-toggle svg { width:15px; height:15px; stroke:var(--gray); fill:none; stroke-width:2; }

        /* Botão salvar */
        .btn-salvar { display:inline-flex; align-items:center; gap:7px; padding:11px 24px; border-radius:50px; background:var(--rosa); color:#fff; border:none; font-family:var(--sans); font-size:.88rem; font-weight:600; cursor:pointer; transition:opacity .2s; }
        .btn-salvar:hover { opacity:.88; }
        .btn-salvar svg { width:14px; height:14px; stroke:#fff; fill:none; stroke-width:2.5; }

        /* Links rápidos */
        .links-rapidos { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px; }
        .link-rapido { background:var(--white); border:1px solid var(--rosa-border); border-radius:var(--r); padding:18px 20px; display:flex; align-items:center; gap:14px; transition:border-color .2s, box-shadow .2s; }
        .link-rapido:hover { border-color:var(--rosa); box-shadow:0 4px 14px rgba(244,63,122,.1); }
        .link-ic { width:40px; height:40px; border-radius:10px; background:var(--rosa-light); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .link-ic svg { width:17px; height:17px; stroke:var(--rosa); fill:none; stroke-width:2; }
        .link-txt strong { display:block; font-size:.88rem; font-weight:600; }
        .link-txt span   { font-size:.76rem; color:var(--gray); }

        @media (max-width:600px) {
            .perfil-topo { flex-direction:column; align-items:flex-start; gap:14px; }
            .perfil-stats { margin-left:0; }
            .campos-2 { grid-template-columns:1fr; }
            .links-rapidos { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-inner">
        <a href="index.php" class="nav-back">
            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            <span class="nav-logo">Sabor<span>&</span>Cia</span>
        </a>
        <a href="logout.php" class="nav-sair">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sair
        </a>
    </div>
</nav>

<div class="page">

    <!-- PERFIL TOPO -->
    <div class="perfil-topo">
        <div class="perfil-avatar"><?= $inicial ?></div>
        <div class="perfil-info">
            <h1>Olá, <?= h($primeiro) ?>!</h1>
            <p><?= h($cliente['email']) ?></p>
        </div>
        <div class="perfil-stats">
            <div class="p-stat">
                <div class="val"><?= (int)$stats['total_pedidos'] ?></div>
                <div class="lbl">Pedidos</div>
            </div>
            <div class="p-stat">
                <div class="val">R$ <?= number_format($stats['total_gasto'], 0, ',', '.') ?></div>
                <div class="lbl">Total gasto</div>
            </div>
        </div>
    </div>

    <?php if ($mensagem): ?>
    <div class="alerta alerta-ok"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><?= h($mensagem) ?></div>
    <?php endif; ?>
    <?php if ($erro): ?>
    <div class="alerta alerta-err"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?= h($erro) ?></div>
    <?php endif; ?>

    <!-- LINKS RÁPIDOS -->
    <div class="links-rapidos">
        <a href="meus-pedidos.php" class="link-rapido">
            <div class="link-ic"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg></div>
            <div class="link-txt"><strong>Meus pedidos</strong><span>Ver histórico completo</span></div>
        </a>
        <a href="produtos.php" class="link-rapido">
            <div class="link-ic"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></div>
            <div class="link-txt"><strong>Fazer pedido</strong><span>Ver cardápio completo</span></div>
        </a>
    </div>

    <!-- DADOS PESSOAIS -->
    <div class="sec-card">
        <div class="sec-head">
            <div class="sec-head-ic"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div><h2>Dados pessoais</h2><p>Nome e telefone para contato</p></div>
        </div>
        <div class="sec-body">
            <form method="POST">
                <input type="hidden" name="acao" value="dados">
                <div class="campos-2">
                    <div class="campo">
                        <label>Nome completo</label>
                        <input type="text" name="nome" value="<?= h($cliente['nome']) ?>" required>
                    </div>
                    <div class="campo">
                        <label>E-mail</label>
                        <input type="email" value="<?= h($cliente['email']) ?>" readonly>
                        <span class="campo-hint">O e-mail não pode ser alterado.</span>
                    </div>
                </div>
                <div class="campo">
                    <label>WhatsApp</label>
                    <input type="tel" name="tel" value="<?= h($cliente['telefone'] ?? '') ?>" placeholder="(81) 99999-0000" oninput="mascaraTel(this)">
                </div>
                <button type="submit" class="btn-salvar">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    Salvar dados
                </button>
            </form>
        </div>
    </div>

    <!-- ALTERAR SENHA -->
    <div class="sec-card">
        <div class="sec-head">
            <div class="sec-head-ic"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
            <div><h2>Alterar senha</h2><p>Mínimo 6 caracteres</p></div>
        </div>
        <div class="sec-body">
            <form method="POST">
                <input type="hidden" name="acao" value="senha">
                <div class="campos-2">
                    <div class="campo">
                        <label>Senha atual</label>
                        <div class="senha-wrap">
                            <input type="password" name="senha_atual" id="s1" placeholder="••••••••">
                            <button type="button" class="senha-toggle" onclick="ts('s1')"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        </div>
                    </div>
                    <div class="campo"></div>
                </div>
                <div class="campos-2">
                    <div class="campo">
                        <label>Nova senha</label>
                        <div class="senha-wrap">
                            <input type="password" name="senha_nova" id="s2" placeholder="••••••••">
                            <button type="button" class="senha-toggle" onclick="ts('s2')"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        </div>
                    </div>
                    <div class="campo">
                        <label>Confirmar nova senha</label>
                        <div class="senha-wrap">
                            <input type="password" name="senha_conf" id="s3" placeholder="••••••••">
                            <button type="button" class="senha-toggle" onclick="ts('s3')"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-salvar">
                    <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Alterar senha
                </button>
            </form>
        </div>
    </div>

</div>

<script>
function ts(id){ var i=document.getElementById(id); i.type=i.type==='password'?'text':'password'; }
function mascaraTel(inp){
    var v=inp.value.replace(/\D/g,'').substring(0,11);
    if(v.length>6) v='('+v.substring(0,2)+') '+v.substring(2,7)+'-'+v.substring(7);
    else if(v.length>2) v='('+v.substring(0,2)+') '+v.substring(2);
    else if(v.length>0) v='('+v;
    inp.value=v;
}
</script>
</body>
</html>