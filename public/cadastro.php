<?php
// public/cadastro.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';

// Se já está logado, redireciona
if (cliente_logado()) {
    redirecionar(BASE_URL . '/public/index.php');
}

$erro    = '';
$sucesso = '';
$dados   = []; // mantém campos preenchidos em caso de erro

// ── PROCESSAR CADASTRO ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome   = trim($_POST['nome']   ?? '');
    $email  = trim($_POST['email']  ?? '');
    $tel    = trim($_POST['tel']    ?? '');
    $senha  = $_POST['senha']       ?? '';
    $conf   = $_POST['senha_conf']  ?? '';

    // Mantém os dados para repreencher o form
    $dados = compact('nome', 'email', 'tel');

    // Validações
    if (empty($nome)) {
        $erro = 'Preencha seu nome completo.';
    } elseif (empty($email) || !email_valido($email)) {
        $erro = 'Digite um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas não coincidem.';
    } else {
        // Verifica se e-mail já existe
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = 'Este e-mail já está cadastrado. <a href="login.php">Fazer login</a>';
        } else {
            // Limpa telefone (só dígitos)
            $tel_limpo = preg_replace('/\D/', '', $tel);

            // Insere o cliente
            $hash = password_hash($senha, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO clientes (nome, email, telefone, senha_hash, ativo, criado_em)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$nome, $email, $tel_limpo, $hash]);
            $novo_id = (int)$pdo->lastInsertId();

            // Loga automaticamente após cadastro
            $cliente = [
                'id'    => $novo_id,
                'nome'  => $nome,
                'email' => $email,
            ];
            cliente_login_sessao($cliente);

            // Redireciona
            $volta = $_SESSION['redirect_apos_login'] ?? (BASE_URL . '/public/index.php');
            unset($_SESSION['redirect_apos_login']);
            redirecionar($volta);
        }
    }
}

