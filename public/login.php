<?php
// public/login.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';

// Se já está logado, redireciona
if (cliente_logado()) {
    redirecionar(BASE_URL . '/public/index.php');
}

$erro     = '';
$sucesso  = '';

// ── PROCESSAR LOGIN ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha e-mail e senha.';
    } elseif (!email_valido($email)) {
        $erro = 'E-mail inválido.';
    } else {

        // ── 1. Verifica se é ADMIN ────────────────────────────
        $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && !empty($admin['senha_hash']) && password_verify($senha, $admin['senha_hash'])) {
            // Login de admin — inicia sessão de admin e vai para o painel
            session_regenerate_id(true);
            $_SESSION['admin']       = true;
            $_SESSION['admin_id']    = $admin['id'];
            $_SESSION['admin_nome']  = $admin['nome'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['login_at']    = time();
            redirecionar(BASE_URL . '/admin/index.php');
        }

        // ── 2. Verifica se é CLIENTE ──────────────────────────
        $stmt = $pdo->prepare("
            SELECT id, nome, email, senha_hash, ativo
            FROM clientes
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $cliente = $stmt->fetch();

        if (!$cliente) {
            $erro = 'E-mail não cadastrado.';
        } elseif (!(bool)$cliente['ativo']) {
            $erro = 'Conta bloqueada. Entre em contato com a loja.';
        } elseif (empty($cliente['senha_hash']) || !password_verify($senha, $cliente['senha_hash'])) {
            $erro = 'Senha incorreta.';
        } else {
            // Login de cliente bem-sucedido
            cliente_login_sessao($cliente);

            $volta = $_SESSION['redirect_apos_login'] ?? (BASE_URL . '/public/index.php');
            unset($_SESSION['redirect_apos_login']);
            redirecionar($volta);
        }
    }
}

