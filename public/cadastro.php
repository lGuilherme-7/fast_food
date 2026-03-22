<?php

require_once __DIR__ . '/../inc/config.php';  
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
// ============================================
// cadastro.php
// ============================================
$erro   = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $conf  = trim($_POST['conf']  ?? '');

    if (empty($nome) || empty($email) || empty($senha) || empty($conf)) {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha precisa ter pelo menos 6 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas não coincidem.';
    } else {
        // TODO: salvar no banco, verificar e-mail duplicado
        $sucesso = 'Conta criada com sucesso! Redirecionando...';
        // header('Location: index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta — Sabor &amp; Cia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/public.css">
</head>
<body>

<!-- Header mobile -->
<div class="mobile-header">
    <a href="index.php" class="logo">Sabor<span>&</span>Cia</a>
</div>

<div class="page">

    <!-- ESQUERDA -->
    <div class="side-img">
        <div class="side-content">
            <a href="index.php" class="side-logo">Sabor<span>&</span>Cia</a>
            <div class="side-bottom">
                <h2>Crie sua conta e peça com <em>ainda mais facilidade.</em></h2>
                <p>Histórico de pedidos, favoritos salvos e promoções exclusivas para membros.</p>
            </div>
        </div>
    </div>

    <!-- DIREITA -->
    <div class="side-form">
        <div class="form-box">

            <a href="login.php" class="voltar">
                <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                Voltar ao login
            </a>

            <div class="form-header">
                <h1>Criar conta</h1>
                <p>Já tem conta? <a href="login.php">Entrar agora</a></p>
            </div>

            <!-- Sociais -->
            <div class="social-btns">
                <a href="auth/google.php" class="btn-social">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Cadastrar com Google</span>
                </a>

                <a href="auth/facebook.php" class="btn-social">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" fill="#1877F2"/>
                    </svg>
                    <span>Cadastrar com Facebook</span>
                </a>
            </div>

            <div class="divider"><span>ou cadastre com e-mail</span></div>

            <!-- Formulário -->
            <form method="POST" action="cadastro.php" novalidate id="formCadastro">

                <?php if ($erro): ?>
                <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
                <?php endif; ?>

                <div class="field">
                    <label for="nome">Nome completo</label>
                    <input
                        type="text" id="nome" name="nome"
                        placeholder="Seu nome"
                        value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                        autocomplete="name" required
                    >
                </div>

                <div class="field">
                    <label for="email">E-mail</label>
                    <input
                        type="email" id="email" name="email"
                        placeholder="seu@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email" required
                    >
                </div>

                <div class="field">
                    <label for="senha">Senha</label>
                    <input
                        type="password" id="senha" name="senha"
                        placeholder="Mínimo 6 caracteres"
                        autocomplete="new-password" required
                    >
                    <div class="senha-forca">
                        <div class="forca-bar" id="bar1"></div>
                        <div class="forca-bar" id="bar2"></div>
                        <div class="forca-bar" id="bar3"></div>
                    </div>
                    <div class="forca-label" id="forcaLabel"></div>
                </div>

                <div class="field">
                    <label for="conf">Confirmar senha</label>
                    <input
                        type="password" id="conf" name="conf"
                        placeholder="Repita a senha"
                        autocomplete="new-password" required
                    >
                </div>

                <div class="termos">
                    <input type="checkbox" id="termos" name="termos" required>
                    <label for="termos">
                        Concordo com os <a href="termos.php" target="_blank">Termos de uso</a>
                        e a <a href="privacidade.php" target="_blank">Política de privacidade</a>
                    </label>
                </div>

                <button type="submit" class="btn-submit" id="btnSubmit">Criar minha conta</button>
            </form>

            <p class="login-link">
                Já tem conta? <a href="login.php">Entrar agora</a>
            </p>

        </div>
    </div>
</div>

<script>
    // Força da senha
    var senhaInput = document.getElementById('senha');
    var bars       = [document.getElementById('bar1'), document.getElementById('bar2'), document.getElementById('bar3')];
    var label      = document.getElementById('forcaLabel');

    senhaInput.addEventListener('input', function () {
        var v = senhaInput.value;
        var forca = 0;

        if (v.length >= 6)                       forca++;
        if (v.length >= 10)                      forca++;
        if (/[A-Z]/.test(v) && /[0-9!@#$%]/.test(v)) forca++;

        var classes = ['', 'fraca', 'media', 'forte'];
        var labels  = ['', 'Fraca', 'Média', 'Forte'];

        bars.forEach(function (b, i) {
            b.className = 'forca-bar';
            if (i < forca) b.classList.add(classes[forca]);
        });

        label.textContent = v.length > 0 ? 'Força: ' + labels[forca] : '';
    });

    // Validação client-side mínima
    document.getElementById('formCadastro').addEventListener('submit', function (e) {
        var senha = document.getElementById('senha').value;
        var conf  = document.getElementById('conf').value;
        var termos = document.getElementById('termos').checked;

        if (senha !== conf) {
            e.preventDefault();
            document.getElementById('conf').classList.add('erro-campo');
            document.getElementById('conf').focus();
            return;
        }
        if (!termos) {
            e.preventDefault();
            document.getElementById('termos').focus();
            return;
        }
        document.getElementById('btnSubmit').disabled = true;
        document.getElementById('btnSubmit').textContent = 'Criando conta...';
    });

    // Remove erro ao digitar
    document.getElementById('conf').addEventListener('input', function () {
        this.classList.remove('erro-campo');
    });
</script>
</body>
</html>