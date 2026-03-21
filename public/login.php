<?php
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        // TODO: validar contra banco de dados
        // header('Location: index.php'); exit;
        $erro = 'Usuário ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Sabor &amp; Cia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="../assets/css/public.css">
   
</head>
<body>

<div class="mobile-header">
    <a href="index.php" class="logo">Sabor<span>&</span>Cia</a>
</div>

<div class="page">

    <div class="side-img">
        <div class="side-content">
            <a href="index.php" class="side-logo">Sabor<span>&</span>Cia</a>
            <div class="side-bottom">
                <h2>Peça com <em>facilidade</em>, receba com sabor.</h2>
                <p>Faça login e acesse seu histórico de pedidos, favoritos e muito mais.</p>
            </div>
        </div>
    </div>

    <div class="side-form">
        <div class="form-box">

            <a href="index.php" class="voltar">
                <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                Voltar ao início
            </a>

            <div class="form-header">
                <h1>Bem-vindo de volta</h1>
                <p>Não tem conta? <a href="cadastro.php">Cadastre-se grátis</a></p>
            </div>

            <?php if ($erro): ?>
            <div class="alerta"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <div class="field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email"
                        placeholder="seu@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email" required>
                </div>

                <div class="field">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha"
                        placeholder="Sua senha"
                        autocomplete="current-password" required>
                </div>

                <div class="forgot">
                    <a href="recuperar-senha.php">Esqueceu a senha?</a>
                </div>

                <button type="submit" class="btn-submit">Entrar</button>
            </form>

            <div class="divider"><span>ou continue com</span></div>

            <div class="social-btns">
                <a href="auth/google.php" class="btn-social">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Continuar com Google</span>
                </a>

                <a href="auth/facebook.php" class="btn-social">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" fill="#1877F2"/>
                    </svg>
                    <span>Continuar com Facebook</span>
                </a>
            </div>

            <p class="cadastro-link">
                Ainda não tem conta? <a href="cadastro.php">Criar conta grátis</a>
            </p>

        </div>
    </div>
</div>

</body>
</html>