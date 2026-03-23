<?php
// admin/login.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Já logado → painel
if (!empty($_SESSION['admin_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}

$erro        = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $email_value = $email;

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash FROM admins WHERE email = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($senha, $admin['senha_hash'])) {
            $erro = 'E-mail ou senha incorretos.';
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_id']    = $admin['id'];
            $_SESSION['admin_nome']  = $admin['nome'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin']       = true;
            $_SESSION['login_at']    = time();
            header('Location: pages/dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sabor&Cia Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --rosa:#f43f7a; --rosa-claro:#fce7f0; --rosa-borda:#f0e8ed;
            --escuro:#1a1014; --cinza:#9ca3af; --branco:#ffffff;
            --bg:#fafafa; --borda:#e5e7eb; --r:12px;
            --f-titulo:Georgia,'Times New Roman',serif;
            --f-corpo:'DM Sans',system-ui,sans-serif;
        }
        html, body { height:100%; font-family:var(--f-corpo); color:var(--escuro); background:var(--bg); }
        a { text-decoration:none; color:inherit; }

        .page { display:grid; grid-template-columns:1fr 480px; min-height:100vh; }

        /* LADO ESQUERDO */
        .lado-esq { background:var(--escuro); position:relative; overflow:hidden; display:flex; flex-direction:column; justify-content:space-between; padding:40px 48px; }
        .lado-esq::before { content:''; position:absolute; width:600px; height:600px; border-radius:50%; background:radial-gradient(circle,rgba(244,63,122,.18) 0%,transparent 70%); top:-150px; right:-150px; pointer-events:none; }
        .lado-esq::after  { content:''; position:absolute; width:400px; height:400px; border-radius:50%; background:radial-gradient(circle,rgba(244,63,122,.1) 0%,transparent 70%); bottom:-100px; left:-100px; pointer-events:none; }

        .branding { position:relative; z-index:1; }
        .brand-logo { font-family:var(--f-titulo); font-size:1.5rem; font-weight:700; color:#fff; margin-bottom:6px; }
        .brand-logo span { color:var(--rosa); }
        .brand-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(244,63,122,.15); border:1px solid rgba(244,63,122,.3); color:var(--rosa); font-size:.72rem; font-weight:600; letter-spacing:1px; text-transform:uppercase; padding:4px 12px; border-radius:50px; }
        .brand-badge svg { width:11px; height:11px; fill:var(--rosa); }

        .brand-content { position:relative; z-index:1; }
        .brand-content h2 { font-family:var(--f-titulo); font-size:clamp(1.8rem,3vw,2.6rem); color:#fff; line-height:1.2; margin-bottom:16px; }
        .brand-content h2 em { font-style:normal; color:var(--rosa); }
        .brand-content p { color:rgba(255,255,255,.5); font-size:.9rem; line-height:1.7; max-width:380px; }

        .brand-footer { position:relative; z-index:1; font-size:.78rem; color:rgba(255,255,255,.25); }
        .brand-footer a { color:rgba(255,255,255,.4); transition:color .2s; }
        .brand-footer a:hover { color:var(--rosa); }

        /* LADO DIREITO */
        .lado-dir { background:var(--branco); display:flex; align-items:center; justify-content:center; padding:40px 48px; border-left:1px solid var(--borda); }
        .form-box { width:100%; max-width:360px; }

        .form-header { margin-bottom:32px; }
        .form-header h1 { font-family:var(--f-titulo); font-size:1.9rem; font-weight:700; color:var(--escuro); margin-bottom:6px; }
        .form-header p  { font-size:.88rem; color:var(--cinza); }
        .form-header p strong { color:var(--escuro); }

        .campo { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
        .campo label { font-size:.82rem; font-weight:600; color:var(--escuro); }
        .campo-input-wrap { position:relative; }
        .campo-input-wrap svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); width:15px; height:15px; stroke:var(--cinza); fill:none; stroke-width:2; pointer-events:none; }
        .campo input { width:100%; padding:12px 13px 12px 40px; border-radius:var(--r); border:1px solid var(--borda); background:var(--bg); font-family:var(--f-corpo); font-size:.9rem; color:var(--escuro); outline:none; transition:border-color .2s,box-shadow .2s,background .2s; }
        .campo input:focus { border-color:var(--rosa); box-shadow:0 0 0 3px rgba(244,63,122,.1); background:var(--branco); }
        .campo input::placeholder { color:var(--cinza); }
        .campo input.erro { border-color:#fca5a5; background:#fff5f5; }

        .senha-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--cinza); transition:color .2s; padding:2px; }
        .senha-toggle:hover { color:var(--rosa); }
        .senha-toggle svg { width:16px; height:16px; stroke:currentColor; fill:none; stroke-width:2; display:block; }

        .form-opcoes { display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; }
        .lembrar { display:flex; align-items:center; gap:8px; font-size:.82rem; color:var(--cinza); cursor:pointer; }
        .lembrar input[type="checkbox"] { width:15px; height:15px; accent-color:var(--rosa); cursor:pointer; }
        .esqueci { font-size:.82rem; color:var(--cinza); transition:color .2s; }
        .esqueci:hover { color:var(--rosa); }

        .alerta-erro { display:flex; align-items:center; gap:8px; background:#fff0f4; border:1px solid var(--rosa-borda); color:#be185d; border-radius:var(--r); padding:11px 14px; font-size:.85rem; font-weight:500; margin-bottom:18px; }
        .alerta-erro svg { width:15px; height:15px; stroke:currentColor; fill:none; stroke-width:2; flex-shrink:0; }

        .btn-login { width:100%; padding:13px; border-radius:var(--r); background:var(--rosa); color:#fff; border:none; font-family:var(--f-corpo); font-size:.95rem; font-weight:600; cursor:pointer; transition:opacity .2s,transform .15s; display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:20px; }
        .btn-login:hover  { opacity:.88; }
        .btn-login:active { transform:scale(.98); }
        .btn-login:disabled { opacity:.6; cursor:not-allowed; }
        .btn-login svg { width:16px; height:16px; stroke:currentColor; fill:none; stroke-width:2.5; }

        .spinner { width:16px; height:16px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; display:none; }
        @keyframes spin { to { transform:rotate(360deg); } }

        .voltar-site { display:flex; align-items:center; gap:6px; font-size:.8rem; color:var(--cinza); margin-bottom:32px; transition:color .2s; }
        .voltar-site:hover { color:var(--rosa); }
        .voltar-site svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; }

        @media (max-width:860px) {
            .page     { grid-template-columns:1fr; }
            .lado-esq { display:none; }
            .lado-dir { border-left:none; padding:32px 24px; align-items:flex-start; padding-top:60px; }
            .form-box { max-width:100%; }
        }
    </style>
</head>
<body>
<div class="page">

    <!-- LADO ESQUERDO -->
    <div class="lado-esq">
        <div class="branding">
            <div class="brand-logo">Sabor<span>&</span>Cia</div>
            <div class="brand-badge">
                <svg viewBox="0 0 24 24"><path d="M12 2L15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2z"/></svg>
                Painel administrativo
            </div>
        </div>
        <div class="brand-content">
            <h2>Gerencie seu delivery com <em>total controle.</em></h2>
            <p>Pedidos em tempo real, estoque, clientes, cupons e muito mais — tudo num único lugar, simples e rápido.</p>
        </div>
        <div class="brand-footer">
            &copy; <?= date('Y') ?> Sabor&amp;Cia &mdash;
            <a href="../public/index.php">Ver site público</a>
        </div>
    </div>

    <!-- LADO DIREITO -->
    <div class="lado-dir">
        <div class="form-box">

            <a href="../public/index.php" class="voltar-site">
                <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                Voltar ao site
            </a>

            <div class="form-header">
                <h1>Bem-vindo de volta</h1>
                <p>Entre com suas credenciais de <strong>administrador</strong>.</p>
            </div>

            <?php if ($erro): ?>
            <div class="alerta-erro">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= htmlspecialchars($erro) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="formLogin" novalidate>

                <div class="campo">
                    <label for="email">E-mail</label>
                    <div class="campo-input-wrap">
                        <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <input type="email" id="email" name="email"
                            placeholder="seu@email.com"
                            value="<?= htmlspecialchars($email_value) ?>"
                            autocomplete="email" required
                            <?= $erro ? 'class="erro"' : '' ?>>
                    </div>
                </div>

                <div class="campo">
                    <label for="senha">Senha</label>
                    <div class="campo-input-wrap">
                        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="senha" name="senha"
                            placeholder="••••••••"
                            autocomplete="current-password" required
                            <?= $erro ? 'class="erro"' : '' ?>>
                        <button type="button" class="senha-toggle" id="toggleSenha" aria-label="Mostrar senha">
                            <svg id="iconOlho" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="form-opcoes">
                    <label class="lembrar">
                        <input type="checkbox" name="lembrar">
                        Lembrar acesso
                    </label>
                    <a href="#" class="esqueci">Esqueceu a senha?</a>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    <span id="btnTxt">Entrar no painel</span>
                    <div class="spinner" id="spinner"></div>
                </button>

            </form>

        </div>
    </div>
</div>

<script>
document.getElementById('toggleSenha').addEventListener('click', function(){
    var inp  = document.getElementById('senha');
    var icon = document.getElementById('iconOlho');
    if (inp.type === 'password'){
        inp.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        inp.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
});

document.getElementById('formLogin').addEventListener('submit', function(){
    var btn     = document.getElementById('btnLogin');
    var txt     = document.getElementById('btnTxt');
    var spinner = document.getElementById('spinner');
    btn.disabled          = true;
    txt.textContent       = 'Entrando...';
    spinner.style.display = 'block';
});

window.addEventListener('load', function(){
    var email = document.getElementById('email');
    var senha = document.getElementById('senha');
    if (!email.value) email.focus();
    else              senha.focus();
});
</script>
</body>
</html>