// Lê configs da loja para o nome/whatsapp
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave IN ('loja_nome','loja_whatsapp')");
$cfg  = [];
foreach ($stmt->fetchAll() as $r) $cfg[$r['chave']] = $r['valor'];
$loja_nome  = $cfg['loja_nome']    ?? 'Sabor & Cia';
$whatsapp   = $cfg['loja_whatsapp'] ?? '5581987028550';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — <?= htmlspecialchars($loja_nome) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --rosa:        #f43f7a;
            --rosa-light:  #fce7f0;
            --rosa-border: #f0e8ed;
            --dark:        #1a1014;
            --gray:        #9ca3af;
            --white:       #ffffff;
            --bg:          #fafafa;
            --serif:       Georgia, 'Times New Roman', serif;
            --sans:        'DM Sans', system-ui, sans-serif;
            --r:           14px;
        }

        html, body { height: 100%; font-family: var(--sans); color: var(--dark); background: var(--bg); }
        a { text-decoration: none; color: inherit; }

        /* ── LAYOUT SPLIT ─────────────────────── */
        .page { min-height: 100vh; display: flex; }

        /* Lado esquerdo — decorativo */
        .lado-esq {
            flex: 1;
            background: var(--dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px;
            position: relative;
            overflow: hidden;
        }
        .lado-esq::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: var(--rosa);
            opacity: .08;
            top: -100px; right: -100px;
        }
        .lado-esq::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: var(--rosa);
            opacity: .06;
            bottom: -80px; left: -80px;
        }
        .esq-logo {
            font-family: var(--serif);
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 16px;
            position: relative; z-index: 1;
        }
        .esq-logo span { color: var(--rosa); }
        .esq-frase {
            font-size: 1.1rem;
            color: rgba(255,255,255,.55);
            text-align: center;
            line-height: 1.7;
            max-width: 300px;
            position: relative; z-index: 1;
        }
        .esq-frase em {
            font-style: normal;
            color: var(--rosa);
        }

        /* Lado direito — formulário */
        .lado-dir {
            width: 460px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            background: var(--white);
        }

        .form-wrap { width: 100%; max-width: 360px; }

        .form-titulo {
            font-family: var(--serif);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .form-sub {
            font-size: .88rem;
            color: var(--gray);
            margin-bottom: 32px;
        }
        .form-sub a { color: var(--rosa); font-weight: 600; }

        /* Alertas */
        .alerta {
            display: flex; align-items: center; gap: 8px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: .85rem;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .alerta svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; flex-shrink: 0; }
        .alerta-err { background: #fff0f4; border: 1px solid var(--rosa-border); color: #be185d; }
        .alerta-ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }

        /* Campos */
        .campo { margin-bottom: 18px; }
        .campo label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--dark);
        }
        .campo input {
            width: 100%;
            padding: 12px 14px;
            border-radius: var(--r);
            border: 1.5px solid var(--rosa-border);
            background: var(--bg);
            font-family: var(--sans);
            font-size: .9rem;
            color: var(--dark);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .campo input:focus {
            border-color: var(--rosa);
            box-shadow: 0 0 0 3px rgba(244,63,122,.1);
            background: var(--white);
        }

        /* Campo senha com toggle */
        .senha-wrap { position: relative; }
        .senha-wrap input { padding-right: 44px; }
        .senha-toggle {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer; padding: 4px;
            display: flex; align-items: center;
        }
        .senha-toggle svg { width: 16px; height: 16px; stroke: var(--gray); fill: none; stroke-width: 2; }

        /* Esqueci senha */
        .esqueci {
            display: block;
            text-align: right;
            font-size: .78rem;
            color: var(--rosa);
            font-weight: 500;
            margin-top: -10px;
            margin-bottom: 24px;
        }

        /* Botão */
        .btn-login {
            width: 100%;
            padding: 14px;
            border-radius: 50px;
            background: var(--rosa);
            color: #fff;
            border: none;
            font-family: var(--sans);
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-login:hover   { opacity: .88; }
        .btn-login:active  { transform: scale(.98); }
        .btn-login.loading { opacity: .6; pointer-events: none; }
        .btn-login svg { width: 16px; height: 16px; stroke: #fff; fill: none; stroke-width: 2.5; }

        /* Divisor */
        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 24px 0;
            font-size: .78rem;
            color: var(--gray);
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--rosa-border);
        }

        /* Continuar sem conta */
        .btn-sem-conta {
            width: 100%;
            padding: 13px;
            border-radius: 50px;
            background: transparent;
            color: var(--dark);
            border: 1.5px solid var(--rosa-border);
            font-family: var(--sans);
            font-size: .88rem;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            transition: border-color .2s, color .2s;
            text-decoration: none;
            display: block;
        }
        .btn-sem-conta:hover { border-color: var(--rosa); color: var(--rosa); }

        /* Cadastro link */
        .cadastro-link {
            text-align: center;
            font-size: .83rem;
            color: var(--gray);
            margin-top: 28px;
        }
        .cadastro-link a { color: var(--rosa); font-weight: 600; }

        /* Responsivo — esconde lado esquerdo no mobile */
        @media (max-width: 768px) {
            .lado-esq { display: none; }
            .lado-dir { width: 100%; padding: 32px 24px; }
        }
    </style>
</head>
<body>

<div class="page">

    <!-- LADO ESQUERDO — decorativo -->
    <div class="lado-esq">
        <div class="esq-logo">Sabor<span>&</span>Cia</div>
        <p class="esq-frase">
            Açaí, burgers e doces feitos com<br>
            <em>muito amor e sabor.</em><br><br>
            Entre na sua conta e acompanhe<br>
            seus pedidos em tempo real.
        </p>
    </div>

    <!-- LADO DIREITO — formulário -->
    <div class="lado-dir">
        <div class="form-wrap">

            <h1 class="form-titulo">Bem-vindo!</h1>
            <p class="form-sub">
                Não tem conta?
                <a href="cadastro.php">Criar conta grátis</a>
            </p>

            <?php if ($erro): ?>
            <div class="alerta alerta-err">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= htmlspecialchars($erro) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="formLogin" novalidate>

                <div class="campo">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="seu@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                        required>
                </div>

                <div class="campo">
                    <label for="senha">Senha</label>
                    <div class="senha-wrap">
                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required>
                        <button type="button" class="senha-toggle" onclick="toggleSenha()" aria-label="Mostrar senha">
                            <svg id="iconOlho" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Entrar
                </button>

            </form>

            <div class="divider">ou</div>

            <a href="index.php" class="btn-sem-conta">
                Continuar sem conta
            </a>

            <p class="cadastro-link">
                Ainda não tem conta?
                <a href="cadastro.php">Criar conta agora</a>
            </p>

        </div>
    </div>
</div>

<script>
    // Toggle mostrar/ocultar senha
    function toggleSenha() {
        var inp  = document.getElementById('senha');
        var icon = document.getElementById('iconOlho');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
        } else {
            inp.type = 'password';
            icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
        }
    }

    // Spinner no botão ao submeter
    document.getElementById('formLogin').addEventListener('submit', function() {
        var btn = document.getElementById('btnLogin');
        btn.classList.add('loading');
        btn.innerHTML = '<svg viewBox="0 0 24 24" style="animation:spin .8s linear infinite"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" stroke="#fff" fill="none" stroke-width="2" stroke-linecap="round"/></svg> Entrando...';
    });
</script>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

</body>
</html>