// Config da loja
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave IN ('loja_nome','loja_whatsapp')");
$cfg  = [];
foreach ($stmt->fetchAll() as $r) $cfg[$r['chave']] = $r['valor'];
$loja_nome = $cfg['loja_nome'] ?? 'Sabor & Cia';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta — <?= htmlspecialchars($loja_nome) ?></title>
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
            bottom: -100px; right: -100px;
        }
        .lado-esq::after {
            content: '';
            position: absolute;
            width: 280px; height: 280px;
            border-radius: 50%;
            background: var(--rosa);
            opacity: .05;
            top: -60px; left: -60px;
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
            font-size: 1rem;
            color: rgba(255,255,255,.55);
            text-align: center;
            line-height: 1.8;
            max-width: 300px;
            position: relative; z-index: 1;
        }
        .esq-frase em { font-style: normal; color: var(--rosa); }

        /* Benefícios */
        .beneficios { margin-top: 36px; display: flex; flex-direction: column; gap: 12px; position: relative; z-index: 1; }
        .beneficio  { display: flex; align-items: center; gap: 10px; font-size: .85rem; color: rgba(255,255,255,.6); }
        .beneficio-icone {
            width: 28px; height: 28px; border-radius: 50%;
            background: rgba(244,63,122,.15);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .beneficio-icone svg { width: 13px; height: 13px; stroke: var(--rosa); fill: none; stroke-width: 2; }

        /* ── LADO DIREITO ──────────────────────── */
        .lado-dir {
            width: 480px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            background: var(--white);
            overflow-y: auto;
        }
        .form-wrap { width: 100%; max-width: 380px; }

        .form-titulo {
            font-family: var(--serif);
            font-size: 1.7rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .form-sub {
            font-size: .88rem;
            color: var(--gray);
            margin-bottom: 28px;
        }
        .form-sub a { color: var(--rosa); font-weight: 600; }

        /* Alerta */
        .alerta {
            display: flex; align-items: flex-start; gap: 8px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: .85rem;
            font-weight: 500;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .alerta svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; flex-shrink: 0; margin-top: 1px; }
        .alerta-err { background: #fff0f4; border: 1px solid var(--rosa-border); color: #be185d; }
        .alerta-err a { color: #be185d; font-weight: 700; text-decoration: underline; }

        /* Campos */
        .campo { margin-bottom: 16px; }
        .campo label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            margin-bottom: 6px;
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
        .campo input.invalido { border-color: #f87171; }
        .campo-hint { font-size: .75rem; color: var(--gray); margin-top: 4px; display: block; }

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

        /* Força da senha */
        .forca-wrap { margin-top: 6px; }
        .forca-barra { display: flex; gap: 4px; margin-bottom: 4px; }
        .forca-seg {
            flex: 1; height: 4px; border-radius: 2px;
            background: var(--rosa-border);
            transition: background .3s;
        }
        .forca-seg.fraca  { background: #f87171; }
        .forca-seg.media  { background: #f59e0b; }
        .forca-seg.forte  { background: #22c55e; }
        .forca-label { font-size: .72rem; color: var(--gray); }

        /* Grid de dois campos */
        .campo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        /* Termos */
        .termos {
            display: flex; align-items: flex-start; gap: 9px;
            font-size: .8rem;
            color: var(--gray);
            margin: 18px 0 22px;
            line-height: 1.5;
            
        }
        .termos input[type="checkbox"] { accent-color: var(--rosa); width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; cursor: pointer; }
        .termos a { color: var(--rosa); font-weight: 600; }

        /* Botão */
        .btn-cadastrar {
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
        .btn-cadastrar:hover  { opacity: .88; }
        .btn-cadastrar:active { transform: scale(.98); }
        .btn-cadastrar:disabled { opacity: .5; cursor: not-allowed; }
        .btn-cadastrar svg { width: 15px; height: 15px; stroke: #fff; fill: none; stroke-width: 2.5; }

        /* Link de login */
        .login-link {
            text-align: center;
            font-size: .83rem;
            color: var(--gray);
            margin-top: 22px;
        }
        .login-link a { color: var(--rosa); font-weight: 600; }

        /* Responsivo */
        @media (max-width: 768px) {
            .lado-esq { display: none; }
            .lado-dir { width: 100%; padding: 32px 24px; }
        }
        @media (max-width: 420px) {
            .campo-grid { grid-template-columns: 1fr; }
        }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="page">

    <!-- LADO ESQUERDO -->
    <div class="lado-esq">
        <div class="esq-logo">Sabor<span>&</span>Cia</div>
        <p class="esq-frase">
            Crie sua conta e aproveite<br>
            <em>vantagens exclusivas.</em>
        </p>
        <div class="beneficios">
            <div class="beneficio">
                <div class="beneficio-icone">
                    <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>
                </div>
                Acompanhe seus pedidos em tempo real
            </div>
            <div class="beneficio">
                <div class="beneficio-icone">
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                Salve seus endereços de entrega
            </div>
            <div class="beneficio">
                <div class="beneficio-icone">
                    <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </div>
                Receba cupons e promoções exclusivas
            </div>
            <div class="beneficio">
                <div class="beneficio-icone">
                    <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                Histórico completo de pedidos
            </div>
        </div>
    </div>

    <!-- LADO DIREITO -->
    <div class="lado-dir">
        <div class="form-wrap">

            <h1 class="form-titulo">Criar conta</h1>
            <p class="form-sub">
                Já tem conta?
                <a href="login.php">Fazer login</a>
            </p>

            <?php if ($erro): ?>
            <div class="alerta alerta-err">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= $erro /* já contém HTML seguro nos links */ ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="cadastro.php" id="formCadastro" novalidate>

                <!-- Nome -->
                <div class="campo">
                    <label for="nome">Nome completo *</label>
                    <input type="text" id="nome" name="nome"
                        placeholder="Seu nome completo"
                        value="<?= htmlspecialchars($dados['nome'] ?? '') ?>"
                        autocomplete="name"
                        required>
                </div>

                <!-- Email -->
                <div class="campo">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email"
                        placeholder="seu@email.com"
                        value="<?= htmlspecialchars($dados['email'] ?? '') ?>"
                        autocomplete="email"
                        required>
                </div>

                <!-- Telefone -->
                <div class="campo">
                    <label for="tel">WhatsApp</label>
                    <input type="tel" id="tel" name="tel"
                        placeholder="(81) 99999-0000"
                        value="<?= htmlspecialchars($dados['tel'] ?? '') ?>"
                        autocomplete="tel"
                        oninput="mascaraTel(this)">
                    <span class="campo-hint">Opcional — para contato sobre pedidos</span>
                </div>

                <!-- Senha -->
                <div class="campo">
                    <label for="senha">Senha *</label>
                    <div class="senha-wrap">
                        <input type="password" id="senha" name="senha"
                            placeholder="Mínimo 6 caracteres"
                            autocomplete="new-password"
                            oninput="verificarForca(this.value)"
                            required>
                        <button type="button" class="senha-toggle" onclick="toggleSenha('senha','icone1')" aria-label="Mostrar senha">
                            <svg id="icone1" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <!-- Barra de força -->
                    <div class="forca-wrap" id="forcaWrap" style="display:none">
                        <div class="forca-barra">
                            <div class="forca-seg" id="seg1"></div>
                            <div class="forca-seg" id="seg2"></div>
                            <div class="forca-seg" id="seg3"></div>
                            <div class="forca-seg" id="seg4"></div>
                        </div>
                        <span class="forca-label" id="forcaLabel"></span>
                    </div>
                </div>

                <!-- Confirmar senha -->
                <div class="campo">
                    <label for="senha_conf">Confirmar senha *</label>
                    <div class="senha-wrap">
                        <input type="password" id="senha_conf" name="senha_conf"
                            placeholder="Repita a senha"
                            autocomplete="new-password"
                            oninput="verificarConf()"
                            required>
                        <button type="button" class="senha-toggle" onclick="toggleSenha('senha_conf','icone2')" aria-label="Mostrar senha">
                            <svg id="icone2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <span class="campo-hint" id="confHint"></span>
                </div>

                <!-- Termos -->
                <label class="termos">
                    <input type="checkbox" id="termos" name="termos" required>
                    Concordo com os <a href="termos.php">termos de uso</a> e <a href="#">política de privacidade</a>
                </label>

                <button type="submit" class="btn-cadastrar" id="btnCadastrar">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    Criar minha conta
                </button>

            </form>

            <p class="login-link">
                Já tem conta?
                <a href="login.php">Entrar agora</a>
            </p>

        </div>
    </div>
</div>

<script>
    // Toggle senha
    function toggleSenha(id, iconeId) {
        var inp   = document.getElementById(id);
        var icone = document.getElementById(iconeId);
        if (inp.type === 'password') {
            inp.type = 'text';
            icone.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
        } else {
            inp.type = 'password';
            icone.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
        }
    }

    // Força da senha
    function verificarForca(senha) {
        var wrap = document.getElementById('forcaWrap');
        var segs = [document.getElementById('seg1'), document.getElementById('seg2'), document.getElementById('seg3'), document.getElementById('seg4')];
        var label = document.getElementById('forcaLabel');

        if (!senha) { wrap.style.display = 'none'; return; }
        wrap.style.display = 'block';

        var pts = 0;
        if (senha.length >= 6)  pts++;
        if (senha.length >= 10) pts++;
        if (/[A-Z]/.test(senha) || /\d/.test(senha)) pts++;
        if (/[!@#$%^&*(),.?":{}|<>]/.test(senha))    pts++;

        var cls   = pts <= 1 ? 'fraca' : pts <= 2 ? 'media' : 'forte';
        var texto = pts <= 1 ? 'Senha fraca' : pts <= 2 ? 'Senha média' : pts <= 3 ? 'Senha boa' : 'Senha forte';

        segs.forEach(function(s, i) {
            s.className = 'forca-seg' + (i < pts ? ' ' + cls : '');
        });
        label.textContent = texto;
        label.style.color = pts <= 1 ? '#f87171' : pts <= 2 ? '#f59e0b' : '#22c55e';
    }

    // Confirmar senha
    function verificarConf() {
        var s1   = document.getElementById('senha').value;
        var s2   = document.getElementById('senha_conf').value;
        var hint = document.getElementById('confHint');
        var inp  = document.getElementById('senha_conf');

        if (!s2) { hint.textContent = ''; return; }
        if (s1 === s2) {
            hint.textContent = '✓ Senhas coincidem';
            hint.style.color = '#22c55e';
            inp.classList.remove('invalido');
        } else {
            hint.textContent = '✗ Senhas não coincidem';
            hint.style.color = '#f87171';
            inp.classList.add('invalido');
        }
    }

    // Máscara de telefone
    function mascaraTel(inp) {
        var v = inp.value.replace(/\D/g, '').substring(0, 11);
        if (v.length > 6) {
            v = '(' + v.substring(0,2) + ') ' + v.substring(2,7) + '-' + v.substring(7);
        } else if (v.length > 2) {
            v = '(' + v.substring(0,2) + ') ' + v.substring(2);
        } else if (v.length > 0) {
            v = '(' + v;
        }
        inp.value = v;
    }

    // Spinner no botão
    document.getElementById('formCadastro').addEventListener('submit', function(e) {
        var termos = document.getElementById('termos');
        if (!termos.checked) {
            e.preventDefault();
            alert('Você precisa aceitar os termos de uso para continuar.');
            return;
        }
        var s1 = document.getElementById('senha').value;
        var s2 = document.getElementById('senha_conf').value;
        if (s1 !== s2) {
            e.preventDefault();
            document.getElementById('senha_conf').focus();
            return;
        }
        var btn = document.getElementById('btnCadastrar');
        btn.disabled = true;
        btn.innerHTML = '<svg viewBox="0 0 24 24" style="animation:spin .8s linear infinite"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" stroke="#fff" fill="none" stroke-width="2" stroke-linecap="round"/></svg> Criando conta...';
    });
</script>

</body>
</html>